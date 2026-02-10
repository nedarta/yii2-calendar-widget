<?php

use yii\helpers\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var int $month */
/** @var int $year */
/** @var array $days */
/** @var array $events */
/** @var string $selectedDate */
/** @var string $widgetId */
/** @var string|array $navUrl */
/** @var string|array $viewUrl */
/** @var array $options */
/** @var string $monthName */
/** @var int $prevMonth */
/** @var int $prevYear */
/** @var int $nextMonth */
/** @var int $nextYear */
/** @var string $todayString */
/** @var array $orderedDayNames */
/** @var \Closure $buildUrl */

?>

<div <?= Html::renderTagAttributes($options) ?>>
	<?php Pjax::begin([
		'id' => $widgetId . '-pjax',
		'enablePushState' => false,
		'enableReplaceState' => true,
	]); ?>

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
				<?php foreach ($orderedDayNames as $index => $dayName):
					$dayOfWeekInGrid = $index % 7;
					$actualDayOfWeek = ($dayOfWeekInGrid + $firstDayOfWeek) % 7;
					$classes = 'day-name';
					if ($actualDayOfWeek === 6) $classes .= ' saturday';
					if ($actualDayOfWeek === 0) $classes .= ' sunday';
					?>
                    <div class="<?= $classes ?>" data-day="<?= $index ?>">
						<?= Html::encode($dayName) ?>
                    </div>
				<?php endforeach; ?>

				<?php foreach ($days as $cell):
					$classes = 'day-number';
					if (!empty($cell['isSaturday'])) $classes .= ' saturday';
					if (!empty($cell['isSunday'])) $classes .= ' sunday';
					if (!empty($cell['isCelebration'])) $classes .= ' celebration';
					?>
                    <div class="<?= $classes ?>" data-day="<?= $cell['dayOfWeek'] ?? '' ?>">
						<?php
						$date = $cell['date'];
						$dayNum = $cell['label'];
						$inMonth = $cell['inMonth'] ?? false;

						if (!$inMonth || empty($date)):
							// Empty cell for padding
							?>
                            <span class="day-link empty">&nbsp;</span>
						<?php
						else:
							$hasEvents = $cell['hasEvents'] ?? isset($events[$date]);
							$isActive = $cell['isSelected'] ?? ($date === $selectedDate);
							$isToday = $cell['isToday'] ?? ($date === $todayString);
							$class = 'day-link'
								. ($isActive ? ' active' : '')
								. ($hasEvents ? ' has-events' : '')
								. ($isToday ? ' today' : '');
							?>
							<?= Html::a($dayNum, $buildUrl($viewUrl, ['month' => $month, 'year' => $year, 'date' => $date]), [
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
                            <?php if (isset($eventRender) && $eventRender instanceof \Closure): ?>
                                <?= $eventRender($event['model'], $this->context) ?>
                            <?php else: ?>
                                <li class="event-item d-flex align-items-baseline">
                                    <div class="event-time me-3"><?= Html::encode($event['time']) ?></div>
                                    <div class="event-title"><?= Html::encode($event['title']) ?></div>
                                </li>
                            <?php endif; ?>
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