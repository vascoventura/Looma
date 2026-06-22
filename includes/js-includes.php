<!-- in production, the below should all be MINIMIZED versions -->
    <script src="js/jquery3.6.0.min.js">      </script>

    <script src="js/looma-utilities.js?v=<?php echo @filemtime('js/looma-utilities.js') ?: time(); ?>"> </script>     <!-- Looma utility functions -->
<!--<script src="js/looma-utilities.min.js"> </script>   -->    <!-- Looma utility functions -->

    <script src="js/looma.js">           </script>      <!-- Looma common page functions -->
    <script src="js/looma-screenfull.js"></script>      <!-- implements FULLSCREEN mode  -->
    <script src="js/looma-keyboard.js">  </script>      <!-- adds a KEYBOARD button if the page has any inputs -->
    <script src="js/looma-telemetry.js"> </script>      <!-- learning telemetry → looma-telemetry.php (Mongo + OTel) -->
    <script>
        // RUM bootstrap. The collector lives behind the same hostname as the web
        // app on port 4318 (otlp/http). Override with window.LOOMA_OTEL_ENDPOINT
        // before this script if your deployment exposes it elsewhere.
        window.LOOMA_RUM_SERVICE = 'looma-web-rum';
        window.LOOMA_ENV = '<?php echo getenv("LOOMA_ENV") ?: "local"; ?>';
        window.LOOMA_HTTP_STATUS = <?php echo (int) (function_exists("http_response_code") ? http_response_code() : 200); ?>;
    </script>
    <script src="js/looma-otel-rum.js"> </script>       <!-- browser RUM → otel-collector :4318 -->
    <script src="js/looma-assistant-button.js?v=<?php echo @filemtime('js/looma-assistant-button.js') ?: time(); ?>"></script>
    <script src="js/looma-word-selection.js?v=<?php echo @filemtime('js/looma-word-selection.js') ?: time(); ?>"></script>     <!-- Inline word selection with definition card -->

    <!-- ResponsiveVoice (cloud TTS) is NOT loaded on page load. It is fetched
         LAZILY by LOOMA.speak.ensureResponsiveVoice() (js/looma-utilities.js) the
         first time the user presses the Speak button with the ResponsiveVoice
         engine selected, so pages that never use it make no request to
         responsivevoice.org. We only publish the script URL here (key included);
         the loader reads it. LOOMA.speak() degrades gracefully if it can't load. -->
    <script>window.LOOMA_RESPONSIVEVOICE_SRC = 'https://code.responsivevoice.org/responsivevoice.js?key=r2w8pU3y';</script>