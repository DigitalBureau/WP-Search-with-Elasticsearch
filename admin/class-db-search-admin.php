<?php
/**
 * Admin-specific functionality.
 *
 * @link       http://www.digitalbureau.com
 * @since      1.0.0
 *
 * @package    db_search
 * @subpackage db_search/admin
 * Version:           1.0.0
 * Author:            Digital Bureau
 * Author URI:        http://www.digitalbureau.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * All Digital Bureau Elasticsearch Hooks go in this file.
 *
 * @package    db_search
 * @subpackage db_search/admin
 * @author     Digital Bureau
 */
class db_Search_Admin extends db_Search {

/**
 * The ID of this plugin.
 *
 * @since    1.0.0
 * @access   protected
 * @var      string    $db_search
 */
	protected $db_search;

/**
 * Plugin Version
 *
 * @since    1.0.0
 * @access   protected
 * @var      string    $version
 */
	protected $version;

/**
 * Plugin Constructor
 *
 * @since    1.0.0
 * @param      string    $db_search       The name of this plugin.
 * @param      string    $version    The version of this plugin.
 */
	public function __construct($db_search, $version) {

		$this->db_search = $db_search;
		$this->version = $version;

	}

/**
 * Registers the stylesheets for the admin area.
 *
 * @since    1.0.0
 */
	public function enqueue_styles() {

		wp_enqueue_style($this->db_search, plugin_dir_url(__FILE__) . 'css/db-search-admin.css', array(), $this->version, 'all');

	}

/**
 * Registers the JavaScript for the admin area.
 *
 * @since    1.0.0
 */
	public function enqueue_scripts() {
		$translation_array = array(
			'path' => plugin_dir_url(__FILE__),
		);
		wp_enqueue_script( 'suggest' );
		wp_enqueue_script($this->db_search, plugin_dir_url(__FILE__) . 'js/db-search-admin.js', array('jquery'), $this->version, false);

		wp_localize_script($this->db_search, 'db_search_plugin_path', $translation_array);
	}


	public function options_update() {
		register_setting("_" . $this->db_search, "_" . $this->db_search, array($this, 'validate'));
	}

/**
 * Registers the administration menu into the WordPress Dashboard menu.
 *
 * @since    1.0.0
 */

	public function add_plugin_admin_menu() {

/*
 * Adds a settings page for this plugin to the Settings menu.
 *
 *
 */
		add_options_page('WP Search with Elasticsearch Settings', 'WP Search with Elasticsearch', 'manage_options', 'db-search', array($this, 'display_plugin_setup_page')
		);

	}

/**
 * Adds settings action link
 *
 * @since    1.0.0
 */

	public function add_action_links($links) {

		$settings_link = array(
			'<a href="' . admin_url('options-general.php?page=' . $this->db_search) . '">' . __('Settings', $this->db_search) . '</a>',
		);

		return array_merge($settings_link, $links);

	}

/**
 * Renders the settings page
 *
 * @since    1.0.0
 */

