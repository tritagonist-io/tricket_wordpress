# Tricket WordPress Plugin

Integrates your WordPress site with Tricket, fetching and displaying production data including movies, shows, and screening information.

## Installation

1. Upload the `tricket` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Tricket to configure your API credentials

## Configuration

Navigate to **Settings > Tricket** in your WordPress admin to configure:

- **API URL**: Your Tricket API endpoint
- **API Key**: Your Tricket API authentication key
- **Cache Time**: How long to cache production data (in minutes, default: 15)
- **Productions Slug**: URL slug for production pages (default: "productions")

## Usage

### Display Productions List

Use the shortcode to display all productions:

```
[tricket_productions]
```

This will show a grid of productions with titles, thumbnails, and descriptions.

### Display Schedule

Use the schedule shortcode to display screenings organized by date:

```
[tricket_schedule]
```

This will show all upcoming screenings grouped by date, with production titles and screening times. Only screenings that haven't started yet are displayed.

### Individual Production Pages

Productions are automatically accessible via custom URLs:

```
https://yoursite.com/productions/production-title/
```

The URL slug ("productions") can be customized in the plugin settings.

### Template Customization

For custom styling, create these templates in your active theme:

- `single-production.php` - Custom template for individual production pages
- Override the shortcode output by customizing the CSS classes:
  - `.tricket-productions` - Productions grid container
  - `.tricket-production` - Individual production item
  - `.tricket-single-production` - Single production page wrapper

### PHP API for Template Developers

Template developers can access production data directly in PHP:

```php
// Get all productions
$tricket = new Tricket();
$productions = $tricket->get_productions();

foreach ($productions as $production) {
    echo '<h2>' . esc_html($production->title) . '</h2>';
    echo '<p>' . esc_html($production->get_description()) . '</p>';
    
    // Access screenings
    foreach ($production->screenings as $screening) {
        echo '<p>Screening: ' . $screening->get_formatted_start_time() . '</p>';
    }
}
```

```php
// Get single production by ID
$tricket = new Tricket();
$production = $tricket->get_production_by_id('12345');
if ($production) {
    echo '<h1>' . esc_html($production->title) . '</h1>';
}
```

```php
// Get single production by title slug (for custom URL handling)
$tricket = new Tricket();
$production_title = get_query_var('production_title');
$production = $tricket->get_production_by_title($production_title);
```

```php
// Work with tags
$tricket = new Tricket();

// Get all available tags
$tags = $tricket->get_all_tags();

// Get productions by tag
$horror_movies = $tricket->get_productions_by_tag_name('Horror');
$action_movies = $tricket->get_productions_by_tag_id('tag-123');

// Get productions with multiple tags (any match)
$genre_movies = $tricket->get_productions_by_tag_ids(['horror-id', 'action-id']);

// Get productions with ALL specified tags
$specific_movies = $tricket->get_productions_with_all_tags(['horror-id', 'new-id']);

// Find specific tags
$horror_tag = $tricket->get_tag_by_name('Horror');
$specific_tag = $tricket->get_tag_by_id('tag-123');

// Generate production URLs for links
foreach ($productions as $production) {
    $url = $production->get_url();
    echo '<a href="' . esc_url($url) . '">' . esc_html($production->title) . '</a>';
}
```

```php
// Get complete schedule with all screenings organized by date
$tricket = new Tricket();
$schedule = $tricket->get_schedule();

// Get screenings for a specific date
$screenings = $schedule->get_screenings_for_date('2024-12-25');

// Get today's screenings
$todays_screenings = $schedule->get_todays_screenings();

// Get this week's screenings
$week_screenings = $schedule->get_this_weeks_screenings();

// Get all available dates
$dates = $schedule->get_available_dates();

// Display schedule by date
foreach ($schedule->get_full_schedule() as $date => $screenings) {
    echo '<h3>' . $date . '</h3>';
    foreach ($screenings as $screening) {
        echo '<p>' . $screening->get_formatted_start_time() . ' - ' . 
             esc_html($screening->venue_name) . '</p>';
    }
}

// Filter screenings by tags
$horror_screenings = $schedule->get_screenings_by_tag_name('Horror');
$todays_action = $schedule->get_todays_screenings_by_tag('action-id');
$weekend_thrillers = $schedule->get_screenings_for_date_range_by_tag(
    '2024-12-21', '2024-12-22', 'thriller-id'
);
```

