<div class="tricket-schedule">
    <?php if (empty($schedule->get_full_schedule())): ?>
        <p>No screenings scheduled.</p>
    <?php else: ?>
        <?php foreach ($schedule->get_full_schedule() as $date => $screenings): ?>
            <div class="tricket-schedule-day">
                <h3 class="tricket-schedule-date"><?php echo esc_html(date('l, F j, Y', strtotime($date))); ?></h3>
                <?php 
                // Group screenings by production
                $productions_screenings = [];
                foreach ($screenings as $screening) {
                    $productions_screenings[$screening->production_id][] = $screening;
                }
                ?>
                <?php foreach ($productions_screenings as $production_id => $prod_screenings): ?>
                    <?php 
                    // Get the production for this screening
                    $production = $schedule->get_production_by_id($production_id);
                    if (!$production) continue;
                    ?>
                    <div class="tricket-schedule-production">
                        <div class="tricket-production-content">
                            <?php if ($production->thumbnail): ?>
                                <div class="tricket-production-poster">
                                    <img src="<?php echo esc_url($production->thumbnail); ?>" alt="<?php echo esc_attr($production->title); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="tricket-production-info">
                                <a href="<?php echo esc_url($production->get_url()); ?>"><h4 class="tricket-production-title"><?php echo esc_html($production->title); ?></h4></a>
                                <?php if ($production->get_short_description('en-US')): ?>
                                    <p class="tricket-production-description"><?php echo esc_html($production->get_short_description('en-US')); ?></p>
                                <?php endif; ?>
                                <div class="tricket-screening-times">
                                    <?php foreach ($prod_screenings as $screening): ?>
                                        <a href="<?php echo esc_url($screening->url); ?>" class="tricket-screening-time" target="_blank">
                                            <?php echo esc_html($screening->get_formatted_start_time('H:i')); ?>
                                            <?php if ($screening->is_online_sale_finished()): ?>
                                                <span class="tricket-sale-ended"> (Sale ended)</span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>