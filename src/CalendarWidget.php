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
 * CalendarWidget displays a monthly calendar view with events and navigation.
 *
 * This widget provides a two-column layout showing a calendar grid on the left
 * and event details for the selected date on the right. It supports AJAX navigation
 * via Pjax for seamless month/year changes and date selection.
 *
 * Example usage:
 * ```php
 * echo CalendarWidget::widget([
 *     'query' => Event::find(),
 *     'dateAttribute' => 'event_date',
 *     'titleAttribute' => 'event_title',
 *     'timeAttribute' => 'event_time',
 *     'month' => 2,
 *     'year' => 2026,
 *     'selectedDate' => '2026-02-09',
 *     'firstDayOfWeek' => 1, // Start week on Monday
 *     'dayNames' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
 * ]);
 * ```
 *
 * @property ActiveQuery|null $query The ActiveQuery to fetch events from the database
 * @property string $dateAttribute The attribute name containing the event date (default: 'date')
 * @property string $titleAttribute The attribute name containing the event title (default: 'title')
 * @property string $timeAttribute The attribute name containing the event time (default: 'time')
 * @property bool $dateIsTimestamp Whether the date attribute stores Unix timestamps (default: false)
 * @property int|null $month The month to display (1-12). Defaults to current month
 * @property int|null $year The year to display. Defaults to current year
 * @property string|null $selectedDate The currently selected date in Y-m-d format. Defaults to today
 * @property string $viewName The name of the view file to render (default: 'calendar')
 * @property string|array|null $navUrl URL for month navigation links. Defaults to current URL
 * @property string|array|null $viewUrl URL for date selection links. Defaults to current URL
 * @property array $dayNames Array of day name abbreviations (7 elements). Default: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
 * @property int $firstDayOfWeek First day of the week (0=Sunday, 1=Monday, etc.). Default: 0
 * @property array $options HTML attributes for the container element
 *
 * @author Nedarta Calendar
 * @package nedarta\calendar
 */
class CalendarWidget extends Widget
{
	/**
	 * @var ActiveQuery|null The ActiveQuery instance to fetch events from the database.
	 * If null, no events will be displayed.
	 */
	public $query;

	/**
	 * @var string The attribute name in the model that contains the event date.
	 * Can be a date string (Y-m-d) or Unix timestamp if $dateIsTimestamp is true.
	 */
	public $dateAttribute = 'date';

	/**
	 * @var string The attribute name in the model that contains the event title/description.
	 */
	public $titleAttribute = 'title';

	/**
	 * @var string The attribute name in the model that contains the event time.
	 * Can be a time string (H:i), datetime string, or Unix timestamp.
	 */
	public $timeAttribute = 'time';

	/**
	 * @var bool Whether the date attribute stores values as Unix timestamps.
	 * If true, dates will be converted from timestamps. If false, dates are expected as strings.
	 */
	public $dateIsTimestamp = false;

	/**
	 * @var int|null The month to display (1-12). If null, defaults to current month.
	 * Can be updated via AJAX request parameters.
	 */
	public $month;

	/**
	 * @var int|null The year to display (e.g., 2026). If null, defaults to current year.
	 * Can be updated via AJAX request parameters.
	 */
	public $year;

	/**
	 * @var string|null The currently selected date in Y-m-d format (e.g., '2026-02-09').
	 * If null, defaults to today's date. Can be updated via AJAX request parameters.
	 */
	public $selectedDate;

	/**
	 * @var string The name of the view file to render (without .php extension).
	 * The view file should be located in the widget's views directory.
	 */
	public $viewName = 'calendar';

	/**
	 * @var string|array|null URL or route for month navigation links.
	 * Can be a string URL or array route. If null, uses current request URL.
	 * Month and year parameters will be appended to this URL.
	 */
	public $navUrl;

	/**
	 * @var string|array|null URL or route for date selection links.
	 * Can be a string URL or array route. If null, uses current request URL.
	 * Date parameter will be appended to this URL.
	 */
	public $viewUrl;

	/**
	 * @var array Array of day name abbreviations for the calendar header.
	 * Must contain exactly 7 elements starting with Sunday.
	 * Example: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
	 * The array will be reordered based on $firstDayOfWeek setting.
	 */
	public $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

	/**
	 * @var int The first day of the week (0=Sunday, 1=Monday, 2=Tuesday, etc.).
	 * This determines both the order of day names in the header and the alignment of dates.
	 * For example, setting this to 1 will make Monday the first column.
	 */
	public $firstDayOfWeek = 0;

	/**
	 * @var string|null The language to use for localized content (e.g., 'lv', 'en-US').
	 * If null, defaults to Yii::$app->language.
	 */
	public $language;