	public function display_plugin_setup_page() {
		include_once 'partials/db-search-admin-display.php';
	}

/**
 * Publish hook, adds article to ES Index
 *
 * @since    1.0.0
 */
	public function es_on_publish() {
		global $post;
		$options = get_option('_db-search');
		$es_index = $options['esindex'];
		if (empty($es_index)) {
			$es_index = "default";
		}
		$es = new db_Search();
		$client = $es->build_es_client();
		$es_id = $post->ID;
		$type = sanitize_text_field($_POST['post_type']);

		//if gutenberg
		if(empty($es_id)){
			$req_url = $_SERVER['SCRIPT_URL'];
			$req_url = explode('/',$req_url);
			$es_id = end($req_url);
			$type = prev($req_url);
			$type = 'posts' ? $type = 'post': $type ='page';
		}

		//run only if user selects publish options
		if ($options['publish']) {

			//get post data
			$thepost = get_post($es_id);
			$es_title = trim(sanitize_text_field($_POST['post_title']));
			$time = get_post_time('c', false, $es_id);
			$es_body = trim(sanitize_text_field($_POST['post_content']));
			$es_post_name = $thepost->post_name;
			$es_permalink = get_permalink($es_id);

			//pull data via query for Gutenberg
			empty($es_title) ? $es_title = $thepost->post_title : $es_title;
			empty($es_body) ? $es_body = $thepost->post_content : $es_body;

			//get taxonomies
			$db_taxonomy = '';
			$cat = $_POST['post_category'];
			$tag = $_POST['tax_input']['post_tag'];
			$author = sanitize_text_field($_POST['post_author']);

			// gutenberg tag logic
			if (empty($tag)) {
				$tag = [];
				$tag_taxonomies = wp_get_post_tags($es_id);

				foreach ($tag_taxonomies as $tax => $v) {
					array_push($tag, $tag_taxonomies[$tax]->term_id);
				}
			}

			if (empty($cat)) {
				$cat = [];
				$cat_taxonomies = wp_get_post_categories($es_id);
				foreach ($cat_taxonomies as $tax) {
					array_push($cat, $tax);
				}
			}

			if ($type === 'post') {
				$tax_check = array_merge($cat, $tag);

				foreach ($cat as $c) {
					if ($c !== '0') {
						$db_taxonomy = $db_taxonomy . sanitize_text_field($c) . ', ';
					}
				}
				foreach ($tag as $t) {
					if ($t !== '0') {
						//check for named tags
						if (is_numeric($t)) {
							$db_taxonomy = $db_taxonomy . sanitize_text_field($t) . ', ';
						} else {
							$tagname = get_term_by('name', sanitize_text_field($t), 'post_tag');
							if ($tagname) {
								$db_taxonomy = $db_taxonomy . $tagname->term_id . ', ';
							}
						}
					}
				}
			} else {
				$tax_check = true;
			}
			//search for existing articles to overwrite
			$params = [
				'index' => $es_index,
				'type' => 'article',
				'body' => [
					'query' => [
						'match' => [
							'ID' => $es_id,
						],
					],
				],
			];

			$results_id = '';

			if (!empty($es_id)) {
				try {
					$results = $client->search($params);
					$results_id = $results['hits']['hits'][0]['_id'];
				} catch (Exception $e) {
					error_log('_digitalbureau_elasticsearch: Cannot use search, client is unavailable');
				}
			}

			if (!empty($results_id)) {
				$params = [
					"index" => $es_index,
					"type" => "article",
					"id" => $results_id,
				];
				$response = $client->delete($params);
				error_log($results_id . ' deleted');
			}

			//make sure article id's aren't in blacklist, or are in whitelist
			if ($type === 'post') {
				$blacklist = $options['blacklist'];
				$blacklist = explode(',', $blacklist);
				$blacklist = $this->get_tag_ids($blacklist);
				$whitelist = $options['whitelist'];
				$whitelist = explode(',', $whitelist);
				$whitelist = $this->get_tag_ids($whitelist);

				foreach ($blacklist as $bl) {
					if (in_array($bl, $tax_check)) {
						error_log('_digitalbureau_elasticsearch: Article ID ' . $es_id . ' not indexed, tag is on blacklist');
						$tax_check = false;
					}
				}

				if (!empty($whitelist) && $whitelist[0] !== 0) {
					$is_whitelist = false;
					foreach ($whitelist as $wl) {
						if (in_array($wl, $tax_check)) {
							$is_whitelist = true;
						}
					}

					if ($is_whitelist) {
						$tax_check = true;
					} else {
						$tax_check = false;
						error_log('_digitalbureau_elasticsearch: Article ID ' . $es_id . ' not indexed, tag is not on whitelist');
					}

				}

			}
			$params = [
				'index' => $es_index,
				'type' => 'article',
				'body' => [
					'ID' => $es_id,
					'taxonomy' => $db_taxonomy,
					'post_title' => $es_title,
					'post_content' => $es_body,
					'post_date' => $time,
					'post_author' => $author,
				],
			];

			if (($type === 'post' && $options['posts']) || ($type === 'page' && $options['pages'])) {
				if ($tax_check) {
					try {
						$response = $client->index($params);
						error_log(' _digitalbureau_elasticsearch: New Article id: ' . $es_id . ' added to index.');
					} catch (Exception $e) {
						error_log(' _digitalbureau_elasticsearch: Article has not been indexed. No ES instance available, check connection settings and verify Elasticsearch is running' . $e->getMessage());
					}
				}
			} else {
				error_log(' _digitalbureau_elasticsearch: Article ID ' . $es_id . ' not indexed, posts or pages not allowed by user defined config.');
			}
		}
	}

