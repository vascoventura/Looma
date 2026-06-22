<?php
//function isLoggedIn() { return (isset($_COOKIE['login']) ? $_COOKIE['login'] : null);};
require_once('includes/looma-isloggedin.php');

// NOTE: this code sending "header" must be before ANY data is sent to client=side
$loggedin = loggedIn(); if (!$loggedin) header('Location: looma-login.php');
error_log("Starting Reading Settings session. logged in as: " . $loggedin);
?>


<?php $page_title = 'Looma Reading Settings';
require_once ('includes/header.php');
define ("CLASSES", 8);

// Renders the per-engine, per-language speech-speed dropdown. Speed is now
// chosen separately for English and Nepali on each engine (the matching voice
// sits right above it), so a teacher can, say, slow Nepali down while keeping
// English at normal pace. Default 2/3 — Looma's long-standing speed for Nepal.
function ttsRateSelect($id) {
    $opts = array('0.50' => '1/2', '0.67' => '2/3', '1' => '1', '1.5' => '1.5', '2' => '2');
    echo '<select id="' . $id . '" class="tts-rate-select">';
    foreach ($opts as $val => $label) {
        $sel = ($val === '0.67') ? ' selected' : '';
        echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
    }
    echo '</select>';
}
?>

<link rel="stylesheet" href="css/looma.css">
<link rel="stylesheet" href="css/looma-test-speech.css?v=20260615-looma">

</head>

<body>
    <div id="main-container-horizontal">
      <div id="tts-test">

        <h1 class="tts-heading">Reading Settings</h1>

        <!-- phrase to speak — used to try the settings out -->
        <div class="tts-row">
            <span class="tts-label">Enter a phrase to speak:</span>
            <input type="text" id="text" value="Hello this is Looma">
        </div>

        <!-- One column per text-to-speech engine. Every column has the same
             rows — Speak button, English voice + English speed, Nepali voice +
             Nepali speed, default checkbox — so the controls line up across all
             engines. Speed is per engine AND per language (higher is faster). -->
        <div class="tts-engine-group">

            <div class="tts-engine">
                <button id="mimic" type="button" class="tts-engine-button">Speak with Mimic</button>
                <div class="tts-voice-field">
                    <label for="mimic-voice-en">English voice</label>
                    <select id="mimic-voice-en" class="tts-voice-select">
                        <option value="cmu_us_aup">Indian male (aup)</option>
                        <option value="cmu_us_awb">Scottish male (awb)</option>
                        <option value="cmu_us_bdl">US male (bdl)</option>
                        <option value="cmu_us_clb">US female (clb)</option>
                        <option value="cmu_us_aew">US male (aew)</option>
                        <option value="cmu_us_ahw">German male (ahw)</option>
                        <option value="cmu_us_axb" selected>Indian female (axb)</option>
                        <option value="cmu_us_eey">US female (eey)</option>
                        <option value="cmu_us_fem">German male (fem)</option>
                        <option value="cmu_us_gka">Indian male (gka)</option>
                        <option value="cmu_us_jmk">US male (jmk)</option>
                        <option value="cmu_us_ksp">Indian male (ksp)</option>
                        <option value="cmu_us_ljm">US female (ljm)</option>
                        <option value="cmu_us_rms">US male (rms)</option>
                        <option value="cmu_us_rxr">US male (rxr)</option>
                        <option value="cmu_us_slt">US female (slt)</option>
                        <option value="mycroft_voice_4.0">English male (mycroft)</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="mimic-rate-en">English speed</label>
                    <?php ttsRateSelect('mimic-rate-en'); ?>
                </div>
                <div class="tts-voice-field">
                    <label for="mimic-voice-np">Nepali voice</label>
                    <select id="mimic-voice-np" class="tts-voice-select" disabled>
                        <option value="" selected>Not supported — Mimic is English-only</option>
                    </select>
                </div>
                <label class="tts-default-label">
                    <input type="checkbox" class="tts-default" id="mimic-default" data-engine="mimic">
                    <span>Default for reading text selections</span>
                </label>
            </div>

            <div class="tts-engine">
                <button id="piper" type="button" class="tts-engine-button">Speak with Piper</button>
                <div class="tts-voice-field">
                    <label for="piper-voice-en">English voice</label>
                    <select id="piper-voice-en" class="tts-voice-select">
                        <option value="en_US-amy-low.onnx" selected>English (US female)</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="piper-rate-en">English speed</label>
                    <?php ttsRateSelect('piper-rate-en'); ?>
                </div>
                <div class="tts-voice-field">
                    <label for="piper-voice-np">Nepali voice</label>
                    <select id="piper-voice-np" class="tts-voice-select">
                        <option value="ne_NP-google-x_low.onnx" selected>Nepali (google)</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="piper-rate-np">Nepali speed</label>
                    <?php ttsRateSelect('piper-rate-np'); ?>
                </div>
                <label class="tts-default-label">
                    <input type="checkbox" class="tts-default" id="piper-default" data-engine="piper">
                    <span>Default for reading text selections</span>
                </label>
            </div>

            <div class="tts-engine">
                <button id="synthesis" type="button" class="tts-engine-button">Speak with Browser Speech</button>
                <div class="tts-voice-field">
                    <label for="synthesis-voice-en">English voice</label>
                    <select id="synthesis-voice-en" class="tts-voice-select">
                        <option value="">Browser default</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="synthesis-rate-en">English speed</label>
                    <?php ttsRateSelect('synthesis-rate-en'); ?>
                </div>
                <div class="tts-voice-field">
                    <label for="synthesis-voice-np">Nepali voice</label>
                    <select id="synthesis-voice-np" class="tts-voice-select">
                        <option value="">Browser default</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="synthesis-rate-np">Nepali speed</label>
                    <?php ttsRateSelect('synthesis-rate-np'); ?>
                </div>
                <label class="tts-default-label">
                    <input type="checkbox" class="tts-default" id="synthesis-default" data-engine="synthesis">
                    <span>Default for reading text selections</span>
                </label>
            </div>

            <div class="tts-engine">
                <button id="responsivevoice" type="button" class="tts-engine-button">Speak with ResponsiveVoice</button>
                <div class="tts-voice-field">
                    <label for="responsivevoice-voice-en">English voice</label>
                    <select id="responsivevoice-voice-en" class="tts-voice-select">
                        <option value="UK English Female" selected>UK English Female</option>
                        <option value="UK English Male">UK English Male</option>
                        <option value="US English Female">US English Female</option>
                        <option value="US English Male">US English Male</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="responsivevoice-rate-en">English speed</label>
                    <?php ttsRateSelect('responsivevoice-rate-en'); ?>
                </div>
                <div class="tts-voice-field">
                    <label for="responsivevoice-voice-np">Nepali voice</label>
                    <select id="responsivevoice-voice-np" class="tts-voice-select">
                        <option value="Hindi Female" selected>Hindi Female (closest to Nepali)</option>
                        <option value="Hindi Male">Hindi Male</option>
                    </select>
                </div>
                <div class="tts-rate-field">
                    <label for="responsivevoice-rate-np">Nepali speed</label>
                    <?php ttsRateSelect('responsivevoice-rate-np'); ?>
                </div>
                <label class="tts-default-label">
                    <input type="checkbox" class="tts-default" id="responsivevoice-default" data-engine="responsivevoice">
                    <span>Default for reading text selections</span>
                </label>
            </div>

        </div>

      </div>
    </div>
    <?php include ('includes/toolbar.php'); ?>
    <?php include ('includes/js-includes.php'); ?>

    <script src="js/looma-test-speech.js?v=20260615-looma"></script>

</body>

</html>
