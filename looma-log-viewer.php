<!doctype html>
<!--
Filename: looma-log-viewer.php

Author: Skip
Owner:  Looma Education Company
Revision: Looma 3
-->

<!--

NOTE: suggested enhancement: show number of unique viewers (IPs)
        cumulutive since start
        by year, or L12M, or YTD, etc
      in an info box on the Looma users map

-->



<?php $page_title = 'Looma Log Viewer';
require_once ('includes/header.php');
/* header.php imports: CSS: looma.css, looma-keyboard.css, bootstrap.css */
?>

<link rel="stylesheet" href="css/looma-log-viewer.css">

</head>

<body>
<div id="main-container-horizontal">
    <div id="fullscreen">
        <h2 id="title">Activity Log</h2>
        <div id="views">
            <button id="line" type="button" class="active" title="Activity"  aria-label="Activity"></button>
            <button id="bar"  type="button"                title="Usage"     aria-label="Usage"></button>
            <button id="map"  type="button"                title="Locations" aria-label="Locations"></button>
        </div>
        <div id="workspace">
            <canvas id="linechart"></canvas>
            <canvas id="barchart"></canvas>
            <canvas id="mapchart"></canvas>
        </div>
        <div id="linecontrols">
            <label><input type="radio" id="hours"  name="timeframe" value="hours">  By hour</label>
            <label><input type="radio" id="days"   name="timeframe" value="days" checked>  By day</label>
            <label><input type="radio" id="weeks"  name="timeframe" value="weeks"> By week</label>
            <label><input type="radio" id="months" name="timeframe" value="months">By month</label>
            <span>
                <button id="prev" type="button">Previous &#60;</button>
                <button id="next" type="button">Next &#62;</button>
            </span>
        </div>
        <div id="barcontrols">
            <label><input type="radio" id="pages"     name="bartype" value="pages" checked> Pages Used</label>
            <label><input type="radio" id="filetypes" name="bartype" value="filetypes">    Filetypes Used</label>
        </div>
    </div>
</div>

<?php include ('includes/toolbar.php'); ?>
<?php include ('includes/js-includes.php'); ?>
<script src="js/chartjs/chart.min.js"></script>
<script src="js/looma-log-viewer.js?v=<?php echo @filemtime('js/looma-log-viewer.js') ?: time(); ?>"></script>
</body>
</html>
