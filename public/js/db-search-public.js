var ES = {
    init: function() {
        //check for POST searchvalue
        if (typeof searchValue !== 'undefined' && searchValue.length > 0) {
            ES.pushDefaultUrl(encodeURIComponent(searchValue), sortByInit);
        } else {
            searchValue = '';
        }
        if (jQuery('body div').hasClass('esSearch')) {
            jQuery('.bread-current').text('Search');
            ES.esCall();
        }
        //initiate datepicker values
        jQuery('#esSearch--datesRange_filterStart').datepicker({
            dateFormat: 'mm-dd-yy'
        });
        jQuery('#esSearch--datesRange_filterEnd').datepicker({
            dateFormat: 'mm-dd-yy'
        });
        //activate first page in navigation
        jQuery('.page-filter#0').addClass('pageActive');
        // listeners
        jQuery('#esSearch--searchFilters').on('click', '.esSearch--searchFilters_filter', ES.esFilter);
        jQuery('#esSearch--searchResults_searchSort').on('change', ES.esFilter);
        jQuery('#esSearch--searchFilters_searchWithin').on('change', ES.esFilter);
        jQuery('#esSearch--datesRange_datesSubmit').on('click', ES.esFilter);
        jQuery('#esSearch--searchResultsPaging').on('click', '.esSearch--searchFilters_filter', ES.esFilter);
        //change input placeholder
        jQuery('#wl-search .widget_search .esSearch--searchField').attr('placeholder', 'Keywords');
        ES.sideBar();
    },
    addFilters: function(current) {
        // filters to be added to the head
        filterId = jQuery(current).attr('id');
        filterText = jQuery(this).text();
        //remove last comma
        filtersList = filtersList.replace(/,\s*$/, '');
        //add commas, various scenarios
        if (filtersList.length < 1) {
            filtersList += encodeURIComponent(filterId + ', ');
        } else {
            filtersList += encodeURIComponent(', ' + filterId + ', ');
        }
        //remove first comma
        filtersList = filtersList.replace(/^,\s*/, '');

        pageIndex = 0;
        // selector = '.page-filter#0';
        ES.pushNewUrl();
        filtersList = decodeURIComponent(filtersList);
        ES.esCall(filtersList, authorsList, within);
    },
    authorFilter: function(current) {
        // filters to be added to the head
        authorId = jQuery(current).attr('id');
        authorText = jQuery(this).text();
        //remove last comma
        authorsList = authorsList.replace(/,\s*$/, '');
        //add commas, various scenarios
        if (authorsList.length < 1) {
            authorsList += encodeURIComponent(authorId + ', ');
        } else {
            authorsList += encodeURIComponent(', ' + authorId + ', ');
        }
        //remove first comma
        authorsList = authorsList.replace(/^,\s*/, '');

        pageIndex = 0;
        // selector = '.page-filter#0';
        ES.pushNewUrl();
        authorsList = decodeURIComponent(authorsList);
        ES.esCall(filtersList, authorsList, within);
    },
    clearFilters: function() {
        dateRange = '';
        sortBy = sortByInit;
        jQuery('#esSearch--searchHeaderFilters').empty();
        jQuery('.hasDatepicker').val('');
        // selector = '.page-filter#0';
        pageIndex = 0;
        within = 'all';
        isCustom = false;
        
        filtersList = '';
        authorsList = '';
        jQuery('#esSearch--searchFilters_searchWithin').val('all');
        jQuery('#esSearch--searchResults_searchSort').val('recency');
        jQuery('#esSearch--datesRange_filterStart').val('');
        jQuery('#esSearch--datesRange_filterEnd').val('');
        jQuery('.esSearch--datesRange_calendarError').empty();
        jQuery('#esSearch--datesRange').removeClass('hidden');
        ES.elasticSearchBase();
    },
    dateFilter: function(current) {
        jQuery('.date-filter').removeClass('filterActive');
        jQuery('#esSearch--datesRange').addClass('hidden');
        //date filters
        fromDate = jQuery(current).attr('id');
        // handle 30/90
        if (fromDate == 'now-30d/d') {
            dateRange = 'past30days';
        }
        if (fromDate == 'now-90d/d') {
            dateRange = 'past90days';
        }
        // create toDate for year ranges
        if (fromDate.indexOf('d') < 1) {
            fromDate = fromDate.substring(0, 4);
            dateRange = fromDate;
        }
        pageIndex = 0;
        // selector = '.page-filter#0';
        ES.elasticSearchBase();
    },
    dateRangeFilter: function() {
        fromDate = jQuery('#esSearch--datesRange_filterStart').val();
        toDate = jQuery('#esSearch--datesRange_filterEnd').val();
        dateRange = fromDate + ':' + toDate;
        pageIndex = 0;
        // selector = '.page-filter#0';
        isCustom = true;
        // validate dates
        today = new Date();
        dateStart = new Date(2000, 0, 1);
        jQuery('.esSearch--datesRange_calendarError').text('');
        var fromParse;
        var toParse;
        try {
            fromParse = jQuery.datepicker.parseDate('mm-dd-yy', jQuery('#esSearch--datesRange_filterStart').val());
            toParse = jQuery.datepicker.parseDate('mm-dd-yy', jQuery('#esSearch--datesRange_filterEnd').val());
        } catch (e) {
            console.log(e);
        }
        switch (true) {
        case ((fromParse - toParse) > 0):
            jQuery('.esSearch--datesRange_calendarError').text('End Date cannot be before Start Date');
            break;
        case (toParse - today > 0):
            jQuery('.esSearch--datesRange_calendarError').text('End Date cannot be in the future');
            break;
        case (fromParse - dateStart < 0):
            jQuery('.esSearch--datesRange_calendarError').text('Please select a date after 1 January, 2000');
            break;
        case (!fromParse || !toParse):
            jQuery('.esSearch--datesRange_calendarError').text('invalid date format');
            break;
        default:
            ES.elasticSearchBase();
        }
    },
    deleteTagButton: function() {
        jQuery('.esSearch--tagDelete').on('click', function() {
            jQuery(this).closest('li').remove();
            // reset dates on date filter delete
            if (jQuery(this).attr('id').indexOf('-') > 0) {
                isCustom = false;
                dateRange = '';
                jQuery('#esSearch--datesRange_filterStart').val('');
                jQuery('#esSearch--datesRange_filterEnd').val('');
                jQuery('#esSearch--datesRange').removeClass('hidden');
                ES.pushNewUrl();
            }
            ES.elasticSearchBase();
        });
    },
    elasticSearchBase: function() {
        //base function to reinitialize page
        ES.recountTags();
        ES.pushNewUrl();
        ES.esCall(filtersList, authorsList, within);
    },
    esCall: function(filtersList, authorsList, within) {
        // handle requests via POST
        
        ES.getRequest();
        ES.handleEmptyUrlValues();
        ES.setUrlVars();
        ES.initializeData();
        ES.postToES();

    },
    esFilter: function(e) {
        //handles filter buttons
        e.preventDefault();
        ES.getRequest();
        ES.handleEmptyUrlValues();
        ES.setUrlVars();
        if(jQuery('#esSearch--datesRange_filterStart').length) {
            ES.initializeDates();
        }
        switch (true) {
        case jQuery(this).hasClass('esSearch--searchFilters_clearFilter'):
            ES.clearFilters();
            break;
        case jQuery(this).hasClass('esSearch--searchFilters_pageFilter'):
            ES.pageFilter(this);
            break;
        case jQuery(this).hasClass('esSearch--searchFilters_pageDotFilter'):
            break;
        case jQuery(this).hasClass('esSearch--searchFilters_dateFilter'):
            ES.dateFilter(this);
            break;
        case jQuery(this).hasClass('esSearch--searchFilters_authorFilter'):
            ES.authorFilter(this);
            break;            
        case jQuery(this).hasClass('esSearch--datesRange_dateRangeFilter'):
            ES.dateRangeFilter();
            break;
        case jQuery(this).hasClass('esSearch--searchResults_sortFilter'):
            sortBy = jQuery(this).val();
            ES.elasticSearchBase();
            break;
        case jQuery(this).hasClass('esSearch--searchFilters_withinFilter'):
            within = jQuery(this).val();
            ES.elasticSearchBase();
            break;
        
        default:
            ES.addFilters(this);
        }
    },
    getRequest: function() {
        //parses url into urlParams object
        (window.onpopstate = function() {
            
            var     pl = /\+/g; // Regex for replacing addition symbol with a space
            var     search = /([^&=]+)=?([^&]*)/g;
            
            var decode = function(s) {
                return decodeURIComponent(s.replace(pl, ' '));
            };
            
            var query = window.location.search.substring(1);
            urlParams = {};
           
            //convert query into array
            var matches = query.match(search);
                       
            matches.forEach(function(smatch) {
                smatch = smatch.split('=');
                urlParams[smatch[0]] = decode(smatch[1]);
            });
        })();
    },
    handleEmptyUrlValues: function() {
        //rebuilds url based on getRequest
        if (urlParams['s'] === undefined) {
            encodeURIComponent(searchValue);
            ES.pushDefaultUrl(searchValue);
            jQuery('.esSearch--searchField').val(searchValue);
            jQuery('#esSearch--esValue').val(searchValue);
            jQuery('#esSearch--esValueHead').text(searchValue);
        }
        //handles shortcut urls
        // check for undefined values
        var indicies = ['sort', 'pageIndex', 'terms', 'tag', 'author', 'isCustom', 'date', 'id', 'wlname'];
        for (var i = 0; i < indicies.length; i++) {
            var key = indicies[i];
            if (urlParams[key] === undefined) {
                urlParams[key] = '';
                switch (key) {
                case 'sort':
                    urlParams[key] = sortByInit;
                    break;
                case 'pageIndex':
                    urlParams[key] = '0';
                    break;
                case 'terms':
                    urlParams[key] = 'all';
                    break;
                case 'isCustom':
                    urlParams[key] = 'false';
                    break;
                default:
                    break;
                }
            }
        }
        // load keys from url
        for (var u in urlParams) {
            if (urlParams.hasOwnProperty(u)) {
                urlParams[u] = urlParams[u];
            }
        }
        // set vars from urlParams
        ES.setUrlVars();
        ES.pushNewUrl();
    },
    initializeData: function() {
        data = {
            'value':     searchValue,
            'sortby':    sortBy,
            'pageindex': pageIndex,
            'within':    within,
            'template':  'general',
            'custom':    isCustom,
            'author':   authorsList,
            'daterange': dateRange,
            'id':        watchlistEdit,
            'wlname':    watchlistName
        };
        // use tax template for tags
        if ((typeof(filtersList) !== 'undefined')) {
            if (filtersList.length > 0 ) {
                filtersList = decodeURIComponent(urlParams['tag']);
                data.tag = filtersList;
                data.template = 'taxonomy';
                ES.pushNewUrl();
            }
        }
    },
    initializeDates: function() {
        if ((jQuery('#esSearch--datesRange_filterStart').val().length > 0) && (jQuery('#esSearch--datesRange_filterEnd').val().length > 0)) {
            // new dates
            fromDate = jQuery('#esSearch--datesRange_filterStart').val();
            toDate = jQuery('#esSearch--datesRange_filterEnd').val();
            dateRange = fromDate + ':' + toDate;
        }
    },
    injectResults: function() {
        // handle empty results
        if ((body['body'] === null)) {
            jQuery('#esSearch--searchResultsList').html('<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>');
            jQuery('#esSearch--datesRange').css('display', 'none');
        } else if(body['body'].length<1){
            jQuery('#esSearch--searchResultsList').html('<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>');
            jQuery('#esSearch--datesRange').css('display', 'none');
        }else {
            // inject new search results
            jQuery('#esSearch--searchResultsList').html(body['body']);
        }
        // inject new tags
        jQuery('#esSearch--tags').html(body['tags']);
        if (jQuery('#esSearch--tags li').length > 10) {
            jQuery('.esSearch--addMoreContainer').css('display', 'block');
        } else {
            jQuery('.esSearch--addMoreContainer').css('display', 'none');
        }
        
        // inject new dates, append add-more if needed
        jQuery('#esSearch--dates').html(body['dates']);
        if (jQuery('#esSearch--dates li').length > 10) {
            jQuery('.esSearch--addMoreContainer_2').css('display', 'block');
        } else {
            jQuery('.esSearch--addMoreContainer_2').css('display', 'none');
        }

        //inject authors
        jQuery('#esSearch--authors').html(body['authors']);
        if (jQuery('#esSearch--authors li').length > 10) {
            jQuery('.esSearch--addMoreContainer_3').css('display', 'block');
        } else {
            jQuery('.esSearch--addMoreContainer_3').css('display', 'none');
        }

        // get result numbers, paging, selected filters
        jQuery('#esSearch--searchResultsCount').html(body['results-count']);
        jQuery('#esSearch--searchResultsPaging').html(body['pages']);
        jQuery('#esSearch--searchHeaderFilters').html(body['tag-buttons-list']);
        // only show reset button if filters are active
        if (jQuery('#esSearch--searchHeaderFilters li').length > 0) {
            jQuery('.esSearch--searchFilters_clearFilter').css('display', 'inline-block');
        } else {
            jQuery('.esSearch--searchFilters_clearFilter').css('display', 'none');
            jQuery('#esSearch--datesRange').removeClass('hidden');
        }
    },
    pageFilter: function(current) {
        pageIndex = jQuery(current).attr('id');
        jQuery('#esSearch--searchResultsPaging').removeClass('pageActive');
        // selector = '#esSearch--searchResultsPaging #' + pageIndex;
        pageIndex = (pageIndex == 0 ? pageIndex : parseInt(pageIndex));
        ES.elasticSearchBase();
    },
    postToES: function() {
        // POST
        jQuery.post('/wp-admin/admin-ajax.php', {
            'action': 'es_search',
            'data':   data
        }, function(response) {
            body = JSON.parse(response);
            // reset page to 0 if count is shorter than pages
            if (pageIndex > parseInt(body['results-count'])) {
                pageIndex = 0;
                ES.pushNewUrl();
                ES.esCall();
            } else {
                // empty containers
                jQuery('#esSearch--searchResultsList').empty();
                jQuery('#esSearch--tags').empty();
                
                jQuery('#esSearch--dates').empty();
                jQuery('#esSearch--searchResultsCount').empty();
                jQuery('#esSearch--searchResultsPaging').empty();
                jQuery('#esSearch--searchHeaderFilters').empty();
                // parse response
                ES.injectResults();
                ES.deleteTagButton();
                document.title = 'Search Results for ' + searchValue + ' - Digital Bureau';
            }
        }).fail(function() {
            jQuery('#esSearch--searchResultsList').html('<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>');
            jQuery('#esSearch--datesRange').css('display', 'none');
        });
    },
    pushDefaultUrl: function(searchValue, sortByInit) {
        history.pushState('', '', '?s=' + searchValue + '&tag=&sort='+sortByInit+'&pageIndex=0&terms=all&isCustom=false&date=');
        ES.getRequest();
    },
    pushNewUrl: function() {
        //builds new url based on urlParams-built vars
        history.pushState('', '', '?s=' + encodeURIComponent(searchValue) + '&tag=' + encodeURIComponent(filtersList) + '&author='+encodeURIComponent(authorsList)+'&sort=' + sortBy + '&pageIndex=' + pageIndex + '&terms=' + within + '&isCustom=' + isCustom + '&date=' + dateRange + '&id=' + watchlistEdit + '&wlname=' + watchlistName);
    },
    recountTags: function() {
        // empty filters
        filtersList = '';
        authorsList = '';
        jQuery('.esSearch--tagDelete').each(function() {
           if(jQuery(this).hasClass('esSearch--author')){
                authorsList += encodeURIComponent(jQuery(this).attr('id')) + ', ';
           } else {
                filtersList += encodeURIComponent(jQuery(this).attr('id')) + ', ';
            }
        });
    },
    setUrlVars: function() {
        //sets global vars from urlParams
        searchValue = urlParams['s'];
        sortBy = urlParams['sort'];
        pageIndex = urlParams['pageIndex'];
        within = urlParams['terms'];
        filtersList = urlParams['tag'];
        authorsList = urlParams['author'];
        isCustom = urlParams['isCustom'];

        dateRange = urlParams['date'];
        watchlistEdit = urlParams['id'];
        watchlistName = urlParams['wlname'];
        jQuery('.esSearch--searchField').val(searchValue);
        jQuery('#esSearch--esValue').val(searchValue);
        jQuery('#esSearch--esValueHead').text(searchValue);
    },
    sideBar: function() {
        function esShowMore(checkbox, container, label, addcheck) {
            jQuery(checkbox).on('change', function() {
                if (!jQuery(checkbox).is(':checked')) {
                    if (jQuery(addcheck).is(':checked')) {
                        jQuery(container).removeClass('fullHeight');
                        jQuery(label).text('View More');
                    }
                }
            });
        }
        esShowMore('#esSearch--showMore', '#esSearch--tags', '.esSearch--addMoreContainer_addMoreLabel', '#esSearch--addMoreContainer_addMore');
        esShowMore('#esSearch--showMore_2', '#esSearch--dates', '.esSearch--addMoreContainer_addMoreLabel_2', '#esSearch--addMoreContainer_addMore_2');
        esShowMore('#esSearch--showMore_3', '#esSearch--authors', '.esSearch--addMoreContainer_addMoreLabel_3', '#esSearch--addMoreContainer_addMore_3');

        function esAddMore(addBtn, container, label) {
            jQuery(addBtn).on('change', function() {
                if (jQuery(addBtn).is(':checked')) {
                    jQuery(container).addClass('fullHeight');
                    jQuery(label).text('View Less');
                } else {
                    jQuery(container).removeClass('fullHeight');
                    jQuery(label).text('View More');
                }
            });
        }
        esAddMore('#esSearch--addMoreContainer_addMore', '#esSearch--tags', '.esSearch--addMoreContainer_addMoreLabel');
        esAddMore('#esSearch--addMoreContainer_addMore_2', '#esSearch--dates', '.esSearch--addMoreContainer_addMoreLabel_2');
        esAddMore('#esSearch--addMoreContainer_addMore_3', '#esSearch--authors', '.esSearch--addMoreContainer_addMoreLabel_3');

    },
};
jQuery(document).ready(function() {
    ES.init();
});
