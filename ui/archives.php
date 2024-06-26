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

// If a user does not have a course management role, there is no reason for them to access the Auto Proctor Dashboard.
// The user will be redirected to the normal dashboard.
if (!$managing_context && !is_siteadmin($user_id)) {
    $previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $CFG->wwwroot . '/my/';  // Use a default redirect path if HTTP_REFERER is not set
    header("Location: $previous_page");
    exit();
}
// Now, we will retrieve all the context IDs for the instances or context that the user manages.

// ========= IF USER IS TEACHER
if (!is_siteadmin($user_id)) {
    // Array for the course IDs we will retrieve.
    $course_ids = array();

    // Loop through the context that the user manages
    foreach ($managing_context as $context) {

        // Get the context id of the context
        $context_id = $context->contextid;
        echo "<script>console.log('Managing Course ID: ', " . json_encode($context_id) . ");</script>";

        // Get instance id of the context from contex table
        $sql = "SELECT instanceid
                FROM {context}
                WHERE id= :id";
        $instance_ids = $DB->get_fieldset_sql($sql, ['id' => $context_id]);

        echo "<script>console.log('instance id: ', " . json_encode($instance_ids) . ");</script>";

        // Push the instance_ids into the $course_ids array
        $course_ids = array_merge($course_ids, $instance_ids);
    }

    // Select category id of BSIT
    $course_name = 'Bachelor of Science in Information Technology (Boni Campus)';
    $sql = "SELECT id
                    FROM {course_categories}
                    WHERE name = :course_name;
                ";
    $params = array('course_name' => $course_name);
    $bsit_id = $DB->get_fieldset_sql($sql, $params);

    foreach ($course_ids as $course_id) {
        $sql = "SELECT category
                    FROM {course}
                    WHERE id = :course_id;
                ";
        $params = array('course_id' => $course_id);
        $course_category = $DB->get_fieldset_sql($sql, $params);

        if ($course_category[0] === $bsit_id[0]) {
            //$course_ids[] = $course_id;
            // echo "an it: " . $course_id . '</br>';
            $course_ids[] = $course_id;
        }
    }

    // Push the instance_ids into the $course_ids array
    $course_ids = array_merge($course_ids);

    $course_id_placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    // GET ALL QUIZZES OF COURSES IN AUTOPROCTOR TABLE
    $sql = "SELECT *
                FROM {auto_proctor_quiz_tb}
                WHERE course IN ($course_id_placeholders)
                AND archived = 1;
            ";
    $ap_quiz_records = $DB->get_records_sql($sql, $course_ids);
    echo "<script>console.log('All Course IDs: ', " . json_encode($course_ids) . ");</script>";
    //print_r($ap_quiz_records);

    // ======= NUMBER OF ALL QUIZZES
    $num_of_all_quizzes= count($ap_quiz_records);
}

// ======== IF USER IS ADMIN
if (is_siteadmin($user_id)) {
    $course_ids = array();
    $sql = "
            SELECT c.id AS course_id, ctx.id AS context_id
            FROM {course} c
            JOIN {course_categories} cc ON c.category = cc.id
            JOIN {context} ctx ON ctx.instanceid = c.id
            WHERE cc.name = 'Bachelor of Science in Information Technology (Boni Campus)'
        ";

    $courses = $DB->get_records_sql($sql);

    foreach ($courses as $course) {
        $course_ids[] = $course->course_id;
    }

    $course_ids = array_merge($course_ids);
    $course_id_placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    // GET ALL QUIZZES OF COURSES IN AUTOPROCTOR TABLE
    $sql = "SELECT *
                FROM {auto_proctor_quiz_tb}
                WHERE course IN ($course_id_placeholders)
                AND archived = 1;
            ";
    $ap_quiz_records = $DB->get_records_sql($sql, $course_ids);
    echo "<script>console.log('All Course IDs: ', " . json_encode($course_ids) . ");</script>";

    // ======= NUMBER OF ALL QUIZZES
    $num_of_all_quizzes= count($ap_quiz_records);
}

// Get the wwwroot of the site
$wwwroot = $CFG->wwwroot;;

?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<link rel="icon" type="image/x-icon" href="/images/favicon.ico">

