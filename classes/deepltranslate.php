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


class deepltranslate {
    public static function getAPIkey () {
        // Ensure the configurations for this site are set
        $settings = get_config('local_h5ptranslate');
        var_dump($settings);
        
            // Check if the API key setting exists - not working
            $apiKey = get_config('local_h5ptranslate','deeplapikey');
            if($apiKey) {
                    // Use the API key
                echo "API Key: " . $apiKey;
                return $apiKey;
            } else {
                return "not set yet";
            }
        
    }

    // USED (debugoff) translation with Deepl of flatten lang text array as json string
    public static function transWithDeeplXML ($string, $trglang) {
        // https://www.deepl.com/docs-api/translate-text/
        echo "<h1> -- translate with Deepl -- </h1>"; 
        // Replace [yourAuthKey] with your actual DeepL API authentication key
        $authKey = self::getAPIkey();
        echo "<h1> KEY <h1>" . $authKey;
       
        //$authKey = 'for-debugging-off';
        if($authKey == 'for-debugging-off' || $authKey == 'not set yet') {
            echo "<h1 style='color: red;'> DeepL API key is: " . $authKey . "</h1>";
            return $string;
        }
        $apiUrl = 'https://api-free.deepl.com/v2/translate';

        // Data to be translated and target language
        $data = array(
            'text' => array($string),
            'target_lang' => $trglang,
            'tag_handling' => 'xml',
            'ignore_tags' => array('library','buttonSize','decorative','contentName','path','mime','image',
            'copyright','subContentId','contentType','license','alwaysDisplayComments','buttonSize','goToSlideType','shape','type','borderStyle',
            'quizType','arithmeticType','equationType','useFractions','maxQuestions','nattx','notranslation')
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
            echo " ------ remove the . in json file --------";
            $newstring = $translatedData['translations'][0]['text'];
            $newstring = trim($newstring, ".");
            return($newstring);
        } else {
            return($string);
        }
    }
    

}
