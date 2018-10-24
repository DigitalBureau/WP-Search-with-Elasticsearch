<?php
/**
 *
 * @link       http://www.digitalbureau.com
 * @since             1.0.0
 * @package           db_search
 *
 * @wordpress-plugin
 * Plugin Name:       WP Search with Elasticsearch
 * Plugin URI:        n/a
 * Description:       A custom tailored, enterprise search solution by Digital Bureau
 * Version:           1.0.0
 * Author:            Digital Bureau
 * Author URI:        http://www.digitalbureau.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       db-search
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */

function activate_db_search() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-db-search-activator.php';
	db_Search_Activator::activate();

	//create search file, rename old if exists
	$srcfile = plugin_dir_path(__FILE__) . 'page-search.php';
	$oldfile = get_stylesheet_directory() . '/search.php';
	if (file_exists($oldfile)) {
		rename($oldfile, get_stylesheet_directory() . '/search-old.php');
	}
	copy($srcfile, $oldfile);
	$new_options = array(
		'posts' => 'on',
		'pages' => 'on',
		'post_title' => 'on',
		'post_date' => 'on',
		'post_content' => 'on',
		'post_author' => 'on',
		'operators' => 'on',
		'quotes' => 'on',
		'publish' => 'on',
		'memory_allocation' => '1024',
	);
	add_option('_db-search', $new_options);
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */

