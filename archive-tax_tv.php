<?php get_header(); ?>

<div class="tax-tv-container">
    <!-- Reproductor del último video -->
    <div class="tax-tv-left">
        <?php echo do_shortcode('[tax_tv_dynamic_player]'); ?>
    </div>

    <!-- Lista de videos -->
    <div class="tax-tv-right">
        <?php echo do_shortcode('[tax_tv_video_list]'); ?>
    </div>
</div>

<?php get_footer(); ?>