	public function es_on_delete() {
		$es_index = get_option('_db-search');
		$es_index = $es_index['esindex'];
		if (empty($es_index)) {
			$es_index = "default";
		}
		global $post;
		$old_status = sanitize_text_field($_POST['original_post_status']);
		$new_status = sanitize_text_field($_POST['post_status']);
		$db_status = trim(sanitize_text_field($_POST['db_status']));
		$omit = trim(sanitize_text_field($_POST['db_search']));

		empty($omit) ? $omit = get_post_meta($post_id, 'db_status', true) : $omit;

		if (($omit === "on") ||
			(did_action('wp_trash_post') === 1)
		) {
			$es = new db_Search();
			$client = $es->build_es_client();
			$es_id = $post->ID;
			$params = [
				'index' => $es_index,
				'type' => 'article',
				'body' => [
					'query' => [
						'match' => [
							'ID' => $es_id,
						],
					],
				],
			];
			$results_id = '';

			if (!empty($es_id)) {
				try {
					$results = $client->search($params);
					$results_id = $results['hits']['hits'][0]['_id'];
				} catch (Exception $e) {
					error_log('_digitalbureau_elasticsearch: Cannot use search, client is unavailable. Article has not been deleted from index.');
				}
			}
			if (!empty($results_id)) {
				$params = [
					"index" => $es_index,
					"type" => "article",
					"id" => $results_id,
				];

				$response = $client->delete($params);
				error_log('_digitalbureau_elasticsearch: Article id ' . $es_id . ' deleted from search.');
			}
		}

	}

	public function es_create_index() {
		$options = get_option('_db-search');
		$es_index = $options['esindex'];
		if (empty($es_index)) {
			$es_index = "default";
		}

		//create index with article and fields
		$params = [
			'index' => $es_index,
			'body' => [
				'settings' => [
					'number_of_shards' => 2,
					'number_of_replicas' => 0,
					'analysis' => [
						'analyzer' => [
							'db_analyzer' => [
								'type' => 'snowball',
								'language' => 'English',
							],
						],
					],
				],
				'mappings' => [
					'article' => [
						'properties' => [
							'ID' => [
								'type' => 'long',
							],
							'post_author' => [
								'type' => 'text',
								'analyzer' => 'db_analyzer',
								'fielddata' => true,
							],
							'post_content' => [
								'type' => 'text',
								'analyzer' => 'db_analyzer',
								'fielddata' => true,
							],
							'post_title' => [
								'type' => 'text',
								'analyzer' => 'db_analyzer',
								'fielddata' => true,
							],
							'taxonomy' => [
								'type' => 'text',
								'fielddata' => true,
							],
							'post_date' => [
								'type' => 'date',
							],

						],
					],
				],
			],
		];

		//add mapping params for field data
		$map_params = [
			'index' => $es_index,
			'type' => 'article',
			'body' => [
				'properties' => [
					'taxonomy' => [
						'type' => 'text',
						'fielddata' => true,
					],
				],
			],
		];
		//increase max_result_window
		$window_params = [
			'index' => $es_index,
			'body' => [
				'settings' => [
					'max_result_window' => '50000',
				],
			],
		];

		$es = new db_Search();
		$client = $es->build_es_client();

		try {
			$response = $client->indices()->create($params);
		} catch (Exception $e) {
			echo ('<b>There was an error with your ES configuration. Please verify you are using the correct url and port, and have allowed access to your ES instance. <br>Details:</b><br><font style="font-size:.8rem; line-height:0">' . $e->getMessage() . '</font>');
		}

		if (!empty($response['acknowledged'])) {
			$map_response = $client->indices()->putMapping($map_params);
			$window_response = $client->indices()->putSettings($window_params);
			$options['activatedIndex'] = 'on';
			update_option('_db-search', $options);
			echo ('<b>Elasticsearch index \'' . $es_index . '\' created at url: </b><br> ' . $options['esurl'] . ':' . $options['esurlport']);
		}

		die();
	}

