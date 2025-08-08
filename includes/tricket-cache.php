<?php

if (!defined('ABSPATH')) {
    exit;
}

class Tricket_Cache
{
    private $cache_time;

    public function __construct($cache_time)
    {
        $this->cache_time = $cache_time;
    }

    public function get($key)
    {
        return get_transient($this->get_cache_key($key));
    }

    public function set($key, $value)
    {
        set_transient($this->get_cache_key($key), $value, $this->cache_time);
    }

    public function delete($key)
    {
        delete_transient($this->get_cache_key($key));
    }

    private function get_cache_key($key)
    {
        return 'tricket_' . md5($key);
    }
}
