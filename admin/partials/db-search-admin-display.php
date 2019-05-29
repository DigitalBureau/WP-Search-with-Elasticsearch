<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.digitalbureau.com
 * @since      1.0.0
 *
 * @package    db_search
 * @subpackage db_search/admin/partials
 */

?>
<div>
<h1 id="esTitle"><?php echo esc_html(get_admin_page_title());
$this->get_plugin_name; ?><span id="esHelp">Help</span></h1>
<p id="esDB">by <a href="http://www.digitalbureau.com"  target="_blank"><img src="<?php echo plugin_dir_url(plugin_dir_path(__FILE__));?>/img/logo.png"></a></p>
<br />
</div>

<div id="esHelp--dialog" class="hidden">
  <p id="esHelp--close">close</p>
  <h2 class="esHelp--title">Basic Setup Instructions</h2>
  <h3 class="esHelp--header">Install Elasticsearch and allow all requests</h3>
  <ul class="esHelp--list">
    <li class="esHelp--listItem">• To set up an Elasticsearch instance from scratch, refer to the official Elasticsearch installation docs <a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/_installation.html" target="_blank" title="Elasticsearch Installation">here</a> for support and allowing access to your ES instance.</li>
    <li class="esHelp--listItem">• A much easier and well supported installation is available from Amazon Web Services. You can follow the 3-minute deployment instructions <a href="https://info.elastic.co/site-link-ext-considerations-for-elasticsearch-in-the-cloud-trial.html?camp=Branded-GGL-Exact&src=adwords&mdm=cpcsl&trm=amazon%20elasticsearch&gclid=EAIaIQobChMIvu-WiOuJ1wIVg4ZpCh0PwAWHEAAYASADEgJv-_D_BwE" target="_blank" title="Elasticsearch Installation on AWS">here.</a></li>
    <li class="esHelp--listItem"><small>You will need to make sure that your ES instance is set up to allow requests from your Wordpress installation.</small></li>
    <li class="esHelp--listItem"><small>For AWS: Make note of the IP of your Wordpress host, and add it to the allowed IP's list in the 'modify access policy' section.</small></li>
  </ul>
  <h3 class="esHelp--header">Set up WP Search with Elasticsearch</h3>
  <ul class="esHelp--list">
    <li class="esHelp--listItem">• Once the plugin has been activated, Settings->WP Search with Elasticsearch in the Wordpress Admin.</li>
    <li class="esHelp--listItem">• Add your information to the 'Available before Index Creation' section:</li>
    <li class="esHelp--listItem"><small>Specify the index name, url, port, and what content you would like to add to the search engine.</small></li>
    <li class="esHelp--listItem">• Click 'Save All Changes'. The page will reload.</li>
    <li class="esHelp--listItem">• If you have used the correct info for your ES instance and have allowed your Wordpress IP to access it, you should see the cluster information at the top of the screen</li>
    <li class="esHelp--listItem"><small>If you are getting error messages, make sure that your Elasticsearch instance is set up to allow requests, and that you have the correct url and port defined.</small></li>
    <li class="esHelp--listItem"><small>NOTE: If using AWS, setting access rules will take time to populate correctly, sometimes as long as 15-20 minutes.</small></li>
    <li class="esHelp--listItem">• Once your cluster is connected, scroll to the bottom of the screen and click on the 'Click to Create Index' button. You will be notified the index has been created, then the page will reload.</li>
    <li class="esHelp--listItem">• Once your index has been created, click on the 'Click to Populate' button at the bottom of the screen, you will be notified how many posts have been added to the index, then the page will reload.
    </li>
  </ul>
  <h3 class="esHelp--header">Configuration defaults</h3>
  <ul class="esHelp--list">
    <li class="esHelp--listItem">• The plugin is automatically set up to function "like Google", where queries in quotes will be treated as a single query, operators like 'and' will combine search terms into one search, etc. </li>
    <li class="esHelp--listItem">• Any new posts and pages will automatically be added to the search index, any changes to a post will be updated in the index.</li>
    <li class="esHelp--listItem"><small> NOTE: Quick editing is not supported, changes to search results will not be updated until the post is edited with Gutenberg or the classic editor.</small></li>
  </ul>

</div>

