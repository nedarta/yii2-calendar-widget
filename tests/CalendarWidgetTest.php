<?php

namespace nedarta\calendar\tests;

use nedarta\calendar\CalendarWidget;
use PHPUnit\Framework\TestCase;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class CalendarWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock the view component
        \Yii::$app->set('view', new \yii\web\View());
        
        // Properly configure the request component for testing
        \Yii::$app->set('request', new \yii\web\Request([
            'cookieValidationKey' => 'test',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
            'baseUrl' => '',
            'hostInfo' => 'http://localhost',
            'url' => '/calendar/index',
        ]));
    }

    public function testDefaultInitialization()
    {
        $widget = new CalendarWidget();
        $widget->init();

        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        $currentDate = date('Y-m-d');

        $this->assertEquals($currentMonth, $widget->month);
        $this->assertEquals($currentYear, $widget->year);
        $this->assertEquals($currentDate, $widget->selectedDate);
        $this->assertEquals('calendar', $widget->viewName);
        $this->assertEquals(0, $widget->firstDayOfWeek);
    }

    public function testCustomInitialization()
    {
        $widget = new CalendarWidget([
            'month' => 5,
            'year' => 2025,
            'selectedDate' => '2025-05-15',
            'dateAttribute' => 'event_date',
            'titleAttribute' => 'event_title',
            'timeAttribute' => 'event_time',
            'firstDayOfWeek' => 1, // Monday
        ]);
        $widget->init();

        $this->assertEquals(5, $widget->month);
        $this->assertEquals(2025, $widget->year);
        $this->assertEquals('2025-05-15', $widget->selectedDate);
        $this->assertEquals('event_date', $widget->dateAttribute);
        $this->assertEquals('event_title', $widget->titleAttribute);
        $this->assertEquals('event_time', $widget->timeAttribute);
        $this->assertEquals(1, $widget->firstDayOfWeek);
    }

    public function testHtmlOptionsDefaults()
    {
        $widget = new CalendarWidget();
        $widget->init();

        $this->assertArrayHasKey('class', $widget->options);
        $this->assertArrayHasKey('id', $widget->options);
        $this->assertEquals('calendar-widget shadow-sm p-4', $widget->options['class']);
    }

    public function testCustomHtmlOptions()
    {
        $widget = new CalendarWidget([
            'options' => [
                'class' => 'custom-calendar',
                'id' => 'my-calendar',
                'data-test' => 'value',
            ],
        ]);
        $widget->init();

        $this->assertEquals('custom-calendar', $widget->options['class']);
        $this->assertEquals('my-calendar', $widget->options['id']);
        $this->assertEquals('value', $widget->options['data-test']);
    }

    /**
     * @dataProvider calendarDaysProvider
     */
    public function testGenerateCalendarDays($year, $month, $firstDayOfWeek, $expectedFirstDate, $expectedLastDate, $expectedNullsBefore)
    {
        $widget = new CalendarWidget([
            'year' => $year,
            'month' => $month,
            'firstDayOfWeek' => $firstDayOfWeek,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('generateCalendarDays');
        $method->setAccessible(true);
        $days = $method->invoke($widget);

        // Should always have 42 days (6 weeks)
        $this->assertCount(42, $days);

        // Count leading nulls
        $leadingNulls = 0;
        foreach ($days as $day) {
            if ($day === null) {
                $leadingNulls++;
            } else {
                break;
            }
        }
        $this->assertEquals($expectedNullsBefore, $leadingNulls);

        // Check first non-null date
        $firstNonNull = array_filter($days);
        $this->assertEquals($expectedFirstDate, reset($firstNonNull));

        // Check last date of the month
        $daysInMonth = (int)date('t', strtotime("$year-$month-01"));
        $expectedLastDateFull = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
        $this->assertContains($expectedLastDateFull, $days);
    }

    public function calendarDaysProvider()
    {
        return [
            // [year, month, firstDayOfWeek, expectedFirstDate, expectedLastDate, expectedNullsBefore]
            'January 2025, Sunday start' => [2025, 1, 0, '2025-01-01', '2025-01-31', 3], // Jan 1 is Wed
            'January 2025, Monday start' => [2025, 1, 1, '2025-01-01', '2025-01-31', 2], // Jan 1 is Wed
            'February 2024, Sunday start (leap year)' => [2024, 2, 0, '2024-02-01', '2024-02-29', 4], // Feb 1 is Thu
            'February 2025, Sunday start' => [2025, 2, 0, '2025-02-01', '2025-02-28', 6], // Feb 1 is Sat
            'May 2025, Sunday start' => [2025, 5, 0, '2025-05-01', '2025-05-31', 4], // May 1 is Thu
            'December 2025, Sunday start' => [2025, 12, 0, '2025-12-01', '2025-12-31', 1], // Dec 1 is Mon
        ];
    }

    public function testGenerateCalendarDaysAlwaysReturns42Days()
    {
        // Test various months to ensure we always get 42 days
        $testCases = [
            [2025, 1], [2025, 2], [2025, 3], [2025, 4],
            [2025, 5], [2025, 6], [2025, 7], [2025, 8],
            [2025, 9], [2025, 10], [2025, 11], [2025, 12],
            [2024, 2], // Leap year
        ];

        foreach ($testCases as [$year, $month]) {
            $widget = new CalendarWidget(['year' => $year, 'month' => $month]);
            $widget->init();

            $reflection = new \ReflectionClass($widget);
            $method = $reflection->getMethod('generateCalendarDays');
            $method->setAccessible(true);
            $days = $method->invoke($widget);

            $this->assertCount(42, $days, "Failed for $year-$month");
        }
    }

    public function testGetEventsWithNoQuery()
    {
        $widget = new CalendarWidget([
            'month' => 5,
            'year' => 2025,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getEvents');
        $method->setAccessible(true);
        $events = $method->invoke($widget);

        $this->assertIsArray($events);
        $this->assertEmpty($events);
    }

    public function testGetEventsWithMockQuery()
    {
        // Create mock models
        $mockModels = [
            $this->createMockModel('2025-05-15', '2025-05-15 10:00:00', 'Event 1'),
            $this->createMockModel('2025-05-15', '2025-05-15 14:30:00', 'Event 2'),
            $this->createMockModel('2025-05-20', '2025-05-20 09:00:00', 'Event 3'),
        ];

        // Create mock query
        $mockQuery = $this->createMock(ActiveQuery::class);
        $mockQuery->method('andWhere')->willReturnSelf();
        $mockQuery->method('orderBy')->willReturnSelf();
        $mockQuery->method('all')->willReturn($mockModels);

        $widget = new CalendarWidget([
            'query' => $mockQuery,
            'month' => 5,
            'year' => 2025,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getEvents');
        $method->setAccessible(true);
        $events = $method->invoke($widget);

        $this->assertIsArray($events);
        $this->assertArrayHasKey('2025-05-15', $events);
        $this->assertArrayHasKey('2025-05-20', $events);
        $this->assertCount(2, $events['2025-05-15']);
        $this->assertCount(1, $events['2025-05-20']);

        // Check event structure
        $this->assertEquals('10:00', $events['2025-05-15'][0]['time']);
        $this->assertEquals('Event 1', $events['2025-05-15'][0]['title']);
        $this->assertEquals('14:30', $events['2025-05-15'][1]['time']);
        $this->assertEquals('Event 2', $events['2025-05-15'][1]['title']);
    }

    public function testGetEventsWithCustomAttributes()
    {
        $mockModels = [
            $this->createMockModel('2025-05-15', '2025-05-15 10:00:00', 'Event 1', 'event_date', 'event_time', 'event_title'),
        ];

        $mockQuery = $this->createMock(ActiveQuery::class);
        $mockQuery->method('andWhere')->willReturnSelf();
        $mockQuery->method('orderBy')->willReturnSelf();
        $mockQuery->method('all')->willReturn($mockModels);

        $widget = new CalendarWidget([
            'query' => $mockQuery,
            'month' => 5,
            'year' => 2025,
            'dateAttribute' => 'event_date',
            'timeAttribute' => 'event_time',
            'titleAttribute' => 'event_title',
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getEvents');
        $method->setAccessible(true);
        $events = $method->invoke($widget);

        $this->assertArrayHasKey('2025-05-15', $events);
        $this->assertEquals('Event 1', $events['2025-05-15'][0]['title']);
    }

    public function testGetEventsWithTimestampColumn()
    {
        $mockModels = [
            $this->createMockModel(strtotime('2025-05-15'), strtotime('2025-05-15 10:00:00'), 'Event 1'),
        ];

        $mockQuery = $this->createMock(ActiveQuery::class);
        $mockQuery->method('andWhere')->willReturnSelf();
        $mockQuery->method('orderBy')->willReturnSelf();
        $mockQuery->method('all')->willReturn($mockModels);

        $widget = new CalendarWidget([
            'query' => $mockQuery,
            'month' => 5,
            'year' => 2025,
            'dateIsTimestamp' => true,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getEvents');
        $method->setAccessible(true);
        $events = $method->invoke($widget);

        $this->assertArrayHasKey('2025-05-15', $events);
        $this->assertEquals('10:00', $events['2025-05-15'][0]['time']);
    }

    public function testDayNamesDefault()
    {
        $widget = new CalendarWidget();
        $widget->init();

        $expected = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $this->assertEquals($expected, $widget->dayNames);
    }

    public function testCustomDayNames()
    {
        $customDayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
        $widget = new CalendarWidget([
            'dayNames' => $customDayNames,
        ]);
        $widget->init();

        $this->assertEquals($customDayNames, $widget->dayNames);
    }

    public function testNavigationUrls()
    {
        // Mock the request component
        \Yii::$app->set('request', new \yii\web\Request([
            'url' => '/calendar/index',
        ]));

        $widget = new CalendarWidget();
        $widget->init();

        $this->assertEquals('/calendar/index', $widget->navUrl);
        $this->assertEquals('/calendar/index', $widget->viewUrl);
    }

    public function testCustomNavigationUrls()
    {
        $widget = new CalendarWidget([
            'navUrl' => '/custom/nav',
            'viewUrl' => '/custom/view',
        ]);
        $widget->init();

        $this->assertEquals('/custom/nav', $widget->navUrl);
        $this->assertEquals('/custom/view', $widget->viewUrl);
    }

    /**
     * Helper method to create mock model
     */
    private function createMockModel(
        $dateValue,
        $timeValue,
        $titleValue,
        $dateAttr = 'date',
        $timeAttr = 'time',
        $titleAttr = 'title'
    ) {
        $mock = $this->createMock(ActiveRecord::class);
        
        $mock->method('__get')
            ->willReturnCallback(function ($name) use ($dateValue, $timeValue, $titleValue, $dateAttr, $timeAttr, $titleAttr) {
                if ($name === $dateAttr) return $dateValue;
                if ($name === $timeAttr) return $timeValue;
                if ($name === $titleAttr) return $titleValue;
                return null;
            });

        return $mock;
    }

    public function testLeapYearFebruary()
    {
        $widget = new CalendarWidget([
            'year' => 2024,
            'month' => 2,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('generateCalendarDays');
        $method->setAccessible(true);
        $days = $method->invoke($widget);

        // February 2024 has 29 days (leap year)
        $this->assertContains('2024-02-29', $days);
        $this->assertNotContains('2024-02-30', $days);
    }

    public function testNonLeapYearFebruary()
    {
        $widget = new CalendarWidget([
            'year' => 2025,
            'month' => 2,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('generateCalendarDays');
        $method->setAccessible(true);
        $days = $method->invoke($widget);

        // February 2025 has 28 days (non-leap year)
        $this->assertContains('2025-02-28', $days);
        $this->assertNotContains('2025-02-29', $days);
    }

    public function testEventDateNormalization()
    {
        // Test with timestamp
        $mockModels = [
            $this->createMockModel(strtotime('2025-05-15'), strtotime('2025-05-15 10:30:00'), 'Event 1'),
        ];

        $mockQuery = $this->createMock(ActiveQuery::class);
        $mockQuery->method('andWhere')->willReturnSelf();
        $mockQuery->method('orderBy')->willReturnSelf();
        $mockQuery->method('all')->willReturn($mockModels);

        $widget = new CalendarWidget([
            'query' => $mockQuery,
            'month' => 5,
            'year' => 2025,
        ]);
        $widget->init();

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getEvents');
        $method->setAccessible(true);
        $events = $method->invoke($widget);

        // Should normalize timestamp to Y-m-d format
        $this->assertArrayHasKey('2025-05-15', $events);
        $this->assertEquals('10:30', $events['2025-05-15'][0]['time']);
    }
}
