<?php

namespace nedarta\calendar;

use yii\web\AssetBundle;

/**
 * CalendarAsset
 */
class CalendarAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/assets';
    
    public $css = [
        'calendar.css',
    ];
    
    public $js = [
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
}
