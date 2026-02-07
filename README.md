# Yii2 Calendar Widget

A premium, modern, and AJAX-powered calendar widget for Yii2 Framework and Bootstrap 5.

![Calendar Widget Preview](https://raw.githubusercontent.com/nedarta/yii2-calendar-widget/main/screenshots/preview.png)

## Features

- **ActiveRecord Integration**: Easily bind the widget to any `ActiveQuery`.
- **AJAX-Powered**: Month switching and day selection use `Pjax` for a seamless experience.
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

Customize day names and the first day of the week:

```php
echo CalendarWidget::widget([
    'query' => Event::find(),
    'dayNames' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'firstDayOfWeek' => 1, // 0 = Sunday, 1 = Monday, etc.
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

## Styling

The widget uses standard Bootstrap 5 classes and a minimal custom CSS file. 

To override styles, you can point to your own CSS in your application's asset bundle or use the widget within a themed container.

## Testing

This package includes a comprehensive PHPUnit test suite with 21 test cases covering all major functionality.

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
- Custom attribute support
- Edge cases and boundary conditions

All tests are passing with 80+ assertions ensuring reliable functionality.

## License

This project is licensed under the MIT License.
