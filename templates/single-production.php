<?php

/**
 * The template for displaying single production pages.
 *
 * This is a placeholder template provided by the Tricket plugin.
 * For best results, your theme should provide its own 'single-production.php' template.
 *
 * @package Tricket
 */

get_header();

$production_title = get_query_var('production_title');
$tricket = new Tricket();
$production = $tricket->get_production_by_title($production_title);

if ($production) :
?>

    <div class="tricket-single-production">
        <div class="tricket-notice">
            <p><strong>Notice:</strong> This is a default template provided by the Tricket plugin. For a custom design, please create a 'single-production.php' file in your theme.</p>
        </div>

        <h1><?php echo esc_html($production->title); ?></h1>

        <?php if ($production->thumbnail) : ?>
            <img src="<?php echo esc_url($production->thumbnail); ?>" alt="<?php echo esc_attr($production->title); ?>">
        <?php endif; ?>

        <div class="tricket-production-description">
            <?php echo wp_kses_post($production->get_description()); ?>
        </div>

        <ul>
            <?php foreach ($production->screenings as $screening) {?>
                <li>
                    <?php echo $screening->get_formatted_start_time() ?>
                    <?php if($screening->is_online_sale_finished()) : ?>
                        <span> - Sale finished</span>
                    <?php endif; ?>
                </li>
            <?php } ?>
        </ul>

        <ul>
            <?php foreach ($production->images as $image) {?>
                <img src="<?php echo esc_url($image->url); ?>" >
            <?php } ?>
        </ul>


    </div>

<?php else : ?>

    <p>Production not found.</p>

<?php
endif;

get_footer();
?>