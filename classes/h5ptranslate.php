<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * 
 * @package    local_h5ptranslate
 * @copyright  2023 Tina John <tina.john@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_h5ptranslate;

defined('MOODLE_INTERNAL') || die;

use local_h5ptranslate\deepltranslate;


class h5ptranslate {
    public static function echo_hello() {
        //debugecho  "hello";
    }

    public static function isText($str) {
        // @string 
        // :string
        // boolean
        // number
        //debugecho  '<br><span style="color:black;"> text ' . $str . '</span>';
        if(!is_string($str)) {
            //debugecho  '<span style="color:orange;"> kein String </span><br>';
            return false;
        } 
        if(ctype_cntrl($str)) {
            //debugecho  '<span style="color:orange;"> ist Controllcharacter </span><br>'; 
            return false;
        }
        if($str == "") {
            //debugecho  '<span style="color:orange;"> ist leer </span><br>'; 
            return false;
        }
        // paths via tag
        // if(preg_match('#^[a-zA-Z0-9_\- ]+[/\\][a-zA-Z0-9_\- ].?[a-zA-Z0-9]?$#', $str, $matches)) {
        //     echo '<span style="color:orange;"> ist ein Pfad' . json_encode($matches) .  '</span><br>';
        //     return false;
        // }  
        //debugecho  '<span style="color:green;"> ist text.</span><br>';
    
        return true;
    }
    
    // USED tag notranslation
    public static function xmlTagVariables($str) { 
        $pattern = '#(:[a-z]{1,})#';
        $replacement = '<notranslation>${1}</notranslation>';
        $str = preg_replace($pattern,$replacement, $str);

        $pattern = '#(@[a-z]{1,})#';
        $replacement = '<notranslation>${1}</notranslation>';
        $str = preg_replace($pattern,$replacement, $str);

        return($str);
    }

