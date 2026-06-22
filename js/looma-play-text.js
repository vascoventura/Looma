/*
Owner: VillageTech Solutions (villagetechsolutions.org)
Date: 2015 03
Revision: Looma 3.0

filename: looma-image.js
author:
Description: JS for looma-xxxx.php
 */
'use strict';

function displayText(result) {
    if (result.dn !== 'File not found') {
        var native = (result.nepali) ? result.nepali : result.data;
        var html = '<div class="english">' + result.data + '</div><div class="native" hidden>' + native + '</div>';
        $('.text-display').html(html);
        LOOMA.translate(language);
    
    }
    else $('.text-display').html('<h2>File not found</h2>');
}; //end displayText()

function removeTags(str) {
    if ((str===null) || (str===''))
        return '';
    else
        str = str.toString();
    return str.replace( /(<([^>]+)>)/ig, '');
}

$(document).ready(function() {

    // Sync the shared Speak button with the current selection as soon as this page loads.
    if (LOOMA.speak && LOOMA.speak.updateButtonAvailability) {
        LOOMA.speak.updateButtonAvailability();
    }


// SPEAK button reads only the current text selection.
    $('button.speak').off('click').click(function () {
        var selection;

        // Reuse the centralized selection helper so text pages behave like replay/pause everywhere else.
        selection = (LOOMA.speak && LOOMA.speak.getSelectedText) ? LOOMA.speak.getSelectedText() : document.getSelection().toString().trim();
        selection = $("<textarea/>").html(selection).text();
        if (!selection) return false;

        console.log('Text file: speaking "' + selection + '"');
        LOOMA.speak(selection);
        return false;
    }); //end speak button onclick function
    
    
    var div = document.getElementById('the_id');
	if (div)
        $.post("looma-database-utilities.php",
                {cmd: "openByID", collection: "text",
                    db: div.getAttribute('data-db'),
                    id: div.getAttribute('data-id')},
                displayText,
                'json'
        );
	else {
	    div = document.getElementById('the_dn');
	    if (div)
            $.post("looma-database-utilities.php",
                {cmd: "open", collection: "text", ft: "text",
                    db: div.getAttribute('data-db'),
                    dn: decodeURIComponent(div.getAttribute('data-dn'))},
                displayText,
                'json'
            );
    };
    


});
