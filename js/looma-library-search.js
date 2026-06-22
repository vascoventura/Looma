/*
author: Skip, Bo
Owner: VillageTech Solutions (villagetechsolutions.org)
Date: 2015 03, 2017 07
Revision: Looma 3.0

filename: looma-library-search.js
Description:
 */

'use strict';

var resultColumn = 1, row = 0, maxButtons = 3;
var resultsShown = 0;
var resultsTotal = 0;
var searchName = 'library-search';
var searchName = 'library-search';
var result_array;
var chapResults;
var actResults;

////////////////////////////////
/////  clearResults()    /////
////////////////////////////////
function clearResults(results) {
    $('#results-div').empty();
    $("#top").hide();
    $("#more").hide();
    resultsShown = 0;
    resultsTotal = 0;
} //end clearResults()

////////////////////////////////
/////  displayResults()    /////
////////////////////////////////
    function displayResults(results) {
    
    // array 'results' holds a 'count' field with number of results
    //                   and a 'list' array with the mongoIDs of all the results
    
    var $display = $('#results-div').empty().append('<h2 style="margin-bottom: 0;">Search Results:</h2>');

    resultsTotal = results['count'];

    // Did-you-mean: if we got zero results AND the backend returned spelling
    // suggestions, surface them as clickable buttons that re-submit the form.
    if (resultsTotal === 0 && Array.isArray(results['suggestions']) && results['suggestions'].length) {
        var $sug = $('<div class="search-suggestions" style="margin:10px 0;font-size:1em"/>');
        $sug.append($('<span/>').text('No matches. Did you mean: '));
        results['suggestions'].forEach(function (term, i) {
            var $btn = $('<button type="button" class="suggestion-btn"/>')
                .text(term)
                .css({ margin: '0 4px', padding: '3px 10px', cursor: 'pointer', borderRadius: '4px' })
                .on('click', function () {
                    $('#search-term').val(term);
                    $('#search').trigger('submit');
                });
            $sug.append($btn);
        });
        $display.append($sug);
    }

   resultColumn = 1;
    
        result_array = [];
        result_array['activities'] = [];
        result_array['chapters']  = [];

    results['list'].forEach(function(e) {
         //   if (e['ft'] == 'chapter') result_array['chapters'].push(e);
         //   else
                result_array['activities'].push(e);
    });

    $("#top").show();
    resultsShown = Math.min(resultsShown + pagesz, resultsTotal);
    if (resultsShown < resultsTotal) $("#more").show();
    
    chapResults = result_array['chapters'].length;
    actResults = result_array['activities'].length;
    
    $display.append("<p> Activities(<span id='count'>" + results['count'] + "</span>)</p>");
    
    $display.append('<table id="results-table"></table>');

    if(actResults != 0)
        displayActivities(result_array['activities'], '#results-table', 1, pagesz);
  //  if(chapResults != 0)
    //    displayChapters(result_array['chapters'], '#results-table');
     
    
    $display.show();
    //translateSearchResults();  // translate search results to current UI language
    
        // $('#results-div').off('click','button.play').on('click', "button.play", playActivity);
   
} //end displayResults()

////////////////////////////////
/////  displayMoreResults()    /////
////////////////////////////////
function displayMoreResults(results) {
    $("#top").show();
    displayActivities(result_array['activities'], '#results-table', resultsShown+1, pagesz);
    if (resultsShown < resultsTotal) $("#more").show(); else $("#more").hide();
} //end displayMoreResults()

///////////////////////////////////
/////  displayActivities()    /////
///////////////////////////////////
function displayActivities(results, table, next, count) {
    // append items in array 'results' into display div 'table' starting at 'next' and adding 'count' new items
    
    var last = Math.min(next+count-1, actResults);
    for (var i=next-1; i <= last-1; i++) {
        
        if(resultColumn % maxButtons == 1){
                row++;
                $(table).append("<tr id='result-row-" + row + "'></tr>");
            }
            //console.log(results[i]);
            $('#result-row-' + row).append("<td id='query-result-" + resultColumn + "'></td>");
    
        var mongoID = (results[i]['mongoID']) ? (results[i]['mongoID']['$id'] || results[i]['mongoID']['$oid']) : "";
        var db = results[i]['db'];
       // var mongoID = results[i]['mongoID']['$id'] || results[i]['mongoID']['$oid'];
            LOOMA.makeActivityButton(results[i],results[i]['_id']['$id'] || results[i]['_id']['$oid'],
                                      db, mongoID, '#query-result-' + resultColumn);
            resultColumn ++;
           };
    resultsShown = last;  //careful: can exceed resultsTotal
    
} //end displayActivities()

