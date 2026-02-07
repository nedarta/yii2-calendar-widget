<?php

// Ensure we have the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define constants
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Load Yii2
require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Create a minimal application for testing
new \yii\console\Application([
    'id' => 'calendar-widget-test',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'components' => [
        'request' => [
            'class' => 'yii\web\Request',
            'cookieValidationKey' => 'test',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
            'baseUrl' => '',
            'hostInfo' => 'http://localhost',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite::memory:',
        ],
    ],
]);