	/**
	 * @var array Array of custom celebration days.
	 * Can contain:
	 * - Full dates in 'Y-m-d' format (e.g., '2025-01-01')
	 * - Recurring dates in 'm-d' format (e.g., '08-20')
	 */
	public $celebrations = [];

	/**
	 * @var string The format/length of localized day names.
	 * Supported values:
	 * - 'narrow': Single letter (e.g., 'P')
	 * - 'short': Two letters (e.g., 'Pr')
	 * - 'abbr': Abbreviated (e.g., 'Pirmd.') - Default
	 * - 'full': Full name (e.g., 'Pirmdiena')
	 */
	public $dayNameFormat = 'abbr';

	/**
	 * @var \Closure|null A callback function to customize the rendering of each event in the sidebar.
	 * The closure should accept two parameters: ($model, $widget) and return a string (HTML).
	 * Example:
	 * ```php
	 * 'eventRender' => function($model, $calendar) {
	 *     return '<div class="custom-event">' . Html::encode($model->title) . '</div>';
	 * }
	 * ```
	 */
	public $eventRender;

	/**
	 * @var array HTML attributes for the widget's container element.
	 * Default class is 'calendar-widget shadow-sm p-4'.
	 * Default id is '{widgetId}-container'.
	 */
	public $options = [];

	/**
	 * Initializes the widget.
	 *
	 * This method sets up default values for month, year, and selectedDate if not provided.
	 * It also handles AJAX requests to update these values, validates input ranges,
	 * and normalizes the selected date to Y-m-d format using the configured timezone.
	 *
	 * @return void
	 * @throws \Exception If date parsing fails critically (falls back to first day of month)
	 */
	public function init()
	{
		parent::init();

		$this->language = $this->language ?? (isset(Yii::$app) ? Yii::$app->language : 'en-US');

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

		$allowedFormats = ['narrow', 'short', 'abbr', 'full'];
		if (!in_array($this->dayNameFormat, $allowedFormats)) {
			$this->dayNameFormat = 'abbr';
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

	/**
	 * Executes the widget.
	 *
	 * Registers the CalendarAsset, fetches events from the database,
	 * generates the calendar grid with proper day alignment, and renders the view.
	 *
	 * @return string The rendered calendar HTML
	 */
	public function run()
	{
		CalendarAsset::register($this->view);

		$events = $this->getEvents();
		$days = $this->generateCalendarDays();

		// Annotate hasEvents and isSelected on each cell
		foreach ($days as &$cell) {
			if (is_array($cell) && isset($cell['date']) && !empty($cell['date'])) {
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
			// Pre-computed helper data
			'monthName' => $this->getMonthName(),
			'prevMonth' => $this->getPrevMonth(),
			'prevYear' => $this->getPrevYear(),
			'nextMonth' => $this->getNextMonth(),
			'nextYear' => $this->getNextYear(),
			'todayString' => $this->getTodayString(),
			'orderedDayNames' => $this->getOrderedDayNames(),
			'buildUrl' => $this->getBuildUrlFunction(),
			'eventRender' => $this->eventRender,
		]);
	}

	/**
	 * Gets the full month name for the current month.
	 *
	 * @return string Month name (e.g., "February")
	 */
	protected function getMonthName(): string
	{
		$locale = $this->language;
		$tz = $this->getTimeZone()->getName();

		try {
			if (class_exists('IntlDateFormatter')) {
				$formatter = new \IntlDateFormatter(
					$locale,
					\IntlDateFormatter::NONE,
					\IntlDateFormatter::NONE,
					$tz,
					\IntlDateFormatter::GREGORIAN,
					'LLLL' // Localized full month name (Stand-alone)
				);
				$dt = DateTime::createFromFormat('!Y-m', "$this->year-$this->month");
				$name = $formatter->format($dt);
				
				// Capitalize first letter (Latvian and others are lowercase by default in Intl)
				return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
			}
		} catch (\Throwable $e) {
			// fallthrough
		}

		return DateTime::createFromFormat('!m', $this->month)->format('F');
	}

	/**
	 * Gets the previous month number.
	 *
	 * @return int Month number (1-12)
	 */
	protected function getPrevMonth(): int
	{
		return $this->month === 1 ? 12 : $this->month - 1;
	}

	/**
	 * Gets the year for the previous month.
	 *
	 * @return int Year number
	 */
	protected function getPrevYear(): int
	{
		return $this->month === 1 ? $this->year - 1 : $this->year;
	}

	/**
	 * Gets the next month number.
	 *
	 * @return int Month number (1-12)
	 */
	protected function getNextMonth(): int
	{
		return $this->month === 12 ? 1 : $this->month + 1;
	}

	/**
	 * Gets the year for the next month.
	 *
	 * @return int Year number
	 */
	protected function getNextYear(): int
	{
		return $this->month === 12 ? $this->year + 1 : $this->year;
	}

	/**
	 * Gets today's date as a string in Y-m-d format.
	 *
	 * @return string Today's date (e.g., "2026-02-09")
	 */
	protected function getTodayString(): string
	{
		try {
			$tz = $this->getTimeZone();
			$now = new DateTimeImmutable('now', $tz);
			return $now->format('Y-m-d');
		} catch (\Throwable $e) {
			return date('Y-m-d');
		}
	}

	/**
	 * Gets reordered day names according to firstDayOfWeek setting.
	 *
	 * Uses IntlDateFormatter if possible to provide localized day names.
	 *
	 * @return array Reordered array of day names
	 */
	protected function getOrderedDayNames(): array
	{
		$names = [];
		$locale = $this->language;
		$tz = $this->getTimeZone()->getName();

		try {
			if (class_exists('IntlDateFormatter')) {
				$pattern = match ($this->dayNameFormat) {
					'narrow' => 'ccccc',
					'short' => 'cccccc',
					'full' => 'cccc',
					default => 'ccc',
				};

				$formatter = new \IntlDateFormatter(
					$locale,
					\IntlDateFormatter::NONE,
					\IntlDateFormatter::NONE,
					$tz,
					\IntlDateFormatter::GREGORIAN,
					$pattern
				);
				
				// Standard weekend order: 0=Sun ... 6=Sat
				// We need to generate names for all 7 days
				// 2024-01-07 was a Sunday
				for ($i = 0; $i < 7; $i++) {
					$dt = new DateTimeImmutable("2024-01-07 +$i days");
					$names[$i] = $formatter->format($dt);
				}
			}
		} catch (\Throwable $e) {
			// fallthrough to default
		}

		if (empty($names)) {
			$names = $this->dayNames;
		}

		return array_merge(
			array_slice($names, $this->firstDayOfWeek),
			array_slice($names, 0, $this->firstDayOfWeek)
		);
	}

	/**
	 * Returns a closure for building URLs with query parameters.
	 *
	 * This function handles both array-based routes and string URLs,
	 * appending the provided parameters as a query string.
	 *
	 * @return \Closure Function that accepts ($base, $params) and returns a URL string
	 */
	protected function getBuildUrlFunction(): \Closure
	{
		return static function ($base, array $params): string {
			if (is_array($base)) {
				return \yii\helpers\Url::to(array_merge($base, $params));
			}

			$url = \yii\helpers\Url::to($base);
			$parts = parse_url($url);
			$path = $parts['path'] ?? '';

			return \yii\helpers\Url::to($path) . '?' . http_build_query($params);
		};
	}

	/**
	 * Fetches events from the database for the current month.
	 *
	 * Queries the database using the configured ActiveQuery, filtering by the current
	 * month and year. Events are grouped by date and sorted by time.
	 *
	 * The method handles both timestamp and string date formats based on $dateIsTimestamp.
	 * It normalizes all dates to Y-m-d format and times to H:i format for consistent display.
	 *
	 * @return array Associative array where keys are dates (Y-m-d) and values are arrays of events.
	 *               Each event contains 'time' (H:i) and 'title' keys.
	 *               Example: ['2026-02-09' => [['time' => '14:30', 'title' => 'Meeting'], ...]]
	 */
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
				'model' => $model, // Keep reference to raw model for custom rendering
			];
		}

