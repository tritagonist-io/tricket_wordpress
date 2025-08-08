<div class="tricket-productions">
    <?php foreach ($productions as $production): ?>
        <div class="tricket-production">
            <h2><?php echo esc_html($production->title); ?></h2>
            <?php if ($production->thumbnail): ?>
                <img src="<?php echo esc_url($production->thumbnail); ?>" alt="<?php echo esc_attr($production->title); ?>">
            <?php endif; ?>
            <p><?php echo esc_html($production->get_description()); ?></p>
        </div>
    <?php endforeach; ?>
</div>
