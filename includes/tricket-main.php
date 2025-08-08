<?php

if (!defined('ABSPATH')) {
    exit;
}

class Tricket
{
    private $api;
    private $cache;
    private $options;

    public function __construct()
    {
        $this->include_files();

        $this->options = $this->get_options();
        $this->cache = new Tricket_Cache($this->options['cache_time'] * 60);
        $this->api = new Tricket_API($this->options['api_url'], $this->options['api_key'], $this->cache);
    }


    private function include_files()
    {
        require_once TRICKET_PLUGIN_DIR . 'includes/tricket-api.php';
        require_once TRICKET_PLUGIN_DIR . 'includes/tricket-production.php';
        require_once TRICKET_PLUGIN_DIR . 'includes/tricket-screening.php';
        require_once TRICKET_PLUGIN_DIR . 'includes/tricket-cache.php';
        require_once TRICKET_PLUGIN_DIR . 'includes/tricket-schedule.php';
    }


    private function get_options(): array {
        $defaults = array(
            'api_url' => '',
            'api_key' => '',
            'cache_time' => 15
        );
        $options = get_option('tricket_options', $defaults);
        return wp_parse_args($options, $defaults);
    }

    public function get_production_by_id($id): Tricket_Production|bool {
        return $this->api->get_production_by_id($id);
    }

    public function get_productions(): array {
        return $this->api->get_productions();
    }

    public function get_production_by_title($title): Tricket_Production|bool {
        $productions = $this->get_productions();
        foreach ($productions as $production) {
            if (sanitize_title($production->title) === $title) {
                return $production;
            }
        }
        return false;
    }

    public function get_schedule(): Tricket_Schedule {
        $productions = $this->get_productions();
        return new Tricket_Schedule($productions);
    }

    /**
     * Get all unique tags across all productions
     * @return Tricket_Production_Tag[]
     */
    public function get_all_tags(): array {
        $productions = $this->get_productions();
        $tags_by_id = [];
        
        foreach ($productions as $production) {
            foreach ($production->tags as $tag) {
                $tags_by_id[$tag->id] = $tag;
            }
        }
        
        $tags = array_values($tags_by_id);
        usort($tags, fn($a, $b) => strcasecmp($a->name, $b->name));
        
        return $tags;
    }

    /**
     * Get productions that have a specific tag
     * @param string $tag_id Tag ID to filter by
     * @return Tricket_Production[]
     */
    public function get_productions_by_tag_id(string $tag_id): array {
        $productions = $this->get_productions();
        
        return array_filter($productions, function($production) use ($tag_id) {
            foreach ($production->tags as $tag) {
                if ($tag->id === $tag_id) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Get productions that have a specific tag name
     * @param string $tag_name Tag name to filter by
     * @return Tricket_Production[]
     */
    public function get_productions_by_tag_name(string $tag_name): array {
        $productions = $this->get_productions();
        
        return array_filter($productions, function($production) use ($tag_name) {
            foreach ($production->tags as $tag) {
                if (strcasecmp($tag->name, $tag_name) === 0) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Get productions that have any of the specified tags
     * @param string[] $tag_ids Array of tag IDs
     * @return Tricket_Production[]
     */
    public function get_productions_by_tag_ids(array $tag_ids): array {
        $productions = $this->get_productions();
        
        return array_filter($productions, function($production) use ($tag_ids) {
            foreach ($production->tags as $tag) {
                if (in_array($tag->id, $tag_ids)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Get productions that have ALL of the specified tags
     * @param string[] $tag_ids Array of tag IDs that ALL must be present
     * @return Tricket_Production[]
     */
    public function get_productions_with_all_tags(array $tag_ids): array {
        $productions = $this->get_productions();
        
        return array_filter($productions, function($production) use ($tag_ids) {
            $production_tag_ids = array_map(fn($tag) => $tag->id, $production->tags);
            
            foreach ($tag_ids as $required_tag_id) {
                if (!in_array($required_tag_id, $production_tag_ids)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Get a tag by its ID
     * @param string $tag_id Tag ID to find
     * @return Tricket_Production_Tag|null
     */
    public function get_tag_by_id(string $tag_id): ?Tricket_Production_Tag {
        $tags = $this->get_all_tags();
        
        foreach ($tags as $tag) {
            if ($tag->id === $tag_id) {
                return $tag;
            }
        }
        
        return null;
    }

    /**
     * Get a tag by its name (case-insensitive)
     * @param string $tag_name Tag name to find
     * @return Tricket_Production_Tag|null
     */
    public function get_tag_by_name(string $tag_name): ?Tricket_Production_Tag {
        $tags = $this->get_all_tags();
        
        foreach ($tags as $tag) {
            if (strcasecmp($tag->name, $tag_name) === 0) {
                return $tag;
            }
        }
        
        return null;
    }
}
