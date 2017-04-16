<?php
/*
Plugin Name: Simple Popular Posts
Plugin URI: http://wordpress.org/plugins/simple-popular-posts
Description: Lets you display popular posts via shortcode or widget. Supports Multisites, Post Indexer and Visual Composer.
Version: 1.0
Author: khromov
Author URI: http://snippets.khromov.se
License: GPL2
*/

/**
 * Class Simple_Popular_Posts
 */
class Simple_Popular_Posts
{
	public static $td = 'spp';
	public static $post_types = array('post'); //,'page'
	public static $disable_self_stats = true; /* If true, users can't increase stats when visiting their own post */

	/* Used for storing scores when using "relative" display_value */
	//private static $scores = array();

	/**
	 * Constructor sets up all our hooks
	 */
	function __construct()
	{
		//TODO: add filters for modifying settings, like which CPT:s (on plugins_loaded, prio 11)
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		add_action('after_setup_theme', array(&$this, 'load_shortcodes'));
		add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action('wp_footer', array(&$this, 'footer_script'), 999);
		add_filter('query_vars', array(&$this, 'query_vars'));
		add_action('wp', array(&$this, 'count'));
		add_action('init', array(&$this, 'integrations'));
		add_action('admin_head', array(&$this, 'admin_css'));

		//Add dashboard widget
		add_action('wp_dashboard_setup', array($this, 'dashboard_widget_register'));
		add_action('add_meta_boxes', array($this, 'meta_box_register'));

		//TODO: Remove stats on uninstall
	}

	function dashboard_widget_register()
	{
		/* Dashboard all-time stats */
		add_meta_box('spp_stats_dashboard', __('Your stats', $this::$td), array(&$this, 'meta_box_dashboard'), 'dashboard', 'side', 'high');
	}

	function meta_box_register()
	{
		global $post;

		/* Single post stats, if post is published */
		if($post->post_status === 'publish')
		{
			foreach($this::$post_types as $post_type)
				add_meta_box('spp_stats_single', __('Post stats', $this::$td), array(&$this, 'meta_box_single'), $post_type, 'side', 'high');
		}
	}

	/**
	 * Dashboard meta box showing amount of views on all posts
	 */
	function meta_box_dashboard()
	{
		?>
			<div class="user-stats">
				<h1>
					<?php echo $this->user_total_views(get_current_user_id()); ?>
				</h1>
				<p>
					<?php _e('Page views on your posts', $this::$td); ?>
				</p>
			</div>
		<?php
	}

	/**
	 * Meta box showing the amount of views on the current post
	 */
	function meta_box_single()
	{
		global $post;

		?>
			<div class="user-stats-single">
				<h1>
					<?php echo (int)get_post_meta($post->ID, '_spp_count', true); ?>
				</h1>
				<p>
					<?php _e('Page views on this post', $this::$td); ?>
				</p>
			</div>
		<?php
	}

	/**
	 * Prepares the post types list for SQL queries
	 *
	 * @return string
	 */
	function get_post_types_for_mysql()
	{
		return '"' . implode('","', $this::$post_types) . '"';
	}

	function user_total_views($user_id)
	{
		global $wpdb;

		$post_types_list = $this->get_post_types_for_mysql();

		$count = ($wpdb->get_results($wpdb->prepare("SELECT SUM(meta_value) as count FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE {$wpdb->posts}.post_type IN({$post_types_list}) AND {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->posts}.post_author = %d AND {$wpdb->postmeta}.meta_key = '_spp_count'", $user_id)));

		if(isset($count[0]))
			return (int)$count[0]->count;
		else
			return 0;
	}

	/**
	 * Load textdomain
	 */
	function load_textdomain()
	{
		load_plugin_textdomain($this::$td, false, dirname(plugin_basename(__FILE__)) . '/languages/' );
	}

	/**
	 * Load shortcode
	 */
	function load_shortcodes()
	{
		add_shortcode('spp', array($this, 'shortcode'));
		add_shortcode('spp-current-post', array($this, 'shortcode_current_post'));
	}