    public static function isArrayAssociative($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
    public static function processNestedArray(&$arr, $ignore_tags = array(), &$allmatches = array()) {
        foreach ($arr as $key => &$val) {
            if (is_array($val) || is_object($val)) {
                self::processNestedObjectOrArray($val, $ignore_tags, $allmatches);
            } else {
                self::processSingleValue($key, $val, $ignore_tags, $allmatches);
            }
        }
        unset($val);
        return $allmatches;
    }
    
    public static function processNestedObjectOrArray(&$nestedVal, $ignore_tags, &$allmatches) {
        if (is_array($nestedVal) && !self::isArrayAssociative($nestedVal)) {
            // Process non-associative array
            // No modifications needed for non-associative arrays
            self::processNestedArray($nestedVal, $ignore_tags, $allmatches);
        } else {
            // Process nested object or associative array
            self::processNestedArray($nestedVal, $ignore_tags, $allmatches);
        }
    }
    
    public static function processSingleValue($key, &$val, $ignore_tags, &$allmatches) {
        if (!in_array($key, $ignore_tags)) {
            if (self::isText($val)) {
                // Process single value
                $newval = self::xmlTagVariables($val);
                $rndind = rand();
                $nkey = 'trans' . $rndind;
                $allmatches[$nkey] = $newval;
                $val = $nkey;
            }
        }
    }
    


    // NOT USED REPLACED BY PROCESS check value for translatable text and tag variables in string with notranslation
    public static function markNextractTransStrings ($arr, $ignore_tags = array(), &$allmatches = array()) {
        //debugecho  '<br>in markNextractTransStrings ';
        foreach ($arr as $key => &$val) {
             if (is_array($val) || is_object($val)) {                
                 $cval = $val;
                 $allmatches = array_merge($allmatches,self::markNextractTransStrings($cval,$ignore_tags));
             } else {
                 if(!in_array($key,$ignore_tags)) {
                    if(self::isText($val)) {
                        // keep for translation
                        $newval = self::xmlTagVariables($val);
                        //$allmatches[] = $newval;
                        $rndind = rand();
                        $nkey = 'trans' . $rndind;
                        $allmatches[$nkey] = $newval;
                        $val = 'trans' . $rndind;
                     }
                 }
             }
        }
        unset($val);
        //debug var_dump($allmatches);
        return($allmatches);
    }

    public static function preprocessing($str) {
        $str = str_replace("\/","/", $str);
        $str = str_replace("\n","<newline />", $str);
        $str = str_replace("\t","<newtab />", $str);
        return($str);
    }

    // USED XML conversion and Preprocessing for backslashed and newline
    public static function flatarrayToXml2($array, $rootElement = '<root></root>', $xml = null) {
        if ($xml === null) {
            $xml = new \SimpleXMLElement($rootElement);
        }
        foreach ($array as $key => $value) {
                $value = self::preprocessing($value);
                $xml->addChild("$key", "$value");
        }
        return $xml->asXML();
    }

    public static function removeXmlVariableTag($str) {
        $str = str_replace('<notranslation>','',$str);
        $str = str_replace('</notranslation>','',$str);
        return($str);
    }
    
    public static function repreprocessing($str) {
        $str = str_replace("<newline />",'\n', $str);
        $str = str_replace("<newtab />",'\t', $str);
        //$str = htmlentities($str);
        //$str = json_encode($str);
        //$str = str_replace("/","\/", $str);
        $str = str_replace("\'","'", $str);
        $str = str_replace("/","\/", $str);

        return($str);
    }
    

    public static function replaceTransTags($contentJsonEncoded,$translation) {
        //debugecho  "<h1> translation in replaceTransTags </h1>"; 
        //debugprint_r($translation);
        //debugecho  "ENDE <br>"; 
        
        $pattern = '/<(trans\d+)>(.*?)<\/trans\d+>/';
        
        preg_match_all($pattern, $translation, $transmatches);
        $combmatched = array_combine($transmatches[1],$transmatches[2]);
        
        //debugprint_r($combmatched);
        // foreach ($transmatches[2] as $match) {
        //     echo "Extracted text: $match <br>";
        // }     
        //debugecho  "ENDE <br>"; 
    
    
        foreach($combmatched as $key => $value) {
            //debugecho  "key: " . $key . " - " . "value:" . $value . "<br>";
            // for quote doublequote backslashes 
            $value = addslashes($value);
            $value = self::removeXmlVariableTag($value);
            $value = self::repreprocessing($value);
            // find value in contentJsonEncoded array and replace it
            $contentJsonEncoded = str_replace($key,$value,$contentJsonEncoded);
        }
        return($contentJsonEncoded);
    }
        
    public static function writeTranslationToDB ($h5pId, $lang, $transContent, $paramshash) {
        global $DB;

        // Check if the record already exists
        if($h5pId !== NULL) {
            $existingRecord = $DB->get_record('local_h5ptranslate', ['h5pid' => $h5pId, 'lang' => $lang]);
        } else {
            $existingRecord = FALSE;
        }

        if ($existingRecord) {
            // Update the existing record
            $existingRecord->transcontent = $transContent;
            $existingRecord->paramshash = $paramshash;
            $DB->update_record('local_h5ptranslate', $existingRecord);
            //debugecho  "Data updated successfully!";
        } else {
            // Insert a new record
            $data = new \stdClass();
            $data->h5pid = $h5pId;
            $data->lang = $lang;
            $data->transcontent = $transContent;
            $data->paramshash = $paramshash;
            $DB->insert_record('local_h5ptranslate', $data);
            //debugecho  "Data inserted successfully!";
        }
    }

    public static function createArrayFromReference(&$variable) {
        return $variable;
    }
    
    public static function geth5ptranslation(&$parameters, $id = NULL) {
        global $DB;
        //debug echo '<script>console.log("geth5ptranslation: in here");</script>';
        $trglang = current_language();
        $notranslang = get_config('local_h5ptranslate','notranslationforlang');
        if($trglang == $notranslang) {
            //echo '<script>console.log("geth5ptranslation: de - standard no translation");</script>';
            return;
        }

        $jsencoded = json_encode($parameters);
        $parametersenc = json_encode($parameters);
        $paramshash = \file_storage::hash_from_string($parametersenc);

        // get translated params from db
        $newRecord = FALSE;
        $sql = "SELECT transcontent FROM {local_h5ptranslate} WHERE paramshash = ? AND lang = ?";
        $h5pRecord = $DB->get_record_sql($sql, [$paramshash, $trglang]);

        if(!$h5pRecord) {
            //echo '<script>console.log("geth5ptranslation: no record found - try to translate onthefly");</script>';
            self::h5ptranslate($parameters, $trglang);
            $sql = "SELECT transcontent FROM {local_h5ptranslate} WHERE paramshash = ? AND lang = ?";
            $h5pRecord = $DB->get_record_sql($sql, [$paramshash, $trglang]);
            if($h5pRecord) {
                $newRecord = TRUE;
            }
        } 
        if ($h5pRecord || $newRecord) {
            $parameterstrans = json_decode($h5pRecord->transcontent);
            if($parameterstrans != null)
                // set parameters for h5p_alter_filtered_parameters
                $parameters = $parameterstrans;
            //echo '<script>console.log("geth5ptranslation: paramters set new");</script>';            
        } else {
            //echo '<script>console.log("No translation available: check Deepl API Key and URl");</script>';            
            debugging("No translation available: check Deepl API Key and URl", DEBUG_NORMAL);
        }
        return;
    }

    public static function h5ptranslate(&$contentjson, $lang, $id = NULL) {
        global $DB;
        //debugecho  "<h1> in h5ptranslate function contentjson</h1>";
        //debugprint_r($contentjson);
        $contentJsonCopy = self::createArrayFromReference($contentjson);

        // create params hash for faster string match - Params json encoded hashed
        // needs to be done in alter_filter
        //debugecho  "<h1> Params json encoded hashed</h1>";
        $parametersenc = json_encode($contentJsonCopy);
        $paramshash = \file_storage::hash_from_string($parametersenc);
        //debugecho  "<br>" . $paramshash ."<br>";
        
        # 0463c9feb6d6aefef9be2e2840678df5c60456b9 filtered V 
        # Es gibt auch filtered NULL - filtered wird belegt, wenn die Aktivität dargestellt wurde. 
        # Es ist die sichere Version des json Inhaltes.
        # Ist die sichere Version - $parameters ist immer filtered 
        # der Hash passt nur leider nicht . das mus ein anderes hashen sein als file_storage::hash_from_string
        # also 
        # 128b30437f0c59e7d2b5385487f6cfc650950010 content
        # 651f4be336228cab75d9507470d4079fa27960cd database
        # 0463c9feb6d6aefef9be2e2840678df5c60456b9 renderer hash parameters

        // set array for ignore tag - might be an option
        $ignore_tags = array('library','buttonSize','decorative','contentName','path','mime','image',
        'copyright','subContentId','contentType','license','alwaysDisplayComments','buttonSize','goToSlideType','shape','type','borderStyle',
        'quizType','arithmeticType','equationType','useFractions','maxQuestions','nattx',
        'licenseVersion','fillColor','borderColor','subContentId','borderWidth','borderColor','borderRadius','borderStyle','borderWidth','contentName','name','buttonSize','playerMode','url',
        'color', 'iconType', 'icon', 'iconSize', 'iconColor', 'iconBackgroundColor', 'iconBackgroundOpacity', 'iconOpacity', 'iconPosition', 'iconAlign', 'iconVerticalAlign', 'iconMargin', 'iconMarginTop', 'iconMarginBottom', 'iconMarginLeft', 'iconMarginRight', 'iconPadding',
        'textTracks','videoTrack','srcLang','track','path','mime','kind');

        //debugecho  "<h1> strings marked and collected</h1>";
        //debugprint_r($contentJsonCopy);
        // mark strings in array by reference and collect string in returned array
        //$jsonstringPrepArray = self::markNextractTransStrings($contentJsonCopy, $ignore_tags);
        //$jsonstringPrepArray = self::markNextractTransStrings($contentJsonCopy, $ignore_tags);
        $jsonstringPrepArray = array();
        $jsonstringPrepArray = self::processNestedArray($contentJsonCopy, $ignore_tags, $jsonstringPrepArray);

        //debug print_r($jsonstringPrepArray);
        //debugecho  "<br><br>";
        //debugecho  "<h1> JSON Content after marking </h1>";
        //debugprint_r($contentJsonCopy);

        // XML Version of strings for better perfomance and single call for DeepL API
        //debugecho  "<h1> XMLed </h1>";
        $jsonstringPrep = self::flatarrayToXml2($jsonstringPrepArray);
        $jsonstringPrep = html_entity_decode($jsonstringPrep);
        //debugprint_r($jsonstringPrep);

        //debugecho  "<h1> Translation </h1>";
        // api key from config not working - it's working now
        //$apiKey = get_config('local_h5ptranslate','deeplapikey');
        //echo "<h1> apikey </h1>" . $apiKey ;
     
        $translation = deepltranslate::transWithDeeplXML($jsonstringPrep, $lang);
       
        
        //DEBUG $translation = array();
        //print_r($translation);

        // NICHT LÖSCHEN Achtung, das ist die Übersetzung, die schon funtioniert hat und wieder funktionieren sollte.
        // replace translated strings in json format, to prevent array looping 
        $contentJsonEncoded = json_encode($contentJsonCopy);
        //debugecho  "<h1> contentJsonEncoded for replacetranstags </h1>";
        //debugprint_r($contentJsonEncoded);

        $transcontentJsonEncoded = self::replaceTransTags($contentJsonEncoded, $translation);   
        //debugecho  "<h1> Translated hier</h1>";
        //debugprint_r($transcontentJsonEncoded);

        //debugecho  "<h1> ready to write to DB </h1>";


        //echo "<h1> Param filtered hashed</h1>";
        // nee geht auch nicht
        // $config = new stdClass();
        // $h5pplayer = new player('', $config);
        // $safeparams = $h5pplayer->core->filterParameters($parameters);
        // $decodedparams = json_decode($safeparams);

        //$h5pplayer->filter_parameter($parameters);
        //$paramshash = file_storage::hash_from_string($parameters);
        //echo "<br>" . $paramshash ."<br>";

        // for DEBUGGIN OFF
        if($jsonstringPrep == $translation) {
            // error handling - reset content 
            $contentjson = json_decode($transcontentJsonEncoded);
            return;
        }
        self::writeTranslationToDB ($id, $lang, $transcontentJsonEncoded, $paramshash);
    }
}