<form method="post" name="elasticsearch-options" action="options.php">
<?php require_once 'db-search-admin-functions.php';?>
<p class="esIndexManagement--adminError"><?php echo $index_error;?></p>
<h2 style="display:<?php echo $health_display; ?>" class="esHealthDisplay esHealthDisplay--title">Your cluster:</h2>
<div style="display:<?php echo $health_display; ?>" class="esHealthDisplay">
<table class="esHealthDisplay--table">
  <thead>
    <tr>
      <th class="esHealthDisplay--th">Cluster Name</th>
      <th class="esHealthDisplay--th">Status</th>
      <th class="esHealthDisplay--th">Number of nodes</th>
      <th class="esHealthDisplay--th">Active Shards</th>
    </tr>
  </thead>
  <tbody>
  <tr>
    <td class="esHealthDisplay--td"><?php echo $health['cluster_name']; ?></td>
    <td class="esHealthDisplay--td" style="background-color:<?php echo $health['status']; ?>"><?php echo $health['status']; ?></td>
    <td class="esHealthDisplay--td"><?php echo $health['number_of_nodes']; ?></td>
    <td class="esHealthDisplay--td"><?php echo $health['active_shards']; ?></td>
  </tr>
</tbody>
</table>
</div>
<h2 style="display:<?php echo $index_display; ?>" class="esHealthDisplay esHealthDisplay--title">Your index:</h2>
<div style="display:<?php echo $index_display; ?>" class="esHealthDisplay">
<table class="esHealthDisplay--table">
  <thead>
    <tr>
      <th class="esHealthDisplay--th">Index Name</th>
      <th class="esHealthDisplay--th">Created (GMT)</th>
      <th class="esHealthDisplay--th">Number of shards</th>
      <th class="esHealthDisplay--th">Number of Replicas</th>
      <th class="esHealthDisplay--th">Article Count</th>
    </tr>
  </thead>
  <tbody>
  <tr>
    <td class="esHealthDisplay--td"><?php echo $es_index_display['provided_name']; ?></td>
    <td class="esHealthDisplay--td"><?php echo gmdate('M d, Y h:i', ($es_index_display['creation_date'] / 1000)); ?></td>
    <td class="esHealthDisplay--td"><?php echo $es_index_display['number_of_shards']; ?></td>
    <td class="esHealthDisplay--td"><?php echo $es_index_display['number_of_replicas']; ?></td>
    <td class="esHealthDisplay--td"><?php echo $count; ?></td>
  </tr>
