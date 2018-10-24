/*
 * Admin facing JS
 * Plugin Name:       WP Search with Elasticsearch
 * Description:       A custom tailored, enterprise search solution by Digital Bureau
 * Version:           1.0.1
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
        document.querySelector('#esMessage--modal_close').addEventListener('click', function(e) {
            document.querySelector('#esMessage').classList.remove('active');
        });

        document.querySelector('#esMessage--message_cancel').addEventListener('click', function(e) {
            document.querySelector('#esMessage').classList.remove('active');
        });

        document.querySelector('#esMessage--message_confirm').addEventListener('click', function(e) {
            document.querySelector('#esMessage').classList.remove('active');
            if (!document.querySelector('#esMessage').classList.contains('es-timeout')) {
                setTimeout(function() {
                    location.reload();
                }, 200);
            }
        });

    },
    createIndex: function() {
        document.querySelector('#esIndexManagement--createIndex').addEventListener('click', function(e) {
            document.querySelector('#esIndexManagement--loading').innerHTML = ' <img src=\"' + db_search_plugin_path.path + '/img/loading_blue.gif\" width=\"25\">';

            var url = '/wp-admin/admin-ajax.php?action=es_create_index';
            var request = new XMLHttpRequest();

            request.open('POST', url, true);
            request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8;');
            request.onload = function (response) {
                document.querySelector('#esIndexManagement--loading').innerHTML = '';
                document.querySelector('#esMessage--message').innerHTML = '<p>' + response.target.responseText + '</p>';
                document.querySelector('#esMessage').classList.add('active');
                document.querySelector('#esIndexManagement--createIndex').disabled = 'disabled';
                document.querySelector('#esIndexManagement--indexPosts').disabled = false;

                //activate search index
                document.querySelector('#esActivated').value = 'on';

                //disable cluster info after index creation
                document.querySelector('input[name="_db-search[esindex]"]').readonly =  true;
                document.querySelector('input[name="_db-search[esurl]"]').readonly =  true;
                document.querySelector('input[name="_db-search[esurlport]"]').readonly =  true;
            };
            request.send();

        });
    },
    deleteIndex: function() {
        document.querySelector('#esIndexManagement--deleteIndex').addEventListener('click', function(e) {
            console.log('running ES index deletion');
            var conf = 'Are you sure you want to delete your index and all data within it? This cannot be undone.';

            document.querySelector('#esMessage--message_confirm').style.display = 'none';
            document.querySelector('#esMessage--message').innerHTML = '<p>' + conf + '</p>';
            document.querySelector('#esMessage').classList.add('active');
            document.querySelector('#esMessage--message_cancel').style.display = 'block';
            document.querySelector('#esMessage--message_exec').style.display = 'block';

            document.querySelector('#esIndexManagement--deleteIndex').addEventListener('click', function() {
                document.querySelector('#esMessage').classList.remove('active');
                document.querySelector('#esMessage--message_cancel').style.display = 'none';
                document.querySelector('#esMessage--message_exec').style.display = 'none';
            });

            document.querySelector('#esMessage--message_exec').addEventListener('click', function() {
                var url = '/wp-admin/admin-ajax.php?action=es_delete_index';
                var request = new XMLHttpRequest();

                request.open('POST', url, true);
                request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8;');
                request.onload = function (response) {
                    document.querySelector('#esMessage--message_cancel').style.display = 'none';
                    document.querySelector('#esMessage--message_exec').style.display = 'none';
                    document.querySelector('#esMessage--message_confirm').style.display = 'block';
                    document.querySelector('#esMessage--message').innerHTML = '<p>' + response.target.responseText + '</p>';
                    document.querySelector('#esMessage').classList.add('active');
                };
                request.send();
            });
        });
    },
    helpDialog: function() {
        document.querySelector('#esHelp').addEventListener('click', function() {
            document.querySelector('#esHelp--dialog').classList.remove('hidden');
        });

        document.querySelector('#esHelp--close').addEventListener('click', function() {
            document.querySelector('#esHelp--dialog').classList.add('hidden');
        });
    },
    indexPosts: function() {
        document.querySelector('#esIndexManagement--indexPosts').addEventListener('click', function() {
            document.querySelector('#esIndexManagement--indexloading').innerHTML = '<img src=\"' + db_search_plugin_path.path + '/img/loading_blue.gif\" width=\"25\">';

            var url = '/wp-admin/admin-ajax.php?action=es_index_posts';
            var request = new XMLHttpRequest();

            request.open('POST', url, true);
            request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8;');
            request.onload = function (response) {
                document.querySelector('#esIndexManagement--indexloading').innerHTML = '';
                if (this.status >= 200 && this.status < 400) {
                    document.querySelector('#esMessage--message').innerHTML = '<p>' + response.target.responseText + '</p>';
                    document.querySelector('#esMessage').classList.add('active');
                    document.querySelector('#esIndexManagement--indexPosts').disabled = 'disabled';
                    document.querySelector('#esPopulated').value = 'on';
                } else {
                    if (this.status === 504) {
                        message = "You have a lot of posts! Don't worry, the script will continue to run in the background, it may take up to 15 minutes to finish. <br><br><b> Your site may become unresponsive during this time.</b><br><br><small>If you aren't seeing posts being indexed, try upping the memory limit for Wordpress in wp-config.php.<br> 50,000 posts may require more than 2048mb of memory.</small>";

                        var check = setInterval(function () {
                            var checkUrl = '/wp-admin/admin-ajax.php?action=es_check_status';
                            var req = new XMLHttpRequest();

                            req.open('POST', checkUrl, true);
                            req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8;');
                            req.onload = function (resp) {
                                if (this.status >= 200 && this.status < 400) {
                                    document.querySelector('#esMessage').classList.remove('es-timeout');
                                    document.querySelector('#esMessage--message').innerHTML = '<p>' + resp.target.responseText + '</p>';
                                    document.querySelector('#esMessage').classList.add('active');
                                    clearInterval(check);
                                } else {
                                    console.log('Elasticsearch is still indexing, please be patient!');
                                }
                            };
                            req.send();
                        }, 5000);

                    } else {
                        message = 'Wordpress encountered an error. If you have a lot of posts to index, try upping the memory limit for Wordpress in wp-config.php. 50,000 posts may require more than 2048mb of memory. Error message:' + msg.responseText;
                    }
                    document.querySelector('#esMessage--message').innerHTML = '<p>' + message + '</p>';
                    document.querySelector('#esMessage').classList.add('es-timeout');
                    document.querySelector('#esMessage').classList.add('active');
                }
            };
            request.send();
        });
    },
    tagSuggest: function() {
        jQuery(function($) {
            jQuery('input[name="_db-search[whitelist]"]').suggest('/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag', {
                multiple:    true,
                multipleSep: ',',
                delay:       100
            });
            jQuery('input[name="_db-search[blacklist]"]').suggest('/wp-admin/admin-ajax.php?action=ajax-tag-search&tax=post_tag', {
                multiple:    true,
                multipleSep: ',',
                delay:       100
            });
        });
    }
};
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('form[name="elasticsearch-options"') !== null) {
        dbElasticSearch.init();
    }

});