	public function es_post_query_args($posts, $pages) {
		//count totals
		$post_q = wp_count_posts()->publish;
		$page_q = wp_count_posts('page')->publish;

		//check for options
		$posts ? $posts = 'post' : $posts = '';
		$pages ? $pages = 'page' : $pages = '';
		$posts ? $post_count = $post_q : $post_count = 0;
		$pages ? $page_count = $page_q : $page_count = 0;

		$posts_and_pages = array($posts, $pages);
		$posts_and_pages = array_filter($posts_and_pages);

		if (empty($posts_and_pages[1])) {
			$posts_and_pages = $posts_and_pages[0];
		}

		$count = $post_count + $page_count;
		$total_count = $post_q + $page_q;

		return array($posts_and_pages, $count, $total_count);
	}

	public function populate_posts($paged, $post_count, $blacklist, $whitelist, $post, $es_index, $client, $added, $posts_and_pages) {
		error_log('_digitalbureau_elasticsearch: Scanned ' . $paged * 100 . ' of ' . $post_count . ' posts to add to ES index: ' . $es_index);

		$args = array(
			'post_type' => $posts_and_pages,
			'post_status' => 'publish',
			'posts_per_page' => '100',
			'paged' => $paged,
		);

		//include/exclude
		!empty($blacklist[0]) ? $args['tag__not_in'] = $blacklist : $args['tag__not_in'] = '';
		!empty($whitelist[0]) ? $args['tag__in'] = $whitelist : $args['tag__in'] = '';

		$query = new WP_Query($args);

		$params = [
			'index' => $es_index,
			'type' => 'article',
			'body' => [],
		];
		$i = -1;
		foreach ($query->posts as $post) {
			$time = date('c', strtotime($post->post_modified));
			$post_tags = wp_get_post_tags($post->ID, array('fields' => 'ids'));
			$post_categories = wp_get_post_categories($post->ID);
			$taxonomy = array_merge($post_tags, $post_categories);

			if (!empty($post->ID)) {
				$added++;

				$taxonomy = implode(', ', $taxonomy);

				$params['body'][] = [
					'index' => [
						'_index' => $es_index,
						'_type' => 'article',
					],
				];

				$i += 2;
				$params['body'][$i] = [
					'ID' => $post->ID,
					'taxonomy' => $taxonomy,
					'post_date' => $time,
					'post_title' => $post->post_title,
					'post_content' => $post->post_content,
					'post_author' => $post->post_author,
				];
			}
		}

		$temp = plugin_dir_path(__FILE__) . 'temp.json';
		$temp_data = file_get_contents($temp);
		$temp_array = json_decode($temp_data, true);
		empty($temp_array) ? $temp_array = [] : $temp_array;

		if (!empty($temp_array['body'])) {
			foreach ($params['body'] as $item) {
				array_push($temp_array['body'], $item);
			}

			$data = $temp_array;
		} else {
			$data = $params;
		}

		$opened = fopen($temp, 'w');
		$t = fwrite($opened, json_encode($data));

		if (($paged > 9) && ($paged % 10 === 0)) {
			try {
				$params = file_get_contents($temp);
				$params = json_decode($params);
				$response = $client->bulk($params);
				$opened = fopen($temp, 'w');
				$t = fwrite($opened, '');
			} catch (Exception $e) {
				return $added;
			}
		};
		//end for
		return $added;
	}

