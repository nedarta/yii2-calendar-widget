# CalendarWidget Examples

## Basic Usage with Auto-Localization

The widget automatically uses your application's language setting:

```php
// In your Yii2 application config
'language' => 'de-DE',

// Widget will automatically use German locale
echo CalendarWidget::widget([
    'query' => Event::find(),
    'dateAttribute' => 'event_date',
    'titleAttribute' => 'name',
    'timeAttribute' => 'start_time',
]);
```

## Explicit Language Setting

Override the application language for specific instances:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'dateAttribute' => 'event_date',
    'titleAttribute' => 'name',
    'timeAttribute' => 'start_time',
    'language' => 'lv-LV', // Latvian
]);
```

## Language with First Day of Week

Different locales may prefer different first days of the week:

```php
// German calendar starting on Monday
echo CalendarWidget::widget([
    'query' => Event::find(),
    'language' => 'de-DE',
    'firstDayOfWeek' => 1, // Monday
]);

// US calendar starting on Sunday
echo CalendarWidget::widget([
    'query' => Event::find(),
    'language' => 'en-US',
    'firstDayOfWeek' => 0, // Sunday
]);
```

## Supported Languages

The widget uses PHP's Intl extension, so it supports all locales supported by ICU:

- English: `'en-US'`, `'en-GB'`
- German: `'de-DE'`, `'de-AT'`, `'de-CH'`
- French: `'fr-FR'`, `'fr-CA'`
- Spanish: `'es-ES'`, `'es-MX'`
- Italian: `'it-IT'`
- Latvian: `'lv-LV'`
- Russian: `'ru-RU'`
- And many more...

## Weekend Styling

Saturdays and Sundays are automatically styled with distinct colors:
- Sunday: Red (`#f44336`)
- Saturday: Blue (`#0DA5F6`)

These styles are applied using CSS classes (`.sunday` and `.saturday`) which can be customized:

```css
/* Custom weekend colors */
.calendar-widget .day-name.sunday,
.calendar-widget .day-number.sunday {
    color: #ff0000;
}

.calendar-widget .day-name.saturday,
.calendar-widget .day-number.saturday {
    color: #0000ff;
}
```

## Custom Day Names (Override Auto-Localization)

If you need specific abbreviations instead of auto-generated ones:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'dayNames' => ['S', 'M', 'T', 'W', 'T', 'F', 'S'], // Single letter
    'firstDayOfWeek' => 0,
]);
```

## Complete Example

```php
use nedarta\calendar\CalendarWidget;
use app\models\Event;

echo CalendarWidget::widget([
    'query' => Event::find()->where(['status' => 'active']),
    'dateAttribute' => 'event_date',
    'titleAttribute' => 'name',
    'timeAttribute' => 'start_time',
    'language' => Yii::$app->language, // Use app language
    'firstDayOfWeek' => 1, // Monday
    'month' => date('n'),
    'year' => date('Y'),
    'navUrl' => ['/events/calendar'],
    'viewUrl' => ['/events/view'],
    'options' => [
        'class' => 'calendar-widget shadow-sm p-4',
    ],
]);
```
