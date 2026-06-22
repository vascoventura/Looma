<!--
    filename: looma-pdf-viewer.php
    used by: looma-play-pdf.php [and looma-play-lesson.php and looma-edit-lesson.php]
-->
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/looma-play-pdf.css">
<script src="js/jquery.min.js">      </script>      <!-- jQuery - needed inside the iframe for looma-play-lesson.php-->

<?php
//include('includes/js-includes.php');

$filename     = (isset($_REQUEST['fn'])      ? urldecode($_REQUEST['fn'])      : "");
$filepath     = (isset($_REQUEST['fp'])      ? urldecode($_REQUEST['fp'])      : "");
$pagenum      = (isset($_REQUEST['page'])    ? urldecode($_REQUEST['page'])    : 1);
$len          = (isset($_REQUEST['len'])     ? urldecode($_REQUEST['len'])     : 10);
$lang         = (isset($_REQUEST['lang'])    ? urldecode($_REQUEST['lang'])    : "");
$zoom         = (isset($_REQUEST['zoom'])    ? urldecode($_REQUEST['zoom'])    : "page-width");

// Alternate-language file & chapter context. The PDF JS uses these to
// implement the "switch language" toolbar button while preserving the
// current page, and to log chapter time/page hits with year/chapter/subject.
$alt_filename = (isset($_REQUEST['nfn'])     ? urldecode($_REQUEST['nfn'])     : "");
$alt_filepath = (isset($_REQUEST['nfp'])     ? urldecode($_REQUEST['nfp'])     : $filepath);
$alt_pagenum  = (isset($_REQUEST['npage'])   ? urldecode($_REQUEST['npage'])   : "");
$ch_id        = (isset($_REQUEST['ch'])      ? urldecode($_REQUEST['ch'])      : "");
$ch_dn        = (isset($_REQUEST['chdn'])    ? urldecode($_REQUEST['chdn'])    : "");
$grade        = (isset($_REQUEST['grade'])   ? urldecode($_REQUEST['grade'])   : "");
$subject      = (isset($_REQUEST['subject']) ? urldecode($_REQUEST['subject']) : "");

echo '<div id="pdf" class="scroll"'  .
        '  data-fn="'    . htmlspecialchars($filename, ENT_QUOTES) .
        '" data-fp="'    . htmlspecialchars($filepath, ENT_QUOTES) .
        '" data-page="'  . htmlspecialchars($pagenum, ENT_QUOTES) .
        '" data-len="'   . htmlspecialchars($len, ENT_QUOTES) .
        '" data-lang="'  . htmlspecialchars($lang, ENT_QUOTES) .
        '" data-zoom="'  . htmlspecialchars($zoom, ENT_QUOTES) .
        '" data-nfn="'   . htmlspecialchars($alt_filename, ENT_QUOTES) .
        '" data-nfp="'   . htmlspecialchars($alt_filepath, ENT_QUOTES) .
        '" data-npage="' . htmlspecialchars($alt_pagenum, ENT_QUOTES) .
        '" data-ch="'    . htmlspecialchars($ch_id, ENT_QUOTES) .
        '" data-chdn="'  . htmlspecialchars($ch_dn, ENT_QUOTES) .
        '" data-grade="' . htmlspecialchars($grade, ENT_QUOTES) .
        '" data-subject="' . htmlspecialchars($subject, ENT_QUOTES) . '">';
    echo '</div>';
?>

<script src="js/pdfjs/pdf.min.js"></script>
<script src="js/looma-pdf-utilities.js"></script>
<script src="js/looma-play-pdf.js"></script>
