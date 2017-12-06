 /*
 * Admin facing JS
 * Plugin Name:       WP Search with Elasticsearch
 * Description:       A custom tailored, enterprise search solution by Digital Bureau
 * Version:           1.0.0
 * Author:            Digital Bureau
 * Author URI:        http://www.digitalbureau.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       db-search
*/

var dbElasticSearch = {
    init: function() {
        dbElasticSearch.closeModal();
        dbElasticSearch.createIndex();
        dbElasticSearch.deleteIndex();
        dbElasticSearch.helpDialog();
        dbElasticSearch.indexPosts();
        dbElasticSearch.tagSuggest();
    },
    closeModal: function() {
        jQuery('#esMessage--modal_close').on('click', function() {
            jQuery('#esMessage').fadeOut(300);
        });
        jQuery('#esMessage--message_cancel').on('click', function() {
            jQuery('#esMessage').fadeOut(300);
        });
        jQuery('#esMessage--message_confirm').on('click', function() {
            jQuery('#esMessage').fadeOut(300);
            if (!jQuery('#esMessage').hasClass('es-timeout')) {
                setTimeout(function() {
                    location.reload()
                }, 200);
            }
        });
    },
    createIndex: function() {
        jQuery('#esIndexManagement--createIndex').on('click', function() {
            jQuery('#esIndexManagement--loading').html('<img src=\"'+db_search_plugin_path.path+'/img/loading_blue.gif\" width=\"25\">');
            jQuery.post('admin-ajax.php', {
                'action': 'es_create_index'
            }, function(response) {
                jQuery('#esIndexManagement--loading').empty();
            }).done(function(response) {
                jQuery('#esMessage--message').html('<p>' + response + '</p>');
                jQuery('#esMessage').css('display', 'block');
                jQuery('#esIndexManagement--createIndex').prop('disabled', 'disabled')
                jQuery('#esIndexManagement--indexPosts').prop('disabled', false)
                //activate search index
                jQuery('#esActivated').val('on');
                //disable cluster info after index creation
                jQuery('input[name="_db-search[esindex]"]').prop('readonly', 'readonly')
                jQuery('input[name="_db-search[esurl]"]').prop('readonly', 'readonly')
                jQuery('input[name="_db-search[esurlport]"]').prop('readonly', 'readonly')
                jQuery('#es-before input[type="checkbox"]').click(function() {
                    return false;
                })
            })
        });
    },
    deleteIndex: function() {
        jQuery('#esIndexManagement--deleteIndex').on('click', function() {
            var conf = 'Are you sure you want to delete your index and all data within it? This cannot be undone.';
            jQuery('#esMessage--message_confirm').css('display', 'none');
            jQuery('#esMessage--message').html('<p>' + conf + '</p>');
            jQuery('#esMessage, #esMessage--message_cancel, #esMessage--message_exec').css('display', 'block');
            jQuery('#esMessage--message_cancel').on('click', function() {
                jQuery('#esMessage').fadeOut(300);
                jQuery('#esMessage--message_cancel, #esMessage--message_exec').fadeOut(100);
            });
            jQuery('#esMessage--message_exec').on('click', function() {
                jQuery.post('admin-ajax.php', {
                    'action': 'es_delete_index'
                }, function(response) {
                    jQuery('#esMessage--message_cancel, #esMessage--message_exec').css('display', 'none');
                    jQuery('#esMessage--message_confirm').css('display', 'block');
                    jQuery('#esMessage--message').html('<p>' + response + '</p>');
                    jQuery('#esMessage').css('display', 'block');
                });
            });
        });
    },
    helpDialog: function() {
        jQuery('#esHelp').on('click', function() {
            jQuery('#esHelp--dialog').removeClass('hidden');
        })
        jQuery('#esHelp--close').on('click', function() {
            jQuery('#esHelp--dialog').addClass('hidden');
        })
    },
    indexPosts: function() {
        jQuery('#esIndexManagement--indexPosts').on('click', function() {
            jQuery('#esIndexManagement--indexloading').html('<img src=\"'+db_search_plugin_path.path+'/img/loading_blue.gif\" width=\"25\">');
            jQuery.post('admin-ajax.php', {
                'action': 'es_index_posts'
            }, function(response) {
                jQuery('#esIndexManagement--indexloading').empty();
            }).done(function(response) {
                jQuery('#esMessage--message').html('<p>' + response + '</p>');
                jQuery('#esMessage').css('display', 'block');
                jQuery('#esIndexManagement--indexPosts').prop('disabled', 'disabled')
                jQuery('#esPopulated').val('on');
            }).fail(function(msg) {
                if (msg.status === 504) {
                    message = "You have a lot of posts! Don't worry, the script will continue to run in the background, it may take up to 15 minutes to finish. <br><br><b> Your site may become unresponsive during this time.</b><br><br><small>If you aren't seeing posts being indexed, try upping the memory limit for Wordpress in wp-config.php.<br> 50,000 posts may require more than 2048mb of memory.</small>";

                    function check_status() {
                        jQuery.ajax({
                            type: "post",
                            url: "admin-ajax.php",
                            data: {
                                action: "es_check_status",
                            },
                            timeout: 1500,
                            success: function(message) {
                                jQuery('#esMessage').removeClass('es-timeout');
                                jQuery('#esMessage--message').html('<p>' + message + '</p>');
                                jQuery('#esMessage').css('display', 'block');
                                clearInterval(check)
                            },
                            error: function(request, status, err) {
                                if (status == "timeout") {
                                    console.log('Elasticsearch is still indexing, please be patient!')
                                }
                            }
                        });
                    }
                    var check = setInterval(check_status, 5000);
                } else {
                    message = "Wordpress encountered an error. If you have a lot of posts to index, try upping the memory limit for Wordpress in wp-config.php. 50,000 posts may require more than 2048mb of memory. Error message:" + msg.responseText;
                }
                jQuery('#esMessage--message').html('<p>' + message + '</p>');
                jQuery('#esMessage').addClass('es-timeout')
                jQuery('#esMessage').css('display', 'block');
            })
        });
    },
    tagSuggest: function() {
        jQuery(function($) {
            jQuery('input[name="_db-search[whitelist]"]').suggest('/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag', {
                multiple: true,
                multipleSep: ',',
                delay: 100
            });
            jQuery('input[name="_db-search[blacklist]"]').suggest('/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag', {
                multiple: true,
                multipleSep: ',',
                delay: 100
            });
        });
    }
};
jQuery(document).ready(function() {
    dbElasticSearch.init();
});