/////////////////////////////////
/////  displayChapters()    /////
/////////////////////////////////
function displayChapters(results, table) {
    var result = 1, row = 0, maxButtons = 3;

    $.each(results, function(index, value) {
        if(result % maxButtons == 1){
            row++;
            $(table).append("<tr id='result-row-" + row + "'></tr>");
        }
        $('#result-row-' + row).append("<td id='query-result-" + result + "'></td>");
        LOOMA.makeChapterButton(value['_id'], '#query-result-' + result);
        result ++;
    });
    
} //end displayChapters()


function playActivity(event) {
    var button = event.currentTarget;
    
    //event.target may be the contained IMG or SPAN, not the BUTTON,
    //so use event.currentTarget which is always the element that the event is attached to,
    //even if a containing element gets the click
    
    //could instead catch the event in BUTTON during capture phase and do event.endPropagation() to keep it from propogating
    // something like $("button.play").on('click', playActivity, true);
    // and, event.stopPropogation(); in the playActivity() function
    
    saveSearchState();  // saves scroll position and search form settings
    LOOMA.playMedia(button);
}

function translateSearchResults() {
    $('button.activity').each(function(){
        var ndn = ($(this).data('ndn') && $(this).data('ndn') !== 'undefined') ? $(this).data('ndn') : $(this).data('dn');
        var dn = ($(this).data('dn') && $(this).data('dn') !== 'undefined') ? $(this).data('dn') : $(this).data('ndn');
        if (language === 'native') {
             $(this).children('span.dn').text(ndn);
        }
        else $(this).children('span.dn').text(dn) ;
    });
};

$(document).ready (function() {
    
    var pagesz = 24;    //NOTE (5/2025) pgaesz is ignored by looma-databae-utilities.php cmd=search
                    // the client code [this file] can decide how and when to paginate
    
    $("#search").find("#pagesz").val(pagesz);
    //$("#search").find("#pageno").val(pageno);
    
    // format the TYPES ckeckboxes by inserting a <br>
    $("#type-div > span:nth-child(7)").after("<br/>");
    
    //$("button.play").click().off().click(playActivity);
    $('#results-div').on('click', "button.play", playActivity);
    
    $("#toggle-database").click(function(){saveSearchState(); window.location = "looma-library.php";});//'fade', {}, 1000
    
    $("#top").hide();
    $("#more").hide();
    
    $("#top").click(function(){
        $("button.zeroScroll").click(function() { LOOMA.setStore ('libraryScroll', 0, 'session');});
        $("#main-container-horizontal").scrollTop(LOOMA.readStore('libraryScroll',    'session'));
        $("#search-term").focus();
    });
    
  /*  $("#more").click(function(){
        pagesz = 24;
        sendSearchRequest ($("#search"), displayMoreResults);
    });
 */
    $("#more").click(function(){  // changed 05 2025 to do client-side pagination of results display
        //pagesz = 24;
        //sendSearchRequest ($("#search"), displayMoreResults);
        displayMoreResults();
    });
    
    // Missing-thumbnail fallback is handled per-image in LOOMA.makeActivityButton()
    // (looma-utilities.js). A delegated handler cannot be used here: the `error`
    // event does not bubble, so it must be bound on each <img> directly.

    $("button.zeroScroll").click(function() { LOOMA.setStore ('libraryScroll', 0, 'session');});
    $("#main-container-horizontal").scrollTop(LOOMA.readStore('libraryScroll',    'session'));
    
    // when translate flag is clicked - translate search results to new UI language
    $('#translate').click(translateSearchResults);
    
});
