<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var int $month */
/** @var int $year */
/** @var array $days */
/** @var array $events */
/** @var string $selectedDate */
/** @var string $widgetId */
/** @var string $navUrl */
/** @var string $viewUrl */
/** @var array $dayNames */
/** @var int $firstDayOfWeek */
/** @var array $options */

$monthName = DateTime::createFromFormat('!m', $month)->format('F');

// Build URLs from route arrays or absolute/relative strings
$buildUrl = static function ($base, array $params): string {
	if (is_array($base)) {
		return Url::to(array_merge($base, $params));
	}

	$url = Url::to($base);

	// remove existing query string
	$parts = parse_url($url);
	$path = $parts['path'] ?? '';

	return Url::to($path) . '?' . http_build_query($params);
};


// Prepare prev/next links
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

?>

<div <?= Html::renderTagAttributes($options) ?>>
    <?php Pjax::begin(['id' => $widgetId . '-pjax', 'enablePushState' => false, 'enableReplaceState' => true,]); ?>
    
    <div class="row">
        <!-- Calendar Grid Column -->
        <div class="col-md-7 border-end">
            <div class="calendar-header d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-light mb-0"><?= Html::encode($monthName) ?> <?= $year ?></h3>
                <div class="nav-controls">
                    <?= Html::a('&lt;', $buildUrl($navUrl, ['month' => $prevMonth, 'year' => $prevYear]), [
                        'class' => 'calendar-nav-btn',
                        'data-pjax' => 1,
                        'aria-label' => 'Previous month',
                    ]) ?>
                    <?= Html::a('&gt;', $buildUrl($navUrl, ['month' => $nextMonth, 'year' => $nextYear]), [
                        'class' => 'calendar-nav-btn',
                        'data-pjax' => 1,
                        'aria-label' => 'Next month',
                    ]) ?>
                </div>
            </div>

            <div class="calendar-grid">
                <?php 
            // Reorder day names based on firstDayOfWeek
            $orderedDayNames = array_merge(
                array_slice($dayNames, $firstDayOfWeek),
                array_slice($dayNames, 0, $firstDayOfWeek)
            );
            foreach ($orderedDayNames as $dayName): 
            ?>
                <div class="day-name"><?= Html::encode($dayName) ?></div>
            <?php endforeach; ?>

                <?php foreach ($days as $date): ?>
                    <div class="day-number">
                        <?php if ($date): ?>
                            <?php 
                            $dayNum = (int)date('d', strtotime($date));
                            $hasEvents = isset($events[$date]);
                            $isActive = ($date === $selectedDate);
                            $class = 'day-link' . ($isActive ? ' active' : '') . ($hasEvents ? ' has-events' : '');
                            ?>
                            <?= Html::a($dayNum, $buildUrl($viewUrl, ['date' => $date, 'month' => $month, 'year' => $year, 'selectedDate' => $date]), [
                                'class' => $class,
                                'data-pjax' => 1,
                                'data-date' => $date,
                                'aria-current' => $isActive ? 'date' : null,
                            ]) ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Event List Column -->
        <div class="col-md-5 ps-md-4">
            <div class="event-list">
                <?php if (isset($events[$selectedDate])): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($events[$selectedDate] as $event): ?>
                            <li class="event-item d-flex align-items-baseline">
                                <div class="event-time me-3"><?= Html::encode($event['time']) ?></div>
                                <div class="event-title"><?= Html::encode($event['title']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted italic py-3">No events scheduled for this day.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>
</div>
