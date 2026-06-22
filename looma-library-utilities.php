<?php
/*
 *
Filename: looma-library-utilities.php
Description: server side code for Looma Library - navigate folders and list contents
Programmer : Skip
Owner: VillageTech Solutions (villagetechsolutions.org)
Date: Apr 2017
Revision: 1.0
*/

/*****************************/
/****   main code here    ****/
/*****************************/

/////////////////////////////////
// call with looma-library-utilities.php?cmd=cmdname&fp=filepath

//commands supported:
//  open
//  list
//  search_folder [to be implemented]
////////////////////////////////

require_once ('includes/mongo-connect.php');
require_once ('includes/otel.php');

if (function_exists('looma_trace_page')) {
    looma_trace_page('library-utilities', [
        'cmd'  => $_REQUEST['cmd'] ?? null,
        'fp'   => $_REQUEST['fp'] ?? null,
    ]);
}

    function name($file) { $f = new SplFileInfo($file);
                           return $f->getBasename();
                         }

function parent($file) { global $content;
                             $f = new SplFileInfo($file);
                             if ($f == $content) return $content;
                             else return $f->getPath(); }

function isRegistered($name, $dir) {
                 global $activities_collection;

                 $query = array('fn' => $name,'fp' => $dir . '/');
                 $projection = array('_id' => 0, 'fn' => 1, 'dn' => 1,  'ch_id' => 1);
                 $activity = function_exists('looma_trace_with')
                     ? looma_trace_with('mongo.library.check_registration', [
                         'filename' => $name,
                         'directory' => $dir,
                       ], function() use ($activities_collection, $query) {
                           return mongoFindOne($activities_collection, $query);
                       })
                     : mongoFindOne($activities_collection, $query);

                 if (! $activity) {  //some legacy activities dont have 'fp' set, look for these if fp + fn search fails
                    $query = array('fn' => $name,  'fp' => array('$exists'=>false));
                    $activity = function_exists('looma_trace_with')
                        ? looma_trace_with('mongo.library.check_registration_legacy', [
                            'filename' => $name,
                          ], function() use ($activities_collection, $query) {
                              return mongoFindOne($activities_collection, $query);
                          })
                        : mongoFindOne($activities_collection, $query);
                  }

                 if ($activity)  {
                     $response = array('reg' => true, 'dn'=>$activity['dn']);
                     if (isset( $activity['ch_id'])) $response['ch_id'] = $activity['ch_id'];
                    return $response;
                  }
                 else return array('reg' => false);
          return ;
            }

function make_activity($item) {
           global $activities_collection;
           $activity = mongoInsert($activities_collection, $item);
           echo "new activity" . $item -> dn . "\n";
    } //end make_activity()