		return $events;
	}

	/**
	 * Generates the calendar grid cells for the current month.
	 *
	 * Creates an array of cells representing the calendar grid, including:
	 * - Empty padding cells before the first day to align with the correct weekday
	 * - Actual day cells for each day in the month
	 * - Empty padding cells after the last day to complete the grid
	 *
	 * The padding accounts for the $firstDayOfWeek setting to ensure proper alignment.
	 * For example, if the month starts on a Wednesday and $firstDayOfWeek is 0 (Sunday),
	 * there will be 3 empty cells before the first day.
	 *
	 * @return array Array of cell data. Each cell is an associative array with keys:
	 *               - 'date' (string): Date in Y-m-d format, or empty string for padding cells
	 *               - 'label' (int|string): Day number (1-31), or empty string for padding cells
	 *               - 'inMonth' (bool): True for actual days, false for padding cells
	 *               - 'isToday' (bool): True if this cell represents today's date
	 *               - 'isSelected' (bool): True if this cell matches $selectedDate
	 *               - 'hasEvents' (bool): Initially false, updated later in run()
	 *               - 'isWeekend' (bool): True if this cell falls on a weekend day
	 *               - 'dayOfWeek' (int): Day of week position (0-6) relative to firstDayOfWeek
	 */
	protected function generateCalendarDays()
	{
		$tz = $this->getTimeZone();
		$firstDayOfMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $this->year, $this->month), $tz);
		$daysInMonth = (int)$firstDayOfMonth->format('t');

		$now = new DateTimeImmutable('now', $tz);

		$cells = [];

		// Get the day of week for the first day of the month (0=Sunday, 6=Saturday)
		$firstDayOfWeek = (int)$firstDayOfMonth->format('w');

		// Calculate how many empty cells we need at the start
		$paddingDays = ($firstDayOfWeek - $this->firstDayOfWeek + 7) % 7;

		$gridPosition = 0;

		// Add empty cells at the beginning
		for ($i = 0; $i < $paddingDays; $i++) {
			$dayOfWeekInGrid = $gridPosition % 7;
			$actualDayOfWeek = ($dayOfWeekInGrid + $this->firstDayOfWeek) % 7;
			$cells[] = [
				'date' => '',
				'label' => '',
				'inMonth' => false,
				'isToday' => false,
				'isSelected' => false,
				'hasEvents' => false,
				'isWeekend' => ($actualDayOfWeek === 0 || $actualDayOfWeek === 6),
				'isSaturday' => ($actualDayOfWeek === 6),
				'isSunday' => ($actualDayOfWeek === 0),
				'isCelebration' => false,
				'dayOfWeek' => $dayOfWeekInGrid,
			];
			$gridPosition++;
		}

		// Add actual days of the month
		for ($i = 1; $i <= $daysInMonth; $i++) {
			$cellDt = $firstDayOfMonth->modify("+".($i-1)." days");
			$cellDate = $cellDt->format('Y-m-d');
			$dayOfWeekInGrid = $gridPosition % 7;
			$actualDayOfWeek = ($dayOfWeekInGrid + $this->firstDayOfWeek) % 7;
			
			$cells[] = [
				'date' => $cellDate,
				'label' => $i,
				'inMonth' => true,
				'isToday' => ($cellDate === $now->format('Y-m-d')),
				'isSelected' => ($cellDate === $this->selectedDate),
				'hasEvents' => false,
				'isWeekend' => ($actualDayOfWeek === 0 || $actualDayOfWeek === 6),
				'isSaturday' => ($actualDayOfWeek === 6),
				'isSunday' => ($actualDayOfWeek === 0),
				'isCelebration' => $this->isCelebrationDay($cellDate),
				'dayOfWeek' => $dayOfWeekInGrid,
			];
			$gridPosition++;
		}

		return $cells;
	}

	/**
	 * Checks if a date is a celebration day.
	 *
	 * Supports full 'Y-m-d' or recurring 'm-d' formats in $celebrations array.
	 *
	 * @param string $date Date in Y-m-d format
	 * @return bool True if the date is in the celebrations list
	 */
	protected function isCelebrationDay(string $date): bool
	{
		if (empty($this->celebrations)) {
			return false;
		}

		$monthDay = substr($date, 5); // Extract 'm-d'

		foreach ($this->celebrations as $celebration) {
			if ($celebration === $date || $celebration === $monthDay) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if a given day of week position is a weekend.
	 *
	 * Weekend days are determined based on the grid position:
	 * - Position 0 (first column) = Sunday (if firstDayOfWeek is 0) or Monday (if firstDayOfWeek is 1)
	 * - Position 6 (last column) = Saturday (if firstDayOfWeek is 0) or Sunday (if firstDayOfWeek is 1)
	 *
	 * This method assumes Saturday and Sunday are weekend days.
	 *
	 * @param int $dayOfWeek Position in the week grid (0-6)
	 * @return bool True if this position represents a weekend day
	 */
	protected function isWeekendDay(int $dayOfWeek): bool
	{
		// Map grid position to actual day of week considering firstDayOfWeek offset
		$actualDayOfWeek = ($dayOfWeek + $this->firstDayOfWeek) % 7;

		// 0 = Sunday, 6 = Saturday
		return $actualDayOfWeek === 0 || $actualDayOfWeek === 6;
	}


	/**
	 * Public accessor for retrieving calendar days.
	 *
	 * This method is primarily intended for testing purposes, allowing external
	 * code to inspect the generated calendar grid structure without rendering the widget.
	 *
	 * @return array Array of calendar cells (see generateCalendarDays() for structure)
	 */
	public function getCalendarDays()
	{
		return $this->generateCalendarDays();
	}

	/**
	 * Resolves the timezone to use for date/time operations.
	 *
	 * The timezone is determined using the following priority:
	 * 1. Yii::$app->formatter->timeZone (if set and valid)
	 * 2. Yii::$app->timeZone (if set and valid)
	 * 3. PHP's default timezone (date_default_timezone_get())
	 *
	 * This ensures consistent timezone handling across all date operations in the widget,
	 * including date parsing, formatting, and "today" detection.
	 *
	 * @return DateTimeZone The resolved timezone object
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