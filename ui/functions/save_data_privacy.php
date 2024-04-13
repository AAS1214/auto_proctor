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

global $DB, $PAGE, $USER, $CFG;

// UPDATING THE DATA PRIVACY AGREEMENT TABLE
// IF THIS SCRIPT WAS CALLED THEN THE USER AGREED TO THE AGREEMENT
// REMEMBER: THIS SCRIPT WOULD ONLY BE TRIGGERED WHEN THE AGREED BUTTON IN THE SETUP MODAL IS CLICKED.

// If the setup data was sent from the setup modal,
// then process the sent data.
if(isset($_POST['userid'])){

    $userid = $_POST['userid'];
    $data_pa = $_POST['data_pa'];

        // SQL paramater
        $params = array('userid' => $userid, 'data_pa' =>  $data_pa);

        $sql = "UPDATE {auto_proctor_data_privacy_agreement_tb}
                SET agreed_to_the_agreement = :data_pa
                WHERE userid = :userid";

        // SQL execution
        $update_data_pa = $DB->execute($sql, $params);
}
?>