</tbody>
</table>
</div>
    <fieldset>
      <!-- values for index setup -->
      <h3>Available before index creation:</h3>
      <div class="esBefore" style="<?php echo $created_css; ?>">
        <table class="esBefore--table">
          <tr>
            <td class="esBefore--td">
              <label for="_es-search-esindex">
                <h4>Name of Elasticsearch Index:</h4>
              </label>
            </td>
            <td class="esBefore--td">
              <input type="text" class="_es-search esBefore--input" name="_db-search[esindex]" value="<?php echo $esindex ?>" <?php echo $created; ?>/>
            </td>
          </tr>
          <tr>
            <td class="esBefore--td">
              <label for="_es-search-esurl">
                <h4>Elasticsearch instance url:</h4>
              </label>
            </td>
            <td class="esBefore--td">
              <input type="url" class="_es-search esBefore--input" name="_db-search[esurl]" value="<?php echo $options['esurl'] ?>" placeholder="https://search-elasticsearch-xxxxxxxxx.us-east-1.es.amazonaws.com" <?php echo $created; ?>/>
            </td>
          </tr>
          <tr>
            <td class="esBefore--td">
              <label for="_es-search-esurlport">
                <h4>Elasticsearch instance port:</h4>
              </label>
            </td>
            <td class="esBefore--td">
              <input type="number" class="_es-search esBefore--input" name="_db-search[esurlport]" value="<?php echo $options['esurlport'] ?>" placeholder="9200" <?php echo $created; ?>/>
            </td>
          </tr>
        </table>

        <p class="esBefore--esNote">
          For AWS with SSL, use port 443. For Self hosted urls, use 9200.
        </p>
        <p class="esBefore--esNote">
          Make sure that your ES instance is open to the IP your installation of Wordpress is hosted on
        </p>
        <h4 class="esBefore--esInclude">
          Include posts/pages
        </h4>

        <span class="esBefore--spacer">&nbsp;</span>
        <label for="_es-search_posts" class="esBefore--esCbLabel">
          Posts:
        </label>
        <input type="checkbox" id="_es-search_posts" name="_db-search[posts]" <?php echo $posts ?> <?php echo $cb_created; ?>/>

        <label for="_es-search_pages" class="esBefore--esCbLabel">
          Pages:
        </label>
        <input type="checkbox" id="_es-search_pages" name="_db-search[pages]" <?php echo $pages ?> <?php echo $cb_created; ?>/>
        <br>

        <label for="_es-search-whitelist" >
          <h4 class="esBefore--esInclude">
            Include tags/categories:
          </h4>
        </label>
        <input type="text" name="_db-search[whitelist]" value="<?php echo $options['whitelist'] ?>" placeholder="Start typing to add tags..." <?php echo $created; ?>/>
        <p class="esBefore--esNote">
          If you would like to ONLY include specific tags and categories, add a comma separated list of tag and category names here.
        </p>

        <label for="_es-search-blacklist">
          <h4 class="esBefore--esInclude">
            Exclude tags/categories:
          </h4>
        </label>
        <input type="text" name="_db-search[blacklist]" value="<?php echo $options['blacklist'] ?>" placeholder="Start typing to add tags..." <?php echo $created; ?>/>
        <p class="esBefore--esNote">
          To exclude posts and pages with certain tags or categories, add a comma separated list of tag and category names.
        </p>
        <label for="_es-search-memory_allocation">
          <h4 class="esAfter--header">
            Script Memory Allocation (in MB)
          </h4>
        </label>
        <input type="number" name="_db-search[memory_allocation]" value="<?php echo (!empty($options['memory_allocation']) ? $options['memory_allocation'] : 1024) ?>" <?php echo $created; ?>/>
        <p class="esBefore--esNote">
          For more than 10,000 posts, Wordpress will require more memory. If you are running into errors, or not all of your posts are indexing, you can set this value higher to allow for more population. 50,000 posts may require as much as 4096MB. 
        </p>
        <br>

      </div>
      
      <!-- live index settings  -->
      <h3>Available after index creation:</h3>      
      <div class="esAfter">
        
        <label for="_es-search-publish">
          <h4 class="esAfter--header">
            Add posts/pages to Elasticsearch Index when published
          </h4>
        </label>
        <input type="checkbox" name="_db-search[publish]" <?php echo $publish; ?>/>
        <br>
        <label for="_es-search-page_size">
          <h4 class="esAfter--header">
            Results per page
          </h4>
        </label>
        <input type="number" name="_db-search[page_size]" value="<?php echo (!empty($options['page_size']) ? $options['page_size'] : 10) ?>"/>
        <br>
        
        <label for="_es-search-sort">
          <h4 class="esAfter--header">
            Sort Results by:
          </h4>
        </label>
        
        <select name="_db-search[sort]">
          <option value="recency" <?php echo $sort_date; ?>>Date</option>
          <option value="relevance" <?php echo $sort_relevance; ?>>Relevance</option>
        </select>
        <br>
        <label for="_es-search_post_author">
          <h4 class="esAfter--header">
            Include post author filters:
          </h4>
        </label>
        <input type="checkbox" id="_es-search_post_author" name="_db-search[post_author]" <?php echo $post_author ?> />
        <br>
        <label for="_es-search_post_date">
          <h4 class="esAfter--header">
            Include post date filters:
          </h4>
        </label>
        <input type="checkbox" id="_es-search_post_date" name="_db-search[post_date]" <?php echo $post_date ?>/>
        <br>
        <label for="_es-search_operators">
          <h4 class="esAfter--header">
            Use AND/OR/NOT operators to combine search terms:
          </h4>
        </label>
        <input type="checkbox" id="_es-search_operators" name="_db-search[operators]" <?php echo $operators ?> />
        <p class="es-note">
          ex. 'USA and cars' will return search results that have both terms
        </p>
        <br>
        
        <label for="_es-search_quotes">
          <h4 class="esAfter--header">
            Treat queries in quotes as single term:
          </h4>
        </label>
        <input type="checkbox" id="_es-search_quotes" name="_db-search[quotes]" <?php echo $quotes ?> />
        <p class="es-note">
          ex. "Party in the USA" will return search results that match the exact phrase "Party in the USA".
        </p>
        <br>
        <br>
        
        <h4 class="esAfter--header">
          CSS Styles and Overrides
        </h4>
        
        <table class="esAfter--table">
          <tr>
            <td>
              <label for="_es-search_style-text-color">
                Text Color
              </label>
            </td>
            <td>
              <input type="color" id="_es-search_style-text-color" name="_db-search[style-text-color]" value="<?php echo !empty($options['style-text-color'])?$options['style-text-color']:'#555555'; ?>" placeholder="#555555"/>
            </td>
          </tr>
          <tr>
            <td>
              <label for="_es-search_style-border-color">
                Border Color
              </label>
            </td>
            <td>
              <input type="color" id="_es-search_style-border-color" name="_db-search[style-border-color]" value="<?php echo !empty($options['style-border-color'])?$options['style-border-color']:'#cccccc'; ?>" placeholder="#cccccc"/>
            </td>
          </tr>
          <tr>
            <td>
              <label for="_es-search_style-main-container-color">
                Main Container Border Color
              </label>
            </td>
            <td>
              <input type="color" id="_es-search_style-main-container-color" name="_db-search[style-main-container-color]" value="<?php echo !empty($options['style-main-container-color'])?$options['style-main-container-color']:'#eeeeee'; ?>" placeholder="#eeeeee"/>
            </td>
          </tr>
          <tr>
            <td>
              <label for="_es-search_style-link-color">
                Link Color
              </label>
            </td>
            <td>
              <input type="color" id="_es-search_style-link-color" name="_db-search[style-link-color]" value="<?php echo !empty($options['style-link-color'])?$options['style-link-color']:'#0093d0'; ?>" placeholder="#0093d0"/>
            </td>
          </tr>
          <tr>
            <td>
              <label for="_es-search_style-link-hover-color">
                Link Hover Color
              </label>
            </td>
            <td>
              <input type="color" id="_es-search_style-link-hover-color" name="_db-search[style-link-hover-color]" value="<?php echo !empty($options['style-link-hover-color'])?$options['style-link-hover-color']:'#ff9000'; ?>" placeholder="#ff9000"/>
            </td>
          </tr>
          <tr>
            <td>
              <label for="_es-search_style-button-color">
                Button Color
              </label>
            </td>
            <td>
              <input type="color" id="_es-search_style-button-color" name="_db-search[style-button-color]" value="<?php echo !empty($options['style-button-color'])?$options['style-button-color']:'#8fbe32'; ?>" placeholder="#8fbe32"/>
            </td>
        </table>
        <p class="es-note">
          Custom CSS colors are supported by all modern browsers except Internet Explorer. For custom colors with IE, use the custom CSS field below.
        </p>
        
        <div class="esAfter--customCss">
          <label for="_es-search_css">
            <h4 class="esAfter--header">
              Custom CSS Rules
            </h4>
          </label>
          <br>
            <textarea class="esAfter--customCss_textarea" rows="5" cols="75" id="_es-search_css" name="_db-search[css]" placeholder="ex. body{
          background-color:red;
        }"><?php echo $options['css']; ?></textarea>
        </div>
      </div>

      <!-- activated/populated index values-->
      <input type="hidden" value="<?php echo $options['activatedIndex'] ?>" name="_db-search[activatedIndex]" id="esActivated"/>
      <input type="hidden" value="<?php echo $options['populatedIndex'] ?>" name="_db-search[populatedIndex]" id="esPopulated"/>
    
    </fieldset>

    <?php
      $attr = array('id' => 'esSubmitAdmin');
      submit_button('Save all changes', 'primary', 'submit', true, $attr);
    ?>
