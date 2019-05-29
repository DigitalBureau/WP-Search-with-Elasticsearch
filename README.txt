# WP Search with Elasticsearch #
Contributors: Ben Fausch, Rob Jacobs, Don MacKinnon

Requires at least: 3.0.1
Tested up to: 5.2.1
Stable tag: 1.0.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A Wordpress plugin designed to make working with Elasticsearch and Wordpress quick and painless. By Digital Bureau.

# Description

WP Search with Elasticsearch is a plugin designed to make adding a powerful search engine to your Wordpress site simple.

Simply install the plugin, point it to your Elasticsearch instance, and with a few clicks you will have all of your content set up to work with Elasticsearch!

 

## Features:

* Easy to set up

* Index posts from a settings page

* Robust search algorithms powered by Elasticsearch

* Functions "like Google" right out of the box

* Quick indexing and lightning-fast performance that supports up to 50,000 posts

* Replaces front end search with a simple widget and a fast, mobile-friendly search page

* No need to be a developer to work with ES! 


# Installation

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

# Frequently Asked Questions
=== WP Search with Elasticsearch ===
Contributors: Ben Fausch, Don McKinnon, Rob Jacobs
Tags: search, elasticsearch, search-wp
Requires at least: 3.0.1
Tested up to: 5.2.1
Requires PHP: 5.6
Stable tag: 1.0.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A Wordpress plugin designed to make working with Elasticsearch and Wordpress quick and painless. By Digital Bureau.


== Description ==
WP Search with Elasticsearch is a plugin designed to make adding a powerful search engine to your Wordpress site simple.

Simply install the plugin, point it to your Elasticsearch instance, and with a few clicks you will have all of your content set up to work with Elasticsearch!



Features:

* Easy to set up

* Works with Gutenberg or Classic Editor

* Index posts from a settings page

* Robust search algorithms powered by Elasticsearch

* Functions \"like Google\" right out of the box

* Quick indexing and lightning-fast performance that supports up to 50,000 posts

* Replaces front end search with a simple widget and a fast, mobile-friendly search page

* No need to be a developer to work with ES!

== Installation ==
Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Frequently Asked Questions ==

## Installation

### Requirements
   * Elasticsearch 6+
   * PHP 5.6
   * At least 512MB allocated for Wordpress

### Install Elasticsearch and allow all requests

  * To set up an Elasticsearch instance from scratch, refer to the official Elasticsearch installation docs here for support and allowing access to your ES instance.

  * A much easier and well supported installation is available from Amazon Web Services. You can follow the 3-minute deployment instructions here.

    - You will need to make sure that your ES instance is set up to allow requests from your Wordpress installation.

 -For AWS: Make note of the IP of your Wordpress host, and add it to the allowed IP\'s list in the \'modify access policy\' section.

### Set up WP Search with Elasticsearch

  * Once the plugin has been activated, Settings->WP Search with Elasticsearch in the Wordpress Admin.

  * Add your information to the \'Available before Index Creation\' section:

      - Specify the index name, url, port, and what content you would like to add to the search engine.

  * Click \'Save All Changes\'. The page will reload.

  * If you have used the correct info for your ES instance and have allowed your Wordpress IP to access it, you should see the cluster information at the top of the screen
      - If you are getting error messages, make sure that your Elasticsearch instance is set up to allow requests, and that you have the correct url and port defined.
      - NOTE: If using AWS, setting access rules will take time to populate correctly, sometimes as long as 15-20 minutes.

  * Once your cluster is connected, scroll to the bottom of the screen and click on the \'Click to Create Index\' button. You will be notified the index has been created, then the page will reload.

  * Once your index has been created, click on the \'Click to Populate\' button at the bottom of the screen, you will be notified how many posts have been added to the index, then the page will reload.



### Configuration defaults

  * The plugin is automatically set up to function \"like Google\", where queries in quotes will be treated as a single query, operators like \'and\' will combine search terms into one search, etc.

  * Any new posts and pages will automatically be added to the search index, any changes to a post will be updated in the index.



### Add a widget to your site

  * Under Appearance->Widgets in wp-admin, you can add the \"WP Search with Elasticsearch\" search bar to your site. This overrides the stock search in Wordpress.

 

 