#### Available Production Properties
- `$production->id` - Production ID
- `$production->title` - Production title
- `$production->get_description($lang)` - Get description (default: 'en-US')
- `$production->get_short_description($lang)` - Get short description
- `$production->get_url()` - Get the production's page URL
- `$production->durationInMinutes` - Runtime in minutes (nullable)
- `$production->cast` - Cast information (nullable)
- `$production->directedBy` - Director information (nullable)
- `$production->thumbnail` - Main thumbnail URL (nullable)
- `$production->videoUrl` - Video URL (nullable)
- `$production->images` - Array of Tricket_Production_Image objects
- `$production->tags` - Array of Tricket_Production_Tag objects
- `$production->screenings` - Dynamic property that returns current/upcoming screening objects (automatically excludes screenings that have already started, computed fresh each time accessed)
- `$production->get_all_screenings()` - Get all screenings including those that have already started
- `$production->contentRatings` - Array of content rating strings

#### Available Production Image Properties
- `$image->url` - Image URL
- `$image->sortOrder` - Display sort order

#### Available Production Tag Properties
- `$tag->id` - Tag ID
- `$tag->name` - Tag name
- `$tag->description` - Tag description (nullable)

#### Available Screening Properties
- `$screening->id` - Screening ID
- `$screening->production_id` - Associated production ID
- `$screening->start_at` - Start DateTime object (in UTC)
- `$screening->ends_at` - End DateTime object (in UTC)
- `$screening-timezone` - Timezone
- `$screening->hall_name` - Hall/theater name
- `$screening->venue_name` - Venue name
- `$screening->hall_capacity` - Total seats
- `$screening->number_of_booked_seats` - Booked seats
- `$screening->seats_left` - Available seats (calculated)
- `$screening->seconds_to_end_of_sale` - Seconds until sale ends
- `$screening->url` - Booking URL
- `$screening->get_formatted_start_time($format)` - Formatted start time
- `$screening->get_formatted_end_time($format)` - Formatted end time
- `$screening->is_online_sale_finished()` - Check if sale has ended
- `$screening->get_time_to_end_of_sale_formatted()` - Human-readable time until sale ends

#### Available Schedule Methods
- `$schedule->get_screenings_for_date($date)` - Get screenings for specific date (Y-m-d format)
- `$schedule->get_todays_screenings()` - Get today's screenings
- `$schedule->get_tomorrows_screenings()` - Get tomorrow's screenings
- `$schedule->get_this_weeks_screenings()` - Get current week's screenings (Mon-Sun)
- `$schedule->get_next_week_screenings()` - Get next 7 days of screenings
- `$schedule->get_screenings_for_date_range($start, $end)` - Get screenings within date range
- `$schedule->get_full_schedule()` - Get all screenings grouped by date
- `$schedule->get_all_screenings()` - Get all screenings chronologically
- `$schedule->get_available_dates()` - Get all dates that have screenings
- `$schedule->get_screenings_for_venue($venue_name)` - Filter by venue
- `$schedule->get_venues()` - Get all venue names
- `$schedule->has_screenings()` - Check if any screenings exist
- `$schedule->get_total_screenings_count()` - Get total number of screenings

#### Available Tag Methods (Tricket class)
- `$tricket->get_all_tags()` - Get all unique tags across productions
- `$tricket->get_productions_by_tag_id($tag_id)` - Get productions with specific tag ID
- `$tricket->get_productions_by_tag_name($tag_name)` - Get productions with specific tag name
- `$tricket->get_productions_by_tag_ids($tag_ids)` - Get productions with any of the specified tags
- `$tricket->get_productions_with_all_tags($tag_ids)` - Get productions with ALL specified tags
- `$tricket->get_tag_by_id($tag_id)` - Find tag by ID
- `$tricket->get_tag_by_name($tag_name)` - Find tag by name

#### Available Schedule Tag Methods
- `$schedule->get_screenings_by_tag_id($tag_id)` - Get all screenings for productions with tag
- `$schedule->get_screenings_by_tag_name($tag_name)` - Get screenings by tag name
- `$schedule->get_screenings_by_tag_ids($tag_ids)` - Get screenings with any of the specified tags
- `$schedule->get_todays_screenings_by_tag($tag_id)` - Get today's screenings filtered by tag
- `$schedule->get_screenings_for_date_range_by_tag($start, $end, $tag_id)` - Get date range screenings by tag
- `$schedule->get_screenings_for_date_by_tag($date, $tag_id)` - Get specific date screenings by tag

## Troubleshooting

- **No productions showing**: Check your API URL and API Key in settings
- **Custom URLs not working**: Go to Settings > Permalinks and click "Save Changes" to flush rewrite rules
