<?php

use nedarta\calendar\CalendarWidget;

class TimeZoneCalendarTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure Yii app exists from tests/bootstrap.php
        if (!isset(Yii::$app)) {
            require __DIR__ . '/bootstrap.php';
        }
    }

    public function testTodayIsMarkedUsingAppTimeZoneWhenFormatterTimeZoneIsNull()
    {
        // Arrange: set app timezone to Europe/Riga and formatter.timeZone to null
        Yii::$app->timeZone = 'Europe/Riga';
        if (Yii::$app->has('formatter')) {
            Yii::$app->formatter->timeZone = null;
        }

        $widget = new CalendarWidget([
            'month' => (int)date('m'),
            'year' => (int)date('Y'),
        ]);

        // Act
        $days = $widget->getCalendarDays();

        // Find the day marked as today using resolved timezone
        $tz = $widget->getTimeZone();
        $today = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

        $found = 0;
        foreach ($days as $cell) {
            if (is_array($cell) && isset($cell['date']) && $cell['date'] === $today) {
                $found++;
            }
        }

        $this->assertEquals(1, $found, "There should be exactly one cell equal to today's date");
    }
}

