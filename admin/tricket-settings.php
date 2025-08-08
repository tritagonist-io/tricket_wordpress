<?php

if (!defined('ABSPATH')) {
    exit;
}

class Tricket_Settings
{
    private $options;
    private $old_slug;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_post_tricket_clear_cache', array($this, 'handle_clear_cache'));
        $this->options = get_option('tricket_options');
        $this->old_slug = isset($this->options['tricket_productions_slug']) ? $this->options['tricket_productions_slug'] : 'productions';
    }

    public function add_plugin_page()
    {
        add_options_page(
            'Tricket Settings',
            'Tricket',
            'manage_options',
            'tricket-settings',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page()
    {
        $this->options = get_option('tricket_options');
        
        // Show cache cleared message
        if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Cache cleared successfully!</p></div>';
        }
?>
        <div class="wrap">
            <h1>Tricket Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('tricket_option_group');
                do_settings_sections('tricket-settings');
                submit_button();
                ?>
            </form>
            
            <h2>Cache Management</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('tricket_clear_cache', 'tricket_cache_nonce'); ?>
                <input type="hidden" name="action" value="tricket_clear_cache">
                <?php submit_button('Clear Cache', 'secondary', 'clear_cache', false); ?>
                <p class="description">Force refresh all cached movies and screening data.</p>
            </form>
        </div>
<?php
    }

    public function page_init()
    {
        register_setting(
            'tricket_option_group',
            'tricket_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'tricket_setting_section',
            'Tricket Settings',
            array($this, 'section_info'),
            'tricket-settings'
        );

        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'api_url_callback'),
            'tricket-settings',
            'tricket_setting_section'
        );

        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'tricket-settings',
            'tricket_setting_section'
        );

        add_settings_field(
            'cache_time',
            'Cache Time (minutes)',
            array($this, 'cache_time_callback'),
            'tricket-settings',
            'tricket_setting_section'
        );

        add_settings_field(
            'tricket_productions_slug',
            'Productions Slug',
            array($this, 'tricket_productions_slug_callback'),
            'tricket-settings',
            'tricket_setting_section'
        );
    }

    public function sanitize($input)
    {
        $sanitary_values = array();
        $has_errors = false;

        // API URL
        if (!empty($input['api_url'])) {
            $sanitary_values['api_url'] = esc_url_raw($input['api_url']);
        } else {
            add_settings_error('tricket_options', 'api_url', 'API URL is required.');
            $has_errors = true;
        }

        // API Key
        if (!empty($input['api_key'])) {
            $sanitary_values['api_key'] = sanitize_text_field($input['api_key']);
        } else {
            add_settings_error('tricket_options', 'api_key', 'API Key is required.');
            $has_errors = true;
        }

        // Cache Time
        if (!empty($input['cache_time'])) {
            $sanitary_values['cache_time'] = absint($input['cache_time']);
        } else {
            add_settings_error('tricket_options', 'cache_time', 'Cache Time is required.');
            $has_errors = true;
        }

        // Productions Slug
        if (!empty($input['tricket_productions_slug'])) {
            $new_slug = sanitize_text_field($input['tricket_productions_slug']);
            $sanitary_values['tricket_productions_slug'] = $new_slug;

            // Check if the slug has changed
            if ($new_slug !== $this->old_slug) {
                add_action('shutdown', array($this, 'flush_rewrite_rules_on_next_request'));
            }
        } else {
            add_settings_error('tricket_options', 'tricket_productions_slug', 'Productions Slug is required.');
            $has_errors = true;
        }

        // If there are errors, return the old options
        if ($has_errors) {
            return $this->options;
        }

        return $sanitary_values;
    }

    public function flush_rewrite_rules_on_next_request()
    {
        update_option('tricket_flush_rewrite_rules', true);
    }

    public function handle_clear_cache()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Verify nonce
        if (!isset($_POST['tricket_cache_nonce']) || !wp_verify_nonce($_POST['tricket_cache_nonce'], 'tricket_clear_cache')) {
            wp_die('Security check failed.');
        }

        // Clear all Tricket cache
        $this->clear_all_tricket_cache();

        // Redirect back with success message
        wp_redirect(add_query_arg(array(
            'page' => 'tricket-settings',
            'cache_cleared' => '1'
        ), admin_url('options-general.php')));
        exit;
    }

    private function clear_all_tricket_cache()
    {
        // Include required files if not already loaded
        if (!class_exists('Tricket_Cache')) {
            require_once TRICKET_PLUGIN_DIR . 'includes/tricket-cache.php';
        }
        
        $cache = new Tricket_Cache(0); // Cache time doesn't matter for deletion
        
        // Clear the cache key used by the API
        $cache->delete('tricket_productions');
    }

    public function section_info()
    {
        echo 'Enter your settings below:';
    }

    public function api_url_callback()
    {
        printf(
            '<input type="text" id="api_url" name="tricket_options[api_url]" value="%s"  />',
            isset($this->options['api_url']) ? esc_attr($this->options['api_url']) : ''
        );
    }

    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api_key" name="tricket_options[api_key]" value="%s" required />',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    public function cache_time_callback()
    {
        printf(
            '<input type="number" id="cache_time" name="tricket_options[cache_time]" value="%s" required />',
            isset($this->options['cache_time']) ? esc_attr($this->options['cache_time']) : '15'
        );
    }

    public function tricket_productions_slug_callback()
    {
        $slug = isset($this->options['tricket_productions_slug']) ? $this->options['tricket_productions_slug'] : 'productions';
        printf(
            '<input type="text" id="tricket_productions_slug" name="tricket_options[tricket_productions_slug]" value="%s" required />',
            esc_attr($slug)
        );
        echo "<p class='description'>Enter the slug for production pages (e.g., 'productions', 'films', 'movies')</p>";
    }
}

if (is_admin()) {
    $tricket_settings = new Tricket_Settings();
}

function tricket_maybe_flush_rewrite_rules()
{
    if (get_option('tricket_flush_rewrite_rules')) {
        flush_rewrite_rules();
        delete_option('tricket_flush_rewrite_rules');
    }
}
add_action('init', 'tricket_maybe_flush_rewrite_rules', 20);
