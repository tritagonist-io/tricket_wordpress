<?php

if (!defined('ABSPATH')) {
    exit;
}

class Tricket_API
{
    private $api_url;
    private $api_key;
    private $cache;

    public function __construct($api_url, $api_key, $cache)
    {
        $this->api_url = $api_url;
        $this->api_key = $api_key;
        $this->cache = $cache;
    }

    private function make_request($endpoint)
    {
        $url = $this->api_url . $endpoint;

        $args = array(
            'headers' => array(
                'X-API-KEY' => $this->api_key,
            ),
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * @return Tricket_Production[]
     */
    public function get_productions(): array {
        $cache_key = 'tricket_productions';
        $cached_productions = $this->cache->get($cache_key);

        if ($cached_productions !== false) {
            return $cached_productions;
        }

        $response = $this->make_request('productions/with-screening');
        if (!$response || !isset($response['productions'])) {
            return [];
        }

        $productions = [];
        foreach ($response['productions'] as $production_data) {
            try {
                $productions[] = new Tricket_Production($production_data);
            } catch (InvalidArgumentException $e) {
                error_log('Error creating Production object: ' . $e->getMessage());
                return [];
            }
        }

        $this->cache->set($cache_key, $productions);

        return $productions;
    }

    public function get_production_by_id($id): bool|Tricket_Production {
        $productions = $this->get_productions();
        if (!$productions) {
            return false;
        }

        foreach ($productions as $production) {
            if ($production->id == $id) {
                return $production;
            }
        }

        return false;
    }
}
