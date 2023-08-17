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
    public static function markTransStrings ($arr, $ignore_tags = array(), &$allmatches = array()) {
    
        foreach ($arr as $key => &$val) {
            if (is_array($val) || is_object($val)) {
                
                $cval = $val;
                $allmatches = array_merge($allmatches,self::markTransStrings($cval,$ignore_tags));
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
        //var_dump($allmatches);
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
       // $str = str_replace("/","\/", $str);
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
            $value = addslashes($value);
            $value = self::removeXmlVariableTag($value);
            $value = self::repreprocessing($value);
            // find value in contentJsonEncoded array and replace it
            $contentJsonEncoded = str_replace($key,$value,$contentJsonEncoded);
        }
        return($contentJsonEncoded);
    }
        
    public static function writeTranslationToDB ($h5pId, $lang, $transContent) {
        global $DB;

        // Check if the record already exists
        $existingRecord = $DB->get_record('local_h5ptranslate', ['h5pid' => $h5pId, 'lang' => $lang]);

        if ($existingRecord) {
            // Update the existing record
            $existingRecord->transcontent = $transContent;
            $DB->update_record('local_h5ptranslate', $existingRecord);
            echo "Data updated successfully!";
        } else {
            // Insert a new record
            $data = new \stdClass();
            $data->h5pid = $h5pId;
            $data->lang = $lang;
            $data->transcontent = $transContent;
            $DB->insert_record('local_h5ptranslate', $data);
            echo "Data inserted successfully!";
        }
    }


}


