<?php

if (!defined('ABSPATH')) {
    exit;
}

class Tricket_Schedule {
    /** @var array<string, Tricket_Screening[]> Screenings grouped by date (Y-m-d format) */
    private array $screenings_by_date = [];
    
    /** @var Tricket_Screening[] All screenings in chronological order */
    private array $all_screenings = [];
    
    /** @var Tricket_Production[] Reference to original productions for tag filtering */
    private array $productions;

    /**
     * @param Tricket_Production[] $productions
     */
    public function __construct(array $productions) {
        $this->productions = $productions;
        $this->build_schedule($productions);
    }

    /**
     * Build the schedule from productions data
     * @param Tricket_Production[] $productions
     */
    private function build_schedule(array $productions): void {
        $all_screenings = [];
        
        foreach ($productions as $production) {
            foreach ($production->screenings as $screening) {
                $all_screenings[] = $screening;
            }
        }

        // Sort screenings chronologically
        usort($all_screenings, fn($a, $b) => $a->start_at <=> $b->start_at);
        $this->all_screenings = $all_screenings;

        // Group by date
        foreach ($all_screenings as $screening) {
            $date = $screening->start_at->format('Y-m-d');
            if (!isset($this->screenings_by_date[$date])) {
                $this->screenings_by_date[$date] = [];
            }
            $this->screenings_by_date[$date][] = $screening;
        }
    }

    /**
     * Get all screenings for a specific date
     * @param string $date Date in Y-m-d format
     * @return Tricket_Screening[]
     */
    public function get_screenings_for_date(string $date): array {
        return $this->screenings_by_date[$date] ?? [];
    }

    /**
     * Get all screenings for today
     * @return Tricket_Screening[]
     */
    public function get_todays_screenings(): array {
        $today = current_datetime()->format('Y-m-d');
        return $this->get_screenings_for_date($today);
    }

    /**
     * Get all screenings for tomorrow
     * @return Tricket_Screening[]
     */
    public function get_tomorrows_screenings(): array {
        $tomorrow = current_datetime()->modify('+1 day')->format('Y-m-d');
        return $this->get_screenings_for_date($tomorrow);
    }

    /**
     * Get screenings for a date range
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @return Tricket_Screening[]
     */
    public function get_screenings_for_date_range(string $start_date, string $end_date): array {
        $screenings = [];
        foreach ($this->screenings_by_date as $date => $date_screenings) {
            if ($date >= $start_date && $date <= $end_date) {
                $screenings = array_merge($screenings, $date_screenings);
            }
        }
        return $screenings;
    }

    /**
     * Get screenings for the current week (Monday to Sunday)
     * @return Tricket_Screening[]
     */
    public function get_this_weeks_screenings(): array {
        $now = current_datetime();
        $monday = $now->modify('Monday this week')->format('Y-m-d');
        $sunday = $now->modify('Sunday this week')->format('Y-m-d');
        return $this->get_screenings_for_date_range($monday, $sunday);
    }

    /**
     * Get screenings for the next 7 days from today
     * @return Tricket_Screening[]
     */
    public function get_next_week_screenings(): array {
        $today = current_datetime()->format('Y-m-d');
        $next_week = current_datetime()->modify('+6 days')->format('Y-m-d');
        return $this->get_screenings_for_date_range($today, $next_week);
    }

    /**
     * Get all dates that have screenings
     * @return string[] Array of dates in Y-m-d format, sorted chronologically
     */
    public function get_available_dates(): array {
        $dates = array_keys($this->screenings_by_date);
        sort($dates);
        return $dates;
    }

    /**
     * Get the complete schedule grouped by date
     * @return array<string, Tricket_Screening[]> Date => screenings array
     */
    public function get_full_schedule(): array {
        return $this->screenings_by_date;
    }

    /**
     * Get all screenings in chronological order
     * @return Tricket_Screening[]
     */
    public function get_all_screenings(): array {
        return $this->all_screenings;
    }

    /**
     * Get screenings for a specific venue
     * @param string $venue_name Venue name to filter by
     * @return Tricket_Screening[]
     */
    public function get_screenings_for_venue(string $venue_name): array {
        return array_filter($this->all_screenings, 
            fn($screening) => $screening->venue_name === $venue_name
        );
    }

    /**
     * Get all unique venue names that have screenings
     * @return string[]
     */
    public function get_venues(): array {
        $venues = [];
        foreach ($this->all_screenings as $screening) {
            if (!in_array($screening->venue_name, $venues)) {
                $venues[] = $screening->venue_name;
            }
        }
        sort($venues);
        return $venues;
    }

    /**
     * Check if there are any screenings available
     * @return bool
     */
    public function has_screenings(): bool {
        return !empty($this->all_screenings);
    }

