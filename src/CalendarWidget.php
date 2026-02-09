<?php

namespace nedarta\calendar;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\db\ActiveQuery;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

/**
 * CalendarWidget
 * 
 * @property ActiveQuery $query
 * @property string $dateAttribute
 * @property string $titleAttribute
 * @property string $timeAttribute
 */
class CalendarWidget extends Widget
{
    public $query;
    public $dateAttribute = 'date';
    public $titleAttribute = 'title';
    public $timeAttribute = 'time';
    public $dateIsTimestamp = false;
    
    public $month;
    public $year;
    public $selectedDate;
    public $viewName = 'calendar';
    
    // Navigation URLs
    public $navUrl;
    public $viewUrl;
    
    // Localization
    public $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    public $firstDayOfWeek = 0; // 0 = Sunday, 1 = Monday, etc.
    
    // HTML options
    public $options = [];

    public function init()
    {
        parent::init();
        
        $tz = $this->getTimeZone();
        $now = new DateTimeImmutable('now', $tz);

        $this->month = $this->month ?? (int)$now->format('m');
        $this->year = $this->year ?? (int)$now->format('Y');
        $this->selectedDate = $this->selectedDate ?? $now->format('Y-m-d');

        // Set default URLs if not provided
        if ($this->navUrl === null) {
            try {
                if (isset(Yii::$app) && Yii::$app->has('request')) {
                    $req = Yii::$app->get('request');
                    if ($req instanceof \yii\web\Request) {
                        $this->navUrl = $req->url;
                    } else {
                        $this->navUrl = null;
                    }
                } else {
                    $this->navUrl = null;
                }
            } catch (\Throwable $e) {
                $this->navUrl = null;
            }
        }
        if ($this->viewUrl === null) {
            try {
                if (isset(Yii::$app) && Yii::$app->has('request')) {
                    $req = Yii::$app->get('request');
                    if ($req instanceof \yii\web\Request) {
                        $this->viewUrl = $req->url;
                    } else {
                        $this->viewUrl = null;
                    }
                } else {
                    $this->viewUrl = null;
                }
            } catch (\Throwable $e) {
                $this->viewUrl = null;
            }
        }
        
        // Set default HTML options
        if (!isset($this->options['class'])) {
            $this->options['class'] = 'calendar-widget shadow-sm p-4';
        }
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId() . '-container';
        }
        
        // Handle AJAX month/year changes
        $request = (isset(Yii::$app) && Yii::$app->has('request')) ? Yii::$app->get('request') : null;
        if ($request instanceof \yii\web\Request && !empty($request->isPjax)) {
            $this->month = (int)$request->get('month', $this->month);
            $this->year = (int)$request->get('year', $this->year);
            $this->selectedDate = $request->get('date', $request->get('selectedDate', $this->selectedDate));
        }

        $this->month = max(1, min(12, (int)$this->month));
        $this->year = max(1970, (int)$this->year);
        $this->firstDayOfWeek = (int)$this->firstDayOfWeek;
        if ($this->firstDayOfWeek < 0 || $this->firstDayOfWeek > 6) {
            $this->firstDayOfWeek = 0;
        }

