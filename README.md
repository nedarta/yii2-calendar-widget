# Yii2 Calendar Widget

A premium, modern, and AJAX-powered calendar widget for Yii2 Framework and Bootstrap 5.

![Calendar Widget Preview](https://raw.githubusercontent.com/nedarta/yii2-calendar-widget/main/screenshots/preview.png)

## Features

- **ActiveRecord Integration**: Easily bind the widget to any `ActiveQuery`.
- **AJAX-Powered**: Month switching and day selection use `Pjax` for a seamless experience.
- **Localization (Intl)**: Automatic translation of month and day names using PHP's `intl` extension.
- **Dynamic Day Names**: Choose between narrow, short, abbreviated, or full day names.
- **Custom Event Rendering**: Pass a closure to fully customize how events appear in the sidebar.
- **Custom Celebrations**: Highlight holidays or special dates with custom styling.
- **Bootstrap 5**: Native support for Bootstrap 5 layout and styling.
- **Rich Interaction**: Days with events are highlighted with a status dot.
- **Flexible Attributes**: Supports dot-notation for relations (e.g., `event.name`) and single `datetime` columns.
- **Mobile Responsive**: Adaptive layout for smaller screens.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require --prefer-dist nedarta/yii2-calendar-widget "*"
```

or add

```json
"nedarta/yii2-calendar-widget": "*"
```

to the require section of your `composer.json` file.

## Usage

### Simple Usage

If you have an `Event` model with separate `date` and `time` columns:

```php
use nedarta\calendar\CalendarWidget;
use app\models\Event;

echo CalendarWidget::widget([
    'query' => Event::find(),
    'dateAttribute' => 'event_date', // e.g., '2023-07-04'
    'titleAttribute' => 'name',
    'timeAttribute' => 'start_time', // e.g., '09:00'
]);
```

### Handling Relationships

For a one-to-many relationship (like `EventTimes` related to an `Event`):

```php
use nedarta\calendar\CalendarWidget;
use app\models\EventTime;

echo CalendarWidget::widget([
    'query' => EventTime::find()->joinWith('event'),
    'dateAttribute' => 'date',
    'timeAttribute' => 'time',
    'titleAttribute' => 'event.name', // Access relation attributes with dot-notation
]);
```

### Using a Single Datetime Column

If your table stores both date and time in a single column (like `datetime` or `timestamp`):

```php
echo CalendarWidget::widget([
    'query' => MovieShowtime::find(),
    'dateAttribute' => 'start_at', // '2023-07-04 14:00:00'
    'timeAttribute' => 'start_at', // Extracts '14:00' automatically
    'titleAttribute' => 'movie_name',
]);
```

### Customizing Navigation URLs

By default, the widget uses the current page URL for navigation. You can customize the URLs:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'navUrl' => '/events/calendar', // URL for month navigation
    'viewUrl' => '/events/view',    // URL for day selection
]);
```

### Localization & Internationalization

The widget uses PHP's `intl` extension to automatically localize month and day names. You can specify the language and the format of day names:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'language' => 'lv-LV',      // language code
    'dayNameFormat' => 'short',  // 'narrow', 'short', 'abbr', or 'full'
    'firstDayOfWeek' => 1,       // 0 = Sunday, 1 = Monday, etc.
]);
```

#### Supported Day Name Formats:
- `narrow`: Single letter (e.g., 'P')
- `short`: Two letters (e.g., 'Pr')
- `abbr`: Abbreviated (e.g., 'Pirmd.') - **Default**
- `full`: Full name (e.g., 'Pirmdiena')

### Marking Celebration Days

You can highlight specific dates (e.g., holidays, birthdays) using the `celebrations` array. It supports both fixed dates (`Y-m-d`) and recurring dates (`m-d`):

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'celebrations' => [
        '2025-01-01', // Fixed date
        '05-04',      // Recurring yearly (May 4th)
        '11-18',      // Recurring yearly (Nov 18th)
    ],
]);
```

### Custom HTML Attributes

Add custom CSS classes or HTML attributes to the container:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'options' => [
        'class' => 'my-custom-calendar',
        'data-theme' => 'dark',
    ],
]);
```

### Custom Event Rendering

You can fully customize the HTML output of each event in the sidebar by providing the `eventRender` callback:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'eventRender' => function($model, $calendar) {
        return '
            <div class="custom-event-card card mb-2">
                <div class="card-body p-2">
                    <h6 class="card-title">' . Html::encode($model->title) . '</h6>
                    <small class="text-muted">' . $model->start_time . '</small>
                </div>
            </div>
        ';
    },
]);
```

## Styling

The widget uses standard Bootstrap 5 classes and includes a custom CSS file with specific classes for Saturdays, Sundays, and celebrations:

- `.saturday`: Applied to Saturday cells (default: blue text).
- `.sunday`: Applied to Sunday cells (default: red text).
- `.celebration`: Applied to cells defined in the `celebrations` array.

To override styles, you can point to your own CSS in your application's asset bundle or use the widget within a themed container.

## Testing

This package includes a comprehensive PHPUnit test suite with 29 test cases covering all major functionality.

### Running Tests

Install development dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
# or
vendor/bin/phpunit
```

### Test Coverage

The test suite includes:
- Widget initialization and configuration
- Calendar day generation (including leap years)
- Event retrieval and formatting
- Navigation URL handling
- **Localization (Intl)**
- **Day name format options**
- **Custom event rendering callback**
- **Celebration day markers**
- **Weekend flag detection**
- Custom attribute support
- Edge cases and boundary conditions

All tests are passing with 120+ assertions ensuring reliable functionality.

## License

This project is licensed under the MIT License.