	/**
	 * Handles shortcode output.
	 *
	 * Shortcode:
	 * [spp]
	 *
	 * Available parameters:
	 * period => day, week, month, year - How far back to fetch popular posts. default = week
	 * title => Optional title displayed in a h2. default is empty.
	 * network => Whether we should grab network posts via Post Indexer. default = false
	 * limit => Amount of posts to fetch. default = 5
	 *
	 * Example:
	 * [spp period="year" title="Last years greatest posts" network="true" limit="10"]
	 *
	 */
	function shortcode($atts, $content)
	{
		$merged_atts = shortcode_atts(
										array(
											'period' => 'all',
											'title' => '',
											'network' => 'false',
											'limit' => '5',
											'display_value' => 'relative', //number = actual number of views, relative = flames/stars system
											'relative_value_count' => '5' //How many flames/stars etc
										)
		, $atts);

		extract($merged_atts);

		$periods = array(
							'day' => '24 hours ago',
							'week' => '7 day ago',
							'month' => '1 month ago',
							'year' => '1 year ago',
							'all' => ''
		);
		$period_query = isset($periods[$period]) ? $periods[$period] : 'week';

		ob_start();

		$args = array(
			'post_status' => 'publish',
			'post_type' => $this::$post_types,
			'posts_per_page' => (int)$limit,
			'meta_key' => '_spp_count',
			'orderby' => 'meta_value_num',
			'order' => 'DESC'
		);

		//Set date query if we have not selected "all"
		if($period_query !== '')
			$args['date_query'] = array('column' => 'post_date', 'after' => $period_query);

		/* Relative scoring */
		if($display_value === 'relative')
		{
			$scores = array();
			/* Calculate relative scores. This is a little messy */
			if($network === 'true' && is_multisite())
			{
				if(class_exists('postindexeradmin'))
				{
					foreach(network_query_posts($args) as $post)
						$scores[$post->ID] = Simple_Popular_Posts::get_share_count($post->ID, $post->BLOG_ID);
				}
				else
				{
					$this->print_error_no_postindexer();
					return ob_get_clean();
				}
			}
			else
			{
				foreach(get_posts($args) as $post)
				{
					$scores[$post->ID] = Simple_Popular_Posts::get_share_count($post->ID);
				}
			}

			$merged_atts['scores'] = $scores;
			$merged_atts['relative_scores'] = $this->calculate_relative_scores($scores, (int)$relative_value_count, 1);
		}

		/* Network-wide search with Post Indexer support */
		if($network === 'true' && is_multisite())
		{
			if(function_exists('network_query_posts') && function_exists('network_reset_postdata'))
			{
				$posts = network_query_posts($args);

				if(sizeof($posts) > 0)
					$this->include_template('spp-header.php', $merged_atts);

				//date_query doesn't actually work with network_query_posts, bummer.
				foreach($posts as $post)
				{
					//Filter for letting users choose whether to use switch_to_blog() or not on multisite
					$should_switch = apply_filters('spp_switch_to_blog_on_multisite', true);

					if($should_switch)
						switch_to_blog($post->BLOG_ID);

					//Add $post to atts array
					$merged_atts['post'] = $post;

					//Load template
					$this->include_template('spp-item.php', $merged_atts);

					if($should_switch)
						restore_current_blog();
				}

				if(sizeof($posts) > 0)
					$this->include_template('spp-footer.php', $atts);

				network_reset_postdata();
			}
			else
				$this->print_error_no_postindexer();
		}
		else
		{
			$posts = get_posts($args);

			if(sizeof($posts) > 0)
				$this->include_template('spp-header.php', $merged_atts);

			/* Single site search (normal usage) */
			foreach($posts as $post)
			{
				$merged_atts['post'] = $post;
				$this->include_template('spp-item.php', $merged_atts);
			}

			if(sizeof($posts) > 0)
				$this->include_template('spp-footer.php', $atts);

			wp_reset_postdata();
		}

		//You can use shortcodes in your templates, how neat is that?
		return do_shortcode(ob_get_clean());
	}

	/**
	 * @return string
	 */
	function shortcode_current_post() {
		//if(!is_user_logged_in())
		//	return "";

		ob_start();
		?>
        <div class="views">
            <strong>
                <span class="number"><?=(int)get_post_meta(get_the_ID(), '_spp_count', true)?></span>
                <span class="text"> views</span>
            </strong>
        </div>
		<?php
		return do_shortcode(ob_get_clean());
	}

	/**
	 * Helper function for including templates
	 *
	 * @param $template
	 */
	function include_template($template, $args)
	{
		extract($args);

		if(locate_template($template) === '')
			include 'templates/' . $template;
		else
			include locate_template($template);
	}

	/**
	 * Enqueue our counting script
	 */
	function enqueue_scripts()
	{
		wp_enqueue_style('simple-popular-posts-grid', plugins_url('/css/grid.css', __FILE__));
		wp_enqueue_script('simple-popular-posts-count', plugins_url('/js/count.js', __FILE__ ));
	}

	/**
	 * Adds counting code to footer
	 */
	function footer_script()
	{
		if((is_single() || is_attachment() || is_page() || is_singular()) && in_array(get_post_type(get_the_ID()), $this::$post_types)) :
			?>
				<script type="text/javascript">
					SimplePopularPosts_AddCount(<?php echo get_the_ID(); ?>, '<?php echo get_site_url(); ?>');
				</script>
			<?php
		endif;
	}

