/*

filename: looma-play-pdf.js

Owner: VillageTech Solutions (villagetechsolutions.org)
Date: 2020 03
Revision: Looma 2.0.0
Author: Skip
Description: display layer built on pdf.js for showing chapters in PDFs
 */

"use strict";

$(window).resize(async function() {
    await drawMultiplePages(pdfdoc, startPage, endPage).promise;
});

function getPdfSelectionText() {
    var selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return "";

    var range = selection.getRangeAt(0);
    var fragment = range.cloneContents();
    var container = document.createElement("div");
    container.appendChild(fragment);

    // PDF.js splits text into many spans, so cloned HTML preserves word breaks for TTS better than a raw selection string.
    var text = container.innerText || container.textContent || selection.toString() || "";

    return text
        .replace(/\|/g, " ")
        .replace(/\s+/g, " ")
        .trim();
}

window.onload = function() {

  $('button.speak').off('click').click(function(){
      // PDFs need their own extractor so the central TTS logic receives readable text instead of broken span fragments.
      var toString = getPdfSelectionText();

      var $def = $('#definition');
      if ($def) toString += " " + $def.text();

      console.log ('selected text to speak: ', toString);
      LOOMA.speak(toString);
      return false;
    });

  $('button.lookup').off('click').click(function(){
       // var toString = selection;

      var toString = getPdfSelectionText();

      console.log ('selected text to lookup: "', toString, '"');
        // LOOMA.lookupWord(toString);
      if ($('#pdf').data('lang') === 'np') {
        //  toString = convertPreeti(toString);
             LOOMA.popupDefinition(toString.split(' ')[0], 15, 'np');

      } else LOOMA.popupDefinition(toString.split(' ')[0], 15, 'en');
      return false;
    });

 /*
    document.addEventListener('selectionchange',
        (e)=>{
        if ($('#pdf').data('lang') === 'np')
             selection = convertPreeti(window.getSelection().toString());
        else selection =               window.getSelection().toString();
        return false;
    });
*/

    $('#zoom-dropdown').removeClass('hide');

// *********  PAGE controls ***************

    enablePageControls();

// *********  ZOOM controls ***************

    enableZoomControls();

      $('#zoom-dropdown').hide();

    $('#zoom-btn').click ( function(){$('#zoom-dropdown').toggle();});

    $('.zoom-item').click( /*async*/ function() {
            var zoom = $(this).data('zoom');
            var level = $(this).data('level');
        /*await*/ setZoom(level);
            $('#zoom-btn').text(zoom);
            $('#zoom-dropdown').hide();
        });

// *********  FULLSCREEN controls ***************

    $('#fullscreen-control').click(function () {
        if (document.fullscreenElement) {
            //currentScale = currentScale * 1 / 1.08;
            $('#pdf').css("transform","scale(1.00");
            //$('#pdf').css( overflowX, "auto");
        }
        else {
            //currentScale = currentScale * 1.08;
           // $('#pdf').css("transform","scale(1.25");
            //$('#pdf').css( overflowX, "none");
        }
        LOOMA.toggleFullscreen;

                //NOTE: maybe dont have to re-draw?? seems to work fine without
                // drawMultiplePages(pdfdoc, startPage, endPage);

        return false;
    });

// *********  SCROLL controls ***************

    enableScrollDetect();

    // the SETINTERVAL call de-bounces scroll events, so the handler "getScrolledPage" is only called every "wait" msec
    setInterval(function() {
        if ( didScroll ) {getScrolledPage();didScroll = false; }
        }, 1000);

    $('#find').change(); //FIND operation not implemented this version

    //playPDF();

    playPDF($('#pdf')[0], $('#pdf').data('fn'),
                          $('#pdf').data('fp'),
                          $('#pdf').data('page'),
                          $('#pdf').data('len'),
                          $('#pdf').data('lang'),
                          $('#pdf').data('zoom') );

    toolbar_button_activate("library");

// *********  TELEMETRY: chapter time tracking ***************
    try {
        var $pdf = $('#pdf');
        var initialMeta = {
            chapter_id:   $pdf.data('ch')      || null,
            chapter_name: $pdf.data('chdn')    || null,
            grade:        ($pdf.data('grade')  || '').toString() || null,
            subject:      ($pdf.data('subject')|| '').toString().toLowerCase() || null,
            language:     $pdf.data('lang')    || null,
            page:         $pdf.data('page')    || null,
        };
        if (window.LOOMA && LOOMA.telemetry) {
            LOOMA.telemetry.track('page', Object.assign({ page: 'pdf' }, initialMeta));
            LOOMA.telemetry.startChapterTimer(initialMeta);
        }
    } catch (e) { /* ignore */ }

// *********  LANGUAGE SWITCH (preserves current page) ***************
//   Reuses the existing global #translate toolbar button. Bound with .on()
//   so the keyword-translation handler in looma.js still runs first; we just
//   piggy-back on the same click to swap the PDF file for its alternate
//   language while keeping the current page number.
    $('#translate').on('click.loomaPdfLang', function () {
        var $pdf = $('#pdf');
        var altFn = $pdf.data('nfn');
        if (!altFn) return; // no alternate-language version → leave the click alone

        var keepPage = (typeof currentPage !== 'undefined' && currentPage) ? currentPage
                       : ($pdf.data('page') || 1);
        var altFp   = $pdf.data('nfp')   || $pdf.data('fp');
        var curLang = ($pdf.data('lang') || '').toString().toLowerCase();
        var newLang = (curLang === 'np') ? 'en' : 'np';

        if (window.LOOMA && LOOMA.telemetry) {
            LOOMA.telemetry.track('lang_switch', {
                chapter_id: $pdf.data('ch') || null,
                from: curLang || null, to: newLang, page: keepPage,
            });
            LOOMA.telemetry.stopChapterTimer();
        }

        window.location = 'pdf?fn=' + encodeURIComponent(altFn) +
                 '&fp='   + encodeURIComponent(altFp) +
                 '&lang=' + encodeURIComponent(newLang) +
                 '&zoom=' + encodeURIComponent($pdf.data('zoom') || '2.3') +
                 '&len='  + encodeURIComponent($pdf.data('len')  || 100) +
                 '&page=' + encodeURIComponent(keepPage) +
                 '&nfn='  + encodeURIComponent($pdf.data('fn')   || '') +
                 '&nfp='  + encodeURIComponent($pdf.data('fp')   || '') +
                 '&npage=' + encodeURIComponent($pdf.data('page') || '') +
                 '&ch='   + encodeURIComponent($pdf.data('ch')   || '') +
                 '&chdn=' + encodeURIComponent($pdf.data('chdn') || '') +
                 '&grade='+ encodeURIComponent($pdf.data('grade')|| '') +
                 '&subject=' + encodeURIComponent($pdf.data('subject') || '');
    });

};
