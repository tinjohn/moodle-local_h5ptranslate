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



 class h5pcontent {
    public static function isTranslatedInLang($h5pIdToCheck, $lang) {
        global $DB;

        // Check if the H5P ID exists in local_h5ptranslate with 'es' in lang column
        $translationRecord = $DB->get_record('local_h5ptranslate', ['h5pid' => $h5pIdToCheck, 'lang' => $lang]);

        if ($translationRecord) {
            // Translation exists
            return true; 
            // Perform your action here
        } else {
            // Translation does not exist
            return false;
            // Perform another action or display a message
        }        
    }

    public static function get_h5p_ids () {
        global $DB;

        // Query the database for H5P IDs and JSON content
        $h5pRecords = $DB->get_records('h5p', [], 'id ASC');

        if ($h5pRecords) {
            echo '<table>';
            echo '<tr><th>ID</th><th>JSON Content</th></tr>';
            foreach ($h5pRecords as $record) {
                echo '<tr>';
                echo '<td>' . $record->id . '</td>';
                if(self::isTranslatedInLang($record->id, $lang)) {
                    echo '<td>' . $lang . '</td>';
                }
                echo '<td>' . $record->contenthash . '</td>';
                echo '<td>' . htmlspecialchars($record->jsoncontent) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo 'No H5P records found.';
        }

        return($h5precords);

    }

    public static function get_h5p_by_id ($id) {
        global $DB;

        // Query the database for H5P IDs and JSON content
        $record = $DB->get_record('h5p', ['id' => $id]);

        if ($record) {
            echo '<table>';
            echo '<tr><th>ID</th><th>JSON Content</th></tr>';
                echo '<tr>';
                echo '<td>' . $record->id . '</td>';
                echo '<td>' . $record->contenthash . '</td>';
                echo '<td>' . htmlspecialchars($record->jsoncontent) . '</td>';
                echo '</tr>';
            echo '</table>';
            return($record);
        } else {
            echo 'No H5P records found.';
        }
        return(false);
    }


}