</form>


<h2>Index Management</h2>
<div class="esIndexManagement">
  <table class="esIndexManagement--table">
  <tr>
    <td>
      <h4 class="esIndexManagement--tableHead">
        Create ES Index
      </h4>
    </td>
  </tr>
  <tr>
    <td>
      <button id="esIndexManagement--createIndex" class="esIndexManagement--tableButton" <?php echo $createIndex; ?> title="This will create an index on your Elasticsearch instance. Available only once you have defined an url and port.">
        Click to create index <span id="esIndexManagement--loading">&nbsp;</span>
      </button>
    </td>
    <td>
      <span class="esIndexManagement--asterisk">&#42;</span> This will create an index on your Elasticsearch instance. Available only once you have defined an index name, a hosting url, and port.
    </td>
  </tr>
  <tr>
    <td>
      <h4 class="esIndexManagement--tableHead">
        Add Content to ES
      </h4>
    </td>
  </tr>
  <tr>
    <td>
      <button id="esIndexManagement--indexPosts" class="esIndexManagement--tableButton" <?php echo $populateIndex; ?>>
        Click to populate<span id="esIndexManagement--indexloading">&nbsp;</span>
      </button>
    </td>
    <td>
      <span class="esIndexManagement--asterisk">&#42;</span> This will add load all posts and pages into your index. Select options above for including/excluding fields and post types. If you have a large amount of posts (>10,000) you will need to set the script's memory allocation to at least 2048MB, and indexing will take up to 15 minutes. Check your server's error log for progress.
    </td>
  </tr>
  <tr>
    <td>
      <h4 class="esIndexManagement--tableHead">
        Delete Index
      </h4>
    </td>
  </tr>
  <tr>
    <td>
      <button id="esIndexManagement--deleteIndex" class="esIndexManagement--tableButton">
        Delete Index
      </button>
    </td>
    <td>
      <span class="esIndexManagement--asterisk">&#42;</span> This will delete your Elasticsearch index and all indexed posts. Cannot be undone.
    </td>
  </tr>
</table>

<div id="esMessage">
  <div id="esMessage--modal">
    <p id="esMessage--modal_close">x</p>
    <p id="esMessage--message"></p>
    <button id="esMessage--message_exec">OK</button>
    <button id="esMessage--message_confirm">OK</button>
    <button id="esMessage--message_cancel">Cancel</button>
  </div>
</div>