<?php
vc_map(array(
	"name" => __('Simple Popular Posts', Simple_Popular_Posts::$td),
	"base" => "spp",
	"icon" => "spp",
	"description" => __('Adds a popular posts list', Simple_Popular_Posts::$td),
	"show_settings_on_create" => true,
	"class" => "vc-featured-author-large-block",
	"category" => "Content", //TODO: Should this be internationalized?
	'custom_markup' => '',
	'default_content' => '',
	'weight' => 100,
	'params' => array(
		array(
			"type" => "dropdown",
			"heading" => __('Period', Simple_Popular_Posts::$td),
			"param_name" => "period",
			"value" => array(
				__('Past day', Simple_Popular_Posts::$td) => 'day',
				__('Past week', Simple_Popular_Posts::$td) => 'week',
				__('Past month', Simple_Popular_Posts::$td) => 'month',
				__('Past year', Simple_Popular_Posts::$td) => 'year',
				__('Total', Simple_Popular_Posts::$td) => 'all',
			),
			"description" => __('Select from which period popular posts will be fetched', Simple_Popular_Posts::$td),
			"admin_label" => true
		),
		array(
			"type" => "textfield",
			"class" => "",
			"heading" => __('Title', Simple_Popular_Posts::$td),
			"param_name" => "title",
			"value" => __('Popular posts', Simple_Popular_Posts::$td),
			"description" => __('Set a title that will be displayed before the list', Simple_Popular_Posts::$td),
			"admin_label" => true
		),
		array(
			'type' => 'checkbox',
			"heading" => __('Network options', Simple_Popular_Posts::$td),
			"param_name" => "network",
			'value' => array(__('Fetch posts from multisite network', Simple_Popular_Posts::$td) => 'true'),
			"description" => __('Uses WPMUDev Post Indexer to fetch posts from the entire multisite network', Simple_Popular_Posts::$td)
		),
		array(
			"type" => "textfield",
			"class" => "",
			"heading" => __('Limit', Simple_Popular_Posts::$td),
			"param_name" => "limit",
			"value" => "5",
			"description" => __('Limit the amount of posts to show', Simple_Popular_Posts::$td)
		)
	)
));