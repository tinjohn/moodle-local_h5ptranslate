<?php


// Standard GPL and phpdocs
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../lib/filelib.php');


use local_h5ptranslate\h5ptranslate;
use local_h5ptranslate\h5pcontent;
use local_h5ptranslate\deepltranslate;
use core_h5p\player;

$home = new moodle_url('/');
$natpage = new moodle_url('/local/h5ptranslate');

//echo_readme();
do_translation();

/**
  * h5ptranslate.
  *
  */
function echo_readme() {
    global $DB;

    // for debug
    $id = 329;
    $lang = "en";
    //echo "h5ptranslate" ;
    //h5ptranslate::echo_hello();
    //h5pcontent::get_h5p_ids();
    $h5precord = h5pcontent::get_h5p_by_id($id); 
    if ($h5precord->filtered !== null) {
        $forhashing = $h5precord->filtered;
    } else {
        $contentJsonFile_content = $h5precord->jsoncontent;
    }
    $contentJsonFile_content = $h5precord->jsoncontent;

    // equals params in alter_filter
    $contentJson = json_decode($contentJsonFile_content);
    echo "<h1> json in DB </h1>";
    echo $contentJsonFile_content;
    echo "<h1> jsonArr decoded from DB </h1>";
    print_r($contentJson);

    // prepare for rendererers
    $parameters = $contentJson;

    // create params hash for faster string match - Params json encoded hashed
    // needs to be done in alter_filter
    echo "<h1> Params json encoded hashed</h1>";
    $forhashing_dec = json_decode($forhashing);
    $parametersenc = json_encode($forhashing_dec);
    $paramshash = file_storage::hash_from_string($parametersenc);
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

    # 2be88ca4242c76e8253ac62474851065032d6833


    // for renderers alter_filter 
    $contentJson = $parameters;

    // set array for ignore tag - might be an option
    $ignore_tags = array('library','buttonSize','decorative','contentName','path','mime','image',
    'copyright','subContentId','contentType','license','alwaysDisplayComments','buttonSize','goToSlideType','shape','type','borderStyle',
    'quizType','arithmeticType','equationType','useFractions','maxQuestions','nattx',
    'licenseVersion','fillColor','borderColor','subContentId','borderWidth','borderColor','borderRadius','borderStyle','borderWidth','contentName','name','buttonSize','playerMode','url',
    'color');

    // mark strings in array by reference and collect string in returned array
    $jsonstringPrepArray = h5ptranslate::markNextractTransStrings($contentJson, $ignore_tags);

    // XML Version of strings for better perfomance and single call for DeepL API
    echo "<h1> XMLed </h1>";
    $jsonstringPrep = h5ptranslate::flatarrayToXml2($jsonstringPrepArray);
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
    $contentJsonEncoded = json_encode($contentJson);
    $transcontentJsonEncoded = h5ptranslate::replaceTransTags($contentJsonEncoded, $translation);   
    echo "<h1> Translated </h1>";
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


    h5ptranslate::writeTranslationToDB ($id, $lang, $transcontentJsonEncoded, $paramshash);

}

function do_translation() {
    // for debug
    $id = 329;
    $lang = "en";
    
    $h5precord = h5pcontent::get_h5p_by_id($id); 
    if ($h5precord->filtered !== null) {
        $forhashing = $h5precord->filtered;
    } else {
        $contentJsonFile_content = $h5precord->jsoncontent;
    }
    $contentJsonFile_content = $h5precord->jsoncontent;

    // equals params in alter_filter
    $contentJson = json_decode($contentJsonFile_content);
    echo "<h1> json in DB </h1>";
    echo $contentJsonFile_content;
    echo "<h1> jsonArr decoded from DB </h1>";
    print_r($contentJson);

    $contentJsonforhashing = json_decode($forhashing);
    echo "<h1> json in DB </h1>";
    print_r($contentJsonforhashing);
    

    h5ptranslate::h5ptranslate($contentJsonforhashing, $lang);
}