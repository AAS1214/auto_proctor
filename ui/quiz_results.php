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
 * @author      Renzi, Angelica
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @var stdClass $plugin
 */

require_once(__DIR__ . '/../../../config.php'); // Setup moodle global variable also
require_login();
// Get the global $DB object

global $DB, $USER, $CFG;
// Get user user id
$user_id = $USER->id;

// Check if the user has a managing role, such as an editing teacher or teacher.
// Only users with those roles are allowed to create or modify a quiz.
$managing_context = $DB->get_records_sql(
    'SELECT * FROM {role_assignments} WHERE userid = ? AND roleid IN (?, ?)',
    [
        $user_id,
        3, // Editing Teacehr
        4, // Teacher
    ]
);


echo "<script>console.log('courses enrolled: ', " . json_encode(count($managing_context)) . ");</script>";

// If a user does not have a course management role, there is no reason for them to access the Auto Proctor Dashboard.
// The user will be redirected to the normal dashboard.
if (!$managing_context && !is_siteadmin($user_id)) {
    $previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot . '/my/';  // Use a default redirect path if HTTP_REFERER is not set
    header("Location: $previous_page");
    exit();
}

// Check if user is techer in this course
$isteacher = false;
if (!is_siteadmin($user_id)) {

    // Loop through the context that the user manages
    foreach ($managing_context as $context) {

        // Get the context id of the context
        $context_id = $context->contextid;
        echo "<script>console.log('Managing Course IDhome: ', " . json_encode($context_id) . ");</script>";

        // Get instance id of the context from contex table
        $sql = "SELECT instanceid
                    FROM {context}
                    WHERE id= :id
                ";
        $instance_id = $DB->get_fieldset_sql($sql, ['id' => $context_id]);

        //echo $instance_id . "</br>";
        if ($_GET['course_id'] == $instance_id[0]) {
            //break;
            // echo "is teacher";
            // echo "</br>";
            $isteacher = true;
            break;
        }
    }
}

if (!$isteacher && !is_siteadmin($user_id)) {
    $previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot . '/my/';  // Use a default redirect path if HTTP_REFERER is not set
    header("Location: $previous_page");
    exit();
}

if (isset($_GET['course_id']) && isset($_GET['quiz_id'])) {
    $course_id = $_GET['course_id'];
    $quiz_name = $_GET['quiz_name'];
    $quiz_id = $_GET['quiz_id'];
    $params = array('course_id' => $course_id);

    // Retrieve all records from AP Table
    $AP_tb = 'auto_proctor_quiz_tb';
    $AP_records = $DB->get_records($AP_tb);

    // SELECTING COURSE FULLNAME
    $sql = "SELECT fullname
            FROM {course}
            WHERE id = :course_id;
        ";
    $course_name = $DB->get_fieldset_sql($sql, $params);

    $sql = "
        SELECT u.id AS user_id,
        CASE WHEN COUNT(c.id) > 0 THEN 'Yes' ELSE 'No' END AS enrolled_in_bs_it
        FROM {user} u
        LEFT JOIN {user_enrolments} ue ON u.id = ue.userid
        LEFT JOIN {enrol} e ON ue.enrolid = e.id
        LEFT JOIN {course} c ON e.courseid = c.id
        LEFT JOIN {course_categories} cc ON c.category = cc.id
        WHERE cc.name = 'Bachelor of Science in Information Technology (Boni Campus)'
        AND u.id = :user_id
        GROUP BY u.id;

    ";

    $userid = $USER->id;
    $params = array('user_id' => $userid);
    $is_user_enrolled_in_BSIT = $DB->get_records_sql($sql, $params);

    if (!empty($is_user_enrolled_in_BSIT)) {
        foreach ($is_user_enrolled_in_BSIT as $record) {
            $user_id = $record->user_id;
            $enrolled_status = $record->enrolled_in_bs_it;

            if ($enrolled_status === "Yes") {
                //print_r($is_user_enrolled_in_BSIT);
            }
        }
    }


    // COLLECTING ALL ID OF EXPECTED QUIZ TAKERS
    $sql = "SELECT u.*
                FROM {user} u
                JOIN {user_enrolments} ue ON u.id = ue.userid
                JOIN {enrol} e ON ue.enrolid = e.id
                LEFT JOIN {role_assignments} ra ON u.id = ra.userid
                WHERE e.courseid = :course_id
                AND ra.roleid <> (SELECT id FROM {role} WHERE shortname = 'editingteacher')
            ";

    $params = array('course_id' => $course_id);

    $enrolled_students = $DB->get_records_sql($sql, $params);

    // Initialize an array to store student IDs
    $student_ids = array();

    // Iterate over the results and push IDs into the array
    foreach ($enrolled_students as $student) {
        $student_ids[] = $student->id;
    }

    //echo implode(', ', $student_ids);

    $student_id_placeholders = implode(', ', array_map(function ($id) {
        return ':student_id_' . $id;
    }, $student_ids));


    // SELECTING QUIZ COMPLETERS
    $sql = "SELECT *
                FROM {quiz_attempts}
                WHERE quiz = :quiz_id
                AND userid IN ($student_id_placeholders)
                AND state = 'finished';
            ";

    $params = array('quiz_id' => $quiz_id);

    if ($student_ids){
        // Merge student IDs into params array
        foreach ($student_ids as $student_id) {
            $params['student_id_' . $student_id] = $student_id;
        }

        $quiz_completers = $DB->get_records_sql($sql, $params);

        // print_r($quiz_completers);
        // echo "</br>";

        // SELECTING IN PROGRESS QUIZ TAKERS
        $sql = "SELECT *
                    FROM {quiz_attempts}
                    WHERE quiz = :quiz_id
                    AND userid IN ($student_id_placeholders)
                    AND state = 'inprogress';
                ";

        $params = array('quiz_id' => $quiz_id);

        // Merge student IDs into params array
        foreach ($student_ids as $student_id) {
            $params['student_id_' . $student_id] = $student_id;
        }

        $inprogress_quiz_takers = $DB->get_records_sql($sql, $params);

        $num_of_inprogress = count($inprogress_quiz_takers);

        // print_r($inprogress_quiz_takers);
        // echo "</br>";

        // NUMBER OF EXEPECTED QUIZ TAKERS
        $num_of_all_students = count($enrolled_students);

        // NUMBER OF QUIZ COMPLETERS
        $num_of_quiz_completers = count($quiz_completers);

        // NUMBER OF STUDENT THAT SUBMITTED THE QUIZ
        $sql = "SELECT *
                    FROM {quiz_attempts}
                    WHERE quiz = :quiz_id
                    AND userid IN ($student_id_placeholders)
                    AND attempt = 1
                    AND state = 'finished';
                ";

        $params = array('quiz_id' => $quiz_id);

        // Merge student IDs into params array
        foreach ($student_ids as $student_id) {
            $params['student_id_' . $student_id] = $student_id;
        }

        $all_student_submitted = $DB->get_records_sql($sql, $params);

        $num_of_student_submitted = count($all_student_submitted);

        // NUMBER OF STUDENT THAT NOT YET SUBMITTED THE QUIZ
        $num_of_student_unsubmitted = $num_of_all_students - $num_of_student_submitted;


        // =========== QUIZ STATUS

        $sql = "SELECT timeclose
                    FROM {quiz}
                    WHERE id = :quiz_id;
                ";

        $params = array('quiz_id' => $quiz_id);
        $quiz_time_close = $DB->get_fieldset_sql($sql, $params);

        $date_quiz_created = date('j-M g:i A', $quiz_time_close[0]);
        $current_time = date('j-M g:i A');

        if ($date_quiz_created > $current_time) {
            $quiz_status = "In progress";
        } else {
            $quiz_status = "Complete";
        }

        if ($quiz_time_close[0] == 0){
            $quiz_status = "In progress";
        }

        //echo $current_time;


        // ========= SELECT DATE QUIZ CREATED
        $sql = "SELECT timecreated
                    FROM {quiz}
                    WHERE id = :quiz_id;
                ";

        $params = array('quiz_id' => $quiz_id);
        $date_quiz_created = $DB->get_fieldset_sql($sql, $params);

        // ============ ALL STUDENTS QUIZ ATTEMPTS
        $sql = "SELECT *
                    FROM {quiz_attempts}
                    WHERE quiz = :quiz_id;
                ";

        $params = array('quiz_id' => $quiz_id);

        $all_quiz_attempts = $DB->get_records_sql($sql, $params);

        $count_quiz_attempts = count($all_quiz_attempts);

        // print_r($all_quiz_attempts);
        // echo "</br>";


        // ========== SELECT ALL QUIZ ACTIVITY REPORT
        $sql = "SELECT *
                        FROM {auto_proctor_activity_report_tb}
                        WHERE quizid = :quiz_id;
                    ";

        $params = array('quiz_id' => $quiz_id);

        $all_quiz_reports = $DB->get_records_sql($sql, $params);
        //print_r($all_quiz_reports);
    }
}

