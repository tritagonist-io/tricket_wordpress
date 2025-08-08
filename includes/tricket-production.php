<?php

if (!defined('ABSPATH')) {
    exit;
}
class Tricket_Production {
    public string $id;
    public string $title;
    private array $description;
    private array $shortDescription;
    public ?int $durationInMinutes;
    public ?string $cast;
    public ?string $directedBy;
    public ?string $thumbnail;
    public array $contentRatings;
    /** @var Tricket_Production_Image[] */
    public array $images;
    public ?string $videoUrl;
    /** @var Tricket_Production_Tag[] */
    public array $tags;
    /** @var Tricket_Screening[] */
    private array $all_screenings;

    public function __construct(array $data) {
        $required_fields = ['id', 'title', 'description', 'shortDescription'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field} for Production");
            }
        }

        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->shortDescription = $data['shortDescription'];
        $this->durationInMinutes = $data['durationInMinutes'] ?? null;
        $this->cast = $data['cast'] ?? null;
        $this->directedBy = $data['directedBy'] ?? null;
        $this->videoUrl = $data['videoUrl'] ?? null;
        $this->thumbnail = $data['thumbnail'] ?? null;
        $this->images = array_map(fn($img) => new Tricket_Production_Image($img), $data['images'] ?? []);
        $this->tags = array_map(fn($tag) => new Tricket_Production_Tag($tag), $data['tags'] ?? []);
        $this->all_screenings = array_map(fn($screening) => new Tricket_Screening($screening), $data['screenings'] ?? []);
        
        $this->contentRatings = $data['contentRatings'] ?? [];

        usort($this->images, fn($a, $b) => $a->sortOrder <=> $b->sortOrder);
    }

    /**
     * Magic method to handle computed properties
     */
    public function __get(string $name) {
        if ($name === 'screenings') {
            return $this->get_current_screenings();
        }
        
        throw new InvalidArgumentException("Property '{$name}' does not exist on Tricket_Production");
    }

    public function get_description(string $lang = 'en-US'): string {
        return $this->description[$lang] ?? '';
    }

    public function get_short_description(string $lang = 'en-US'): string {
        return $this->shortDescription[$lang] ?? '';
    }

    public function get_url(): string {
        $options = get_option('tricket_options');
        $slug = $options['tricket_productions_slug'] ?? 'productions';
        $title_slug = sanitize_title($this->title);
        return home_url("/{$slug}/{$title_slug}/");
    }

    /**
     * Get all screenings including those that have already started
     * @return Tricket_Screening[]
     */
    public function get_all_screenings(): array {
        return $this->all_screenings;
    }

    /**
     * Get only current/future screenings (filtered dynamically)
     * @return Tricket_Screening[]
     */
    private function get_current_screenings(): array {
        $current_time = current_datetime();
        return array_filter($this->all_screenings, function($screening) use ($current_time) {
            // Convert both times to UTC for accurate comparison
            $screening_utc = $screening->start_at->getTimestamp();
            $current_utc = $current_time->getTimestamp();
            return $screening_utc > $current_utc;
        });
    }
}

class Tricket_Production_Image {
    public string $url;
    public int $sortOrder;

    public function __construct(array $data) {
        $this->url = $data['url'];
        $this->sortOrder = $data['sortOrder'];
    }
}

class Tricket_Production_Tag {
    public string $id;
    public string $name;
    public ?string $description;

    public function __construct(array $data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
    }
}