	/**
	 * Adds our special query var
	 */
	function query_vars($query_vars)
	{
		$query_vars[] = 'spp_count';
		$query_vars[] = 'spp_post_id';
		$query_vars[] = 'spp_get_counts';
		return $query_vars;
	}

	/**
	 * Count function
	 */
	function count()
	{
		/**
		 * Endpoint for counting visits
		 */
		if(intval(get_query_var('spp_count')) === 1 && intval(get_query_var('spp_post_id')) !== 0)
		{
			global $wpdb;

			//JSON response
			header('Content-Type: application/json');
			$id = intval(get_query_var('spp_post_id'));

			$post_types_list = $this->get_post_types_for_mysql();
			$find_post_result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_author from {$wpdb->posts} WHERE ID = %d AND post_type IN({$post_types_list})" , $id));

			if(isset($find_post_result[0]) && ((int)$find_post_result[0] !== 0))
			{
				$current_count = get_post_meta($id, '_spp_count', true);

				//Bail early and don't record stats if user is the same as the author.
				if($this::$disable_self_stats && ((int)$find_post_result[0]->post_author === get_current_user_id()))
				{
					echo json_encode(array('status' => 'OK', 'visits' => (int)$current_count, 'message' => 'user visited own post. stats not incremented.'));
					exit;
				}

				if($current_count === '')
					$count = 1;
				else
					$count = intval($current_count)+1;

				//Update post meta
				$meta_id = update_post_meta($id, '_spp_count', $count);

				//Check if post indexer is available and update the meta there as well.
				if(class_exists('postindexeradmin'))
				{
				    global $postindexeradmin;
					$postindexeradmin->model->insert_or_update($postindexeradmin->model->network_postmeta, array(
						'blog_id' => get_current_blog_id(),
						'post_id' => $id,
						'meta_id' => intval($wpdb->get_var($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $id, '_spp_count'))),
						'meta_key' => '_spp_count',
						'meta_value' => $count
					));
				}

				echo json_encode(array('status' => 'OK', 'visits' => intval($current_count)+1));
			}
			else
				echo json_encode(array('status' => 'ERROR', 'message' => 'post with this id does not exist'));

			exit;
		}
	}

	/**
	 * Print error if we can't find Post Indexer when a network query was specified
	 */
	function print_error_no_postindexer()
	{
		?>
			<div class="error">
				<strong>
					<?php _e('Simple Popular Posts Error', $this::$td); ?>
				</strong>
				<br>
				<?php _e('Network query was specified, but Post Indexer is not installed. Please install Post Indexer or set network="false"', $this::$td); ?>
			</div>
		<?php
	}

	/**
	 * Integrations
	 */
	function integrations()
	{
		if(function_exists('vc_map'))
			include('includes/visual-composer-integration.php');

		if(function_exists('shortcode_ui_register_for_shortcode'))
		    include('includes/shortcake-integration.php');
	}

	/**
	 * Add some styles to admin head
	 */
	function admin_css()
	{
		?>
		<style type="text/css">
			.user-stats,
			.user-stats-single
			{
				text-align: center;
			}

			.user-stats h1
			{

				font-size: 65px;
				font-weight: bold;
			}

			.user-stats-single h1
			{
				font-size: 35px;
				font-weight: bold;
			}
		</style>
	<?php
	}

	/**
	 * Static helper function for getting share count
	 *
	 * @param $post_id
	 * @return int
	 */
	static function get_share_count($post_id, $blog_id = NULL)
	{
		if($blog_id !== NULL)
			switch_to_blog($blog_id);

		$share_count = (int)get_post_meta($post_id, '_spp_count', true);

		if($blog_id !== NULL)
			restore_current_blog();

		return $share_count;
	}


	/**
	 * @param $scores - Array - post_id => amount of views
	 * @param int $base_value - What the maximum relative value is
	 * @param int $min_value - What the minimum relative value should be (typically 1)
	 * @return array - All scores as post_id => score
	 */
	function calculate_relative_scores($scores, $base_value = 100, $min_value = 0)
	{
		if(empty($scores))
			return array();

		$max = max($scores);
		$relative_scores = array();

		foreach($scores as $key => $value)
		{
			$calculated_value = round(($value * $base_value) / $max, 0);
			if($calculated_value < $min_value)
				$calculated_value = $min_value;

			$relative_scores[$key] = $calculated_value;
		}

		return $relative_scores;
	}
}

$simple_popular_posts = new Simple_Popular_Posts();