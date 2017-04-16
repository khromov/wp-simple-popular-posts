<?php
/**
 * spp
 */
add_action('register_shortcode_ui', function() {
	
	$fields = array(
		array(
			'label'       => esc_html__( 'Time period', Simple_Popular_Posts::$td ),
			'description' => '',
			'attr'        => 'period',
			'type'        => 'select',
			'options'     => array(
				array( 'value' => 'all', 'label' => esc_html__( 'All time', Simple_Popular_Posts::$td  )),
				array( 'value' => 'year', 'label' => esc_html__( 'This year', Simple_Popular_Posts::$td  )),
				array( 'value' => 'month', 'label' => esc_html__( 'This month', Simple_Popular_Posts::$td  )),
				array( 'value' => 'week', 'label' => esc_html__( 'This week', Simple_Popular_Posts::$td  )),
				array( 'value' => 'day', 'label' => esc_html__( 'Today', Simple_Popular_Posts::$td  )),
			),
		),
		array(
			'label'       => esc_html__( 'Title', Simple_Popular_Posts::$td   ),
			'description' => esc_html__( 'Optional title to display above the list', Simple_Popular_Posts::$td  ),
			'attr'        => 'title',
			'type'        => 'text',
		),
		array(
			'label'       => esc_html__( 'Max number of posts', Simple_Popular_Posts::$td ),
			'description' => esc_html__( '', Simple_Popular_Posts::$td ),
			'attr'        => 'limit',
			'type'        => 'number',
			'meta'        => array(
				'min'         => '1',
				'max'         => '999999',
				'step'        => '1',
			),
		),
		array(
			'label'       => esc_html__( 'Relative scoring (Fire icons)', Simple_Popular_Posts::$td ),
			'description' => '',
			'attr'        => 'display_value',
			'type'        => 'select',
			'options'     => array(
				array( 'value' => 'relative', 'label' => esc_html__( 'Yes', Simple_Popular_Posts::$td  )),
				array( 'value' => 'none', 'label' => esc_html__( 'No', Simple_Popular_Posts::$td  )),
			),
		),
		array(
			'label'       => esc_html__( 'Relative number count', Simple_Popular_Posts::$td ),
			'description' => esc_html__( 'Max number of relative values (1-10)', Simple_Popular_Posts::$td ),
			'attr'        => 'relative_value_count',
			'type'        => 'number',
			'meta'        => array(
				'placeholder' => '5',
				'min'         => '1',
				'max'         => '10',
				'step'        => '1',
			),
		),
		array(
			'label'       => esc_html__( 'List all posts across the network', Simple_Popular_Posts::$td ),
			'description' => esc_html__('Show all the posts in the multisite (Reqiures WPMUDev Post Indexer plugin)'),
			'attr'        => 'network',
			'type'        => 'select',
			'default'     => 'false',
			'options'     => array(
				array( 'value' => 'false', 'label' => esc_html__( 'No', Simple_Popular_Posts::$td  )),
				array( 'value' => 'true', 'label' => esc_html__( 'Yes', Simple_Popular_Posts::$td  )),
			),
		),
	);
	/*
	 * Define the Shortcode UI arguments.
	 */
	$shortcode_ui_args = array(
		'label' => esc_html__( 'SPP: Popular posts list', Simple_Popular_Posts::$td ),
		'listItemImage' => 'dashicons-list-view',
		'attrs' => $fields,
	);
	shortcode_ui_register_for_shortcode( 'spp', $shortcode_ui_args );
});

/**
 * spp-current-post
 */
add_action('register_shortcode_ui', function() {

	$fields = array();

	$shortcode_ui_args = array(
		'label' => esc_html__( 'SPP: Current post views', Simple_Popular_Posts::$td ),
		'listItemImage' => 'dashicons-welcome-view-site',
		'attrs' => $fields,
	);
	shortcode_ui_register_for_shortcode( 'spp-current-post', $shortcode_ui_args );
});