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
 * LearnR renderer class. This file is currently only used to extend the mod_h5p and mod_hvp renderers.
 *
 * @package   theme_learnr
 * @copyright 2022 Nina Herrmann <nina.herrmann@gmx.de>
 * @copyright on behalf of Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_h5p\player;
use local_h5ptranslate\h5ptranslate;
use local_h5ptranslate\deepltranslate;


/**
 * Extend the core_h5p renderer.
 *
 * @package   theme_learnr
 * @copyright 2022 Nina Herrmann <nina.herrmann@gmx.de>
 * @copyright on behalf of Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_learnr_core_h5p_renderer extends \core_h5p\output\renderer {

    /**
     * Add CSS styles when H5P content is displayed in core_h5p.
     *
     * @param array $styles Styles that will be applied.
     * @param array $libraries Libraries that wil be shown.
     * @param string $embedtype How the H5P is displayed.
     */
    public function h5p_alter_styles(&$styles, $libraries, $embedtype) {
        // Build the H5P CSS file URL.
        $h5pcssurl = new moodle_url('/theme/learnr/h5p/styles.php');

        // Add the CSS file path and a version (to support browser caching) to H5P.
        $styles[] = (object) array(
                'path' => $h5pcssurl->out(),
                'version' => '?ver='.theme_get_revision(),
        );
    }
    // Added tinjohn 20230802.
    
    
    public function h5p_alter_filtered_parameters(&$parameters, string $name, int $majorversion, int $minorversion) {
        global $DB;

        //echo '<br>' . $h5pContentId . '<br>';
        $trglang = current_language();
        if($trglang == "de") {
            return;
        }
        // Get the current language code.        
        echo "current lang:" . $trglang;
        
        // DEBUG return;

        // Speichere mal den Hash und vergleiche den in translate 
        
        //mach alles von index h5ptranslate index macht hier
        // ODER/UND

        // da bekommt man jedes h5p mit dieser Bibliothek - im Bsp. Deutsch und das manuell hochgeladene Spanisch
        // $name = "H5P.ImageHotspots";
        // $majorversion = 1;
        // $minorversion = 10;

        // die Übersetzung könnte jedoch gleich mit dem filtered content passieren
        // db braucht beides content und filtered content
        // contenthash ist das gefilterete
        // es bleibt die Frage inwiefern ein filtered null
        // SELECT h5pid FROM `mdl_h5p_contents_libraries` WHERE libraryid = (     	
        //     SELECT id as libid 
        //         FROM `mdl_h5p_libraries`
        //         WHERE `machinename` = 'H5P.ImageHotspots' AND `majorversion` = 1 AND `minorversion` = 10
        //         )

        # 329
        # 328

        // print_r($parameters);
        $jsencoded = json_encode($parameters);
        $parametersenc = json_encode($parameters);
        //echo "in renderers.php parameters &array encoded: ".  $parametersenc;
        $paramshash = file_storage::hash_from_string($parametersenc);
        echo " - paramshash " . $paramshash;
                
       // print_r($parameters);
   
       // get translated params from db
        //a working query for reference $h5pRecords = $DB->get_records('h5p', [], 'id ASC');
        $newRecord = FALSE;
        $sql = "SELECT transcontent FROM {local_h5ptranslate} WHERE paramshash = ? AND lang = ?";
        $h5pRecord = $DB->get_record_sql($sql, [$paramshash, $trglang]);

        if(!$h5pRecord) {
            echo " - no record found - try to translate onthefly";
            h5ptranslate::h5ptranslate($parameters, $trglang);
            $sql = "SELECT transcontent FROM {local_h5ptranslate} WHERE paramshash = ? AND lang = ?";
            $h5pRecord = $DB->get_record_sql($sql, [$paramshash, $trglang]);
            $newRecord = TRUE;
        }
        if ($h5pRecord || $newRecord) {
            echo "- load transversion";
            //echo "<h1>newparams json</h1>";
            //print_r($h5pRecord->transcontent);
            $parameterstrans = json_decode($h5pRecord->transcontent);
            //echo "<h1>newparams json decoded</h1>";
            //print_r($parameterstrans);
            //echo "<h1>orgparams</h1>";
            //print_r($parameters);
            $parameters = $parameterstrans;
        } else {
            echo "no translation available";

            // // // for renderers alter_filter 
            // $contentJson = $parameters;

            // // set array for ignore tag - might be an option
            // $ignore_tags = array('library','buttonSize','decorative','contentName','path','mime','image',
            // 'copyright','subContentId','contentType','license','alwaysDisplayComments','buttonSize','goToSlideType','shape','type','borderStyle',
            // 'quizType','arithmeticType','equationType','useFractions','maxQuestions','nattx',
            // 'licenseVersion','fillColor','borderColor','subContentId','borderWidth','borderColor','borderRadius','borderStyle','borderWidth','contentName','name','buttonSize','playerMode','url',
            // 'color');
            // //h5ptranslate::echo_hello();
            // // mark strings in array by reference and collect string in returned array
            // $jsonstringPrepArray = h5ptranslate::markNextractTransStrings($contentJson, $ignore_tags);
            // echo "<br>after h5ptranslate::markNextractTransStrings";
            // print_r($jsonstringPrepArray);

            // // XML Version of strings for better perfomance and single call for DeepL API
            // echo "<h1> XMLed </h1>";
            // $jsonstringPrep = h5ptranslate::flatarrayToXml2($jsonstringPrepArray);
            // $jsonstringPrep = html_entity_decode($jsonstringPrep);
            // print_r($jsonstringPrep);

            // echo "<h1> Translation </h1>";
            // // api key from config not working - it's working now
            // //$apiKey = get_config('local_h5ptranslate','deeplapikey');
            // //echo "<h1> apikey </h1>" . $apiKey ;
            // $translation = deepltranslate::transWithDeeplXML($jsonstringPrep, $trglang);
            // //DEBUG $translation = array();
            // print_r($translation);

            // $contentJsonEncoded = json_encode($contentJson);
            // $transcontentJsonEncoded = h5ptranslate::replaceTransTags($contentJsonEncoded, $translation);   
            // echo "<h1> Translated </h1>";
            // print_r($transcontentJsonEncoded);
        
            // echo "<h1> ready to write to DB </h1>";
            // h5ptranslate::writeTranslationToDB (NULL, $trglang, $transcontentJsonEncoded, $paramshash);

            // $parameters = json_decode($transcontentJsonEncoded);
        
        }

// ENDE




        //$player = H5PPlayer::getInstance();
        // Get the H5P content ID from the page context
        // NOT $h5pContentId = $PAGE->activityrecord->id;

        // Get the current H5P activity instance

        // Get the current player instance from the H5P activity instance
       // $currentPlayer = $h5pInstance->get_current_player();
       // $h5pContentId = $currentPlayer->contentId; 

        //echo '<br>' . $h5pContentId . '<br>';
        //$trglang = current_language();
        // Get the current language code.        
         //echo "current lang:" . $trglang . '<br>';
         //print_r($parameters);

         //$conthash = sha1($jsencoded ?? '');
         // echo '<br>' . $conthash . '<br>';
         
         //$h5pidsrecs = $DB->get_record('h5p', ['jsoncontent' => $jsencoded]);
         //  if ($h5pidsrecs) {
        //     foreach ($h5pidsrecs as $h5pidrec) {
        //         $h5pid = $h5pidsrec->id;
        //         echo $h5pid;
        //     }
        //  }
         
        //echo "<br>h5p filter finishd / off - name" . $name . "<br>";
        return;

        //     // parameter is an array pointer od decoded json in content.json stored to database/mdl_h5p
    //     // already has parameter but no id
    //     // only on the fly translation possible
        // USED (debugoff) translation with Deepl of flatten lang text array as json string
        function transWithDeeplXML ($string, $trglang) {
            // https://www.deepl.com/docs-api/translate-text/
            echo "translated with Deepl<br>"; 
            // Replace [yourAuthKey] with your actual DeepL API authentication key
            $authKey = '0bff0170-f25b-814e-a7fb-10e4ec2c1930:fx';
            //$authKey = 'for debugging off';
            if($authKey == 'for debugging off') {
                echo "<span style='color: red;'> DeepL API in debug mode: off string returned unprocessed</span><br>";
                return $string;
            }
            $apiUrl = 'https://api-free.deepl.com/v2/translate';

            // Data to be translated and target language
            $data = array(
                'text' => array($string),
                'target_lang' => $trglang,
                'tag_handling' => 'xml',
                'ignore_tags' => array('library','buttonSize','decorative','contentName','path','mime',
                'copyright','subContentId','contentType','license','alwaysDisplayComments','buttonSize','goToSlideType',
                'quizType','arithmeticType','equationType','useFractions','maxQuestions','nattx')

            );

            // Convert data to JSON format
            $dataJson = json_encode($data);

            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: DeepL-Auth-Key ' . $authKey,
                'Content-Type: application/json'
            ));

            // Execute cURL session and get the response
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                echo 'cURL Error: ' . curl_error($ch);
            }

            // Close cURL session
            curl_close($ch);

            // Decode the JSON response
            $translatedData = json_decode($response, true);

            // Output the translated text
            if (isset($translatedData['translations'][0]['text'])) {
                //echo " ------ remove the . in json file --------";
                $newstring = $translatedData['translations'][0]['text'];
                $newstring = trim($newstring, ".");
                return($newstring);
            } else {
                return($string);
            }
        }

        function jsonToXml($jsonString, $rootElement = 'root') {
            function arrayToXml($data, $xmlElement) {
                foreach ($data as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        if (!is_numeric($key)) {
                            $subnode = $xmlElement->addChild("$key");
                            if (is_object($value)) {
                                $value = json_decode(json_encode($value), true);
                            }
                            arrayToXml($value, $subnode);
                        } else {
                            arrayToXml($value, $xmlElement);
                        }
                    } else {
                        // Convert boolean values to string representation ("true" or "false")
                        $value = is_bool($value) ? ($value ? '@nattysbooleantrue' : '@nattysbooleanfalse') : $value;
                        // More moods within xml string.
                        $xmlElement->addChild("$key", htmlspecialchars("$value"));
                    }
                }
            }
            
    
            $dataArray = json_decode($jsonString);
            $xml = "<root></root>";
            $xml = simplexml_load_string($xml);
            arrayToXml($dataArray, $xml);
            return $xml->asXML();
            //return;
        }
            
        
        function replaceStringBooleanInJson($jsonString) {
            // Replace "true" with boolean true
            $jsonString = str_replace('"@nattysbooleantrue"', 'true', $jsonString);
        
            // Replace "false" with boolean false
            $jsonString = str_replace('"@nattysbooleanfalse"', 'false', $jsonString);
        
            return $jsonString;
        }
        
        function xmlToJson($xmlString) {
            $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml, JSON_PRETTY_PRINT | JSON_OBJECT_AS_ARRAY | JSON_NUMERIC_CHECK);
            $json = replaceStringBooleanInJson($json);
            return $json;
        }
        function tagVariables($jsonstringXML) { 
            $pattern = '#(:[a-z]{1,})#';
            $replacement = '<nattx>${1}</nattx>';
            $jsonstringXML = preg_replace($pattern,$replacement, $jsonstringXML);
    
            $pattern = '#(@[a-z]{1,})#';
            $replacement = '<nattx>${1}</nattx>';
            $jsonstringXML = preg_replace($pattern,$replacement, $jsonstringXML);
    
            return($jsonstringXML);
     }    
        function revertXmlPrepsOLD($xml) {
            $pattern = '#@dopppkt([a-z]{1,})#';
            $replacement = ':${1}';
            $xml = preg_replace($pattern,$replacement, $xml);
        
            $xml = str_replace('@nattyskey','',$xml);
        
            return($xml);
        }

        function revertXmlPreps($xml) {
            $pattern = '#<nattx>(:?[a-z]{1,})\</nattx>#';
            $replacement = '${1}';
            $xml = preg_replace($pattern,$replacement, $xml);
            $pattern = '#<nattx>(@?[a-z]{1,})\</nattx>#';
            $replacement = '${1}';
            $xml = preg_replace($pattern,$replacement, $xml);
        
            return($xml);
        }

        $trglang = current_language();
        // Get the current language code.        
         echo "current lang:" . $trglang . '<br>';
         if(!$trglang) {
            return;
         };
         if($trglang == "de") {
            return;
         };

         $jsencoded = json_encode($parameters);
         $conthash = sha1($jsencoded);

         echo '<br>' . $conthash . '<br>';
         $h5pidsrecs = $DB->get_record('h5p', ['contenthash' => $conthash],'id ASC');
         if ($h5pidsrecs) {
            $h5pid = $h5pidsrecs->id;
            echo $h5pid;
         }
         
        //  // extract values from parameter
        //  $jsonArray = $parameters;
        //  var_dump($jsonArray);
        //  $jsonString = json_encode($parameters);

        //  // funktioniert nur wenn xml die json structur abbilden kann
        //  $jsonstringXML = jsonToXml($jsonString);
        //  //var_dump($jsonstringXML);
        //  $jsonstringXML = tagVariables($jsonstringXML);
        // // translate values
        //  $translationXML = transWithDeeplXML($jsonstringXML, $trglang);
        //  //var_dump($translationXML);
        //  $translationXML = revertXmlPreps($translationXML);
        //  //var_dump($translationXML);
        //  $translationXMLjson = xmlToJson($translationXML);
        // // var_dump($translationXMLjson);
        //  $translationXMLjsondecode = json_decode($translationXMLjson);
        //  var_dump($translationXMLjsondecode);
        //  $parameters = $translationXMLjsondecode;
    }

}