<main>
    
    <!-- <div class=" p-4 items-center  justify-between block sm:flex  mt-16">
        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl ">ARCHIVES</h1>
        <div class="flex items-center mb-4 sm:mb-0">
            <form action="#" method="GET" class=" lg:pl-3">
                <label for="topbar-search" class="sr-only">Search</label>
                <div class="relative mt-1 lg:w-72">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 px-2 py-2 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500 " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input type="text" name="text" id="topbar-search" onkeyup="myFunction()" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 px-4 py-2  text-white " placeholder="Search">
                </div>
            </form>
            
        </div>
    </div> -->

        <!-- NEW CODE -->
    <div class="p-4 items-center justify-between flex flex-col-reverse sm:flex-row mt-16">
        <!-- ARCHIVES TEXT -->
        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl mb-4 sm:mb-0">ARCHIVES</h1>
    
        <!-- SEARCH INPUT -->
        <div class="flex items-center sm:flex-1 justify-end mb-4 sm:mb-0 mr-2">
            <form action="#" method="GET" class="lg:pl-3">
                <label for="topbar-search" class="sr-only">Search</label>
                <div class="relative mt-1 lg:w-72">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 px-2 py-2 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input type="text" name="text" id="topbar-search" onkeyup="myFunction()" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 px-4 py-2 text-gray" placeholder="Search" style="margin-right: 10px;">
                </div>
            </form>
        </div>

        <!-- DELETE ALL BUTTON -->
        <?php
            if (is_siteadmin($user_id)){
                if ($ap_quiz_records){
                    echo '
                    <div class="flex items-center">
                        <div class="flex items-center">
                            <a href="" id = "deleteAll" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 uppercase focus:ring-blue-300 font-medium rounded-lg text-xs px-5 py-2.5 me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800" data-quizid="delete_all_archive">Delete All</a>
                        </div>
                    </div>
                    ';
                }
                else{
                    echo '
                    <div class="flex items-center">
                        <div class="flex items-center">
                            <button class="text-white bg-gray-400 focus:ring-4 uppercase font-medium rounded-lg text-xs px-5 py-2.5 me-2 mb-2 dark:bg-gray-600 dark:hover:bg-gray-400" data-quizid="delete_all_archive" disabled>Delete All</button>
                        </div>
                    </div>
                    ';
                }
            }
        ?>
    </div>
    <!-- NEW CODE -->
    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm ">
        <!-- Table -->
        <div class="flex flex-col mt-6">
            <div class="overflow-x-auto rounded-lg">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow sm:rounded-lg">
                        <table id="quizTable" class="min-w-full divide-y divide-gray-200 ">
                            <thead class="bg-gray-100 ">
                                <tr>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <button onclick="sortTableByName()" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Quiz Name
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
                                        <button onclick="sortTableByCourse()" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                course
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
                                        <button onclick="sortTableByProctor()" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Proctor
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
                                        <button onclick="sortTableByDate()" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                Date Archived
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
                                        <button onclick="window.location.href='https:#';" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                
                                                <span class="ml-2">

                                            
                                                </span>
                                            </div>
                                        </button>
                                    </th>
                                    <th scope="col" class="p-4 text-sm font-bold tracking-wider text-left text-gray-700">
                                        <button onclick="window.location.href='https:#';" class="hover:text-[#FFD66E]">
                                            <div class="flex items-center uppercase text-xs font-medium tracking-wider ">
                                                
                                                <span class="ml-2">

                                            
                                                </span>
                                            </div>
                                        </button>
                                    </th>

                                </tr>
                            </thead>
                            <tbody class="bg-white ">
                                <?php
                                    //echo "num of quizzes: " . $num_of_all_quizzes . "</br>";

                                    // Predict total num of pages
                                    $num_pages = $num_of_all_quizzes / 30;
                                    //echo "num of pages: " . $num_pages . "</br>";
    
                                    // If number is not even
                                    if (is_float($num_pages)) {
                                        $float_page = $num_pages;
                                        $num_pages = (int)$float_page;
                                        $num_pages++;
                                    }
                                    //echo "num of pages: " . $num_pages . "</br>";

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

                                    //print_r($pages_name);
                                    foreach ($ap_quiz_records as $archived_quiz) {
                                        // Select quiz name
                                        $sql = "SELECT name
                                                                    FROM {quiz}
                                                                    WHERE id = :quiz_id;
                                                                ";
                                        $param = array('quiz_id' => $archived_quiz->quizid);
                                        $quiz_name = $DB->get_fieldset_sql($sql, $param);

                                        // Selec quiz course name
                                        $sql = "SELECT shortname
                                                            FROM {course}
                                                            WHERE id = :course_id;
                                                        ";
                                        $param = array('course_id' => $archived_quiz->course);
                                        $course_name = $DB->get_fieldset_sql($sql, $param);

                                        // Select quiz teacher name
                                        $teacher_role_id = 3;
                                        $editing_teacher_role_id = 4;

                                        $sql = "SELECT DISTINCT u.*
                                                                FROM {user} u
                                                                INNER JOIN {role_assignments} ra ON ra.userid = u.id
                                                                INNER JOIN {context} ctx ON ctx.id = ra.contextid
                                                                INNER JOIN {course} c ON c.id = ctx.instanceid
                                                                WHERE c.id = :course_id
                                                                AND (ra.roleid = :teacher_role_id OR ra.roleid = :editing_teacher_role_id)";

                                        // Parameters for the SQL query
                                        $params = array(
                                            'course_id' => $archived_quiz->course,
                                            'teacher_role_id' => $teacher_role_id,
                                            'editing_teacher_role_id' => $editing_teacher_role_id
                                        );

                                        $course_teacher = $DB->get_records_sql($sql, $params);

                                        // // Select quiz date created
                                        // $sql = "SELECT timecreated
                                        //                         FROM {quiz}
                                        //                         WHERE id = :quiz_id;
                                        //                     ";
                                        // $param = array('quiz_id' => $archived_quiz->quizid);
                                        // $date_created = $DB->get_fieldset_sql($sql, $param);

                                        $timestamp = strtotime($archived_quiz->archived_on);
                                        $date_archived = date("d M Y", $timestamp);

                                        $quiz_counter++;

                                        if ($quiz_counter === 31){
                                            $quiz_counter = 1;
                                        }
                                        if ($quiz_counter === 1){
                                            $page_turner++;
                                        }

                                        echo '
                                                        <tr name = "'. $pages_name[$page_turner] .'" style = "display: none;">
                                                            <td class="p-4 text-sm font-semibold  whitespace-nowrap text-gray-800">
                                                                <h1>' . $quiz_name[0] . '</h1>
                                                                <span class="font-normal text-[10px] text-center">
                                                                
                                                                </span>
                                                            </td>
                                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                                ' . $course_name[0] . '
                                                            </td>
                                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                            ';
                                        foreach ($course_teacher as $teacher) {
                                            $teacher_fullname = $teacher->firstname . ' ' . $teacher->lastname;
                                            echo $teacher_fullname;
                                        }
                                        echo '
                                                            </td>
                                                            <td class="p-4 text-sm font-normal text-gray-500 whitespace-nowrap ">
                                                                ' . $date_archived . '
                                                            </td>
                                                            <td class="p-4 text-sm font-normal text-blue-700 hover:text-blue-900 whitespace-nowrap ">
                                                                    <a href="" class="restoreThis" data-quizid="' . $archived_quiz->quizid . '">Restore</a>
                                                            </td>';
                                                            if (is_siteadmin($user_id)) {
                                                            echo '
                                                            <td class="p-4 text-sm font-normal text-red-700 hover:text-red-900 whitespace-nowrap ">
                                                                    <a href="" class="deleteThis" data-quizid="' . $archived_quiz->quizid . '">Delete</a>
                                                            </td>
                                                        </tr>
                                                    ';
                                                            }
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- card footer -->
        <div class="sticky bottom-0 right-0 items-center w-full p-4 pb-2 bg-white border-t border-gray-200 sm:flex sm:justify-between ">
            <!-- note: do not delete this haha -->
            <div class="flex items-center mb-4 sm:mb-0">
            </div>
            <div class="flex items-center space-x-3">
                <div id = "pagination_controls" class="flex items-center mb-4 sm:mb-0 gap-1">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<script src="https://flowbite-admin-dashboard.vercel.app//app.bundle.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // ====== DELETE, RESTORE, DELETE ALL CONTROLS
            // Select all elements with class 'archiveThis'
            var restoreLinks = document.querySelectorAll('.restoreThis');
            var deleteLinks = document.querySelectorAll('.deleteThis');
            var deleteAllLink = document.getElementById("deleteAll");

            // Iterate over each 'archiveThis' link
            restoreLinks.forEach(function(link) {
                // Add click event listener
                link.addEventListener('click', function(event) {
                    // Prevent the default action of the link (i.e., navigating to href)
                    event.preventDefault();
                    //createOverlay();

                    var confirmation = confirm("Restore an archived quiz?");

                    if (confirmation) {

                        // Retrieve the quizid from the data attribute
                        var quizId = link.getAttribute('data-quizid');

                        // Send the quizid to a PHP script via AJAX
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'functions/archives_functions.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                                alert("Quiz restored successfully.");
                                //removeOverlay();
                                location.reload();
                            }
                        };
                        xhr.send('quizid=' + quizId);
                    }
                    // When page is loading prevent clicking archive button
                    // when still loading it will not function
                    restoreLinks.removeAttribute('href');
                    restoreLinks.disabled = true;

                    // Here you can perform further actions like sending the quizId via AJAX
                });
            });

            deleteLinks.forEach(function(link) {
                // Add click event listener
                link.addEventListener('click', function(event) {
                    // Prevent the default action of the link (i.e., navigating to href)
                    event.preventDefault();
                    //createOverlay();

                    var confirmation = confirm("Delete an archived quiz?");

                    if (confirmation) {
                        // Retrieve the quizid from the data attribute
                        var quizId = link.getAttribute('data-quizid');

                        // Send the quizid to a PHP script via AJAX
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'functions/delete_archive.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                                alert("Quiz deleted successfully.");
                                //removeOverlay();
                                location.reload();
                            }
                        };
                        xhr.send('quizid=' + quizId);
                    }
                    
                    // When page is loading prevent clicking archive button
                    // when still loading it will not function
                    deleteLinks.removeAttribute('href');
                    deleteLinks.disabled = true;

                    // Here you can perform further actions like sending the quizId via AJAX
                });
            });

            deleteAllLink.addEventListener('click', function(event) {
                // Prevent the default action of the link (i.e., navigating to href)
                event.preventDefault();

                var confirmation = confirm("Delete all archived quizzes?");

                if (confirmation) {

                    // Retrieve the quizid from the data attribute
                    var quizId = deleteAllLink.getAttribute('data-quizid');

                    // Send the quizid to a PHP script via AJAX
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'functions/delete_archive.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                            alert("All archived quizzes deleted successfully.");
                            console.log('All archived quizzes deleted successfully.');
                            // You may want to perform some UI updates here
                            //removeOverlay();
                            location.reload();
                        }
                    };
                    xhr.send('quizid=' + quizId);
                }
                
                // Disable the link
                deleteAllLink.removeAttribute('href');
                deleteAllLink.disabled = true;
            });
        // ======

        // Preventing the search input to be submitted
        searchBox = document.getElementById("topbar-search");
        searchBox.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Prevent form submission when Enter is pressed
                console.log("Input value:", input.value); // You can do whatever you want with the value here
            }
        });
    });

    function createOverlay() {
        // Check if overlay already exists
        if (!document.getElementById('overlay')) {
            // Create a div element for the overlay
            var overlay = document.createElement('div');

            // Set attributes for the overlay
            overlay.id = 'overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
            overlay.style.zIndex = '9999';

            // Append the loading animation HTML to the overlay
            overlay.innerHTML = `
                <style>
                    body {
                        font-family: 'Titillium Web', sans-serif;
                        font-size: 18px;
                        font-weight: bold;
                    }
                    .loading {
                        position: absolute;
                        left: 0;
                        right: 0;
                        top: 50%;
                        width: 100px;
                        color: #000;
                        margin: auto;
                        -webkit-transform: translateY(-50%);
                        -moz-transform: translateY(-50%);
                        -o-transform: translateY(-50%);
                        transform: translateY(-50%);
                    }
                    .loading span {
                        position: absolute;
                        height: 10px;
                        width: 84px;
                        top: 50px;
                        overflow: hidden;
                    }
                    .loading span > i {
                        position: absolute;
                        height: 10px;
                        width: 10px;
                        border-radius: 50%;
                        -webkit-animation: wait 4s infinite;
                        -moz-animation: wait 4s infinite;
                        -o-animation: wait 4s infinite;
                        animation: wait 4s infinite;
                    }
                    .loading span > i:nth-of-type(1) {
                        left: -28px;
                        background: black;
                    }
                    .loading span > i:nth-of-type(2) {
                        left: -21px;
                        -webkit-animation-delay: 0.8s;
                        animation-delay: 0.8s;
                        background: black;
                    }
                    @keyframes wait {
                        0%   { left: -7px  }
                        30%  { left: 52px  }
                        60%  { left: 22px  }
                        100% { left: 100px }
                    }
                </style>
                <div class="loading">
                    <p>Please wait</p>
                    <span><i></i><i></i></span>
                </div>`;

            // Append the overlay to the body
            document.body.appendChild(overlay);
        }
    }

    // Function to remove overlay
    function removeOverlay() {
        var overlay = document.getElementById('overlay');
        if (overlay) {
            overlay.parentNode.removeChild(overlay);
        }
    }

    var ascending = true;
    var ascendingCourse = true;
    var ascendingProctor = true;
    var ascendingDate = true;

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

    function sortTableByCourse() {
        console.log("Sorting table by course...");
        var table = document.querySelector('.min-w-full');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            var courseA = a.querySelector('.p-4.text-sm.font-normal:nth-of-type(2)').innerText.trim().toLowerCase();
            var courseB = b.querySelector('.p-4.text-sm.font-normal:nth-of-type(2)').innerText.trim().toLowerCase();
            
            // Toggle sorting direction
            var comparison = ascendingCourse ? courseB.localeCompare(courseA) : courseA.localeCompare(courseB);
            return comparison;
        });
        
        // Reorder table rows in the sorted order
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        ascendingCourse = !ascendingCourse; // Toggle sorting direction
    }

    function sortTableByProctor() {
        console.log("Sorting table by proctor...");
        var table = document.querySelector('.min-w-full');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            var proctorA = a.querySelector('.p-4.text-sm.font-normal:nth-of-type(3)').innerText.trim().toLowerCase();
            var proctorB = b.querySelector('.p-4.text-sm.font-normal:nth-of-type(3)').innerText.trim().toLowerCase();
            
            // Toggle sorting direction
            var comparison = ascendingProctor ? proctorB.localeCompare(proctorA) : proctorA.localeCompare(proctorB);
            return comparison;
        });
        
        // Reorder table rows in the sorted order
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        ascendingProctor = !ascendingProctor; // Toggle sorting direction
    }

    function sortTableByDate() {
        console.log("Sorting table by date created...");
        var table = document.querySelector('.min-w-full');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort(function(a, b) {
            var dateA = new Date(a.querySelector('.p-4.text-sm.font-normal:nth-of-type(4)').innerText.trim());
            var dateB = new Date(b.querySelector('.p-4.text-sm.font-normal:nth-of-type(4)').innerText.trim());
            
            // Toggle sorting direction
            var comparison = ascendingDate ? dateB - dateA : dateA - dateB;
            return comparison;
        });
        
        // Reorder table rows in the sorted order
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });

        ascendingDate = !ascendingDate; // Toggle sorting direction
    }

    function myFunction() {
        var input, filter, table, tr, td, i, j, txtValue;
        input = document.getElementById("topbar-search");
        filter = input.value.toUpperCase();
        table = document.getElementById("quizTable");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows
        for (i = 0; i < tr.length; i++) {
            var found = false; // Flag to indicate if the search query is found in any cell

            // Loop through all cells in the current row
            for (j = 0; j < tr[i].cells.length; j++) {
                td = tr[i].cells[j];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    // Check if the cell text contains the search query
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true; // Set the flag to true if a match is found
                        break; // No need to check other cells if a match is found
                    }
                }
            }
            // Display or hide the row based on whether the search query is found
            if (found) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
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
                for (i = 0; i < tr.length; i++) {
                    // Loop through all td elements in the row
                    var found = false;
                    td = tr[i].getElementsByTagName("td");

                    tr[i].style.display = "none"; // Hide the row if the search query is not found in any column        
                }
                paginationControls.removeAttribute("style");

                var backToPage = "page" + pageInputPlaceholder.placeholder;
                var displayElements = document.querySelectorAll("tr[name='" + backToPage + "']");
                for(var i = 0; i < displayElements.length; i++){
                    displayElements[i].removeAttribute("style");
                }
                    }
        // ===
    }

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

                currPage = currPageInput.value;
                currPageName = 'page' + currPage;
            }
            else{
                // No input data
                console.log("No input data");
            }
        });
</script>