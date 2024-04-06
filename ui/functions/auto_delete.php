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
// Get the global $DB object

global $DB, $USER, $CFG;

if($_POST['auto_delete']){
    
    // ================== DELETE ARCHIVED QUIZ ==================
        // SELECT QUIZ 60 DAYS OVER
        $archived = 1;
        $sql = "SELECT quizid
            FROM {auto_proctor_quiz_tb}
            WHERE archived = :archived
            AND archived_on < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ";
        $params = array('archived' => $archived);
        $archived_quiz = $DB->get_fieldset_sql($sql, $params);

        if (!empty($archived_quiz)){
            foreach($archived_quiz as $quiz){
                $quizid = $quiz;

                // DELETE ALL CAPTURED EVIDENCE
                        // Select all report under the selected quiz
                            $sql = "SELECT * FROM {auto_proctor_activity_report_tb}
                            WHERE quizid = :quizid
                        ";

                        $params = array('quizid' => $quizid);
                        $quiz_reports = $DB->get_records_sql($sql, $params);

                    if (!empty($quiz_reports)) {

                        foreach ($quiz_reports as $report) {
                            
                            $activity_type = $report->activity_type;
                            if ($activity_type >= 1 && $activity_type <= 5){
                                $directory = '../../proctor_tools/evidences/screen_capture_evidence/';
                            }

                            if ($activity_type >= 6 && $activity_type <= 10){
                                $directory = '../../proctor_tools/evidences/camera_capture_evidence/';
                            }

                            if ($activity_type >= 11 && $activity_type <= 14){
                                $directory = '../../proctor_tools/evidences/microphone_capture_evidence/';
                            }

                            $file_path = $directory . $report->evidence;

                            echo $file_path . "<br>";

                            $file_handle = @fopen($file_path, 'r');
                            if ($file_handle !== false) {
                                echo "File opened<br>";
                                // Check if the file exists
                                if (file_exists($file_path)) {
                                    echo "File exists<br>";
                                    // Attempt to delete the file
                                    if (unlink($file_path)) {
                                        echo "File deleted successfully.<br>";
                                    } else {
                                        echo "Error deleting the file.<br>";
                                    }
                                } else {
                                    echo "File does not exist<br>";
                                }
                            } else {
                                echo "Error opening the file.<br>";
                            }
                            
                        }
                    }
                    else {
                        echo "no report";
                    }


                    // Select all camera recording under the selected quiz
                        $sql = "SELECT * FROM {auto_proctor_session_camera_recording}
                            WHERE quizid = :quizid
                        ";

                        $params = array('quizid' => $quizid);
                        $quiz_recording = $DB->get_records_sql($sql, $params);
                    if (!empty($quiz_recording)) {
                        foreach ($quiz_recording as $recording) {
                            $directory = $CFG->wwwroot . '../../local/auto_proctor/proctor_tools/evidences/camera_capture_evidence/';
                            $file_path = $directory . $recording->camera_recording;
                            echo $file_path . "<br>";

                            if (unlink($file_path)) {
                                echo "File deleted successfully." . "<br>";
                            } else {
                                echo "Error deleting the file." . "<br>";
                            }
                        }
                    }
                    else {
                        echo "no report";
                    }

                // DELETE REMAINING CAPTURED FILES THAT ARE NOT RECORDED IN DATABASE
                    // Camera capture folder
                        $directory = '../../proctor_tools/evidences/camera_capture_evidence/';
                        // Scan the directory for files
                        $files = scandir($directory);

                        // Remove "." and ".." from the list
                        $files = array_diff($files, array('.', '..'));

                        // Output the list of files
                        foreach ($files as $file) {
                            // Check if the file is a PNG file
                            if (pathinfo($file, PATHINFO_EXTENSION) === 'png' || pathinfo($file, PATHINFO_EXTENSION) === 'webm' || pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                                // Construct the file path
                                $file_path = $directory . $file;
                                $parts = explode("_", $file); 
                                $quiz_number = $parts[4];

                                if ($quiz_number === $quizid){
                                    // Attempt to delete the file
                                    if (unlink($file_path)) {
                                        echo "File '$file' deleted successfully.<br>";
                                    } else {
                                        echo "Error deleting the file '$file'.<br>";
                                    }
                                }
                            }
                        }

                    // Screen capture folder
                        $directory = '../../proctor_tools/evidences/screen_capture_evidence/';
                        // Scan the directory for files
                        $files = scandir($directory);

                        // Remove "." and ".." from the list
                        $files = array_diff($files, array('.', '..'));

                        // Output the list of files
                        foreach ($files as $file) {
                            // Check if the file is a PNG file
                            if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                                // Construct the file path
                                $file_path = $directory . $file;
                                $parts = explode("_", $file); 
                                $quiz_number = $parts[4];

                                if ($quiz_number === $quizid){
                                    // Attempt to delete the file
                                    if (unlink($file_path)) {
                                        echo "File '$file' deleted successfully.<br>";
                                    } else {
                                        echo "Error deleting the file '$file'.<br>";
                                    }
                                }
                            }
                        }
                    // Microphone capture folder
                        $directory = '../../proctor_tools/evidences/microphone_capture_evidence/';
                        // Scan the directory for files
                        $files = scandir($directory);

                        // Remove "." and ".." from the list
                        $files = array_diff($files, array('.', '..'));

                        // Output the list of files
                        foreach ($files as $file) {
                            // Check if the file is a PNG file
                            if (pathinfo($file, PATHINFO_EXTENSION) === 'wav') {
                                // Construct the file path
                                $file_path = $directory . $file;
                                $parts = explode("_", $file); 
                                $quiz_number = $parts[4];

                                if ($quiz_number === $quizid){
                                    // Attempt to delete the file
                                    if (unlink($file_path)) {
                                        echo "File '$file' deleted successfully.<br>";
                                    } else {
                                        echo "Error deleting the file '$file'.<br>";
                                    }
                                }
                            }
                        }

                // DELETE FROM THE DATABASE

                    // Delete quiz
                        $sql = "DELETE from {auto_proctor_quiz_tb}
                                WHERE quizid = :quizid";

                        $params = array('quizid' => $quizid);
                        $delete_quiz = $DB->execute($sql, $params);

                    // Delete all report under the selected quiz
                        $sql = "DELETE from {auto_proctor_activity_report_tb}
                                WHERE quizid = :quizid";

                        $params = array('quizid' => $quizid);
                        $delete_reports = $DB->execute($sql, $params);

                    // Delete all camera recording under the selected quiz
                        $sql = "DELETE from {auto_proctor_session_camera_recording}
                            WHERE quizid = :quizid";

                        $params = array('quizid' => $quizid);
                        $delete_recordings = $DB->execute($sql, $params);

                    // Delete all trust score under the selected quiz
                        $sql = "DELETE from {auto_proctor_trust_score_tb}
                            WHERE quizid = :quizid";

                        $params = array('quizid' => $quizid);
                        $delete_trust_scores = $DB->execute($sql, $params);

                    // Delete all trust score under the selected quiz
                        $sql = "DELETE from {auto_proctor_proctoring_session_tb}
                            WHERE quizid = :quizid";

                        $params = array('quizid' => $quizid);
                        $delete_session = $DB->execute($sql, $params);
            }
        }
        else{
            echo "empty";
        }
}