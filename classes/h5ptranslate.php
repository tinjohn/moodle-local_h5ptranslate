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
        echo "hello";
    }

    public static function isText($str) {
        // @string 
        // :string
        // boolean
        // number
        echo '<br><span style="color:black;"> text ' . $str . '</span>';
        if(!is_string($str)) {
            echo '<span style="color:orange;"> kein String </span><br>';
            return false;
        } 
        if(ctype_cntrl($str)) {
            echo '<span style="color:orange;"> ist Controllcharacter </span><br>'; 
            return false;
        }
        if($str == "") {
            echo '<span style="color:orange;"> ist leer </span><br>'; 
            return false;
        }
        // paths via tag
        // if(preg_match('#^[a-zA-Z0-9_\- ]+[/\\][a-zA-Z0-9_\- ].?[a-zA-Z0-9]?$#', $str, $matches)) {
        //     echo '<span style="color:orange;"> ist ein Pfad' . json_encode($matches) .  '</span><br>';
        //     return false;
        // }  
        echo '<span style="color:green;"> ist text.</span><br>';
    
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


    // USED check value for translatable text and tag variables in string with notranslation
    public static function markNextractTransStrings ($arr, $ignore_tags = array(), &$allmatches = array()) {
        echo '<br>in markNextractTransStrings ';
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
        var_dump($allmatches);
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
        echo "<h1> translation in replaceTransTags </h1>"; 
        print_r($translation);
        echo "ENDE <br>"; 
        
        $pattern = '/<(trans\d+)>(.*?)<\/trans\d+>/';
        
        preg_match_all($pattern, $translation, $transmatches);
        $combmatched = array_combine($transmatches[1],$transmatches[2]);
        
        print_r($combmatched);
        // foreach ($transmatches[2] as $match) {
        //     echo "Extracted text: $match <br>";
        // }     
        echo "ENDE <br>"; 
    
    
        foreach($combmatched as $key => $value) {
            echo "key: " . $key . " - " . "value:" . $value . "<br>";
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
            echo "Data updated successfully!";
        } else {
            // Insert a new record
            $data = new \stdClass();
            $data->h5pid = $h5pId;
            $data->lang = $lang;
            $data->transcontent = $transContent;
            $data->paramshash = $paramshash;
            $DB->insert_record('local_h5ptranslate', $data);
            echo "Data inserted successfully!";
        }
    }

    public static function createArrayFromReference(&$variable) {
        return $variable;
    }
    
    
    public static function h5ptranslate(&$contentjson, $lang, $id = NULL) {
        global $DB;
        echo "<h1> in h5ptranslate function contentjson</h1>";
        print_r($contentjson);
        $contentJsonCopy = self::createArrayFromReference($contentjson);

        // create params hash for faster string match - Params json encoded hashed
        // needs to be done in alter_filter
        echo "<h1> Params json encoded hashed</h1>";
        $parametersenc = json_encode($contentJsonCopy);
        $paramshash = \file_storage::hash_from_string($parametersenc);
        echo "<br>" . $paramshash ."<br>";
        
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
        'color', 'iconType', 'icon', 'iconSize', 'iconColor', 'iconBackgroundColor', 'iconBackgroundOpacity', 'iconOpacity', 'iconPosition', 'iconAlign', 'iconVerticalAlign', 'iconMargin', 'iconMarginTop', 'iconMarginBottom', 'iconMarginLeft', 'iconMarginRight', 'iconPadding');

        echo "<h1> strings marked and collected</h1>";
        print_r($contentJsonCopy);
        // mark strings in array by reference and collect string in returned array
        $jsonstringPrepArray = self::markNextractTransStrings($contentJsonCopy, $ignore_tags);
        print_r($jsonstringPrepArray);
        echo "<br><br>";
        echo "<h1> JSON Content after marking </h1>";
        print_r($contentJsonCopy);

        // XML Version of strings for better perfomance and single call for DeepL API
        echo "<h1> XMLed </h1>";
        $jsonstringPrep = self::flatarrayToXml2($jsonstringPrepArray);
        $jsonstringPrep = html_entity_decode($jsonstringPrep);
        print_r($jsonstringPrep);

        echo "<h1> Translation </h1>";
        // api key from config not working - it's working now
        //$apiKey = get_config('local_h5ptranslate','deeplapikey');
        //echo "<h1> apikey </h1>" . $apiKey ;
     
        $translation = deepltranslate::transWithDeeplXML($jsonstringPrep, $lang);
        //DEBUG $translation = array();
        print_r($translation);

        // NICHT LÖSCHEN Achtung, das ist die Übersetzung, die schon funtioniert hat und wieder funktionieren sollte.
        // replace translated strings in json format, to prevent array looping 
        $contentJsonEncoded = json_encode($contentJsonCopy);
        echo "<h1> contentJsonEncoded for replacetranstags </h1>";
        print_r($contentJsonEncoded);

        $transcontentJsonEncoded = self::replaceTransTags($contentJsonEncoded, $translation);   
        echo "<h1> Translated hier</h1>";
        print_r($transcontentJsonEncoded);

        echo "<h1> ready to write to DB </h1>";


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
       self::writeTranslationToDB ($id, $lang, $transcontentJsonEncoded, $paramshash);

    }
}


