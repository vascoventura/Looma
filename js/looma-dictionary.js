/*
Author: Grant Dumanian, Jayden Kunwar, Mahir Arora
Owner: VillageTech Solutions (villagetechsolutions.org)
Date: 2015 07, 2018 07
Revision: Looma 3.0

filename: looma-dictionary.js
Description:
*/
'use strict';

var displayArea;

    function italicize (def) {  //put part-of-speech in italics
        def = def.replace('pronoun', '<i>pronoun</i>');
        def = def.replace('preposition', '<i>preposition</i>');
        def = def.replace('noun', '<i>noun</i>');
        def = def.replace('verb', '<i>verb</i>');
        def = def.replace('adj.', '<i>adjective</i>');
        def = def.replace('adverb', '<i>adverb</i>');
        def = def.replace('adv.', '<i>adverb</i>');
        def = def.replace('contraction', '<i>contraction</i>');
        return def;
    };

    function OK(html) {
        $('#definitionDisplay').html(html);
    
        var img = document.getElementById('definitionThumb');
        if (img) {
            
            //The MODAL - opens window of image when clicked
            var modal = document.getElementById('definitionImage');
            // Get the image and insert it inside the modal
            var modalImg = document.getElementById("img01");
            img.onclick = function () {
                modal.style.display = "block";
                modalImg.src = this.src;
            };
            // Get the <span> element (x) that closes the modal
            var span = document.getElementsByClassName("close")[0];
            span.onclick = function () {
                modal.style.display = "none";
            };
        }
    
        if ($('#definition').text().length > 100) $('#definition').css('font-size', '0.75em');
        if ($('#rwdef').text().length > 100)      $('#rwdef').css('font-size', '0.75em');

        // If the word is not in Looma's own dictionary, fetch a definition
        // from the internet in the background and add it below.
        loomaOnlineFallback();
    }; //end OK()

    // True when the rendered result has no usable definition — either the
    // server returned "Word not found" (defHTML adds a #dict-suggestions
    // block) or the dictionary entry simply has an empty definition.
    function definitionMissing() {
        if ($('#dict-suggestions').length) return true;
        var d = $('#definition').text().trim().toLowerCase();
        return d === '' || d === 'word not found.' || d === 'word not found';
    }

    // Background online lookup for the Dictionary page. English only — there
    // is no reliable free Nepali dictionary API. This NEVER speaks; it only
    // adds text to the page.
    function loomaOnlineFallback() {
        if (typeof language !== 'undefined' && language !== 'english') return;
        if (!definitionMissing()) return;

        var word = ($('#input').val() || '').trim();
        if (!word) return;
        if (!window.LOOMA || typeof LOOMA.onlineLookup !== 'function') return;

        var $box = $('<div id="online-definition" class="online-pending"></div>')
            .text('Looking up "' + word + '" online…');
        $('#definitionDisplay').append($box);

        LOOMA.onlineLookup(word, function (result) {
            $box.removeClass('online-pending')
                .empty()
                .append($('<strong></strong>').text('Online definition: '))
                .append($('<span></span>').text(result.def));
        }, function () {
            $box.removeClass('online-pending').addClass('online-none')
                .text('No online definition found for "' + word + '".');
        });
    }

    //This is the fail function, used when the Looma database can not find the word
    function fail(jqXHR, textStatus, errorThrown) {
        LOOMA.alert("Dictionary lookup failedx");
    } //end FAIL

function getDefinition(event) {
    var input;
    event.preventDefault();
    if (language === 'english') {
         input = document.getElementById("input").value;
        try {
            fetch('looma-telemetry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
                body: JSON.stringify({ event: 'dictionary', activity: 'lookup_en', language: 'en' })
            }).catch(function () {});
        } catch (e) {}
        LOOMA.define(input, OK, fail);
    }
    else                  {
         input = document.getElementById("input").value;
        try {
            fetch('looma-telemetry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
                body: JSON.stringify({ event: 'dictionary', activity: 'lookup_np', language: 'np' })
            }).catch(function () {});
        } catch (e) {}
        LOOMA.reversedefine(input, OK, fail);
    }
    return false;
}; //end getDefinition()

$(document).ready (function() {
    
    var elem = document.getElementById("lookup");
    elem.addEventListener('submit', getDefinition);
    
    $('#input').focus(
        function(){$('#npinput').val('')});
 //   $('#npinput').focus(
   //     function(){$('#input').val('')});
 
    // SPEAK button will say the word, unless text is selected, in which case, it will speak the selected text
    $('button.speak').off('click').click(function () {
        var selection, word, nepali, pos, definition, rwdef, toSpeak;
    
        selection = document.getSelection().toString();
        word =      document.getElementById('input').value;
        nepali =    document.getElementById('nepali').innerText;
        pos =       document.getElementById('partOfSpeech').innerText;
        if (document.getElementById('definition')) definition = document.getElementById('definition').innerText;
        if (definition && definition != "") {
            definition = definition.replace("(v)", " verb ");
            definition = definition.replace("(n)", " noun ");
        };
        rwdef = document.getElementById('rwdef'); if (rwdef) definition += ', ' + rwdef.innerText;
    
        if (selection) {
            LOOMA.speak(selection);
        } else {
            LOOMA.speak(word + '. ' + nepali + '. ' + definition);
            
            //LOOMA.speak(nepali);
            //LOOMA.speak(definition);
        }
        
        $('#input').trigger('focus');
        
    }); //end speak button onclick function
    
    toolbar_button_activate("dictionary");
    
});