	//cleanup and explode lists
	public function get_tag_ids($tag_array) {
		$new_tags = [];
		foreach ($tag_array as $tag_name) {
			$tag = get_term_by('name', $tag_name, 'post_tag');

			if ($tag) {
				array_push($new_tags, $tag->term_id);
			}
		}
		return $new_tags;
	}

	public function es_index_posts() {
		//force long execution and large memory
		set_time_limit(3600);
		$options = get_option('_db-search');
		$memory_limit = $options['memory_allocation'] . 'M';
		ini_set("memory_limit", $memory_limit);
		$es_index = $options['esindex'];
		$es = new db_Search();
		$client = $es->build_es_client();
		$temp = plugin_dir_path(__FILE__) . 'temp.json';
		$opened = fopen($temp, 'w');
		$t = fwrite($opened, '');

		//initialize counts
		$post_count = 0;
		$added = 0;
		$i = 0;

		$blacklist = trim($options['blacklist']);
		$blacklist = explode(',', $blacklist);
		$whitelist = trim($options['whitelist']);
		$whitelist = explode(',', $whitelist);

		$blacklist = $this->get_tag_ids($blacklist);
		$whitelist = $this->get_tag_ids($whitelist);

		//check for including posts/pages
		list($posts_and_pages, $post_count, $total_count) = $this->es_post_query_args($options['posts'], $options['pages']);

		//page through posts
		for ($paged = 1; $paged <= (ceil($post_count / 100)); $paged++) {
			$added = $this->populate_posts($paged, $post_count, $blacklist, $whitelist, $post, $es_index, $client, $added, $posts_and_pages);
		}

		$vals = file_get_contents($temp);
		$params = json_decode($vals, true);
		if (!empty($params['body'])) {
			$response = $client->bulk($params);
		}
		$options['populatedIndex'] = 'on';

		update_option('_db-search', $options);
		$msg = $added . " posts added to index '" . $es_index . "' of " . $total_count . " available posts and pages.";
		echo ($msg);
		error_log($msg);
		$opened = fopen($temp, 'w');

		$t = fwrite($opened, '');

		die();
	}

	public function es_delete_index() {

		$options = get_option('_db-search');
		unset($options['activatedIndex']);
		unset($options['populatedIndex']);
		update_option('_db-search', $options);

		$es_index = $options['esindex'];
		$es = new db_Search();
		$client = $es->build_es_client();

		$params = ['index' => $es_index];
		try {
			$response = $client->indices()->delete($params);
			echo "Your index '" . $es_index . "' has been deleted.";
		} catch (Exception $e) {
			echo ('<b>There was an error with your ES configuration. Your index has not been deleted. Please verify you are using the correct url and port, and have allowed access to your ES instance.</b> <br>Details:<br><font style="font-size:.8rem; line-height:0">' . $e->getMessage() . '</font>');
		}

		die();

	}

	public function es_check_status() {
		$options = get_option('_db-search');
		$es_index = $options['esindex'];
		$es = new db_Search();
		$client = $es->build_es_client();
		$params = ['index' => $es_index, 'type' => 'article'];
		list($posts_and_pages, $post_count, $total_count) = $this->es_post_query_args($options['posts'], $options['pages']);
		try {
			$response = $client->count($params);
			$added = $response['count'];
			$msg = $added . " posts added to index '" . $es_index . "' of " . $total_count . " available posts and pages.";
			echo $msg;
		} catch (Exception $e) {
			echo ('<b>There was an error with your ES configuration. Please verify you are using the correct url and port, and have allowed access to your ES instance. <br>Details:</b><br><font style="font-size:.8rem; line-height:0">' . $e->getMessage() . '</font>');
		}
		die();
	}

//end class
}
