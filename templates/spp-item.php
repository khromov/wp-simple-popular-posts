<?php
$period_translations = array(
	'day' => __('today', Simple_Popular_Posts::$td),
	'week' => __('this week', Simple_Popular_Posts::$td),
	'month' => __('this month', Simple_Popular_Posts::$td),
	'year' => __('this year', Simple_Popular_Posts::$td),
	'all' => __('total', Simple_Popular_Posts::$td)
);
$period_translation = isset($period_translations[$period]) ? $period_translations[$period] : __('this week', Simple_Popular_Posts::$td);
?>
<div class="simple-popular-posts-single spp-col-xs-12">
	<div class="left spp-col-xs-3">
		<a href="<?php echo get_permalink($post->ID); ?>">
			<?php echo get_avatar(get_userdata($post->post_author)->user_email, 128); ?>
		</a>
	</div>
	<div class="right spp-col-xs-9">
		<h3>
			<a href="<?php echo get_permalink($post->ID); ?>">
				<?php echo get_the_title($post->ID); ?>
			</a>
		</h3>
		<div class="shares" alt="<?php echo Simple_Popular_Posts::get_share_count($post->ID); ?> <?php _e('views', Simple_Popular_Posts::$td); ?> <?php echo $period_translation; ?>" title="<?php echo Simple_Popular_Posts::get_share_count($post->ID); ?> <?php _e('views', Simple_Popular_Posts::$td); ?> <?php echo $period_translation; ?>">
			<?php if($display_value === 'number') : ?>
                <?php echo json_decode('"\uD83D\uDD25"'); ?>
				&nbsp;
				<?php echo Simple_Popular_Posts::get_share_count($post->ID); ?>
			<?php elseif($display_value === 'relative'): ?>
				<?php for($i = 0; $i < $relative_scores[$post->ID]; $i++) : ?>
					<?php echo json_decode('"\uD83D\uDD25"'); ?>
				<?php endfor; ?>
			<?php else: ?>
			<?php endif; ?>
		</div>
	</div>
</div>