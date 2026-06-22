<?php
header("Access-Control-Allow-Origin: *\n");
$page_title = 'Looma';

/**
 *Name: Justin Cardozo, skip, Charlotte
 *Email: justin.cardozo@menloschool.org
 *Owner: VillageTech Solutions (villagetechsolutions.org)
 *Date: 2015 03, 2017 08, 2021 07
 *Revision: 2.1
 * for Looma 3.0
 *File: looma-dictionary-utilities.php
 */

/**
 *called by AJAX
 *		sample call (jQuery):
 *			   $.getJSON("looma-dictionary.php", {"cmd":"lookup", "word": word}, function(result) {wordObj = result;});
 *		LOOKUP: looks up $_REQUEST['word'] to match 'en' filed in dictionary and
 * 				 returns a JSON object {en:"word", np:"nepaliword", defn:"definition", img: "filename of picture"}
 *				other properties (phonetic, useinsentence, partofspeech, hom, ant, syn) to be added later
 *		REVERSELOOKUP: looks up $_REQUEST['word'] to match 'np' field in dictionary and
 * 				returns a JSON object {en:"word", np:"nepaliword", defn:"definition", img: "filename of picture"}
 *				other properties (phonetic, useinsentence, partofspeech, hom, ant, syn) to be added later
 *		LIST: takes an object {class, subject, [ch_id], [count], [boolean]} and returns an array of english words
 *				matching the filter criteria,
 *				length=count and randomized if boolean=true
 *		ADD:    takes a JSON object {en:"word", np:"nepaliword", defn:"definition", img: "filename of picture"}
 *				validates properties and inserts the word in the database
 *				and returns success: TRUE or FALSE
 * 		DELETE: deletes the document corresponding to the word's ID
 * 		UPDATE: updates the document corresponding to the word's ID
 *
 */
include ('includes/mongo-connect.php');
require_once ('includes/otel.php');
function keyIsSet($key, $array) { return isset($array[$key]);} //compatibility shiv for php 5.x "keyIsSet()"

/**
 * Fetch an English definition from the internet (api.dictionaryapi.dev — free,
 * no API key). Used ONLY as a fallback when a word is missing from Looma's own
 * dictionary. Returns ['def'=>..., 'part'=>..., 'phon'=>...] or null on any
 * failure (offline, timeout, 404). Short timeouts keep a missing internet
 * connection from ever stalling the dictionary.
 */
function looma_fetch_online_definition($word) {
	$word = strtolower(trim($word));
	if ($word === '') return null;
	$url = 'https://api.dictionaryapi.dev/api/v2/entries/en/' . rawurlencode($word);

	$body = false;
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT        => 6,
			CURLOPT_USERAGENT      => 'Looma-Dictionary/1.0',
			CURLOPT_SSL_VERIFYPEER => false,
		));
		$body = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($body === false || (int)$code !== 200) $body = false;
	} else {
		// Fallback when cURL is unavailable on this PHP build.
		$ctx = stream_context_create(array('http' => array('timeout' => 6)));
		$body = @file_get_contents($url, false, $ctx);
	}
	if ($body === false || $body === null || $body === '') return null;

	$data = json_decode($body, true);
	if (!is_array($data) || !isset($data[0]['meanings']) || !is_array($data[0]['meanings'])) return null;

	$entry     = $data[0];
	$phon      = isset($entry['phonetic']) ? $entry['phonetic'] : '';
	$parts     = array();
	$firstPart = '';
	foreach ($entry['meanings'] as $meaning) {
		if (empty($meaning['definitions'][0]['definition'])) continue;
		$pos = isset($meaning['partOfSpeech']) ? trim($meaning['partOfSpeech']) : '';
		if ($firstPart === '' && $pos !== '') $firstPart = $pos;
		$d = trim($meaning['definitions'][0]['definition']);
		if ($d === '') continue;
		$parts[] = ($pos !== '' ? '(' . $pos . ') ' : '') . $d;
		if (count($parts) >= 3) break;   // keep the definition card compact
	}
	if (!$parts) return null;

	return array(
		'def'  => implode('  ', $parts),
		'part' => $firstPart,
		'phon' => $phon,
	);
}

$DEFAULT_NUM = 25;
$MAX_NUM = 250;

