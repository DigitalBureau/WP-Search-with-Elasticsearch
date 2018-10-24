var ES = {
    init: function() {
        ES.polyfills();
        //check for POST searchvalue
        if (typeof searchValue !== 'undefined' && searchValue.length > 0) {
            ES.pushDefaultUrl(encodeURIComponent(searchValue), sortByInit);
        } else {
            searchValue = '';
        }
        try {
            if (document.querySelector('.content-area').classList.contains('esSearch')) {
                let bread = document.querySelector('.bread-current');
                if (bread) {bread.textContent = 'Search';}
                ES.esCall();
            }
        } catch (e) {
            return;
        }

        //initiate jQuery UI datepicker
        jQuery('#esSearch--datesRange_filterStart').datepicker({
            dateFormat: 'mm-dd-yy'
        });
        jQuery('#esSearch--datesRange_filterEnd').datepicker({
            dateFormat: 'mm-dd-yy'
        });

        // listeners
        if (document.querySelector('#esSearch--searchFilters')) {
            document.querySelector('#esSearch--searchFilters').addEventListener('click', function(e) {
                let selector = '#' + e.target.id;
                if (e.target.classList.contains('esSearch--searchFilters_filter')) {
                    ES.esFilter(selector, e);
                }
            });
            document.querySelector('#esSearch--searchResults_searchSort').addEventListener('change', function(e) {ES.esFilter('#esSearch--searchResults_searchSort', e);});

            document.querySelector('#esSearch--searchFilters_searchWithin').addEventListener('change', function(e) {ES.esFilter('#esSearch--searchFilters_searchWithin', e);});

            document.querySelector('#esSearch--datesRange_datesSubmit').addEventListener('click', function(e) {ES.esFilter('#esSearch--datesRange_datesSubmit', e);});

            document.querySelector('#esSearch--searchResultsPaging').addEventListener('click', function(e) {ES.esFilter('#esSearch--searchResultsPaging', e);});

            ES.sideBar();
        }


    },
    addFilters: function(current) {

        // filters to be added to the head
        filterId = current.id;
        filterText = current.textContent;

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
        ES.pushNewUrl();
        filtersList = decodeURIComponent(filtersList);
        ES.esCall(filtersList, authorsList, within);
    },
    authorFilter: function(current) {
        // filters to be added to the head
        authorId = current.replace(/#/g, '');
        authorText = document.getElementById(authorId).textContent;

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

        document.querySelector('#esSearch--searchHeaderFilters').innerHTML = '';

        document.querySelector('.hasDatepicker').value = '';

        pageIndex = 0;
        within = 'all';
        isCustom = false;

        filtersList = '';
        authorsList = '';

        document.querySelector('#esSearch--searchFilters_searchWithin').value = 'all';

        document.querySelector('#esSearch--searchResults_searchSort').value = 'recency';

        document.querySelector('#esSearch--datesRange_filterStart').value = '';
        document.querySelector('#esSearch--datesRange_filterEnd').value = '';

        document.querySelector('.esSearch--datesRange_calendarError').innerHTML = '';

        document.querySelector('#esSearch--datesRange').classList.remove('hidden');
        document.getElementById('esSearch--datesRange').style.display = null;

        ES.elasticSearchBase();
    },
    dateFilter: function(current) {

        document.querySelector('#esSearch--datesRange').classList.add('hidden');

        //date filters
        fromDate = current;

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
        ES.elasticSearchBase();
    },
    dateRangeFilter: function() {

        fromDate = document.querySelector('#esSearch--datesRange_filterStart').value;
        toDate = document.querySelector('#esSearch--datesRange_filterEnd').value;

        dateRange = fromDate + ':' + toDate;
        pageIndex = 0;
        isCustom = true;

        // validate dates
        today = new Date();
        dateStart = new Date(2000, 0, 1);

        document.querySelector('.esSearch--datesRange_calendarError').textContent = '';

        var fromParse;
        var toParse;
        try {
            fromParse = jQuery.datepicker.parseDate('mm-dd-yy', document.querySelector('#esSearch--datesRange_filterStart').value);
            toParse = jQuery.datepicker.parseDate('mm-dd-yy', document.querySelector('#esSearch--datesRange_filterEnd').value);
        } catch (e) {
            console.log(e);
        }
        let errorContainer = '.esSearch--datesRange_calendarError';
        switch (true) {
        case ((fromParse - toParse) > 0):
            document.querySelector(errorContainer).textContent = 'End Date cannot be before Start Date';
            break;
        case (toParse - today > 0):
            document.querySelector(errorContainer).textContent = 'End Date cannot be in the future';
            break;
        case (fromParse - dateStart < 0):
            document.querySelector(errorContainer).textContent = 'Please select a date after 1 January, 2000';
            break;
        case (!fromParse || !toParse):
            document.querySelector(errorContainer).textContent = 'invalid date format';
            break;
        default:
            ES.elasticSearchBase();
        }
    },
    deleteTagButton: function() {

        let tagDelete = document.querySelectorAll('.esSearch--tagDelete');

        if (tagDelete) {
            tagDelete.forEach(function(td) {
                td.addEventListener('click', function(e) {

                    // e.target.closest('li').remove();
                    let el = e.target.closest('li');
                    el.parentNode.removeChild(el);

                    if (e.target.id.indexOf('-') > 0) {
                        isCustom = false;
                        dateRange = '';
                        document.querySelector('#esSearch--datesRange_filterStart').value = '';
                        document.querySelector('#esSearch--datesRange_filterEnd').value = '';
                        document.querySelector('#esSearch--datesRange').classList.remove('hidden');

                        ES.pushNewUrl();
                    }
                    ES.elasticSearchBase();
                });
            });
        }
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
    esFilter: function(selector, event) {
        //handles filter buttons

        //escape forward slash in recent filter (#now-30d/d)
        selector = selector.replace(/[/]/g, '\\/');

        if (event.target.classList[0] !== undefined) {

            event.preventDefault();

            ES.getRequest();
            ES.handleEmptyUrlValues();
            ES.setUrlVars();

            if (document.querySelector('#esSearch--datesRange_filterStart').length >    1) {
                ES.initializeDates();
            }

            //handle special IDS
            let selectedEl = document.getElementById(selector);
            if (selectedEl === null) {
                selectedEl = document.getElementById(selector.replace(/#/g, ''));
            }

            switch (true) {

            case event.target.classList.contains('esSearch--searchFilters_clearFilter'):
                ES.clearFilters();
                break;
            case event.target.classList.contains('esSearch--searchFilters_pageFilter'):
                ES.pageFilter(event.target.id);
                break;
            case event.target.classList.contains('esSearch--searchFilters_pageDotFilter'):
                break;
            case event.target.classList.contains('esSearch--searchFilters_dateFilter'):
                ES.dateFilter(event.target.id);
                break;
            case event.target.classList.contains('esSearch--searchFilters_authorFilter'):
                ES.authorFilter(selector);
                break;
            case event.target.classList.contains('esSearch--datesRange_dateRangeFilter'):
                ES.dateRangeFilter();
                break;
            case event.target.classList.contains('esSearch--searchResults_sortFilter'):
                sortBy = event.target.value;
                ES.elasticSearchBase();
                break;
            case event.target.classList.contains('esSearch--searchFilters_withinFilter'):
                within = event.target.value;
                ES.elasticSearchBase();
                break;
            default:
                ES.addFilters(event.target);
            }

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

            //document.querySelector('.esSearch--searchField').value = searchValue;
            document.querySelector('#esSearch--esValue').value = searchValue;
            document.querySelector('#esSearch--esValueHead').value = searchValue;
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
            'author':    authorsList,
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
        if ((document.querySelector('#esSearch--datesRange_filterStart').value.length > 0) && (document.querySelector('#esSearch--datesRange_filterEnd').value.length > 0)) {
            // new dates
            fromDate = document.querySelector('#esSearch--datesRange_filterStart').value;
            toDate = document.querySelector('#esSearch--datesRange_filterEnd').value;
            dateRange = fromDate + ':' + toDate;
        }
    },
    injectResults: function() {
        // handle empty results
        if ((body['body'] === null)) {
            document.querySelector('#esSearch--searchResultsList').innerHTML = '<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>';
            document.querySelector('#esSearch--datesRange').style.display = 'none';
        } else if (body['body'].length < 1) {
            document.querySelector('#esSearch--searchResultsList').innerHTML = '<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>';
            document.querySelector('#esSearch--datesRange').style.display = 'none';
        } else {
            // inject new search results
            document.querySelector('#esSearch--searchResultsList').innerHTML = body['body'];
        }
        // inject new tags
        document.querySelector('#esSearch--tags').innerHTML = body['tags'];

        if (document.querySelectorAll('.esSearch--searchFilters_list#esSearch--tags li').length > 10) {
            document.querySelector('.esSearch--addMoreContainer').style.display = 'block';
        } else {
            document.querySelector('.esSearch--addMoreContainer').style.display = 'none';
        }

        // inject new dates, append add-more if needed
        document.querySelector('#esSearch--dates').innerHTML = body['dates'];

        if (document.querySelectorAll('.esSearch--searchFilters_list#esSearch--dates li').length > 10) {
            document.querySelector('.esSearch--addMoreContainer_2').style.display = 'block';
        } else {
            document.querySelector('.esSearch--addMoreContainer_2').style.display = 'none';
        }

        //inject authors
        document.querySelector('#esSearch--authors').innerHTML = body['authors'];

        if (document.querySelectorAll('.esSearch--searchFilters_list#esSearch--authors li').length > 10) {
            document.querySelector('.esSearch--addMoreContainer_3').style.display = 'block';
        } else {
            document.querySelector('.esSearch--addMoreContainer_3').style.display = 'none';
        }

        // get result numbers, paging, selected filters
        document.querySelector('#esSearch--searchResultsCount').innerHTML = body['results-count'];
        document.querySelector('#esSearch--searchResultsPaging').innerHTML = body['pages'];
        document.querySelector('#esSearch--searchHeaderFilters').innerHTML = body['tag-buttons-list'];

        // only show reset button if filters are active
        if (document.querySelectorAll('ul#esSearch--searchHeaderFilters li').length > 0) {
            document.querySelector('.esSearch--searchFilters_clearFilter').style.display = 'inline-block';
        } else {
            document.querySelector('.esSearch--searchFilters_clearFilter').style.display = 'none';
            document.querySelector('#esSearch--datesRange').classList.remove('hidden');
        }
    },
    pageFilter: function(current) {
        pageIndex = current;
        document.querySelector('#esSearch--searchResultsPaging').classList.remove('pageActive');
        pageIndex = (pageIndex == 0 ? pageIndex : parseInt(pageIndex));
        ES.elasticSearchBase();
    },
    postToES: function() {
        let url = '/wp-admin/admin-ajax.php?action=es_search';
        var request = new XMLHttpRequest();

        request.open('POST', url, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8;');
        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                body = JSON.parse(this.response);
                // reset page to 0 if count is shorter than pages
                if (pageIndex > parseInt(body['results-count'])) {
                    pageIndex = 0;
                    ES.pushNewUrl();
                    ES.esCall();
                } else {
                // empty containers
                    document.querySelector('#esSearch--searchResultsList').innerHTML = '';
                    document.querySelector('#esSearch--tags').innerHTML = '';

                    document.querySelector('#esSearch--dates').innerHTML = '';
                    document.querySelector('#esSearch--searchResultsCount').innerHTML = '';
                    document.querySelector('#esSearch--searchResultsPaging').innerHTML = '';
                    document.querySelector('#esSearch--searchHeaderFilters').innerHTML = '';
                    // parse response
                    ES.injectResults();
                    ES.deleteTagButton();
                    document.title = 'Search Results for ' + searchValue + ' - Digital Bureau';
                }
            } else {
                document.querySelector('#esSearch--searchResultsList').innerHTML = '<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>';
            }
        };
        request.onerror = function() {
            document.querySelector('#esSearch--searchResultsList').innerHTML = '<h3 id="esSearch--noResults">Sorry, but nothing matched your search terms. <br>Please try again using different search criteria.</h3>';
        };

        var formBody = [];
        for (var property in data) {
            if (data[property]) {
                var encodedKey = encodeURIComponent(property);
                var encodedValue = encodeURIComponent(data[property]);
                formBody.push(encodedKey + '=' + encodedValue);
            }
        }
        formBody = formBody.join('&');
        request.send(formBody);
    },
    pushDefaultUrl: function(searchValue, sortByInit) {
        history.pushState('', '', '?s=' + searchValue + '&tag=&sort=' + sortByInit + '&pageIndex=0&terms=all&isCustom=false&date=');
        ES.getRequest();
    },
    pushNewUrl: function() {
        //builds new url based on urlParams-built vars
        history.pushState('', '', '?s=' + encodeURIComponent(searchValue) + '&tag=' + encodeURIComponent(filtersList) + '&author=' + encodeURIComponent(authorsList) + '&sort=' + sortBy + '&pageIndex=' + pageIndex + '&terms=' + within + '&isCustom=' + isCustom + '&date=' + dateRange + '&id=' + watchlistEdit + '&wlname=' + watchlistName);
    },
    recountTags: function() {
        // empty filters
        filtersList = '';
        authorsList = '';

        let btns = document.querySelectorAll('.esSearch--tagDelete');

        for (let i = 0; i < btns.length; i++) {
            if (btns[i].classList.contains('esSearch--author')) {
                authorsList += encodeURIComponent(btns[i].id) + ', ';
            } else if (!btns[i].classList.contains('esSearch--daterange')) {
                filtersList += encodeURIComponent(btns[i].id) + ', ';
            }
        }
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

        if (document.querySelector('#esSearch--searchField')) {
            document.querySelector('#esSearch--searchField').value = searchValue;
        }
        document.querySelector('#esSearch--esValue').value = searchValue;
        document.querySelector('#esSearch--esValueHead').textContent = searchValue;
    },
    sideBar: function() {
        function esShowMore(checkbox, container, label, addcheck) {
            document.querySelector(checkbox).addEventListener('change', function(e) {
                let checked = document.querySelector(checkbox).checked;

                if (!checked) {
                    let adchk = document.querySelector(addcheck).checked;

                    if (adchk) {
                        document.querySelector(container).classList.remove('fullHeight');
                        document.querySelector(label).textContent = 'View More';
                    }
                }
            });
        }

        function esAddMore(addBtn, container, label) {
            document.querySelector(addBtn).addEventListener('change', function(e) {
                let checked = document.querySelector(addBtn).checked;

                if (checked) {
                    document.querySelector(container).classList.add('fullHeight');
                    document.querySelector(label).textContent = 'View Less';
                } else {
                    document.querySelector(container).classList.remove('fullHeight');
                    document.querySelector(label).textContent = 'View More';
                }
            });
        }

        //list dropdowns
        esShowMore('#esSearch--showMore', '#esSearch--tags', '.esSearch--addMoreContainer_addMoreLabel', '#esSearch--addMoreContainer_addMore');
        esShowMore('#esSearch--showMore_2', '#esSearch--dates', '.esSearch--addMoreContainer_addMoreLabel_2', '#esSearch--addMoreContainer_addMore_2');
        esShowMore('#esSearch--showMore_3', '#esSearch--authors', '.esSearch--addMoreContainer_addMoreLabel_3', '#esSearch--addMoreContainer_addMore_3');

        esAddMore('#esSearch--addMoreContainer_addMore', '#esSearch--tags', '.esSearch--addMoreContainer_addMoreLabel');
        esAddMore('#esSearch--addMoreContainer_addMore_2', '#esSearch--dates', '.esSearch--addMoreContainer_addMoreLabel_2');
        esAddMore('#esSearch--addMoreContainer_addMore_3', '#esSearch--authors', '.esSearch--addMoreContainer_addMoreLabel_3');

    },
    polyfills: function() {
        //jQuery closest polyfill for IE
        (function (ElementProto) {
            if (typeof ElementProto.matches !== 'function') {
                ElementProto.matches = ElementProto.msMatchesSelector || ElementProto.mozMatchesSelector || ElementProto.webkitMatchesSelector || function matches(selector) {
                    var element = this;
                    var elements = (element.document || element.ownerDocument).querySelectorAll(selector);
                    var index = 0;

                    while (elements[index] && elements[index] !== element) {
                        ++index;
                    }

                    return Boolean(elements[index]);
                };
            }

            if (typeof ElementProto.closest !== 'function') {
                ElementProto.closest = function closest(selector) {
                    var element = this;

                    while (element && element.nodeType === 1) {
                        if (element.matches(selector)) {
                            return element;
                        }

                        element = element.parentNode;
                    }

                    return null;
                };
            }
        })(window.Element.prototype);
        //forEach polyfill for nodelist
        (function () {
            if ( typeof NodeList.prototype.forEach === 'function' ) return false;
            NodeList.prototype.forEach = Array.prototype.forEach;
        })();
    }
};
document.addEventListener('DOMContentLoaded', function() {
    ES.init();
});