    /**
     * Get the total number of screenings
     * @return int
     */
    public function get_total_screenings_count(): int {
        return count($this->all_screenings);
    }

    /**
     * Filter screenings by tag ID
     * @param Tricket_Screening[] $screenings Screenings to filter
     * @param string $tag_id Tag ID to filter by
     * @return Tricket_Screening[]
     */
    private function filter_screenings_by_tag_id(array $screenings, string $tag_id): array {
        return array_filter($screenings, function($screening) use ($tag_id) {
            return $this->screening_has_tag_id($screening, $tag_id);
        });
    }

    /**
     * Filter screenings by tag name (case-insensitive)
     * @param Tricket_Screening[] $screenings Screenings to filter
     * @param string $tag_name Tag name to filter by
     * @return Tricket_Screening[]
     */
    private function filter_screenings_by_tag_name(array $screenings, string $tag_name): array {
        return array_filter($screenings, function($screening) use ($tag_name) {
            return $this->screening_has_tag_name($screening, $tag_name);
        });
    }

    /**
     * Filter screenings by multiple tag IDs (any match)
     * @param Tricket_Screening[] $screenings Screenings to filter
     * @param string[] $tag_ids Tag IDs to filter by
     * @return Tricket_Screening[]
     */
    private function filter_screenings_by_tag_ids(array $screenings, array $tag_ids): array {
        return array_filter($screenings, function($screening) use ($tag_ids) {
            foreach ($tag_ids as $tag_id) {
                if ($this->screening_has_tag_id($screening, $tag_id)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Check if a screening's production has a specific tag ID
     * @param Tricket_Screening $screening
     * @param string $tag_id
     * @return bool
     */
    private function screening_has_tag_id(Tricket_Screening $screening, string $tag_id): bool {
        $production = $this->get_production_by_id($screening->production_id);
        if (!$production) {
            return false;
        }

        foreach ($production->tags as $tag) {
            if ($tag->id === $tag_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a screening's production has a specific tag name
     * @param Tricket_Screening $screening
     * @param string $tag_name
     * @return bool
     */
    private function screening_has_tag_name(Tricket_Screening $screening, string $tag_name): bool {
        $production = $this->get_production_by_id($screening->production_id);
        if (!$production) {
            return false;
        }

        foreach ($production->tags as $tag) {
            if (strcasecmp($tag->name, $tag_name) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get production by ID from stored productions
     * @param string $production_id
     * @return Tricket_Production|null
     */
    public function get_production_by_id(string $production_id): ?Tricket_Production {
        foreach ($this->productions as $production) {
            if ($production->id === $production_id) {
                return $production;
            }
        }
        return null;
    }

    /**
     * Get screenings for productions that have a specific tag
     * @param string $tag_id Tag ID to filter by
     * @return Tricket_Screening[]
     */
    public function get_screenings_by_tag_id(string $tag_id): array {
        return $this->filter_screenings_by_tag_id($this->all_screenings, $tag_id);
    }

    /**
     * Get screenings for productions that have a specific tag name
     * @param string $tag_name Tag name to filter by (case-insensitive)
     * @return Tricket_Screening[]
     */
    public function get_screenings_by_tag_name(string $tag_name): array {
        return $this->filter_screenings_by_tag_name($this->all_screenings, $tag_name);
    }

    /**
     * Get screenings for productions that have any of the specified tags
     * @param string[] $tag_ids Array of tag IDs
     * @return Tricket_Screening[]
     */
    public function get_screenings_by_tag_ids(array $tag_ids): array {
        return $this->filter_screenings_by_tag_ids($this->all_screenings, $tag_ids);
    }

    /**
     * Get today's screenings filtered by tag
     * @param string $tag_id Tag ID to filter by
     * @return Tricket_Screening[]
     */
    public function get_todays_screenings_by_tag(string $tag_id): array {
        return $this->filter_screenings_by_tag_id($this->get_todays_screenings(), $tag_id);
    }

    /**
     * Get screenings for a date range filtered by tag
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @param string $tag_id Tag ID to filter by
     * @return Tricket_Screening[]
     */
    public function get_screenings_for_date_range_by_tag(string $start_date, string $end_date, string $tag_id): array {
        return $this->filter_screenings_by_tag_id($this->get_screenings_for_date_range($start_date, $end_date), $tag_id);
    }

    /**
     * Get screenings for a specific date filtered by tag
     * @param string $date Date in Y-m-d format
     * @param string $tag_id Tag ID to filter by
     * @return Tricket_Screening[]
     */
    public function get_screenings_for_date_by_tag(string $date, string $tag_id): array {
        return $this->filter_screenings_by_tag_id($this->get_screenings_for_date($date), $tag_id);
    }
}