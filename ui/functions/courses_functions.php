<?php
// This file is part of Moodle Course Rollover Plugin
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
 * @package     local_auto_proctor
 * @author      Angelica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var stdClass $plugin
 */

require_once(__DIR__ . '/../../../../config.php'); // Setup moodle global variable also
require_login();
// Get the global $DB object

global $DB, $USER, $CFG;

if ($_POST['quizid']){
    $quizid = $_POST['quizid'];
    // The data that will be updated.
    $update_data = new stdClass();
    $update_data->archived = 1;
    $update_data->archived_on = date('Y-m-d H:i:s');
    $update_data->quizid = $quizid;

    $sql = "UPDATE {auto_proctor_quiz_tb}
            SET archived = :archived, archived_on = :archived_on
            WHERE quizid = :quizid";

    // Add the data that will be updated in parameter.
    $params['archived'] = $update_data->archived;
    $params['archived_on'] = $update_data->archived_on;
    $params['quizid'] = $update_data->quizid;
    $archive_quiz = $DB->execute($sql, $params);
}