        // Normalize selectedDate using resolved timezone
        try {
            $tz = $this->getTimeZone();
            if (is_numeric($this->selectedDate)) {
                $dt = (new DateTimeImmutable('@' . (int)$this->selectedDate))->setTimezone($tz);
            } else {
                // Try strict Y-m-d first
                $dt = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$this->selectedDate, $tz);
                if ($dt === false) {
                    // Try generic parse
                    $dt = new DateTimeImmutable((string)$this->selectedDate, $tz);
                }
            }
            $this->selectedDate = $dt->format('Y-m-d');
        } catch (\Throwable $e) {
            // Fallback to first day of current month in resolved tz
            $this->selectedDate = sprintf('%04d-%02d-01', $this->year, $this->month);
        }
    }

    public function run()
    {
        CalendarAsset::register($this->view);
        
        $events = $this->getEvents();
        $days = $this->generateCalendarDays();

        // Annotate hasEvents and isSelected on each cell
        foreach ($days as &$cell) {
            if (is_array($cell) && isset($cell['date'])) {
                $cellDate = $cell['date'];
                $cell['hasEvents'] = isset($events[$cellDate]);
                $cell['isSelected'] = ($cellDate === $this->selectedDate);
            }
        }
        unset($cell);

        return $this->render($this->viewName, [
            'month' => $this->month,
            'year' => $this->year,
            'days' => $days,
            'events' => $events,
            'selectedDate' => $this->selectedDate,
            'widgetId' => $this->getId(),
            'navUrl' => $this->navUrl,
            'viewUrl' => $this->viewUrl,
            'dayNames' => $this->dayNames,
            'firstDayOfWeek' => $this->firstDayOfWeek,
            'options' => $this->options,
            'timeZone' => $this->getTimeZone(),
        ]);
    }

    protected function getEvents()
    {
        if (!$this->query) {
            return [];
        }

        $tz = $this->getTimeZone();
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->year, $this->month), $tz);
        $endExclusive = $start->modify('+1 month');

        if ($this->dateIsTimestamp) {
            $startDate = $start->getTimestamp();
            $endDateExclusive = $endExclusive->getTimestamp();
        } else {
            $startDate = $start->format('Y-m-d H:i:s');
            $endDateExclusive = $endExclusive->format('Y-m-d H:i:s');
        }

        $models = (clone $this->query)
            ->andWhere(['>=', $this->dateAttribute, $startDate])
            ->andWhere(['<', $this->dateAttribute, $endDateExclusive])
            ->orderBy([$this->timeAttribute => SORT_ASC])
            ->all();

        $events = [];
        foreach ($models as $model) {
            $dateValue = ArrayHelper::getValue($model, $this->dateAttribute);
            $timeValue = ArrayHelper::getValue($model, $this->timeAttribute);

            // Parse date value
            if (is_numeric($dateValue)) {
                $dateDt = (new DateTimeImmutable('@' . (int)$dateValue))->setTimezone($tz);
            } else {
                $dateDt = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$dateValue, $tz);
                if ($dateDt === false) {
                    try {
                        $dateDt = new DateTimeImmutable((string)$dateValue, $tz);
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            }

            // Parse time value
            if (is_numeric($timeValue)) {
                $timeDt = (new DateTimeImmutable('@' . (int)$timeValue))->setTimezone($tz);
            } else {
                // Time may be H:i or full datetime; try H:i first
                $timeDt = DateTimeImmutable::createFromFormat('!H:i', (string)$timeValue, $tz);
                if ($timeDt === false) {
                    try {
                        $timeDt = new DateTimeImmutable((string)$timeValue, $tz);
                    } catch (\Throwable $e) {
                        // fallback to midnight
                        $timeDt = $dateDt->setTime(0, 0);
                    }
                }
            }

            // Normalize date to Y-m-d for grouping
            $date = $dateDt->format('Y-m-d');
            // Normalize time to H:i for display
            $time = $timeDt->format('H:i');

            $events[$date][] = [
                'time' => $time,
                'title' => ArrayHelper::getValue($model, $this->titleAttribute),
            ];
        }

        return $events;
    }

    protected function generateCalendarDays()
    {
        $tz = $this->getTimeZone();
        $firstDayOfMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $this->year, $this->month), $tz);
        $daysInMonth = (int)$firstDayOfMonth->format('t');
        $startDayOfWeek = (int)$firstDayOfMonth->format('w'); // 0 (Sun) to 6 (Sat)

        // Adjust for firstDayOfWeek setting
        $offset = ($startDayOfWeek - $this->firstDayOfWeek + 7) % 7;

        // Calculate the start date for the calendar grid (visible start cell)
        $startDate = $firstDayOfMonth->modify(sprintf('-%d days', $offset));

        $now = new DateTimeImmutable('now', $tz);

        $cells = [];
        for ($i = 0; $i < 42; $i++) {
            $cellDt = $startDate->modify("+{$i} days");
            $cellDate = $cellDt->format('Y-m-d');
            $cells[] = [
                'date' => $cellDate,
                'label' => (int)$cellDt->format('j'),
                'inMonth' => ($cellDt->format('Y-m') === $firstDayOfMonth->format('Y-m')),
                'isToday' => ($cellDt->format('Y-m-d') === $now->format('Y-m-d')),
                'isSelected' => ($cellDt->format('Y-m-d') === $this->selectedDate),
                'hasEvents' => false,
            ];
        }

        return $cells;
    }

    /**
     * Public accessor for testing to retrieve calendar days
     */
    public function getCalendarDays()
    {
        return $this->generateCalendarDays();
    }

    /**
     * Resolve timezone using Formatter->timeZone > App timeZone > PHP default
     *
     * @return DateTimeZone
     */
    public function getTimeZone(): DateTimeZone
    {
        // Prefer formatter's timeZone if explicitly set
        if (isset(Yii::$app) && Yii::$app->has('formatter')) {
            $formatter = Yii::$app->get('formatter');
            if (!empty($formatter->timeZone)) {
                try {
                    return new DateTimeZone($formatter->timeZone);
                } catch (\Throwable $e) {
                    // fallthrough
                }
            }
        }

        if (isset(Yii::$app) && !empty(Yii::$app->timeZone)) {
            try {
                return new DateTimeZone(Yii::$app->timeZone);
            } catch (\Throwable $e) {
                // fallthrough
            }
        }

        return new DateTimeZone(date_default_timezone_get());
    }
}
