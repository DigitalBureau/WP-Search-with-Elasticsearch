<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.digitalbureau.com
 * @since      1.0.0
 *
 * @package    db_search
 * @subpackage db_search/includes
 * Version:           1.0.0
 * Author:            Digital Bureau
 * Author URI:        http://www.digitalbureau.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    db_search
 * @subpackage db_search/includes
 * @author     Digital Bureau
 */
class db_Search {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      db_Search_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $db_search    The string used to uniquely identify this plugin.
     */
    protected $db_search;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->db_search = 'db-search';
        $this->version   = '1.0.4';

        $this->load_dependencies();
        $this->build_es_client();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
     * - Plugin_Name_i18n. Defines internationalization functionality.
     * - Plugin_Name_Admin. Defines all hooks for the admin area.
     * - Plugin_Name_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-db-search-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-db-search-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-db-search-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-db-search-public.php';

        $this->loader = new db_Search_Loader();

    }

    public function build_es_client() {

        //client dependencies
        require plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

        //config settings
        $options = get_option('_db-search');

        // Build the client object
        if ($options['esurl'] && $options['esurlport']) {
          try{
            $host           = [$options['esurl'] . ':' . $options['esurlport']];
            $connectionPool = '\Elasticsearch\ConnectionPool\StaticNoPingConnectionPool';
            $selector       = '\Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector';
            $serializer     = '\Elasticsearch\Serializers\SmartSerializer';
            $client         = Elasticsearch\ClientBuilder::create()
                ->setHosts($host)
                ->setRetries(2)
                ->setHandler($defaultHandler)
                ->setConnectionPool($connectionPool)
                ->setSelector($selector)
                ->setSerializer($serializer)
                ->build();
            }catch(Exception $e){
                $client = false;
                echo ('Elasticsearch failed! Message: '.$e->getMessage());
            }
            return $client;
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new db_Search_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new db_Search_Admin($this->db_search, $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Add menu item
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Add Settings link to the plugin
        $plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->db_search . '.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

        $this->loader->add_action('admin_init', $plugin_admin, 'options_update');

        //create new table for ES index ingestion
        $this->loader->add_action('wp_ajax_es_create_data_table', $plugin_admin, 'es_create_data_table');

        //create new ES index
        $this->loader->add_action('wp_ajax_es_create_index', $plugin_admin, 'es_create_index');

        //add posts to index
        $this->loader->add_action('wp_ajax_es_index_posts', $plugin_admin, 'es_index_posts');

        //delete index
        $this->loader->add_action('wp_ajax_es_delete_index', $plugin_admin, 'es_delete_index');

        //check status
        $this->loader->add_action('wp_ajax_es_check_status', $plugin_admin, 'es_check_status');

        //percolator utilities
        $this->loader->add_action('wp_ajax_es_get_percolate_queries', $plugin_admin, 'es_get_percolate_queries');

        //publish/delete hooks
        $this->loader->add_filter('publish_post', $plugin_admin, 'es_on_publish'); 
        $this->loader->add_filter('publish_page', $plugin_admin, 'es_on_publish');
        $this->loader->add_action('transition_post_status', $plugin_admin, 'es_on_delete');

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new db_Search_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->db_search;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
