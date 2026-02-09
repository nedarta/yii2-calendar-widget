<?php

namespace nedarta\calendar;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\db\ActiveQuery;
use DateTime;

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
        
        $this->month = $this->month ?? (int)date('m');
        $this->year = $this->year ?? (int)date('Y');
        $this->selectedDate = $this->selectedDate ?? date('Y-m-d');
        
        // Set default URLs if not provided
        if ($this->navUrl === null) {
            $this->navUrl = Yii::$app->request->url;
        }
        if ($this->viewUrl === null) {
            $this->viewUrl = Yii::$app->request->url;
        }
        
        // Set default HTML options
        if (!isset($this->options['class'])) {
            $this->options['class'] = 'calendar-widget shadow-sm p-4';
        }
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId() . '-container';
        }
        
        // Handle AJAX month/year changes
        $request = Yii::$app->request;
        if ($request->isPjax) {
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

        $selectedTimestamp = strtotime((string)$this->selectedDate);
        if ($selectedTimestamp === false || $selectedTimestamp <= 0) {
            $this->selectedDate = sprintf('%04d-%02d-01', $this->year, $this->month);
        } else {
            $this->selectedDate = date('Y-m-d', $selectedTimestamp);
        }
    }

    public function run()
    {
        CalendarAsset::register($this->view);
        
        $events = $this->getEvents();
        $days = $this->generateCalendarDays();
        
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
        ]);
    }

    protected function getEvents()
    {
        if (!$this->query) {
            return [];
        }

        $startDate = sprintf('%04d-%02d-01 00:00:00', $this->year, $this->month);
        $endDateExclusive = date('Y-m-d 00:00:00', strtotime($startDate . ' +1 month'));
        if ($this->dateIsTimestamp) {
            $startDate = strtotime($startDate);
            $endDateExclusive = strtotime($endDateExclusive);
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

            $dateTimestamp = is_numeric($dateValue) ? (int)$dateValue : strtotime((string)$dateValue);
            if ($dateTimestamp === false || $dateTimestamp <= 0) {
                continue;
            }

            $timeTimestamp = is_numeric($timeValue) ? (int)$timeValue : strtotime((string)$timeValue);
            if ($timeTimestamp === false || $timeTimestamp <= 0) {
                continue;
            }

            // Normalize date to Y-m-d for grouping
            $date = date('Y-m-d', $dateTimestamp);
            // Normalize time to H:i for display
            $time = date('H:i', $timeTimestamp);

            $events[$date][] = [
                'time' => $time,
                'title' => ArrayHelper::getValue($model, $this->titleAttribute),
            ];
        }

        return $events;
    }

    protected function generateCalendarDays()
    {
        $firstDayOfMonth = new DateTime(sprintf('%04d-%02d-01', $this->year, $this->month));
        $daysInMonth = (int)$firstDayOfMonth->format('t');
        $startDayOfWeek = (int)$firstDayOfMonth->format('w'); // 0 (Sun) to 6 (Sat)

        // Adjust for firstDayOfWeek setting
        $offset = ($startDayOfWeek - $this->firstDayOfWeek + 7) % 7;

        $days = [];
        
        // Fill leading empty slots
        for ($i = 0; $i < $offset; $i++) {
            $days[] = null;
        }

        // Fill month days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[] = sprintf('%04d-%02d-%02d', $this->year, $this->month, $day);
        }

        // Fill trailing empty slots to ensure 42 days (6 weeks)
        while (count($days) < 42) {
            $days[] = null;
        }

        return $days;
    }
}
