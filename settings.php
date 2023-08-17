<?php

/**
 * @package   local_h5ptranslate
 * @copyright 2023, Tina John <tina.john@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Ensure the configurations for this site are set
if ($hassiteconfig) {

    // Create the new settings page
    // - in a local plugin this is not defined as standard, so normal $settings->methods will throw an error as
    // $settings will be null
    $settings = new admin_settingpage('local_h5ptranslate', 'H5P translate');

    // Create
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
        // This is the reference you will use to your configuration
        'local_h5ptranslte/deeplapikey',

        // This is the friendly title for the config, which will be displayed
        'DeepL API: Key',

        // This is helper text for this config field
        'This is the key is available from deepl.org',

        // This is the default value
        'not set yet',

        // This is the type of Parameter this config is
        PARAM_TEXT
    ));
}