?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<link rel="icon" type="image/x-icon" href="/images/favicon.ico">




<main>
    <div class="p-4 bg-white block sm:flex items-center justify-between pb-0 lg:mt-1.5 ">
        <div class="w-full mb-1">
            <div class="pt-10">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl "><?php echo $quiz_name; ?></h1>
                <span class="text-base font-normal text-gray-500 ">Report of all test takers and their
                    attempts</span>
            </div>
        </div>
    </div>
    <!-- for proctoreing setting -->
    <div class="p-4 bg-white">
        <div class="border-t border-gray-400 border-b grid w-full grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-3">
            <div class="items-center justify-between p-4 bg-white sm:flex border-r border-gray-400">
                <div class="w-full text-center">
                    <h3 class="text-base font-normal text-gray-500">Proctoring Settings:
                        <span id="editSettingsLink"><a href="<?php echo $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id=' . $course_id . '&quiz_id=' . $quiz_id . '&quiz_name=' . $quiz_name . '&course_name=' . $course_name[0] . '&quiz_settings=1'; ?>" class="text-blue-700 text-base">Edit Settings</a></span>
                    </h3>
                    <span class="text-base font-md font-bold text-gray-700">
                        <div class="flex space-x-6 sm:justify-center mt-2">
                            <button onclick="window.location.href='https:#';" class="text-gray-500 hover:text-gray-900  ">
                                <svg class=" text-gray-800 " viewBox="0 0 24 24" width=" 20px" height="20px" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="24" height="24" fill="white" />
                                    <circle cx="12" cy="12" r="9" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 5.5V12H18" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>

                            <button onclick="window.location.href='https:#';" class="text-gray-500 hover:text-gray-900  ">
                                <svg width=" 20px" height="20px" class=" text-gray-800 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="gray-800" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6H4a1 1 0 0 0-1 1v10c0 .6.4 1 1 1h10c.6 0 1-.4 1-1V7c0-.6-.4-1-1-1Zm7 11-6-2V9l6-2v10Z" />
                                </svg>

                            </button>
                            <button onclick="window.location.href='https:#';" class="text-gray-500 hover:text-gray-900  ">
                                <svg width=" 20px" height="208px" fill="#000000" class="w-[26px] h-[26px] text-gray-800 " viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M425.818 709.983V943.41c0 293.551 238.946 532.497 532.497 532.497 293.55 0 532.496-238.946 532.496-532.497V709.983h96.818V943.41c0 330.707-256.438 602.668-580.9 627.471l-.006 252.301h242.044V1920H667.862v-96.818h242.043l-.004-252.3C585.438 1546.077 329 1274.116 329 943.41V709.983h96.818ZM958.315 0c240.204 0 435.679 195.475 435.679 435.68v484.087c0 240.205-195.475 435.68-435.68 435.68-240.204 0-435.679-195.475-435.679-435.68V435.68C522.635 195.475 718.11 0 958.315 0Z" fill-rule="evenodd" />
                                </svg>

                            </button>
                            <button onclick="window.location.href='https:#';" class="text-gray-500 hover:text-gray-900  ">
                                <svg width=" 18px" height="18px" class=" text-gray-800 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M13 10c0-.6.4-1 1-1a1 1 0 1 1 0 2 1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                                    <path fill-rule="evenodd" d="M2 6c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v12c0 .6-.2 1-.6 1.4a1 1 0 0 1-.9.6H4a2 2 0 0 1-2-2V6Zm6.9 12 3.8-5.4-4-4.3a1 1 0 0 0-1.5.1L4 13V6h16v10l-3.3-3.7a1 1 0 0 0-1.5.1l-4 5.6H8.9Z" clip-rule="evenodd" />
                                </svg>

                            </button>
                            <button onclick="window.location.href='https:#';" class="text-gray-500 hover:text-gray-900  ">
                                <svg class="text-gray-800 " width=" 20px" height="20px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                        <g id="Icon-Set" sketch:type="MSLayerGroup" transform="translate(-256.000000, -671.000000)" fill="#000000">
                                            <path d="M265,675 C264.448,675 264,675.448 264,676 C264,676.553 264.448,677 265,677 C265.552,677 266,676.553 266,676 C266,675.448 265.552,675 265,675 L265,675 Z M269,675 C268.448,675 268,675.448 268,676 C268,676.553 268.448,677 269,677 C269.552,677 270,676.553 270,676 C270,675.448 269.552,675 269,675 L269,675 Z M286,679 L258,679 L258,675 C258,673.896 258.896,673 260,673 L284,673 C285.104,673 286,673.896 286,675 L286,679 L286,679 Z M286,699 C286,700.104 285.104,701 284,701 L260,701 C258.896,701 258,700.104 258,699 L258,681 L286,681 L286,699 L286,699 Z M284,671 L260,671 C257.791,671 256,672.791 256,675 L256,699 C256,701.209 257.791,703 260,703 L284,703 C286.209,703 288,701.209 288,699 L288,675 C288,672.791 286.209,671 284,671 L284,671 Z M261,675 C260.448,675 260,675.448 260,676 C260,676.553 260.448,677 261,677 C261.552,677 262,676.553 262,676 C262,675.448 261.552,675 261,675 L261,675 Z" id="browser" sketch:type="MSShapeGroup">

                                            </path>
                                        </g>
                                    </g>
                                </svg>
                            </button>
                            <!-- ARROW -->
                            <button onclick="window.location.href='https:#';" class="text-gray-500 hover:text-gray-900  ">
                                <svg class=" text-gray-800 " viewBox="0 0 24 24" width=" 25px" height="25px" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16.3891 8.11096L8.61091 15.8891" stroke="#333333" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M16.3891 8.11096L16.7426 12" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M16.3891 8.11096L12.5 7.75741" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                            </button>
                        </div>



                    </span>
                </div>
            </div>
            <div class="items-center justify-between p-4 bg-white sm:flex border-r border-gray-400">
                <div class="w-full text-center text-base">
                    <h3 class="text-base font-normal text-gray-500">Status</h3>
                    <span class="text-base font-md font-bold text-gray-700"><?php echo $quiz_status; ?></span>
                </div>
            </div>
            <div class="items-center justify-between p-4 bg-white sm:flex">
                <div class="w-full text-center">
                    <h3 class="text-base font-normal text-gray-500">Created On</h3>
                    <span class="text-base font-md font-bold text-gray-700"><?php echo date('j-M g:i A', $date_quiz_created[0]); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- insert here -->
    <div class="p-4 bg-white">
        <h1 class="px-4 text-xl mb-4 font-semibold text-gray-900 sm:text-2xl py-0">Submission Summary</h1>
        <div class="border-t border-gray-400 border-b grid w-full grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-3">
            <div class="items-center justify-between p-4 bg-white sm:flex border-r border-gray-400">
                <div class="w-full text-center">
                    <h3 class="text-base font-normal text-gray-500">Num Started</h3>
                    <span class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl"><?php echo $num_of_inprogress; ?></span>
                </div>
            </div>
            <div class="items-center justify-between p-4 bg-white sm:flex border-r border-gray-400">
                <div class="w-full text-center">
                    <h3 class="text-base font-normal text-gray-500">Num Submitted</h3>
                    <span class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl"><?php echo $num_of_student_submitted; ?></span>
                </div>
            </div>
            <div class="items-center justify-between p-4 bg-white sm:flex">
                <div class="w-full text-center">
                    <h3 class="text-base font-normal text-gray-500">Num Unsubmitted</h3>
                    <span class="text-2xl font-bold leading-none text-gray-900 sm:text-3xl"><?php echo $num_of_student_unsubmitted; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 items-center justify-between block sm:flex md:divide-x md:divide-gray-100 ">
        <div class="flex items-center mb-4 sm:mb-0">
            <form action="<?php echo  $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1'; ?>" method="GET" class=" lg:pl-3">
                <label for="topbar-search" class="sr-only">Search</label>
                <div class="relative mt-1 lg:w-72">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 px-2 py-2 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500 " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input type = "hidden" name = "course_id" value = "<?php echo $course_id; ?>">
                    <input type = "hidden" name = "quiz_id" value = "<?php echo $quiz_id; ?>">
                    <input type = "hidden" name = "quiz_name" value = "<?php echo $quiz_name; ?>">
                    <input type = "hidden" name = "quiz_results" value = "1">
                    <input type="text" id="myInput" onkeyup="myFunction()" name="searchKey" id="topbar-search" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 px-4 py-2  text-black " placeholder="Search">
                </div>
            </form>
        </div>
        <div class="items-center sm:flex">
            <div class="flex items-center">
                <button type="button" id="exportThis" data-quizid="<?php echo $quiz_id; ?>" data-quizname="<?php echo $quiz_name; ?>" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 uppercase focus:ring-blue-300 font-medium rounded-lg text-xs px-5 py-2.5 me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800" <?php if (!$student_ids){ echo 'disabled';}?>>Export</button>
            </div>
        </div>
    </div>
    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm ">
        <!-- Table -->
        <div class="flex flex-col mt-6">
            <div class="overflow-x-auto rounded-lg">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow sm:rounded-lg">
                        <table id="myTable" class="min-w-full divide-y divide-gray-200 ">
                            <thead class="bg-gray-100 ">
                                <tr>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <button id = "sortByNameBtn" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Name
                                                <span class="ml-2">

                                                    <svg width=" 25px" height="25px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6 9.65685L7.41421 11.0711L11.6569 6.82843L15.8995 11.0711L17.3137 9.65685L11.6569 4L6 9.65685Z" fill="#6b7280" />
                                                        <path d="M6 14.4433L7.41421 13.0291L11.6569 17.2717L15.8995 13.0291L17.3137 14.4433L11.6569 20.1001L6 14.4433Z" fill="#6b7280" />
                                                    </svg>

                                                </span>
                                            </div>
                                        </button>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <button id = "sortByEmailBtn" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Email
                                                <span class="ml-2">

                                                    <svg width=" 25px" height="25px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6 9.65685L7.41421 11.0711L11.6569 6.82843L15.8995 11.0711L17.3137 9.65685L11.6569 4L6 9.65685Z" fill="#6b7280" />
                                                        <path d="M6 14.4433L7.41421 13.0291L11.6569 17.2717L15.8995 13.0291L17.3137 14.4433L11.6569 20.1001L6 14.4433Z" fill="#6b7280" />
                                                    </svg>

                                                </span>
                                            </div>
                                        </button>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <button id = "sortByStartedBtn" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Started at
                                                <span class="ml-2">

                                                    <svg width=" 25px" height="25px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6 9.65685L7.41421 11.0711L11.6569 6.82843L15.8995 11.0711L17.3137 9.65685L11.6569 4L6 9.65685Z" fill="#6b7280" />
                                                        <path d="M6 14.4433L7.41421 13.0291L11.6569 17.2717L15.8995 13.0291L17.3137 14.4433L11.6569 20.1001L6 14.4433Z" fill="#6b7280" />
                                                    </svg>

                                                </span>
                                            </div>
                                        </button>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <a id = "submittedSort" href="<?php echo $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&submittedAsc=1'; ?>"
                                            <button class="hover:text-[#FFD66E]">
                                                <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                    Submitted at
                                                    <span class="ml-2">

                                                        <svg width=" 25px" height="25px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M6 9.65685L7.41421 11.0711L11.6569 6.82843L15.8995 11.0711L17.3137 9.65685L11.6569 4L6 9.65685Z" fill="#6b7280" />
                                                            <path d="M6 14.4433L7.41421 13.0291L11.6569 17.2717L15.8995 13.0291L17.3137 14.4433L11.6569 20.1001L6 14.4433Z" fill="#6b7280" />
                                                        </svg>

                                                    </span>
                                                </div>
                                            </button>
                                        </a>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <a id = "durationSort" href="<?php echo $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&durationAsc=1'; ?>"
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Duration
                                                <span class="ml-2">

                                                    <svg width=" 25px" height="25px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6 9.65685L7.41421 11.0711L11.6569 6.82843L15.8995 11.0711L17.3137 9.65685L11.6569 4L6 9.65685Z" fill="#6b7280" />
                                                        <path d="M6 14.4433L7.41421 13.0291L11.6569 17.2717L15.8995 13.0291L17.3137 14.4433L11.6569 20.1001L6 14.4433Z" fill="#6b7280" />
                                                    </svg>

                                                </span>
                                            </div>
                                        </button>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <a id = "trustSort" href="<?php echo $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&trustAsc=1'; ?>"
                                            <button class="hover:text-[#FFD66E]">
                                                <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                    Trust Score
                                                    <span class="ml-2">

                                                        <svg width=" 25px" height="25px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M6 9.65685L7.41421 11.0711L11.6569 6.82843L15.8995 11.0711L17.3137 9.65685L11.6569 4L6 9.65685Z" fill="#6b7280" />
                                                            <path d="M6 14.4433L7.41421 13.0291L11.6569 17.2717L15.8995 13.0291L17.3137 14.4433L11.6569 20.1001L6 14.4433Z" fill="#6b7280" />
                                                        </svg>

                                                    </span>
                                                </div>
                                            </button>
                                        </a>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        
                                    </th>

                                </tr>
                            </thead>
                            <tbody class="bg-white ">
                                <?php
                                // // SORTING FUNCTION SAMPLE
                                // function compareAttempts($attempt1, $attempt2) {
                                //     // Calculate duration of attempts
                                //     $duration1 = $attempt1->timefinish - $attempt1->timestart;
                                //     $duration2 = $attempt2->timefinish - $attempt2->timestart;

                                //     // Compare durations
                                //     if ($duration1 == $duration2) {
                                //         return 0;
                                //     }
                                //     return ($duration1 < $duration2) ? -1 : 1;
                                // }

                                // // Sort the array of attempts using the custom sorting function
                                // usort($all_quiz_attempts, 'compareAttempts');

                                function compareAttemptsAsc($a, $b) {
                                    if ($a->timefinish == $b->timefinish) {
                                        return 0;
                                    }
                                    return ($a->timefinish < $b->timefinish) ? -1 : 1;
                                }
                                
                                // Define a custom comparison function to compare the timefinish field of two attempts for descending order
                                function compareAttemptsDesc($a, $b) {
                                    if ($a->timefinish == $b->timefinish) {
                                        return 0;
                                    }
                                    return ($a->timefinish > $b->timefinish) ? -1 : 1;
                                }

                                function compareAttemptsDurationAsc($attempt1, $attempt2) {
                                    // Calculate duration of attempts
                                    $duration1 = $attempt1->timefinish - $attempt1->timestart;
                                    $duration2 = $attempt2->timefinish - $attempt2->timestart;
                                
                                    // Compare durations for ascending order
                                    if ($duration1 == $duration2) {
                                        return 0;
                                    }
                                    return ($duration1 < $duration2) ? -1 : 1;
                                }
                                
                                function compareAttemptsDurationDesc($attempt1, $attempt2) {
                                    // Calculate duration of attempts
                                    $duration1 = $attempt1->timefinish - $attempt1->timestart;
                                    $duration2 = $attempt2->timefinish - $attempt2->timestart;
                                
                                    // Compare durations for descending order
                                    if ($duration1 == $duration2) {
                                        return 0;
                                    }
                                    return ($duration1 > $duration2) ? -1 : 1;
                                }

                                function compareAttemptsTrustScoreAsc($attempt1, $attempt2) {
                                    global $DB;
                                
                                    // Get trust score for attempt 1
                                    $sql1 = "SELECT trust_score
                                             FROM {auto_proctor_trust_score_tb}
                                             WHERE userid = :user_id1
                                             AND quizid = :quiz_id1
                                             AND attempt = :quiz_attempt1";
                                    $params1 = array('user_id1' => $attempt1->userid, 'quiz_id1' => $attempt1->quiz, 'quiz_attempt1' => $attempt1->attempt);
                                    $trust_score1 = $DB->get_field_sql($sql1, $params1);
                                
                                    // Get trust score for attempt 2
                                    $sql2 = "SELECT trust_score
                                             FROM {auto_proctor_trust_score_tb}
                                             WHERE userid = :user_id2
                                             AND quizid = :quiz_id2
                                             AND attempt = :quiz_attempt2";
                                    $params2 = array('user_id2' => $attempt2->userid, 'quiz_id2' => $attempt2->quiz, 'quiz_attempt2' => $attempt2->attempt);
                                    $trust_score2 = $DB->get_field_sql($sql2, $params2);
                                
                                    // Compare trust scores for ascending order
                                    if ($trust_score1 == $trust_score2) {
                                        return 0;
                                    }
                                    return ($trust_score1 < $trust_score2) ? -1 : 1;
                                }
                                
                                function compareAttemptsTrustScoreDesc($attempt1, $attempt2) {
                                    global $DB;

                                    // Get trust score for attempt 1
                                    $sql1 = "SELECT trust_score
                                            FROM {auto_proctor_trust_score_tb}
                                            WHERE userid = :user_id1
                                            AND quizid = :quiz_id1
                                            AND attempt = :quiz_attempt1";
                                    $params1 = array('user_id1' => $attempt1->userid, 'quiz_id1' => $attempt1->quiz, 'quiz_attempt1' => $attempt1->attempt);
                                    $trust_score1 = $DB->get_field_sql($sql1, $params1);

                                    // Get trust score for attempt 2
                                    $sql2 = "SELECT trust_score
                                            FROM {auto_proctor_trust_score_tb}
                                            WHERE userid = :user_id2
                                            AND quizid = :quiz_id2
                                            AND attempt = :quiz_attempt2";
                                    $params2 = array('user_id2' => $attempt2->userid, 'quiz_id2' => $attempt2->quiz, 'quiz_attempt2' => $attempt2->attempt);
                                    $trust_score2 = $DB->get_field_sql($sql2, $params2);

                                    // Compare trust scores for descending order
                                    if ($trust_score1 == $trust_score2) {
                                        return 0;
                                    }
                                    return ($trust_score1 > $trust_score2) ? -1 : 1;
                                }
                                
                                if (isset($_GET['submittedAsc'])){
                                    usort($all_quiz_attempts, 'compareAttemptsAsc');

                                    echo "
                                        <script>
                                            var submittedSort = document.getElementById('submittedSort');
                                            
                                            // Change the href attribute
                                            submittedSort.setAttribute('href', '". $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&submittedDesc=1'."');
                                        </script>
                                    ";
                                }

                                if (isset($_GET['submittedDesc'])){
                                    usort($all_quiz_attempts, 'compareAttemptsDesc');

                                    echo "
                                        <script>
                                            var submittedSort = document.getElementById('submittedSort');
                                            
                                            // Change the href attribute
                                            submittedSort.setAttribute('href', '". $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&submittedAsc=1'."');
                                        </script>
                                    ";
                                }

                                if (isset($_GET['durationAsc'])){
                                    usort($all_quiz_attempts, 'compareAttemptsDurationAsc');

                                    echo "
                                        <script>
                                            var durationSort = document.getElementById('durationSort');
                                            
                                            // Change the href attribute
                                            durationSort.setAttribute('href', '". $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&durationDesc=1'."');
                                        </script>
                                    ";
                                }

                                if (isset($_GET['durationDesc'])){
                                    usort($all_quiz_attempts, 'compareAttemptsDurationDesc');

                                    echo "
                                        <script>
                                            var durationSort = document.getElementById('durationSort');
                                            
                                            // Change the href attribute
                                            durationSort.setAttribute('href', '". $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&durationAsc=1'."');
                                        </script>
                                    ";
                                }

                                if (isset($_GET['trustAsc'])){
                                    usort($all_quiz_attempts, 'compareAttemptsTrustScoreAsc');

                                    echo "
                                        <script>
                                            var trustSort = document.getElementById('trustSort');
                                            
                                            // Change the href attribute
                                            trustSort.setAttribute('href', '". $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&trustDesc=1'."');
                                        </script>
                                    ";
                                }

                                if (isset($_GET['trustDesc'])){
                                    usort($all_quiz_attempts, 'compareAttemptsTrustScoreDesc');

                                    echo "
                                        <script>
                                            var trustSort = document.getElementById('trustSort');
                                            
                                            // Change the href attribute
                                            trustSort.setAttribute('href', '". $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id='. $course_id . '&quiz_id=' . $quiz_id . '&quiz_name='. $quiz_name .'&quiz_results=1' . '&trustAsc=1'."');
                                        </script>
                                    ";
                                }

                                // === PAGINATION
                                    // Predict total num of pages
                                    $num_pages = count($all_quiz_attempts) / 30;

                                    // If number is not even
                                    if (is_float($num_pages)) {
                                        $float_page = $num_pages;
                                        $num_pages = (int)$float_page;
                                        $num_pages++;
                                    }

                                    // If number is 0
                                    if ($num_pages === 0){
                                        $num_pages = 1;
                                    }

                                    // Generate page name for the element
                                    $pages_name = array();
                                    $pages_name[] = $pagename; // Skipping the first array
                                    // Create name of the section per page
                                    for ($i = 1; $i <= $num_pages; $i++) {
                                        $pagenum++;
                                        $pagename = "page" . $pagenum;

                                        $pages_name[] = $pagename;

                                    }
                                //======


                                foreach ($all_quiz_attempts as $attempt) {
                                    $userid = $attempt->userid;

                                    // ======= SELECT USER INFO
                                    $sql = "SELECT *
                                                    FROM {user}
                                                    WHERE id = :userid";

                                    // Parameters for the query
                                    $params = array('userid' => $userid);
                                    $user_info = $DB->get_record_sql($sql, $params);

                                    $user_full_name = $user_info->firstname . ' ' . $user_info->lastname;

                                    $user_email = $user_info->email;

                                    // ======= SELECT USER'S ATTEMPT INFO
                                    $date_start_attempt = date('j-M g:i A', $attempt->timestart);

                                    if ($attempt->timefinish == 0) {
                                        $date_submitted = "-----------------";
                                    } else {
                                        $date_submitted = date('j-M g:i A', $attempt->timefinish);
                                    }

                                    // ======= COMPUTE DUATION
                                    // Convert the timestamps to Unix timestamp for calculation
                                    $start_timestamp = $attempt->timestart;
                                    $end_timestamp = $attempt->timefinish;

                                    // Calculate the duration in seconds
                                    $duration_seconds = $end_timestamp - $start_timestamp;

                                    // Calculate the duration in days, hours, minutes, and seconds
                                    $duration_days = floor($duration_seconds / (60 * 60 * 24));
                                    $duration_hours = floor(($duration_seconds % (60 * 60 * 24)) / 3600);
                                    $duration_minutes = floor(($duration_seconds % 3600) / 60);
                                    $duration_seconds = $duration_seconds % 60;

                                    // Format the duration
                                    $duration = sprintf("%d days, %02d:%02d:%02d", $duration_days, $duration_hours, $duration_minutes, $duration_seconds);

                                    if ($duration < 0) {
                                        $duration = "-----------------";
                                    }
                                    //echo "Duration: $duration";

                                         // SELECTING TRUST SCORE
                                            $sql = "SELECT trust_score
                                                FROM {auto_proctor_trust_score_tb}
                                                WHERE userid = :user_id
                                                AND quizid = :quiz_id
                                                AND attempt = :quiz_attempt;
                                            ";
                                            $params = array('user_id' => $attempt->userid, 'quiz_id' => $attempt->quiz, 'quiz_attempt' => $attempt->attempt);
                                            $trust_score = $DB->get_fieldset_sql($sql, $params);

                                    // === PAGINATION
                                        $attempt_counter++;

                                        if ($attempt_counter === 31){
                                            $attempt_counter = 1;
                                        }
                                        if ($attempt_counter === 1){
                                            $page_turner++;
                                        }
                                    // ===

                                    echo '
                                            <tr name = "'. $pages_name[$page_turner] .'" style = "display: none;">
                                            <td class="p-4 text-sm font-normal text-gray-900 whitespace-nowrap ">
                                                <span class="font-semibold">' . $user_full_name . '</span>
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                ' . $user_email . '
                                            </td>
                                            <td class="p-4 text-sm font-semibold text-gray-900 whitespace-nowrap ">
                                                ' . $date_start_attempt . '
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                ' . $date_submitted . '
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                ' . $duration . '
                                            </td>
                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                ' . $trust_score[0] . '%
                                            </td>
                                            <td class="p-4 whitespace-nowrap">
                                            <a href="' . $CFG->wwwroot . '/local/auto_proctor/ui/auto_proctor_dashboard.php?course_id=' . $course_id . '&user_id=' . $userid . '&quiz_id=' . $attempt->quiz . '&quiz_attempt=' . $attempt->attempt . '" class="text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5">View Report</a>
                                            </td>
                                            </tr>
                                        ';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- card footer -->
        <div id = "pagination_controls" class="sticky bottom-0 right-0 items-center w-full p-4 pb-2 bg-white border-t border-gray-200 sm:flex sm:justify-between ">
            <!-- note: do not delete this haha -->
            <div class="flex items-center mb-4 sm:mb-0">
            </div>
            <div class="flex items-center space-x-3">
                <div class="flex items-center mb-4 sm:mb-0 gap-1">
                    <!-- previous 2 -->
                    <a href="#" id = "prev" class="inline-flex border justify-center p-1 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                    <!-- next 1 -->
                    <a href="#" id = "next" class="inline-flex justify-center border  p-1 mr-1 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                    <span class="text-sm font-normal text-gray-500 ">Page <span class="font-semibold text-gray-900 " id = "page_locator"> 1 of 1 </span>| <span class="text-sm font-normal text-gray-500 pr-1 ">Go to Page</span></span>
                    <input type="text" id="page_num_text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-gray-500 focus:border-gray-500 block w-7 h-7 px-1" placeholder="1" value = "1">

                </div>
            </div>
        </div>
    </div>
</main>
<?php
    echo "<script> var pagesNum = []; var lastPageNumber = ".$num_pages."</script>";
    foreach ($pages_name as $p_name) {
        echo '<script>pagesNum.push("' . $p_name . '");</script>';
    }
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Select the element with the id 'exportThis'
        var exportButton = document.getElementById('exportThis');

        // Add click event listener
        exportButton.addEventListener('click', function(event) {
            // Prevent the default action of the button (i.e., submitting a form)
            event.preventDefault();

            // Retrieve the quizid and quizname from the data attributes
            var quizId = exportButton.getAttribute('data-quizid');
            var quizName = exportButton.getAttribute('data-quizname');

            // Send the quizid and quizname to a PHP script via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'functions/export_functions.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.responseType = 'blob'; // Set response type to blob
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        console.log('Files saved successfully');

                        // Initiate the download only when the response is received
                        var blob = new Blob([xhr.response], { type: 'application/zip' });
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'QUIZ_ID_' + quizId + '_' + quizName + '_reports.zip'; // Set zip file name
                        link.click();
                    } else {
                        console.error('Failed to save files');
                    }
                }
            };
            xhr.send('quiz_id=' + quizId + '&quiz_name=' + quizName);

        });

        // PAGINATION
            var displayElements = document.querySelectorAll("tr[name='page1']");
            var prev = document.getElementById('prev');
            var next = document.getElementById('next');
            var pageLocator = document.getElementById("page_locator");
            var currPageInput = document.getElementById('page_num_text');
            

            // DEFAULT SHOW PAGE 1
            for(var i = 0; i < displayElements.length; i++){
                displayElements[i].removeAttribute("style");
            }

            // Update the page locator
            pageLocator.textContent = "1 of " + lastPageNumber;

            var currPageInput = document.getElementById('page_num_text');
            var currPage = page_num_text.value;
            var currPageName = 'page' + currPage;

            prev.addEventListener('click', function(event) {
                var currPageInput = document.getElementById('page_num_text');
                var currPage = page_num_text.value;
                var currPageName = 'page' + currPage;

                var intendedPage;

                // Prevent the default action of the link (i.e., navigating to href)
                event.preventDefault();      
                console.log('prev clicked');   

                // If already in firstpage make it disable
                if(parseInt(currPage) === 1){
                    return;
                }

                // Disable the anchor element
                prev.removeAttribute('href');
                prev.disabled = true;

                // Predicting the intended page
                console.log('curr page: ', currPage);
                console.log('curr pagename: ', currPageName)
                intendedPage = parseInt(currPage) - 1;
                console.log('intended page: ', intendedPage)
                console.log('redirecting to page: ', pagesNum[intendedPage]);

                // If the page text box is empty or blank
                if (currPage === ""){
                    intendedPage = 1;
                }

                // Hide curr page
                var displayElements = document.querySelectorAll("tr[name='" + pagesNum[currPage] + "']");
                for(var i = 0; i < displayElements.length; i++){
                    displayElements[i].setAttribute("style", "display: none;");
                }

                // Display intended page
                var displayElements = document.querySelectorAll("tr[name='" + pagesNum[intendedPage] + "']");

                for(var i = 0; i < displayElements.length; i++){
                    displayElements[i].removeAttribute("style");
                }

                // Update page text holder
                currPageInput.value = intendedPage;

                // Update the page locator
                pageLocator.textContent = intendedPage + " of " + lastPageNumber;

                // Update input placeholder
                currPageInput.placeholder = intendedPage;

                currPageName = "page" + intendedPage;
            }); 

            next.addEventListener('click', function(event) {
                var currPageInput = document.getElementById('page_num_text');
                var currPage = page_num_text.value;
                var currPageName = 'page' + currPage;

                var intendedPage;

                // Prevent the default action of the link (i.e., navigating to href)
                event.preventDefault();      
                console.log('next clicked');   

                // If already in lastpage make it disable
                if(parseInt(currPage) === lastPageNumber){
                    return;
                }

                // Disable the anchor element
                next.removeAttribute('href');
                next.disabled = true;

                // Predicting the intended page
                console.log('curr page: ', currPage);
                console.log('curr pagename: ', currPageName)
                intendedPage = parseInt(currPage) + 1;
                console.log('intended page: ', intendedPage)
                console.log('redirecting to page: ', pagesNum[intendedPage]);

                // If the page text box is empty or blank
                if (currPage === ""){
                    intendedPage = lastPageNumber;
                }

                // Hide curr page
                var displayElements = document.querySelectorAll("tr[name='" + pagesNum[currPage] + "']");
                for(var i = 0; i < displayElements.length; i++){
                    displayElements[i].setAttribute("style", "display: none;");
                }

                // Display intended page
                var displayElements = document.querySelectorAll("tr[name='" + pagesNum[intendedPage] + "']");

                for(var i = 0; i < displayElements.length; i++){
                    displayElements[i].removeAttribute("style");
                }

                // Update page text holder
                currPageInput.value = intendedPage;

                // Update the page locator
                pageLocator.textContent = intendedPage + " of " + lastPageNumber;

                // Update input placeholder
                currPageInput.placeholder = intendedPage;

                currPageName = "page" + intendedPage;
            });  

            currPageInput.addEventListener("input", function() {
                var inputPage= currPageInput.value.trim(); // Trim any leading or trailing spaces
                var pageLocator = document.getElementById("page_locator");
                var content = pageLocator.textContent.trim(); // Get the text content and remove leading/trailing spaces
                var firstDigit = content.match(/\d/);
                var currPage = firstDigit[0];
                var currPageName = "page" + currPage;

                if (currPageInput !== "") {
                    // Process the input data
                    console.log("Input data:", currPageInput.value);

                    var intendedPage;
                    // Current page element

                    // If already in last page make it disable
                    if(currPageInput.value > lastPageNumber){
                        currPageInput.value = lastPageNumber;
                    }

                    // If already in first page make it disable
                    if(parseInt(currPageInput.value) < 1){
                        currPageInput.value = 1;
                    }

                    // Predicting the intended page
                    console.log('curr page: ', currPage);
                    console.log('curr pagename: ', currPageName)
                    intendedPage = currPageInput.value;
                    console.log('intended page: ', intendedPage)
                    console.log('redirecting to page: ', 'page' + intendedPage);

                    // Hide the current page
                    for (var i = 0; i < pagesNum.length; i++) {
                        if (pagesNum[i] != pagesNum[intendedPage]){
                            console.log('pages to hide: ',pagesNum[i]);
                            var hideElements = document.querySelectorAll("tr[name='" + pagesNum[i] + "']");
                            for(var j = 0; j < hideElements.length; j++){
                                hideElements[j].setAttribute("style", "display: none;");
                            }
                        }
                    }

                    // Dsiplay the current page
                    var displayElements = document.querySelectorAll("tr[name='" + pagesNum[intendedPage] + "']");
                    for(var i = 0; i < displayElements.length; i++){
                        displayElements[i].removeAttribute("style");
                    }

                    // Update page text holder
                    currPageInput.placeholder = intendedPage;

                    // Update the page locator
                    pageLocator.textContent = intendedPage + " of " + lastPageNumber;

                    // Update input placeholder
                    currPageInput.placeholder = intendedPage;

                    currPage = currPageInput.value;
                    currPageName = 'page' + currPage;
                }
                else{
                    // No input data
                    console.log("No input data");
                }
            });
        // ===

        // Preventing the search input to be submitted
        searchBox = document.getElementById("myInput");
        searchBox.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Prevent form submission when Enter is pressed
                console.log("Input value:", input.value); // You can do whatever you want with the value here
            }
        });
    });



    var ascending = true; // Initialize sorting direction as ascending
    var emailAscending = true;
    var startedAscending = true;
    var submittedAscending = true;

    function sortTableByName() {
        console.log("Sorting table by name...");
        var table = document.querySelector('.min-w-full');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            var nameA = a.querySelector('.p-4.text-sm.font-semibold').innerText.trim().toLowerCase();
            var nameB = b.querySelector('.p-4.text-sm.font-semibold').innerText.trim().toLowerCase();
            
            // Toggle sorting direction
            var comparison = ascending ? nameB.localeCompare(nameA) : nameA.localeCompare(nameB);
            return comparison;
        });
        
        // Reorder table rows in the sorted order
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        ascending = !ascending; // Toggle sorting direction
    }

    function sortTableByEmail() {
        console.log("Sorting table by email...");
        var table = document.querySelector('.min-w-full');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            var emailA = a.querySelector('.p-4.text-sm.font-normal').innerText.trim().toLowerCase();
            var emailB = b.querySelector('.p-4.text-sm.font-normal').innerText.trim().toLowerCase();
            
            // Toggle sorting direction for email
            var comparison = emailAscending ? emailA.localeCompare(emailB) : emailB.localeCompare(emailA);
            return comparison;
        });
        
        // Reorder table rows in the sorted order
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        emailAscending = !emailAscending; // Toggle sorting direction for email
    }

    function sortTableByStarted() {
        console.log("Sorting table by started at...");
        var table = document.querySelector('.min-w-full');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            var startedA = new Date(a.querySelector('.p-4.text-sm.font-semibold').innerText.trim());
            var startedB = new Date(b.querySelector('.p-4.text-sm.font-semibold').innerText.trim());
            
            // Toggle sorting direction for started at
            var comparison = startedAscending ? startedA - startedB : startedB - startedA;
            return comparison;
        });
        
        // Reorder table rows in the sorted order
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        startedAscending = !startedAscending; // Toggle sorting direction for started at
    }
    

    // Event listener to trigger sorting function when Name button is clicked
    document.getElementById("sortByNameBtn").addEventListener("click", function () {
        console.log("Name button clicked...");
        sortTableByName();
    });

    // Event listener to trigger sorting function when Email button is clicked
    document.getElementById("sortByEmailBtn").addEventListener("click", function () {
        console.log("Email button clicked...");
        sortTableByEmail();
    });

    document.getElementById("sortByStartedBtn").addEventListener("click", function () {
        console.log("Started at button clicked...");
        sortTableByStarted();
    });

    function myFunction() {
        var input, filter, table, tr, td, i, j, txtValue;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");
        
        for (i = 0; i < tr.length; i++) {
            // Loop through all td elements in the row
            var found = false;
            td = tr[i].getElementsByTagName("td");
            for (j = 0; j < td.length; j++) {
            txtValue = td[j].textContent || td[j].innerText; // Get the text content of the td
            if (txtValue.toUpperCase().indexOf(filter) > -1) { // Perform case-insensitive search
                found = true;
                break;
            }
            }
            if (found) {
            tr[i].style.display = ""; // Display the row if the search query is found in any column
            } else {
            tr[i].style.display = "none"; // Hide the row if the search query is not found in any column
            }
        }

        // PAGINATION
            var paginationControls = document.getElementById('pagination_controls');
            var pageInputPlaceholder = document.getElementById('page_num_text');
            var currPageInput = document.getElementById('page_num_text');

            // Hide the pagination control
            paginationControls.setAttribute("style", "display: none;");

            // If input is emptied
            // Go back to the recent page
            if (input.value === ""){
                console.log('blank');

                for (i = 0; i < tr.length; i++) {
                    // Loop through all td elements in the row
                    var found = false;
                    td = tr[i].getElementsByTagName("td");

                    tr[i].style.display = "none"; // Hide the row if the search query is not found in any column
                    
                }
                paginationControls.removeAttribute("style");

                var backToPage = "page" + pageInputPlaceholder.placeholder;
                console.log('redirecting to pageee: ', backToPage);
                var displayElements = document.querySelectorAll("tr[name='" + backToPage + "']");
                    for(var i = 0; i < displayElements.length; i++){
                        displayElements[i].removeAttribute("style");
                    }
            }
        // ===
    }

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<script src="https://flowbite-admin-dashboard.vercel.app//app.bundle.js"></script>