///////// MAIN CODE ///////////

    $content = "../content";

    if ( isset($_REQUEST["cmd"]) ) {$cmd =  $_REQUEST["cmd"];
    //accepted commands are "open", "list""  to ADD: "search_folder""

    if ( isset($_REQUEST["fp"]) ) $dir = $_REQUEST["fp"]; else $dir = $content;

    switch ($cmd)
    {
/// open
        case "open":
            // return {dir: foldername, parent: "..", children: [subfolder, subfolder,...]}

            $list = function_exists('looma_trace_with')
                ? looma_trace_with('filesystem.library.open', [
                    'directory' => $dir,
                  ], function() use ($dir) {
                    $result = array();
                    foreach (new DirectoryIterator($dir) as $fileInfo) {
                        $file =  $fileInfo->getFilename();
                        if   ( $file[0] !== "." &&
                             ($fileInfo -> isDir()) &&
                             (! file_exists($dir . "/" . $file . "/hidden.txt")))
                        {array_push($result,$file); }
                    }
                    return $result;
                  })
                : (function() use ($dir) {
                    $result = array();
                    foreach (new DirectoryIterator($dir) as $fileInfo) {
                        $file =  $fileInfo->getFilename();
                        if   ( $file[0] !== "." &&
                             ($fileInfo -> isDir()) &&
                             (! file_exists($dir . "/" . $file . "/hidden.txt")))
                        {array_push($result,$file); }
                    }
                    return $result;
                  })();
            $parent = parent($dir);
            $parentname = name($parent);
            echo json_encode(array('dir'=>$dir,'parent'=>$parentname, 'parentpath'=>$parent, 'list'=>$list));
            return;
            //end case "open"

 /// list
        case "list":
            // return array of filenames [fn, fn, fn, ...]

            $list = function_exists('looma_trace_with')
                ? looma_trace_with('filesystem.library.list', [
                    'directory' => $dir,
                  ], function() use ($dir) {
                    $result = array();
                    foreach (new DirectoryIterator($dir) as $fileInfo) {
                        $file =  $fileInfo->getFilename();

                        //skip ".", "..", and any ".filename" and any filename with '_thumb' in the name
                        if (($file[0]  == ".")       ||                    // skip hidden files
                             strpos($file, "_thumb") ||    // skip thumbnail files
                             strpos($file, '.srt') ||      // skip close caption files
                             $file == "thumbnail.png"||
                             $file == "images.txt")
                        continue;

                        if ($fileInfo -> isFile()) {
                            $item = array('fn'=>$file, 'reg' => isRegistered($file, $dir));
                            array_push($result,$item);
                        }
                    } //end foreach
                    return $result;
                  })
                : (function() use ($dir) {
                    $result = array();
                    foreach (new DirectoryIterator($dir) as $fileInfo) {
                        $file =  $fileInfo->getFilename();

                        //skip ".", "..", and any ".filename" and any filename with '_thumb' in the name
                        if (($file[0]  == ".")       ||                    // skip hidden files
                             strpos($file, "_thumb") ||    // skip thumbnail files
                             strpos($file, '.srt') ||      // skip close caption files
                             $file == "thumbnail.png"||
                             $file == "images.txt")
                        continue;

                        if ($fileInfo -> isFile()) {
                            $item = array('fn'=>$file, 'reg' => isRegistered($file, $dir));
                            array_push($result,$item);
                        }
                    } //end foreach
                    return $result;
                  })();

            echo json_encode($list);
            return;
            // end case "list"

// KHAN import
// call with url=looma-library-utilities.php?cmd=khanimport
// WARNING this is dangerous and can mess up Looma MongoDB activities collection
// use with care
//
// reads directly from /Users/skip/bin/khanfilelist and creates activities for each line of khanfilelist
//
        case "khanimport":
            $khanpath = "/Users/skip/bin/khanfilelist";
            $khanfile = (file_get_contents($khanpath));
            $khancontents = json_decode("[" . $khanfile . "]");
            foreach ($khancontents as $item) {
                //echo $item -> dn . '<br>'; //debug

            //NOTE: following line is commented out to prevent inadvertent use
            // uncomment it temporarily to do a khan import

            make_activity($item);

            }
            return;
        // end case "khanimport"

       case "w4simport":
            $w4spath = "/Users/skip/Desktop/w4sfilelist";
            $w4sfile = (file_get_contents($w4spath));
            $w4scontents = json_decode("[" . $w4sfile . "]");
            foreach ($w4scontents as $item) {
                //echo $item -> dn . '<br>'; //debug

            //NOTE: following line is commented out to prevent inadvertent use
            // uncomment it temporarily to do a wiki import

            make_activity($item);

            }
           return;
        // end case "w4simport"



/// KHAN list (internal use only)
        case "khanlist":
            $khanpath = "/Users/skip/bin/khanfilelist";
            $khanfile = (file_get_contents($khanpath));

            echo "[";
            print_r($khanfile);
            echo "{}]";
            //echo json_encode($khanfile);
            return;
///




/// NEW ACTIVITIES
        case "new_activities":
            $list = $_REQUEST['list'];
            foreach ($list as $item) {
                make_activity($item);
            }
            echo "created " . count($list) . " new activities";
            return;
            // end case "search_folder"

/// SEARCH_FOLDER
        case "search_folder":
            echo "folder search not yet implemented";
            return;
            // end case "search_folder"

        default:
            echo "looma illegal command";
            exit(); //end ILLEGAL CMD

    } //end switch "cmd"
}
else return; //no CMD given
?>

