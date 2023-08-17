<?php


// Standard GPL and phpdocs
require_once(__DIR__ . '/../../config.php');

use local_h5ptranslate\h5ptranslate;
use local_h5ptranslate\h5pcontent;
use local_h5ptranslate\deepltranslate;

$home = new moodle_url('/');
$natpage = new moodle_url('/local/livecoprogressuiups');

echo_readme();


/**
  * h5ptranslate.
  *
  */
function echo_readme() {
    global $DB;

    $id = 329;
    $lang = "en";
    $ignore_tags = array('library','buttonSize','decorative','contentName','path','mime','image',
    'copyright','subContentId','contentType','license','alwaysDisplayComments','buttonSize','goToSlideType','shape','type','borderStyle',
    'quizType','arithmeticType','equationType','useFractions','maxQuestions','nattx',
    'licenseVersion','fillColor','borderColor','subContentId','borderWidth','borderColor','borderRadius','borderStyle','borderWidth','contentName','name','buttonSize','playerMode','url',
    'color');
    //echo "h5ptranslate" ;
    //h5ptranslate::echo_hello();
    //h5pcontent::get_h5p_ids();
    $contentJsonFile_content = h5pcontent::get_h5p_by_id($id); 
    $contentJson = json_decode($contentJsonFile_content);
    print_r($contentJson);

    $jsonstringPrepArray = h5ptranslate::markTransStrings($contentJson, $ignore_tags);

    // KOPie
    $jsonstringPrep = h5ptranslate::flatarrayToXml2($jsonstringPrepArray);
    $jsonstringPrep = html_entity_decode($jsonstringPrep);

    echo "<h1> XMLed </h1>";
    print_r($jsonstringPrep);

    // api key from config not working
    $apiKey = get_config('local_h5ptranslate','deeplapikey');
    echo "<h1> apikey </h1>" . $apiKey ;

    $translation = deepltranslate::transWithDeeplXML($jsonstringPrep, $lang, $apiKey);
    echo "<h1> Translation </h1>";
    print_r($translation);

    $contentJsonEncoded = json_encode($contentJson);
    // repreprocession not possible new tags are touched
    //$translation = htmlentities($translation);
    //$translation=repreprocessing($translation);
    //file_put_contents($translation_xml_trg_file_rev, $translation);

    $transcontentJsonEncoded = h5ptranslate::replaceTransTags($contentJsonEncoded, $translation);   
    print_r($transcontentJsonEncoded);
    h5ptranslate::writeTranslationToDB ($id, $lang, $transcontentJsonEncoded);

}
