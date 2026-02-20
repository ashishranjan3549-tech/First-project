<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="flipx-panel" style="margin-top:16px;">
    <h1><?php esc_html_e('FlipX Gaming', 'flipx-theme'); ?></h1>
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div><?php the_excerpt(); ?></div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p><?php esc_html_e('No content found.', 'flipx-theme'); ?></p>
    <?php endif; ?>
</section>
<?php
get_footer();
