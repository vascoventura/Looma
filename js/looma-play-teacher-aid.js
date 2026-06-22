/*
LOOMA javascript file
Filename: looma-play-teacher-aid.js
Description: supports looma-teacher-aids.php

Programmer name: Skip
Date:
Revision: 1.0
Looma version 8.0
 */

'use strict';

window.onload = function () {
    // Keep the shared Speak button state correct when teacher aids first render.
    if (LOOMA.speak && LOOMA.speak.updateButtonAvailability) {
        LOOMA.speak.updateButtonAvailability();
    }

    // SPEAK button reads only the current text selection.
    $('button.speak').off('click').click(function () {
        // Teacher aids also delegate selection handling to the shared Speak helper.
        var selection = (LOOMA.speak && LOOMA.speak.getSelectedText) ? LOOMA.speak.getSelectedText() : document.getSelection().toString().trim();
        if (!selection) return false;
        console.log('Text file: speaking "' + selection + '"');
        LOOMA.speak(selection);
        return false;
    });
};