if (isset($_REQUEST["cmd"])) {
	// accepted CMDs are 'lookup', 'reverselookup', 'list', 'add', 'delete', 'update'
	$cmd = $_REQUEST["cmd"];
	if (function_exists('looma_trace_page')) {
	    looma_trace_page('dictionary-utilities', [
	        'cmd'  => $cmd,
	        'word' => $_REQUEST['word'] ?? null,
	        'lang' => $_REQUEST['lang']  ?? null,
	    ]);
	}
	switch ($cmd) {

////////////////////////////
////// command LOOKUP   ////
////////////////////////////

	    // cmd = 'lookup', param 'word' = word to lookup
		case "lookup":
		case "reverselookup":
			// lookup $_REQUEST["word"] in the dictionary and return the dictionary document for the word
			if(isset($_REQUEST["word"]) && $_REQUEST["word"] != "")
			{   $englishWord = trim($_REQUEST["word"]);

				//NOTE: using regex to do a case insensitive search for the word
			//	$query = array('en' => mongoRegexOptions("^$englishWord$",'i'));

				$query = ['$or' => [['en' => mongoRegexOptions("^$englishWord$",'i')], ['np' => mongoRegexOptions("^$englishWord$",'i')]]];

				$word = function_exists('looma_trace_with')
					? looma_trace_with('mongo.dictionary.lookup', [
						'word' => $englishWord,
						'collection' => 'dictionary',
					  ], function() use ($dictionary_collection, $query) {
						  return mongoFindOne($dictionary_collection, $query);
					  })
					: mongoFindOne($dictionary_collection, $query);

				if (! $word) {  // if the WORD is not found, see if it is a PLURAL
					$query = array('plural' => mongoRegexOptions("^$englishWord$",'i'));
					$word = function_exists('looma_trace_with')
						? looma_trace_with('mongo.dictionary.lookup_plural', [
							'word' => $englishWord,
							'type' => 'plural',
						  ], function() use ($dictionary_collection, $query) {
							  return mongoFindOne($dictionary_collection, $query);
						  })
						: mongoFindOne($dictionary_collection, $query);
					if ($word) {
						$word['part'] = 'Plural of noun: ' . $word['en'];
						$word['en'] = $word['plural'];
					}
				}

				if($word != null)
				{   //Add fields with blanks to avoid errors on code that receives words

					if (file_exists('../content/dictionary images/' . $word['en'] . '.jpg')) $word['img'] = $word['en'];

					if(!keyIsSet('np', $word))    $word['np'] = '';
					if(!keyIsSet('en', $word))    $word['en'] = '';
					if(!keyIsSet('rw', $word))    $word['rw'] = '';
					if(!keyIsSet('part', $word))  $word['part'] = '';
					if(!keyIsSet('def', $word))   $word['def'] = '';
					if(!keyIsSet('meanings', $word))   $word['meanings'] = '';
					if(!keyIsSet('ch_id', $word)) $word['ch_id'] = '';
					$word = json_encode($word);
					echo $word . "\n";  //probalby should/could remove "\n"
				}
				else
				{   // Word not found — build a "did you mean" list. Strategy:
					// 1. find candidates whose `en`/`np` shares the first 1–2 letters
					//    so we never scan the whole dictionary (Mongo index-friendly).
					// 2. score them with PHP's levenshtein() against the user's query
					//    and keep the closest matches whose distance is small enough
					//    to be plausibly the intended word.
					$suggestions = array();
					$qLower = strtolower($englishWord);
					$qLen   = strlen($qLower);
					if ($qLen > 0) {
						$prefix = preg_quote(substr($qLower, 0, max(1, min(2, $qLen))), '/');
						$candQuery = ['$or' => [
							['en' => mongoRegexOptions("^$prefix", 'i')],
							['np' => mongoRegexOptions("^$prefix", 'i')],
						]];
						// Cap the scan: 200 candidates is enough to find good matches
						// without turning a typo into a full-collection scan.
						$cands = mongoFind($dictionary_collection, $candQuery, null, null, 200);
						$scored = array();
						foreach ($cands as $c) {
							$candidates = array();
							if (isset($c['en']) && $c['en']) $candidates[] = $c['en'];
							if (isset($c['np']) && $c['np']) $candidates[] = $c['np'];
							foreach ($candidates as $term) {
								$d = levenshtein($qLower, strtolower($term));
								// Allow up to ~30% edit distance, min 1, max 3.
								$maxDist = max(1, min(3, (int) floor($qLen * 0.34)));
								if ($d <= $maxDist) {
									$scored[] = ['term' => $term, 'dist' => $d];
								}
							}
						}
						// Sort by distance asc, then alpha; dedupe; cap to 6.
						usort($scored, function($a, $b) {
							if ($a['dist'] !== $b['dist']) return $a['dist'] - $b['dist'];
							return strcmp($a['term'], $b['term']);
						});
						$seen = array();
						foreach ($scored as $s) {
							$k = strtolower($s['term']);
							if (isset($seen[$k])) continue;
							$seen[$k] = true;
							$suggestions[] = $s['term'];
							if (count($suggestions) >= 6) break;
						}
					}
					$failed = array(
						'en' => $englishWord, 'np' => '', 'ch_id' => '',
						'def' => 'Word not found', 'phon' => '', 'img' => '',
						'suggestions' => $suggestions,
					);
					echo json_encode($failed);
				}
			}
			else
			{   $failed = array('en' => '','np' => '', 'ch_id' => '', 'def' => 'word not found','phon' => '','img' => '');
				$failed = json_encode($failed);
				echo "$failed";
			}
			exit(); //end LOOKUP cmd

/*
////////////////////////////
/// command REVERSELOOKUP ///
////////////////////////////

		// cmd = 'reverselookup', param 'word' = Nepali word to lookup
		case "reverselookup":
			// lookup Nepali word in the dictionary and return an object describing the word
			if(isset($_REQUEST["word"]) && $_REQUEST["word"] != "")
			{   $nativeWord = trim($_REQUEST["word"]);

				$query = array('np' => mongoRegexOptions("^$nativeWord$",'i'));
				$word = mongoFindOne($dictionary_collection, $query);

				if($word != null)
				{   //Add fields with blanks to avoid errors on code that receives words

					if (file_exists('../content/dictionary images/' . $word['en'] . '.jpg')) $word['img'] = $word['en'];


					if(!keyIsSet('np', $word))    $word['np'] = '';
					if(!keyIsSet('en', $word))    $word['en'] = '';
					if(!keyIsSet('rw', $word))    $word['rw'] = '';
					if(!keyIsSet('part', $word))  $word['part'] = '';
					if(!keyIsSet('def', $word))   $word['def'] = '';
					if(!keyIsSet('meanings', $word))   $word['meanings'] = '';
					if(!keyIsSet('ch_id', $word)) $word['ch_id'] = '';
					$word = json_encode($word);
					echo $word . "\n";
				}
				else
				{   $failed = array('en' => $englishWord,'np' => '', 'ch_id' => '', 'def' => 'Word not found','phon' => '','img' => '');
					$failed = json_encode($failed);
					echo "$failed";
				}
			}
			else
			{   $failed = array('en' => '','np' => '', 'ch_id' => '', 'def' => 'word not found','phon' => '','img' => '');
				$failed = json_encode($failed);
				echo "$failed";
			}
			exit(); //end reverseLOOKUP cmd
*/

////////////////////////////
////// command LIST   //////
////////////////////////////

		case "list":
			// cmd = "list"
			// params ["class" AND "subj"] OR ["ch_id"] and [optionally] "count" (default 25)
			//    and "picturesonly" (boolean, default FALSE)
			// return an array of 'count' words that match class&subj or ch_id

			$picturesonly = (isset($_REQUEST["picturesonly"]) &&
				             $_REQUEST["picturesonly"] === "true");

			$maxCount = (isset($_REQUEST["count"]) ?
				min(max(0,$_REQUEST['count']),$MAX_NUM) : $DEFAULT_NUM);

			$classes =  array('class1','class2','class3','class4', 'class5', 'class6',
							  'class7','class8','class9','class10','class11','class12');
			$subjects = array('english','english optional','math','math optional','social studies','moral education',
							  'science', 'science optional','nepali','serafera','health', 'vocation','computer');
			$prefixes['english'] = 'EN'; $prefixes['english optional'] = 'ENa'; $prefixes['math'] = 'M';
			$prefixes['math optional'] = 'Ma'; $prefixes['science'] = 'S'; $prefixes['science optional'] = 'Sa';
			$prefixes['serafero'] = 'SF'; $prefixes['nepali'] = 'N'; $prefixes['health'] = 'H';
			$prefixes['social studies'] = 'SS'; $prefixes['moral education'] = 'SSa';
			$prefixes['vocation'] = 'V'; $prefixes['computer'] = 'CS';

			// if the parameter ch_id has been set, if it has been set and is valid
			// it will override class and subject parameters

			$legalCH_IDregex = '/^([1-9]|10|11|12)(EN|ENa|Sa|S|SF|Ma|M|SSa|SS|N|H|V|CS)[0-9]{2}(\.[0-9]{2})?/';
			// NOTE: see looma-utilities.js for the latest ch_id regex

			if (isset($_REQUEST["ch_id"]) &&
				(preg_match($legalCH_IDregex, $_REQUEST["ch_id"]))) {
				$query_id = $_REQUEST["ch_id"];
				if (preg_match( '/\d+([a-zA-Z]+)\d/', $query_id,$matches))
					$prefix = $matches[1];
				else $prefix = "";
			} else {
				$class = isset($_REQUEST["class"]) && in_array($_REQUEST["class"], $classes) ? $_REQUEST['class'] : '';
				$class = substr($class, 5);
				$subject = isset($_REQUEST["subject"]) && in_array($_REQUEST["subject"], $subjects) ? $_REQUEST['subject'] : '';

				$query_id = $class . $prefixes[$subject];
				$prefix = $subject ? $prefixes[$subject] : '';
			};

			$list = array();

			$query = array("ch_id.$prefix" => mongoRegexOptions('^' . $query_id,'i'));

			//print_r($query);

			//exit();
//NOTE: to find dictionary entries with a given ch_id:
//   db.dictionaryV2.find({ch_id:{'EN':'1EN01.01'}},{_id:0,en:1})
//NOTE: to find dictionary entries with a ch_id regex:
//	db.dictionaryV2.find({'ch_id.EN':{$regex:/1EN01/}},{_id:0,en:1})

		if (!$picturesonly) {
			$words = function_exists('looma_trace_with')
				? looma_trace_with('mongo.dictionary.list', [
					'query_id' => $query_id,
					'maxCount' => $maxCount,
					'pictures_only' => 'false',
				  ], function() use ($dictionary_collection, $query, $maxCount) {
					  return mongoFindRandom($dictionary_collection, $query, (int) $maxCount);
				  })
				: mongoFindRandom($dictionary_collection, $query, (int) $maxCount);
			$count = 0;
			foreach ($words as $newWord) {
				array_push($list, $newWord);
				$count++;
				if ($count >= $maxCount) break;
			}
		} else {
			$words = function_exists('looma_trace_with')
				? looma_trace_with('mongo.dictionary.list', [
					'query_id' => $query_id,
					'maxCount' => $maxCount,
					'pictures_only' => 'true',
				  ], function() use ($dictionary_collection, $query, $maxCount) {
					  return mongoFindRandom($dictionary_collection, $query, 100 * (int) $maxCount);
				  })
				: mongoFindRandom($dictionary_collection, $query, 100 * (int) $maxCount);

		//print_r($words);

			$count = 0;
			foreach ($words as $newWord) {
				//echo "looking for " . "../content/dictionary images/" . $newWord['en'] . ".jpg";
					if (file_exists("../content/dictionary images/" . $newWord['en'] . ".jpg")) {
					array_push($list, $newWord);
					$count++;
					if ($count >= $maxCount) break;
				}
			}
		};

 		$list = json_encode($list);
		echo $list;
		exit(); //end LIST cmd


////////////////////////////
////// command ADD   //////
////////////////////////////

		// cmd = "add"
		// params = "en", "np", [ch_id, def, meanings, part]
		// create new dictionary entry - return "true" or "false"
		case "add":
			// add a word to the dictionary using the object passed in, return T/F for success
			// add a word only if it contains at the very least an english and nepali form of the word
			if(isset($_REQUEST["en"]) && isset($_REQUEST["np"]))
			{
				$en = $_REQUEST["en"];
				$np = $_REQUEST["np"];
				if (function_exists('looma_trace_with')) {
					looma_trace_with('mongo.dictionary.add', [
						'en' => $en,
						'np' => $np,
					  ], function() use ($dictionary_collection, $en, $np) {
						  return mongoInsert($dictionary_collection, array('en' => $en, 'np' => $np));
					  });
				} else {
					mongoInsert($dictionary_collection, array('en' => $en, 'np' => $np));
				}
				if(isset($_REQUEST["ch_id"]))
				{
					$ch_id = $_REQUEST["ch_id"];
					$newdata = array('$set' => array("ch_id" => "$ch_id"));
					if (function_exists('looma_trace_with')) {
						looma_trace_with('mongo.dictionary.add_field', [
							'en' => $en,
							'field' => 'ch_id',
						  ], function() use ($dictionary_collection, $en, $np, $newdata) {
							  return mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
						  });
					} else {
						mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
					}
				}
				if(isset($_REQUEST["def"]))
				{
					$def = $_REQUEST["def"];
					$newdata = array('$set' => array("def" => "$def"));
					if (function_exists('looma_trace_with')) {
						looma_trace_with('mongo.dictionary.add_field', [
							'en' => $en,
							'field' => 'def',
						  ], function() use ($dictionary_collection, $en, $np, $newdata) {
							  return mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
						  });
					} else {
						mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
					}
				}
				if(isset($_REQUEST["meanings"]))
				{
					$meanings = $_REQUEST["meanings"];
					$newdata = array('meanings' => $meanings);
					if (function_exists('looma_trace_with')) {
						looma_trace_with('mongo.dictionary.add_field', [
							'en' => $en,
							'field' => 'meanings',
						  ], function() use ($dictionary_collection, $en, $np, $newdata) {
							  return mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
						  });
					} else {
						mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
					}
				}
				if(isset($_REQUEST["part"]))
				{
					$part = $_REQUEST["part"];
					$newdata = array('$set' => array("part" => "$part"));
					if (function_exists('looma_trace_with')) {
						looma_trace_with('mongo.dictionary.add_field', [
							'en' => $en,
							'field' => 'part',
						  ], function() use ($dictionary_collection, $en, $np, $newdata) {
							  return mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
						  });
					} else {
						mongoUpdate($dictionary_collection, array('en' => $en, 'np' => $np), $newdata);
					}
				}

				$word = mongoFindOne($dictionary_collection, array('en' => $en, 'np' => $np));
				echo "true";
				exit();
			}
			else
			{ echo "false"; exit();
			}  // end ADD cmd


////////////////////////////
////// command DELETE   ////
////////////////////////////

		// cmd = "delete"
		// param "wordID"
		case "delete":
			if(isset($_REQUEST["wordID"])) {
				$id = mongoId($_REQUEST["wordID"]);
				if (function_exists('looma_trace_with')) {
					looma_trace_with('mongo.dictionary.delete', [
						'wordID' => $_REQUEST["wordID"],
					  ], function() use ($dictionary_collection, $id) {
						  return mongoDeleteOne($dictionary_collection, array('_id' => $id));
					  });
				} else {
					mongoDeleteOne($dictionary_collection, array('_id' => $id));
				}
				echo "deleted";
			} else {
				echo "no word ID given";
			};
			exit();


////////////////////////////
////// command UPDATE   ////
////////////////////////////

		// cmd = "update"
		// params "wordID", wordEn, meanings and optionally "np", "def", "part", "root", "plural", "ch_id"
		//    any parameter that is NOT specified will be UNSET (deleted) from the dictionary entry
		case "update":
			if(isset($_REQUEST["wordID"]))
			{
				if ($_REQUEST["wordID"] == "newentry") $id = "newentry";
				else $id = mongoId($_REQUEST["wordID"]);

				$updates = [];
				$empty = [];

				$updates['en'] = $_REQUEST["wordEn"];
				$updates['meanings'] = $_REQUEST["meanings"];

				if (isset($_REQUEST["wordDef"]) && $_REQUEST["wordDef"] !== "") {
					$updates['def'] = $_REQUEST["wordDef"];
				}
				else {
					$empty['def'] = $_REQUEST["wordDef"];
				}


				if (isset($_REQUEST["wordPart"]) && $_REQUEST["wordPart"] !== "") {
					$updates['part'] = $_REQUEST["wordPart"];
				}
				else {
					$empty['part'] = $_REQUEST["wordPart"];
				}
				if (isset($_REQUEST["wordNp"]) && $_REQUEST["wordNp"] !== "") {
					$updates['np'] = $_REQUEST["wordNp"];
				}
				else {
					$empty['np'] = $_REQUEST["wordNp"];
				}

				if (isset($_REQUEST["wordPlural"]) && $_REQUEST["wordPlural"] !== "") {
					$updates['plural'] = $_REQUEST["wordPlural"];
				}
				else {
					$empty['plural'] = $_REQUEST["wordPlural"];
				}

				if (isset($_REQUEST["wordRw"]) && $_REQUEST["wordRw"] !== "") {
					$updates['rw'] = $_REQUEST["wordRw"];
				}
				else {
					$empty['rw'] = $_REQUEST["wordRw"];
				}

				if (isset($_REQUEST["wordCh_id"]) && $_REQUEST["wordCh_id"] !== "") {
					$updates['ch_id'] = $_REQUEST["wordCh_id"];
				}
				else {
					$empty['ch_id'] = $_REQUEST["wordCh_id"];
				}

				if ($id == "newentry") { // indicating new entry
				if (function_exists('looma_trace_with')) {
					looma_trace_with('mongo.dictionary.update', [
						'type' => 'new_entry',
						'en' => $updates['en'] ?? '',
					  ], function() use ($dictionary_collection, $updates) {
						  return mongoInsert($dictionary_collection, $updates);
					  });
				} else {
					mongoInsert($dictionary_collection, $updates);
				}
			}
			else {
				$filter = ['_id' => $id];
				if (function_exists('looma_trace_with')) {
					looma_trace_with('mongo.dictionary.update', [
						'type' => 'existing_entry',
						'wordID' => $_REQUEST["wordID"],
						'en' => $updates['en'] ?? '',
					  ], function() use ($dictionary_collection, $filter, $updates, $empty) {
						  mongoUpdate($dictionary_collection, $filter, ['$set' => $updates]);
						  if ($empty !== []) {
							  mongoUpdate($dictionary_collection, $filter, ['$unset' => $empty]);
						  }
					  });
				} else {
					mongoUpdate($dictionary_collection, $filter, ['$set' => $updates]);
					if ($empty !== []) {
						mongoUpdate($dictionary_collection, $filter, ['$unset' => $empty]);
					}
				}

				echo "true";
				exit();
			}
		}
		else {
			echo "false";
			exit();
		}

////////////////////////////////
////// command ONLINELOOKUP ////
////////////////////////////////

		// cmd = 'onlinelookup', param 'word' = word to look up on the internet
		// Internet fallback used ONLY when a word is missing from Looma's own
		// dictionary. Fetches an English definition from api.dictionaryapi.dev.
		// Returns {en, np:'', def, part, phon, source:'online', found:bool}.
		// Meant to be called in the background by the dictionary card and the
		// dictionary page — it never blocks the fast local (Mongo) lookup.
		case "onlinelookup":
			$resp = array('en' => '', 'np' => '', 'def' => '', 'part' => '',
			              'phon' => '', 'source' => 'online', 'found' => false);

			if (!isset($_REQUEST['word']) || trim($_REQUEST['word']) === '') {
				echo json_encode($resp);
				exit();
			}
			$word = trim($_REQUEST['word']);
			$resp['en'] = $word;

			// English words only — the free API has no Nepali coverage.
			// Reject anything with non-ASCII characters (e.g. Devanagari) or
			// with no Latin letters at all.
			if (preg_match('/[^\x00-\x7F]/', $word) || !preg_match('/[A-Za-z]/', $word)) {
				echo json_encode($resp);
				exit();
			}

			// dictionaryapi.dev matches single words; use the first word of a
			// short (1-3 word) selection so a phrase still gets a useful result.
			$firstWord = preg_split('/\s+/', $word);
			$lookupWord = $firstWord[0];

			$online = function_exists('looma_trace_with')
				? looma_trace_with('dictionary.online_lookup', [
					'word' => $lookupWord,
				  ], function() use ($lookupWord) {
					  return looma_fetch_online_definition($lookupWord);
				  })
				: looma_fetch_online_definition($lookupWord);

			if ($online) {
				$resp['def']   = $online['def'];
				$resp['part']  = $online['part'];
				$resp['phon']  = $online['phon'];
				$resp['found'] = true;
			}
			echo json_encode($resp);
			exit(); //end ONLINELOOKUP cmd


		default:
			echo "looma dictinary utilities illegal command";
			exit(); //end ILLEGAL CMD
	} //end CASE LIST
}
?>
