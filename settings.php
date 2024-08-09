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
 * @package   local_h5ptranslate
 * @copyright 2023, Tina John <tina.john@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


 defined('MOODLE_INTERNAL') || die;

 if ($hassiteconfig) {
     $modfolder = new admin_category(
         'localh5ptranslatefolder',
         new lang_string(
             'pluginname',
             'local_h5ptranslate'
         )
     );
     $ADMIN->add('localplugins', $modfolder);
 
     $settingspage = new admin_settingpage('managelocalh5ptranslate', new lang_string('managelocalh5ptranslate', 'local_h5ptranslate'));
 
     if ($ADMIN->fulltree) {
         $settingspage->add(new admin_setting_configtext(
             'local_h5ptranslate/deeplapikey',
             new lang_string('deeplapikey', 'local_h5ptranslate'),
             new lang_string('deeplapikey_desc', 'local_h5ptranslate'),
             'not set yet',
             PARAM_TEXT
        )); 
        $settingspage->add(new admin_setting_configtext(
            'local_h5ptranslate/deeplapiUrl',
            new lang_string('deeplapiurl', 'local_h5ptranslate'),
            new lang_string('deeplapiurl_desc', 'local_h5ptranslate'), 
            'https://api-free.deepl.com/v2/translate',
            PARAM_TEXT
        )); 
        $settingspage->add(new admin_setting_configtext(
            'local_h5ptranslate/notranslationforlang',
            new lang_string('notranslationforlang', 'local_h5ptranslate'),
            new lang_string('notranslationforlang_desc', 'local_h5ptranslate'),
            'de',
            PARAM_TEXT
        )); 
    }
     $ADMIN->add('localh5ptranslatefolder', $settingspage);

 }
