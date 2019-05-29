<?php
/**
 * Template Name: Elasticsearch
 *
 * The template for displaying search results
 * @package
 * Version:           1.0.4
 * Author:            Digital Bureau
 * Author URI:        http://www.digitalbureau.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
*/

$value       = sanitize_text_field($_POST['value']);
$search_term = sanitize_text_field($_GET['s']);
$options     = get_option('_db-search');
$stylevars;

foreach($options as $o => $v){
  if(strpos($o, 'style-') !== false){
    $label = str_replace('style-','',$o);
    $stylevars.='--'.$label.':'.$v.';';
  }
}

//override title
get_header();

//Check if the WP Elastic Search plugin is enabled
if (class_exists('db_Search')):
?>

<script>
  searchValue = '<?php echo $value; ?>';
  sortByInit = '<?php echo $options['sort']; ?>';
</script>
<style>
  :root {
  --text-color:#555;
  --border-color:#ccc;
  --main-container-color:#eee;
  --link-color:#0093d0;
  --link-hover-color:#ff9000;
  --button-color:#8fbe32;
  <?php echo $stylevars;?>
}
<?php echo $options['css'];?>
</style>
<div id="primary" class="content-area esSearch">
  <main id="main" class="site-main category" role="main">
    <div id="esSearch--colmainSearch" >
      <article id="esSearch--colmainSearch_article">
        <div id="esSearch--searchHeader">
          <h1 class="esSearch--searchHeader_head">Search Results For "<span id="esSearch--esValueHead"><?php echo $value; ?></span>"</h1>
          <form role="search" method="post" id="esSearch--elasticSearch" action="<?php esc_url(home_url('/search'));?>">
            <input type="search" id="esSearch--esValue" name="value" placeholder="find content">
            <input type="submit" class="esSearch--searchSubmit" id="esSearch--esSubmit" value="go" />
          </form>
          <ul id="esSearch--searchHeaderFilters">
          </ul>
        </div>
        <!-- search filters logic  -->
        <input type="checkbox" id="esSearch--showFilters">
        <label for="esSearch--showFilters" class="esSearch--showFilters_label esSearch--searchFilters_label">filter results<span class="esSearch--dropdownArrow">></span></label>
         <div id="esSearch--searchFilters">
          <h3 class="esSearch--colmainSearch_articleHead" id="esSearch--searchFilters_filterTitle">filter results</h3>
          <button class="esSearch--searchFilters_filter esSearch--searchFilters_clearFilter" id="esSearch--searchFilters_clearSearch">Reset Filters</button>
          <div id="esSearch--within">
          <label class="esSearch--within_label esSearch--searchFilters_label">search within:</label>
          <select id="esSearch--searchFilters_searchWithin" class="esSearch--searchFilters_filter esSearch--searchFilters_withinFilter">
            <option class="" value="all">All</option>
            <option class="" value="title">Title</option>
            <option class="" value="content">Body Text</option>
          </select>
          </div>
          <!-- populate tag filters -->
          <input type="checkbox" id="esSearch--showMore">
          <label for="esSearch--showMore" class="esSearch--showMoreLabel esSearch--searchFilters_label">Topics<span class="esSearch--dropdownArrow">></span></label>
          <ul class="esSearch--searchFilters_list" id="esSearch--tags">
          </ul>
          <div class="esSearch--addMoreContainer">
          <input type="checkbox" id="esSearch--addMoreContainer_addMore">
          <label for="esSearch--addMoreContainer_addMore" class="esSearch--addMoreContainer_addMoreLabel esSearch--searchFilters_label">View More</label>
          <input type="checkbox" id="esSearch--addMoreContainer_addLess">
          <label for="esSearch--addMoreContainer_addLess" class="esSearch--addMoreContainer_addLessLabel esSearch--searchFilters_label">View Less</label>
          </div>

          <!-- populate date filters -->
          <?php if($options['post_date']) : ?>
          <input type="checkbox" id="esSearch--showMore_2">
          <label for="esSearch--showMore_2" class="esSearch--showMoreLabel_2 esSearch--searchFilters_label">Dates<span class="esSearch--dropdownArrow">></span></label>

          <ul class="esSearch--searchFilters_list" id="esSearch--dates">
          </ul>
          <ul class="esSearch--searchFilters_list" id="esSearch--datesRange">
            <p id="esSearch--datesRange_text">Custom Range</p>
            <label class="esSearch--searchFilters_label">Start Date</label>
            <input type="text" id="esSearch--datesRange_filterStart" name="startdate" placeholder="MM/DD/YYYY">
            <label class="esSearch--searchFilters_label">End Date</label>
            <input type="text" id="esSearch--datesRange_filterEnd" name="enddate" placeholder="MM/DD/YYYY">
            <button id="esSearch--datesRange_datesSubmit" class="esSearch--datesRange_dateRangeFilter">Submit</button>
            <div class="esSearch--datesRange_calendarError"></div>
          </ul>
          <div class="esSearch--addMoreContainer_2">
          <input type="checkbox" id="esSearch--addMoreContainer_addMore_2">
          <label for="esSearch--addMoreContainer_addMore_2" class="esSearch--addMoreContainer_addMoreLabel_2 esSearch--searchFilters_label">View More</label>
          </div>
        <?php endif; ?>
         <!-- populate author filters -->
          <?php if($options['post_author']) : ?>
          <input type="checkbox" id="esSearch--showMore_3">
          <label for="esSearch--showMore_3" class="esSearch--showMoreLabel_3 esSearch--searchFilters_label">Authors<span class="esSearch--dropdownArrow">></span></label>
          <ul class="esSearch--searchFilters_list" id="esSearch--authors">
          </ul>
          <div class="esSearch--addMoreContainer_3">
          <input type="checkbox" id="esSearch--addMoreContainer_addMore_3">
          <label for="esSearch--addMoreContainer_addMore_3" class="esSearch--addMoreContainer_addMoreLabel_3 esSearch--searchFilters_label">View More</label>
          <input type="checkbox" id="esSearch--addMoreContainer_addLess_3">
          <label for="esSearch--addMoreContainer_addLess_3" class="esSearch--addMoreContainer_addLessLabel_3 esSearch--searchFilters_label">View Less</label>
          </div>
        <?php endif; ?>
          <hr id="esSearch-separator">
        </div>
        <!-- search results body -->
        <div id="esSearch--searchResults">
          <div id="esSearch--searchResultsHeader">
          <h3 class="esSearch--searchResultsHeader_articleHead">Articles (<span id="esSearch--searchResultsCount"><?php echo $response['hits']['total']; ?></span>)</h3>
          <!-- sort by filters -->
          <label id="esSearch-searchResults_searchSort_label" for="esSearch--searchResults_searchSort">Sort By:</label>
          <select id="esSearch--searchResults_searchSort" class="esSearch--searchResults_sortFilter">
            <?php $so = 'sort_' . $options['sort'];$$so = 'selected';?>
            <option value="recency"  <?php echo $sort_date; ?>class="esSearch--searchFilters_filter">Date</option>
            <option value="relevance" <?php echo $sort_relevance; ?> class="esSearch--searchFilters_filter">Relevance</option>
          </select>
          <br>
          </div>
          <!-- search results list -->
          <ul id="esSearch--searchResultsList">
          </ul>
          <br>
          <ul id="esSearch--searchResultsPaging">
          </ul>
          </div>
          <div style="clear:both"></div>
          </article><!-- #post-## -->
        </div><!--colmain-->
      </main><!-- .site-main -->
    </div><!-- .content-area -->

    <?php
// render results using default search template if plugin is disabled
else:get_template_part('template-parts/search-default');endif;?>
    <?php get_footer();?>
