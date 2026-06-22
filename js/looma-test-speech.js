/*
LOOMA javascript file
Filename: looma-test-speech.js
Description: drives looma-test-speech.php — the "Reading Settings" page.
             It lets you set the speech speed, pick a separate English and
             Nepali voice for every text-to-speech engine (mimic, piper,
             browser speechSynthesis, ResponsiveVoice), and choose which
             engine Looma uses by default when it reads a text selection.

             Every setting is saved to cookies and read back automatically:
               tts-rate      — speech speed
               tts-engine    — default TTS engine
               tts-voice-en  — default engine's English voice
               tts-voice-np  — default engine's Nepali voice
             LOOMA.speak() reads these cookies, so English text is spoken
             with the English voice and Nepali text with the Nepali voice.

             The page also re-loads the saved settings on open, so it always
             shows what Looma will actually use.
Programmer name: Skip
Revision: Looma 2.0.x
 */

'use strict';

$(document).ready(function () {

    var FALLBACK_ENGINE = 'piper';

    // Per-engine selected voices — English and Nepali kept separately so each
    // language is read with the right voice. Mimic is English-only.
    var voices = {
        mimic:           { en: 'cmu_us_axb',         np: '' },
        piper:           { en: 'en_US-amy-low.onnx', np: 'ne_NP-google-x_low.onnx' },
        synthesis:       { en: '',                   np: '' },
        responsivevoice: { en: 'UK English Female',  np: 'Hindi Female' }
    };

    // The <select> id for each engine/language. Mimic has no Nepali select.
    var selectIds = {
        mimic:           { en: 'mimic-voice-en' },
        piper:           { en: 'piper-voice-en',           np: 'piper-voice-np' },
        synthesis:       { en: 'synthesis-voice-en',       np: 'synthesis-voice-np' },
        responsivevoice: { en: 'responsivevoice-voice-en', np: 'responsivevoice-voice-np' }
    };

    var DEFAULT_RATE = '0.67';   // 2/3 — Looma's default reading speed for Nepal

    // Per-engine, per-language speech speed — kept separately so each language
    // (and each engine) can have its own pace. Mimic is English-only.
    var rates = {
        mimic:           { en: DEFAULT_RATE },
        piper:           { en: DEFAULT_RATE, np: DEFAULT_RATE },
        synthesis:       { en: DEFAULT_RATE, np: DEFAULT_RATE },
        responsivevoice: { en: DEFAULT_RATE, np: DEFAULT_RATE }
    };

    // The speed <select> id for each engine/language (mirrors selectIds).
    var rateSelectIds = {
        mimic:           { en: 'mimic-rate-en' },
        piper:           { en: 'piper-rate-en',           np: 'piper-rate-np' },
        synthesis:       { en: 'synthesis-rate-en',       np: 'synthesis-rate-np' },
        responsivevoice: { en: 'responsivevoice-rate-en', np: 'responsivevoice-rate-np' }
    };

    function phrase() { return $('input#text').val(); }

    // The speed map { en, np } LOOMA.speak() expects for a given engine. Mimic
    // is English-only, so its Nepali speed mirrors English.
    function rateObj(engine) {
        return { en: rates[engine].en, np: (rates[engine].np || rates[engine].en) };
    }

    // true if SELECT #id contains an <option> with this exact value
    function hasOption(id, value) {
        return $('#' + id + ' option').filter(function () {
            return this.value === value;
        }).length > 0;
    }

    /* ---- Browser Speech: populate the voice lists from the browser ----
       speechSynthesis voices load asynchronously, hence onvoiceschanged.
       It selects whatever is stored in voices.synthesis, then syncs back so
       voices.synthesis always matches what the dropdown can actually offer. */
    function populateSynthesisVoices() {
        if (!('speechSynthesis' in window)) return;
        var all = window.speechSynthesis.getVoices() || [];
        if (!all.length) return;
        var $en = $('#synthesis-voice-en'), $np = $('#synthesis-voice-np');
        var enSel = voices.synthesis.en, npSel = voices.synthesis.np;
        $en.empty().append('<option value="">Browser default</option>');
        $np.empty().append('<option value="">Browser default</option>');
        all.forEach(function (v) {
            var opt = $('<option>').val(v.name).text(v.name + ' (' + v.lang + ')');
            if (/^ne([-_]|$)/i.test(v.lang)) $np.append(opt);
            else if (/^hi([-_]|$)/i.test(v.lang)) $np.append(opt.clone());  // Hindi ≈ Nepali fallback
            else if (/^en([-_]|$)/i.test(v.lang)) $en.append(opt);
        });
        $en.val(enSel); $np.val(npSel);
        // A stored voice the current browser doesn't have falls back to "".
        voices.synthesis.en = $en.val() || '';
        voices.synthesis.np = $np.val() || '';
    }

    /* ---- engine Speak buttons — all route through LOOMA.speak() so the page
            exercises the exact same path (and telemetry) as the rest of
            Looma. The voice is passed as { en, np }; LOOMA.speak picks the
            right one per sentence from the detected language. ---- */
    $('#mimic').click(function () {
        LOOMA.speak(phrase(), 'mimic', { en: voices.mimic.en, np: '' }, rateObj('mimic'));
    });
    $('#piper').click(function () {
        LOOMA.speak(phrase(), 'piper', { en: voices.piper.en, np: voices.piper.np }, rateObj('piper'));
    });
    $('#synthesis').click(function () {
        LOOMA.speak(phrase(), 'synthesis', { en: voices.synthesis.en, np: voices.synthesis.np }, rateObj('synthesis'));
    });
    $('#responsivevoice').click(function () {
        LOOMA.speak(phrase(), 'responsivevoice', { en: voices.responsivevoice.en, np: voices.responsivevoice.np }, rateObj('responsivevoice'));
    });

    /* ---- speech speed (per engine, per language) ----
       Each engine/language speed is saved to its own cookie
       (tts-rate-<engine>-<lang>). When the user changes the speed of whichever
       engine is currently the DEFAULT, its English/Nepali speeds are also
       mirrored to tts-rate-en / tts-rate-np — the cookies LOOMA.speak() reads
       when it speaks a text selection. */
    function bindRate(engine, lang, selectId) {
        $('#' + selectId).change(function () {
            rates[engine][lang] = this.value;
            LOOMA.setStore('tts-rate-' + engine + '-' + lang, this.value, 'cookie');
            syncDefaultRate(engine);
            trackConfigChange('rate', engine, lang);
        });
    }

    /* ---- voice pickers ---- */
    function bindVoice(engine, lang, selectId) {
        $('#' + selectId).change(function () {
            voices[engine][lang] = this.value;
            syncDefaultVoice(engine);
            trackConfigChange('voice', engine, lang);
        });
    }

    /* Report a Reading-Settings change to looma-telemetry.php so it shows up
       in OpenSearch / Prometheus behind the Grafana TTS dashboards. Best
       effort — silent on offline / endpoint errors. */
    function trackConfigChange(field, engine, lang) {
        try {
            if (!window.LOOMA || !LOOMA.telemetry || !LOOMA.telemetry.track) return;
            var def = currentDefaultEngine();
            LOOMA.telemetry.track('tts_config', {
                tts_status:   'changed',
                tts_engine:   engine || def,
                tts_voice:    (engine && lang) ? (voices[engine] && voices[engine][lang]) || '' : '',
                tts_language: lang || '',
                tts_rate:     parseFloat(
                                  (engine && lang && rates[engine] && rates[engine][lang]) ||
                                  (rates[def] && rates[def].en)
                              ) || null,
                tts_source:   'reading-settings:' + field
            });
        } catch (e) { /* swallow */ }
    }
    bindVoice('mimic', 'en', 'mimic-voice-en');
    bindVoice('piper', 'en', 'piper-voice-en');
    bindVoice('piper', 'np', 'piper-voice-np');
    bindVoice('synthesis', 'en', 'synthesis-voice-en');
    bindVoice('synthesis', 'np', 'synthesis-voice-np');
    bindVoice('responsivevoice', 'en', 'responsivevoice-voice-en');
    bindVoice('responsivevoice', 'np', 'responsivevoice-voice-np');

    bindRate('mimic', 'en', 'mimic-rate-en');
    bindRate('piper', 'en', 'piper-rate-en');
    bindRate('piper', 'np', 'piper-rate-np');
    bindRate('synthesis', 'en', 'synthesis-rate-en');
    bindRate('synthesis', 'np', 'synthesis-rate-np');
    bindRate('responsivevoice', 'en', 'responsivevoice-rate-en');
    bindRate('responsivevoice', 'np', 'responsivevoice-rate-np');

    /* ---- default TTS engine for reading text selections ----
       The Speak control button reads selections via LOOMA.speak() without
       naming an engine; LOOMA.speak() falls back to the tts-engine /
       tts-voice-en / tts-voice-np cookies written here. The checkboxes act as
       a radio group — exactly one engine is the default at any time. */

    function saveDefaultEngine(engine) {
        LOOMA.setStore('tts-engine',   engine,                  'cookie');
        LOOMA.setStore('tts-voice-en', voices[engine].en || '', 'cookie');
        LOOMA.setStore('tts-voice-np', voices[engine].np || '', 'cookie');
        // Mirror the default engine's per-language speeds to the cookies
        // LOOMA.speak() reads when reading a selection. Mimic (English-only)
        // reuses its English speed for Nepali.
        LOOMA.setStore('tts-rate-en', rates[engine].en || DEFAULT_RATE, 'cookie');
        LOOMA.setStore('tts-rate-np', rates[engine].np || rates[engine].en || DEFAULT_RATE, 'cookie');
    }
    function showDefaultEngine(engine) {
        $('.tts-default').each(function () {
            this.checked = ($(this).data('engine') === engine);
        });
    }
    function currentDefaultEngine() {
        return $('.tts-default:checked').data('engine') || FALLBACK_ENGINE;
    }
    // Keep the saved default voices in sync when the user re-picks the voice
    // of whichever engine is currently the default.
    function syncDefaultVoice(engine) {
        if (currentDefaultEngine() === engine) saveDefaultEngine(engine);
    }
    // Same idea for speed: when the user changes the speed of the engine that is
    // currently the default, re-save so the reading-selection cookies follow.
    function syncDefaultRate(engine) {
        if (currentDefaultEngine() === engine) saveDefaultEngine(engine);
    }

    $('.tts-default').change(function () {
        var engine = $(this).data('engine');
        if (this.checked) {
            showDefaultEngine(engine);     // radio-style: only one default
            saveDefaultEngine(engine);
            trackConfigChange('engine', engine);
        } else {
            this.checked = true;           // there must always be one default
        }
    });

    /* ---- restore the saved settings when the page opens ----
       so the page always reflects what LOOMA.speak() will actually use. */
    function restoreSavedSettings() {
        // Legacy single-speed cookie — used to seed any engine/language that has
        // no per-engine speed saved yet, so upgrading keeps the old speed.
        var legacyRate = LOOMA.readStore('tts-rate', 'cookie');

        // default engine, and the English/Nepali voices saved for it
        var savedEngine = LOOMA.readStore('tts-engine', 'cookie') || FALLBACK_ENGINE;
        if (!voices[savedEngine]) savedEngine = FALLBACK_ENGINE;

        var savedEn = LOOMA.readStore('tts-voice-en', 'cookie');
        var savedNp = LOOMA.readStore('tts-voice-np', 'cookie');
        if (savedEn) voices[savedEngine].en = savedEn;
        if (savedNp && savedEngine !== 'mimic') voices[savedEngine].np = savedNp;

        // push the voices into the dropdowns. Browser Speech is filled later,
        // asynchronously, by populateSynthesisVoices().
        ['mimic', 'piper', 'responsivevoice'].forEach(function (engine) {
            ['en', 'np'].forEach(function (lang) {
                var id = selectIds[engine][lang];
                if (!id) return;
                var want = voices[engine][lang];
                if (want && hasOption(id, want)) $('#' + id).val(want);
                else voices[engine][lang] = $('#' + id).val() || '';   // stale value → use what's shown
            });
        });

        // restore per-engine, per-language speeds. The speed options are static
        // (unlike Browser Speech voices), so every engine restores synchronously.
        ['mimic', 'piper', 'synthesis', 'responsivevoice'].forEach(function (engine) {
            ['en', 'np'].forEach(function (lang) {
                var id = rateSelectIds[engine][lang];
                if (!id) return;
                var saved = LOOMA.readStore('tts-rate-' + engine + '-' + lang, 'cookie') || legacyRate;
                if (saved && hasOption(id, saved)) { rates[engine][lang] = saved; $('#' + id).val(saved); }
                else rates[engine][lang] = $('#' + id).val() || DEFAULT_RATE;
            });
        });

        showDefaultEngine(savedEngine);
    }

    restoreSavedSettings();
    populateSynthesisVoices();
    if ('speechSynthesis' in window) {
        window.speechSynthesis.addEventListener('voiceschanged', populateSynthesisVoices);
    }

}); //end document.ready function