function deactivate_db_search() {
	$options = get_option('_db-search');
	require_once plugin_dir_path(__FILE__) . 'includes/class-db-search-deactivator.php';

	//delete overwritten search file, rename search-old back to search
	$searchpage = get_stylesheet_directory() . '/search.php';
	$searchold = get_stylesheet_directory() . '/search-old.php';

	if (file_exists($searchpage) && file_exists($searchold)) {
		unlink($searchpage);
		rename($searchold, $searchpage);
	} else if (file_exists($searchpage)) {
		unlink($searchpage);
	}

	//delete index
	$es_index = $options['esindex'];
	if ($es_index) {
		$es = new db_Search();
		$client = $es->build_es_client();
		$params = ['index' => $es_index];

		if ($client) {
			try {
				$response = $client->indices()->delete($params);
			} catch (Exception $e) {
				error_log('_digitalbureau_elasticsearch: There was an error with your ES configuration. Your index has not been deleted. Please verify you are using the correct url and port, and have allowed access to your ES instance. Details:' . $e->getMessage());
			}
		}
	}

	delete_option('_db-search');
	db_Search_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_db_search');
register_deactivation_hook(__FILE__, 'deactivate_db_search');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

require plugin_dir_path(__FILE__) . 'includes/class-db-search.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.2
 */

function run_db_search() {
	$plugin = new db_Search();
	$plugin->run();
}

run_db_search();

/**
 * Main ES Class, contains all ES call functions for search and WL
 *
 * @package digital_bureau_elasticsearch
 */

class ES_Search_Class {

/**
 * The main ES search function for desktop
 *
 * @package digital_bureau_elasticsearch
 */

	public function es_search() {
		//initiate ES
		$es = new db_Search();
		$client = $es->build_es_client();

		//define index in ES admin settings, otherwise use default
		$options = get_option('_db-search');
		$es_index = $options['esindex'];
		$page_size = $options['page_size'];

		if (empty($es_index)) {
			$es_index = "default";
		}

		//initialize and sanitize post values
		$template = sanitize_text_field($_POST['template']);
		$value = sanitize_text_field($_POST['value']);
		$tax_id = sanitize_text_field($_POST['tag']);
		$author_id = sanitize_text_field($_POST['author']);
		$sort_by = sanitize_text_field($_POST['sortby']);
		$page_index = sanitize_text_field($_POST['pageindex']);
		$within = sanitize_text_field($_POST['within']);
		$is_custom = sanitize_text_field($_POST['custom']);
		$range = sanitize_text_field($_POST['daterange']);
		//validate post data
		foreach ($_POST as $data_item) {
			if (!is_string($data_item)) {
				$data = [
					'body' => null,
				];
				echo json_encode($data);
				die();
			}
		}

		//is year flag
		$isYear = false;

		//translate dates from url
		list($range, $from, $to, $isYear) = $this->translate_dates($range, $from, $to, $isYear);

		//set boosts
		list($title, $content, $db_article_title_boost, $summary, $min_score, $author, $within, $$within) = $this->set_boosts($title, $content, $db_article_title_boost, $summary, $min_score, $author, $within);

		//uppercase operators in non-quoted strings
		if ($options['operators']) {
			$value = $this->operators($value);
		}

		//add double quotes to allow for phrase queries/special chars
		$value = $this->handle_string($value, $options);

		//map sort by from front end
		$sort_by = $this->map_sort($sort_by);

		//get tag ids by name
		$taxonomy = $this->get_tag_ids($template, $tax_id);

		//get author ids
		$author_id = $this->get_authors($author_id);

		//add parameters to search template
		$params = [
			"index" => $es_index,
			"type" => "article",
			"file" => $template,
			"page_index" => $page_index,
			"page_size" => $page_size,
			"post_status" => "ready",
			"subscriber_status" => "subscriber",
			"search_term" => $value,
			"sort_by" => $sort_by,
			"sort_order" => "desc",
			"from" => $from,
			"to" => $to,
			"post_title_boost" => $title,
			"post_content_boost" => $content,
			"db_article_title_boost" => $db_article_title_boost,
			"db_blurb_boost" => $summary,
			"db_author_boost" => $author,
			"min_score" => $min_score,
			"taxonomy" => $taxonomy,
			"author" => $author_id,
		];

		$body = $this->load_into_template($params);

		$searchparams = [
			'index' => $es_index,
			'type' => 'article',
			'body' => $body,
		];

		//send params to ES
		$response = $client->search($searchparams);

		$results = $response['hits']['hits'];

		//declare containers for response
		$body;
		$tags;
		$regions;
		//get results count
		$results_count = $response['hits']['total'];

		//populate search results body
		$body = $this->populate_result_body_desktop($results);

		//get list of tags
		$tag_list = $response['aggregations']['taxonomy']['buckets'];

		//populate tags list
		$tags = $this->populate_tags_list($tag_list, $taxonomy);

		$authors = $this->populate_authors_list($response, $author_id);

		//populate tag buttons
		$tagbuttons = $this->populate_tag_buttons($_POST);
		$tagbuttons_date = '';
		$tagbuttons_agg = '';

		//add special date filter to buttons list
		list($tagbuttons_agg, $tagbuttons_date) = $this->populate_tag_buttons_special_date($from, $to, $tagbuttons_agg, $tagbuttons_date, $is_custom);

		//fill out tags
		$tagbuttons = $tagbuttons . $tagbuttons_agg . $tagbuttons_date;

		//populate date list
		$dates = $this->populate_date_list($response, $is_custom, $isYear, $from, $results_count);

		//get pages
		$pages = $this->populate_pages_desktop($response, $page_index);

		// handle empty tags
		if (empty($tags)) {
			$tags = '';
		}

		//populate data array
		$data = [
			'body' => $body,
			'tags' => $tags,
			'authors' => $authors,
			'results-count' => $results_count,
			'pages' => $pages,
			'tag-buttons-list' => $tagbuttons,
			'dates' => $dates,
			'pageIndex' => $page_index,
		];
		//strip any non-approved html
		$data = $this->validate_output($data);

		echo json_encode($data);
		die();
	}

/**
 * ES function to load arguments into search template
 *
 * @package digital_bureau_elasticsearch
 */

	private function load_into_template($params) {

		$body = [
			"from" => $params['page_index'],
			"size" => $params['page_size'],
			"aggs" => [
				"taxonomy" => [
					"terms" => [
						"field" => "taxonomy",
						"size" => 100,
					],
				],
				"post_author" => [
					"terms" => [
						"field" => "post_author",
						"size" => 100,
					],
				],
				"years" => [
					"date_histogram" => [
						"field" => "post_date",
						"interval" => "year",
					],
				],
				"range90" => [
					"date_range" => [
						"field" => "post_date",
						"ranges" => [
							[
								"to" => "now/d",
							], [
								"from" => "now-90d/d",
							],
						],
					],
				],
				"range30" => [
					"date_range" => [
						"field" => "post_date",
						"ranges" => [
							[
								"to" => "now/d",
							], [
								"from" => "now-30d/d",
							],
						],
					],
				],
			],
			"query" => [
				"bool" => [
					"must" => [
						[
							"range" => [
								"post_date" => [
									"gte" => $params['from'],
									"lte" => $params['to'],
								],
							],
						],
					],
					"should" => [
						["query_string" => [
							"fields" => ["post_title"],
							"query" => $params['search_term'],
							"boost" => $params['post_title_boost'],
							"default_operator" => "AND",
						],
						],
						["query_string" => [
							"fields" => ["post_content"],
							"query" => $params['search_term'],
							"boost" => $params['post_content_boost'],
							"default_operator" => "AND",
						],
						],
					],
					"minimum_should_match" => 1,
				],
			],
			"min_score" => $params['min_score'],
			"sort" => [
				$params['sort_by'] => [
					"order" => $params['sort_order'],
				],
				"_score" => [
					"order" => "desc",
				],
			],
		];

		//add tax if necessary
		if (!empty($params['taxonomy'])) {
			$i = count($body['query']['bool']['must']);
			$body['query']['bool']['must'][$i] = [
				"match" => [
					"taxonomy" => [
						"query" => $params['taxonomy'],
						"operator" => "and",
					],
				],
			];
		}
		//add author if necessary
		if (!empty($params['author'])) {
			$i = count($body['query']['bool']['must']);
			$body['query']['bool']['must'][$i] = [
				"match" => [
					"post_author" => [
						"query" => $params['author'],
						"operator" => "and",
					],
				],
			];
		}

		return $body;
	}

/**
 * ES function to handle uppercase operators in non-quoted strings
 *
 * @package digital_bureau_elasticsearch
 */

	private function operators($value) {
		if (strpos($value, '"') === false) {
			$value = str_replace(" and ", " AND ", $value);
			$value = str_replace(" or ", " OR ", $value);
			$value = str_replace(" not ", " NOT ", $value);
		}
		return $value;
	}

/**
 * ES Function to translate dates
 *
 * @package digital_bureau_elasticsearch
 */

	private function translate_dates($range, $from, $to, $isYear) {
		if (!empty($range)) {
			switch ($range) {
			case 'past30days':
				$from = 'now-30d/d';
				$to = 'now/d+1d';
				break;
			case 'past90days':
				$from = 'now-90d/d';
				$to = 'now/d+1d';
				break;
			case (strpos($range, ':') == false):
				$from = $range . '-01-01';
				$to = ($range + 1) . '-01-01';
				$isYear = true;
				break;
			default:
				$date_range = explode(':', $range);
				$from = $date_range[0];
				$to = $date_range[1];
				$from = explode('-', $from);
				$from = $from[2] . '-' . $from[0] . '-' . $from[1];
				$to = explode('-', $to);
				$to = $to[2] . '-' . $to[0] . '-' . $to[1];
			}

		} else {
			$from = '2000-01-01';
			$to = 'now/d+1d';
		}
		return array($range, $from, $to, $isYear);
	}

/**
 * ES Function to set field-by-field boosts
 *
 * @package digital_bureau_elasticsearch
 */

	private function set_boosts($title, $content, $db_article_title_boost, $summary, $min_score, $author, $within) {
		$title = 10;
		$content = 5;
		$db_article_title_boost = 3;
		$summary = 2;
		$min_score = 2;
		$author = 2;
		//only boost one field, 0 out others
		if ($within !== 'all') {
			$title = 0;
			$content = 0;
			$db_article_title_boost = 0;
			$summary = 0;
			$author = 0;
			$min_score = 100;
			$$within = 100;
		}
		return array($title, $content, $db_article_title_boost, $summary, $min_score, $author, $within, $$within);
	}

/**
 * ES Function to handle string queries
 *
 * @package digital_bureau_elasticsearch
 */

	private function handle_string($value, $options) {
		if ((strpos($value, '"') !== false) && ($options['quotes'])) {
			$value = '"' . $value . '"';
		} else if (strpos($value, '&') !== false) {
			//replace & with space and treat as a single entity
			$value = preg_replace('/[^A-Za-z0-9\-]/', ' ', $value);
		} else if (preg_match('/[\'^%&*()}{@#~?><>,\|=_+¬]/', $value)) {
			//strip all other chars
			$value = preg_replace("/[^ \w]+/", "", $value);
		}
		return $value;
	}

/**
 * ES Function to rename sort from front end
 *
 * @package digital_bureau_elasticsearch
 */

	private function map_sort($sort_by) {
		if ($sort_by == 'recency') {
			$sort_by = 'post_date';
		} else if ($sort_by == 'relevance') {
			$sort_by = '_score';
		}
		return $sort_by;
	}

/**
 * ES function to lookup taxonomy id's by name
 *
 * @package digital_bureau_elasticsearch
 */

	private function get_tag_ids($template, $tax_id) {
		if ($template == 'taxonomy') {
			$taxonomy = $regiontax = '';
			$taglist = explode(',', $tax_id);

			foreach ($taglist as $tagname) {
				$tagname = trim($tagname);
				$tagname = esc_attr($tagname);
				$tag = get_term_by('name', $tagname, 'post_tag');

				//cat
				if (empty($tag)) {
					$tag = get_term_by('name', $tagname, 'category');
				}

				$taxonomy = $taxonomy . $tag->term_id . ', ';
			}

			$taxonomy = str_replace(' , ', '', $taxonomy);

		}
		return $taxonomy;
	}

/**
 * ES function to lookup taxonomy id's by name
 *
 * @package digital_bureau_elasticsearch
 */

	private function get_authors($authors) {
		$authors = rawurldecode($authors);
		return $authors;
	}

/**
 * Returned either the display date or the article date
 *
 * @package digital_bureau_elasticsearch
 */
	private function db_get_display_date($article_date, $display_date = null) {
		if (empty($display_date)) {
			$display_date = $article_date;
		} else {
			$display_date = date('j F Y', strtotime($display_date));
		}
		return $display_date;
	}

/**
 * ES function to populate result body for desktop
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_result_body_desktop($results) {
		foreach ($results as $result) {

			$id = $result['_source']['ID'];
			$post_title = get_the_title($id);

			$display_date = $this->db_get_display_date(get_the_date('j F Y', $id), get_post_meta($id, 'db_display_date', true));
			$link = get_post_permalink($id);

			$image_array = wp_get_attachment_image_src(get_post_thumbnail_id($id), 'thumbnail');
			$img;
			if (!empty($image_array[0])) {
				$img = '<img src="' . $image_array[0] . '" class="esSearch--searchResultsList_image">';
			}

			if (!empty($post_title)) {
				$body = $body . '<li class="esSearch--searchResultsList_item">' . $img . '<a href="' . $link . '" class="esSearch--searchResultsList_link">' . $post_title . '</a><p class="esSearch--searchResultsList_searchDate">' . $display_date . '</p></li>';

				$body = str_replace('\\', '', $body);
			}
		}
		return $body;
	}

/**
 * ES Function to populate tags list
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_tags_list($tag_list, $taxonomy) {

		foreach ($tag_list as $tag) {
			$tag_id = intval($tag['key']);
			$the_tag = get_term($tag_id);

			//check for tags and do not add already used tags
			if ((!empty($the_tag)) && (strstr($taxonomy, $tag['key']) < 1)) {
				$tags = $tags . '<li><a href="#" class="esSearch--searchFilters_filter" id="' . $the_tag->name . '">' . $the_tag->name . '</a><span class="esSearch--searchFilters_filter_span">(' . $tag['doc_count'] . ')</span></li>';
			}
		}
		if (empty($tags)) {
			$tags = '<p class="esSearch--emptyResults">No more to filter</p>';
		}
		return $tags;
	}

/**
 * ES Function to populate authors list
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_authors_list($response, $author_id) {
		$auth_arr = [];
		$authors = '';
		$results = $response['aggregations']['post_author']['buckets'];
		foreach ($results as $result) {
			if (strstr($author_id, $result['key']) < 1) {
				$a_id = $result['key'];
				$name = get_the_author_meta('user_nicename', $a_id);

				$auth_arr[$a_id] = $name;
				$authors = $authors . '<li><a href="#" class="esSearch--searchFilters_authorFilter esSearch--searchFilters_filter" id="' . $a_id . '">' . $name . '</a><span class="esSearch--searchFilters_filter_span">(' . $result['doc_count'] . ')</span></li>';
			}
		}
		if (empty($authors)) {
			$authors = '<p class="esSearch--emptyResults">No more to filter</p>';
		}
		return $authors;
	}

/**
 * ES Function to populate tag buttons
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_tag_buttons($post) {

		$button_list = sanitize_text_field($post['tag']);
		$buttons = explode(",", $button_list);
		//remove whitespace and null elements
		$author_list = rawurldecode(sanitize_text_field($post['author']));
		$author_buttons = explode(",", $author_list);
		$author_buttons = array_map('trim', $author_buttons);
		$author_buttons = array_filter($author_buttons);

		//populate tag buttons
		foreach ($buttons as $button) {
			$button = trim($button);
			if (strlen($button) > 1) {
				$tag = get_term_by('name', $button, 'post_tag');
				//cat
				if (empty($tag)) {
					$tag = get_term_by('name', $button, 'category');
				}

				$tag_id = $tag->term_id;
				$the_term = get_term($tag_id);

				if (strlen($the_term->name) > 0) {
					$tagbuttons = $tagbuttons . '<li class="esSearch--searchHeaderFilters_item">' . $the_term->name . '<button class="esSearch--tagDelete" id="' . $the_term->name . '">x</button></li>';
				}

			}
		}
		//populate author buttons
		foreach ($author_buttons as $abutton) {
			$author = get_the_author_meta('user_nicename', $abutton);
			if (strlen($author) > 0) {
				$tagbuttons = $tagbuttons . '<li class="esSearch--searchHeaderFilters_item">' . $author . '<button class="esSearch--tagDelete esSearch--author" id="' . $abutton . '">x</button></li>';
			}
		}
		return $tagbuttons;
	}

/**
 * ES function to add special date ranges to tag list
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_tag_buttons_special_date($from, $to, $tagbuttons_agg, $tagbuttons_date, $is_custom) {

		$from_display = explode('-', $from);
		$to_display = explode('-', $to);
		$from_display = $from_display[1] . '-' . $from_display[2] . '-' . $from_display[0];
		$to_display = $to_display[1] . '-' . $to_display[2] . '-' . $to_display[0];

		switch (true) {
		case ($from == 'now-30d/d'):
			$tagbuttons_agg = '<li class="esSearch--searchHeaderFilters_item">Past 30 Days<button class="esSearch--tagDelete esSearch--daterange" id="' . $from . '">x</button></li>';
			break;
		case ($from == 'now-90d/d'):
			$tagbuttons_agg = '<li class="esSearch--searchHeaderFilters_item">Past 90 Days<button class="esSearch--tagDelete esSearch--daterange" id="' . $from . '">x</button></li>';
			break;
		//custom year date
		case ($is_custom == 'true' && $to !== 'now/d+1d'):
			$tagbuttons_agg = '';
			$tagbuttons_date = '<li class="esSearch--searchHeaderFilters_item">' . $from_display . ' to ' . $to_display . '<button class="esSearch--tagDelete esSearch--daterange" id="' . $from . ' to ' . $to . '">x</button></li>';
			break;
		case ($to !== 'now/d+1d'):
			$tagbuttons_agg = '<li class="esSearch--searchHeaderFilters_item">' . date("Y", strtotime($from)) . '<button class="esSearch--tagDelete esSearch--daterange" id="' . $from . '">x</button></li>';
			break;
		}

		return array($tagbuttons_agg, $tagbuttons_date);
	}

/**
 * ES function to populate date list w/aggs
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_date_list($response, $is_custom, $isYear, $from, $results_count) {

		$year_list = $response['aggregations']['years']['buckets'];
		$day_90 = $response['aggregations']['range90']['buckets'][1]['doc_count'];
		$day_30 = $response['aggregations']['range30']['buckets'][1]['doc_count'];
		$this_year = date("Y", strtotime('now'));
		$start_of_year = (date('Y-m-d', strtotime('first day of January ' . date('Y'))));

		foreach ($year_list as $year) {
			$id = date("Y-m-d", strtotime($year['key_as_string']));
			$name = date("Y", strtotime($year['key_as_string']));
			if ($year['doc_count'] > 0) {
				$dates = '<li><a href="" class="esSearch--searchFilters_dateFilter esSearch--searchFilters_filter" id="' . $id . '">' . $name . '</a><span class="esSearch--searchFilters_filter_span">(' . $year['doc_count'] . ')</span></li>' . $dates;
			}
		}
		//add 30/90 day aggs if needed
		if ($day_90 !== 0 && !$isYear) {
			$dates = '<li><a href="" class="esSearch--searchFilters_dateFilter esSearch--searchFilters_filter" id="now-90d/d">Past 90 Days</a><span class="esSearch--searchFilters_filter_span">(' . $day_90 . ')</span></li>' . $dates;
		}
		if ($day_30 !== 0 && !$isYear) {
			$dates = '<li><a href="" class="esSearch--searchFilters_dateFilter esSearch--searchFilters_filter" id="now-30d/d">Past 30 Days</a><span class="esSearch--searchFilters_filter_span">(' . $day_30 . ')</span></li>' . $dates;
		}

		//show only 30/90/year if selected
		switch ($from) {
		case 'now-30d/d':
			$dates = '<p class="esSearch--emptyResults">No more to filter</p>';
			break;
		case 'now-90d/d':
			$dates = '<p class="esSearch--emptyResults">No more to filter</p>';
			break;
		case $start_of_year && $isYear:
			$dates = '<p class="esSearch--emptyResults">No more to filter</p>';
			break;
		}

		//empty container for custom dates
		if ($is_custom == 'true') {
			$dates = '';
		}
		return $dates;
	}

/**
 * ES function to build page navigation
 *
 * @package digital_bureau_elasticsearch
 */

	private function populate_pages_desktop($response, $page_index) {
		$options = get_option('_db-search');
		$page_size = $options['page_size'];
		if (empty($page_size)) {
			$page_size = 10;
		}

		$page_count = intval(ceil($response['hits']['total'] / $page_size));

		//handle fewer pages than results
		if ($page_count < $page_index / $page_size) {
			$page_index = 0;
		}

		//handle empty results
		if ($page_count == 0) {
			$pages = '';
		}

		//create indices
		$page_index = $page_index / $page_size;
		$active_index = ($page_index + 1);

		//create page array, clean up and reindex
		$pages_index = [($page_index - 1), $page_index, ($page_index + 1), ($page_index + 2), ($page_index + 3)];
		$pages_index = array_unique($pages_index);
		$pages_index = array_filter($pages_index, function ($v) use ($page_count) {
			if (($v > 0) && ($v < $page_count)) {
				return $v;
			}
		});
		$pages_index = array_values($pages_index);

		//add ellipses and last page
		if (($active_index + 2) < $page_count) {
			array_push($pages_index, '...');
		}
		array_push($pages_index, $page_count);
		if ($pages_index[0] > 1) {
			array_unshift($pages_index, 1, '...');
		}

		//populate pages
		$pages = '';
		foreach ($pages_index as $page) {
			$page == $active_index ? $pageActive = 'pageActive' : $pageActive = '';
			if (is_int($page)) {
				$id = (($page - 1) * $page_size);
				$filter_class = 'esSearch--searchFilters_pageFilter';
			} else {
				$id = '';
				$filter_class = 'esSearch--searchFilters_pageDotFilter';
			}
			$pages .= '<li class="esSearch--searchResultsPaging_item"><a href="#" class="' . $filter_class . ' esSearch--searchResultsPaging_link esSearch--searchFilters_filter ' . $pageActive . '" id="' . $id . '">' . $page . '</a></li>';
		}

		//set prev/next
		$page_index = $page_index * $page_size;
		$page_index > 0 ? $prev = '<li class="esSearch--searchResultsPaging_item"><a href="#" class="esSearch--searchFilters_pageFilter esSearch--searchResultsPaging_link esSearch--searchFilters_filter" id="' . ($page_index - $page_size) . '">&laquo; Previous</a></li>' : $prev = '';
		$active_index == $page_count ? $next = '' : $next = '<li class="esSearch--searchResultsPaging_item"><a href="#" class="esSearch--searchFilters_pageFilter esSearch--searchResultsPaging_link esSearch--searchFilters_filter" id="' . ($page_index + $page_size) . '">Next &raquo;</a></li>';

		return $prev . $pages . $next;
	}

	private function validate_output($data) {
		//allowed html tags for body
		$allowed = array(
			'a' => array(
				'class' => array(),
				'href' => array(),
				'rel' => array(),
				'title' => array(),
				'id' => array(),
			),
			'li' => array(
				'class' => array(),
				'id' => array(),
			),
			'p' => array(
				'class' => array(),
				'id' => array(),
			),
			'span' => array(
				'class' => array(),
				'id' => array(),
			),
			'button' => array(
				'class' => array(),
				'id' => array(),
			),
			'br' => array(),
			'em' => array(),
			'strong' => array(),
		);
		foreach ($data as $key => $val) {
			$data[$key] = wp_kses($val, $allowed);
		}
		return $data;
	}

//end class
}

/**
 * Search Hook
 *
 * @package digital_bureau_elasticsearch
 */

function es_search() {
	$search = new ES_Search_Class;
	$search->es_search();
}

/**
 * Add sidebar widget
 *
 * @package digital_bureau_elasticsearch
 */
function db_es_load_widget() {
	register_widget('db_es_widget');
}

class db_es_widget extends WP_Widget {

	public function __construct() {
		parent::__construct('db_es_widget', __('WP Search with Elasticsearch', 'db_es_widget_domain'), array('description' => __('Elasticsearch Search Bar', 'db_es_widget_domain'))
		);
	}

	public function widget($args, $instance) {
		$placeholder = apply_filters('widget_title', $instance['title']);

		echo $args['before_widget'];
		$formActive = '<form role="search" method="get" class="search-form" action="' . esc_url(home_url('/')) . '"><label><span class="screen-reader-text">' . _x('', 'label') . '</span><input type="search" id="esSearch--searchField" placeholder="' . $placeholder . '" value="' . esc_attr(get_search_query()) . '" name="s" title="' . esc_attr_x('', 'label') . '" /><input type="hidden" value="1" name="paged" /></label><input type="submit" class="esSearch--searchSubmit" value="' . esc_attr_x('go', 'submit button') . '" /></form>';

		echo __($formActive, 'db_es_widget_domain');
		echo $args['after_widget'];
	}

	public function form($instance) {
		if (isset($instance['title'])) {
			$title = $instance['title'];
		} else {
			$title = __('Search Posts', 'db_es_widget_domain');
		}
		?>
    <p>
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Placeholder:');?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <?php
}

	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
		return $instance;
	}
}

/**
 * WP Hooks
 *
 * @package digital_bureau_elasticsearch
 */

add_action('wp_ajax_es_search', 'es_search');
add_action('wp_ajax_nopriv_es_search', 'es_search');

add_action('wp_ajax_es_search_json', 'es_search_json');
add_action('wp_ajax_nopriv_es_search_json', 'es_search_json');

add_action('widgets_init', 'db_es_load_widget');