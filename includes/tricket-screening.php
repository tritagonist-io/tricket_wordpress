<?php

if (!defined('ABSPATH')) {
    exit;
}

class Tricket_Screening {
    public string $id;
    public string $production_id;
    public DateTime $start_at;
    public DateTime $ends_at;
    public string $timezone;
    public string $hall_name;
    public string $venue_name;
    public string $url;
    public int $hall_capacity;
    public int $number_of_booked_seats;
    public int $seconds_to_end_of_sale;
    public int $seats_left;

    public function __construct(array $data) {
        $required_fields = ['id', 'startAtUtc', 'endsAtUtc', 'timezone', 'productionId', 'hallName', 'venueName', 'hallCapacity', 'numberOfBookedSeats', 'secondsToEndOfSale', 'url'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field} for Screening");
            }
        }

        $this->id = $data['id'];
        $this->start_at = new DateTime($data['startAtUtc'], new DateTimeZone('UTC'));
        $this->ends_at = new DateTime($data['endsAtUtc'], new DateTimeZone('UTC'));
        $this->timezone = $data['timezone'];
        $this->production_id = $data['productionId'];
        $this->hall_name = $data['hallName'];
        $this->venue_name = $data['venueName'];
        $this->hall_capacity = $data['hallCapacity'];
        $this->number_of_booked_seats = $data['numberOfBookedSeats'];
        $this->seconds_to_end_of_sale = $data['secondsToEndOfSale'];
        $this->url = $data['url'];

        $this->seats_left = $this->hall_capacity - $this->number_of_booked_seats;
    }

    public function get_formatted_start_time(string $format = 'Y-m-d H:i'): string {
        $local_time = clone $this->start_at;
        $local_time->setTimezone(new DateTimeZone($this->timezone));
        return $local_time->format($format);
    }

    public function get_formatted_end_time(string $format = 'Y-m-d H:i'): string {
        $local_time = clone $this->ends_at;
        $local_time->setTimezone(new DateTimeZone($this->timezone));
        return $local_time->format($format);
    }

    public function is_online_sale_finished(): bool {
        return $this->seconds_to_end_of_sale <= 0;
    }

    public function get_seconds_to_end_of_sale(): int {
        return max(0, $this->seconds_to_end_of_sale);
    }

    public function get_time_to_end_of_sale_formatted(): string {
        $seconds = $this->get_seconds_to_end_of_sale();
        
        if ($seconds <= 0) {
            return 'Sale ended';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remaining_seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $remaining_seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $remaining_seconds);
        } else {
            return sprintf('%ds', $remaining_seconds);
        }
    }
}