// Only if mod_hvp is installed.
if (file_exists($CFG->dirroot.'/mod/hvp/renderer.php')) {
    // Load the mod_hvp renderer.
    require_once($CFG->dirroot.'/mod/hvp/renderer.php');

    // If the mod_hvp_renderer exists now.
    if (class_exists('mod_hvp_renderer')) {
        /**
         * Add CSS styles when H5P content is displayed in mod_hvp.
         *
         * @package   theme_learnr
         * @copyright 2022 Nina Herrmann <nina.herrmann@gmx.de>
         * @copyright on behalf of Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
         * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        class theme_learnr_mod_hvp_renderer extends mod_hvp_renderer {

            /**
             * Add styles when an H5(V)P is displayed.
             *
             * @param array $styles Styles that will be applied.
             * @param array $libraries Libraries that wil be shown.
             * @param string $embedtype How the H5P is displayed.
             */
            public function hvp_alter_styles(&$styles, $libraries, $embedtype) {
                // Build the H5P CSS file URL.
                $h5pcssurl = new moodle_url('/theme/learnr/h5p/styles.php');

                // Add the CSS file path and a version (to support browser caching) to H5P.
                $styles[] = (object)array(
                        'path' => $h5pcssurl->out(),
                        'version' => '?ver='.theme_get_revision(),
                );
            }
        }
    }
}
