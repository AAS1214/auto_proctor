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
global $DB, $PAGE, $USER, $CFG;

require_once($CFG->libdir . '/outputrenderers.php');

// Get required parameters
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(url:'/local/auto_proctor/prompts.php')); // Set url

// Retrieve the data from the URL parameter
$data_param = optional_param('data', '', PARAM_RAW);

// Decode the JSON data
$jsdata = json_decode(urldecode($data_param), true);

// Access the values
$wwwroot = $jsdata['wwwroot'];
$userid = $jsdata['userid'];
$quizid = $jsdata['quizid'];
$quizattempt = $jsdata['quizattempt'];
$quizattempturl = $jsdata['quizattempturl'];
$cmid = $jsdata['cmid'];
$monitor_camera_activated = $jsdata['monitor_camera_activated'];
$monitor_microphone_activated = $jsdata['monitor_microphone_activated'];
$monitor_tab_switching_activated = $jsdata['monitor_tab_switching_activated'];
$strict_mode_activated = $jsdata['strict_mode_activated'];
$chosen_data_pa = $jsdata['chosen_data_pa'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css"  rel="stylesheet" />
    <script src="https://unpkg.com/current-device/umd/current-device.min.js"></script>

    <title>Document</title>
</head>
<body class="overflow-hidden">
    
<!-- MODAL HERE YOU CAN COPY IT PASE IT TO THE MAIN CODE -->
<div id="data-popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full flex" aria-modal="true" role="dialog">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow ">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t ">
                <h3 class="text-xl font-semibold text-gray-900 ">
                  Data Privacy Policy Agreement 
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center " data-modal-hide="default-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5 max-h-96 overflow-y-auto">
                <p class="text-base leading-relaxed text-gray-500 ">
                  This Data Privacy Policy Agreement ("Agreement") outlines the manner in which personal information is collected, used, and disclosed by <a class="text-blue-800" href="https://e-rtu.edu.ph">e-RTU</a>. By accessing or using the Website, you agree to the terms outlined in this Agreement. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">1. Information Collection: </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  We collect personal information provided voluntarily by users, including but not limited to names, email addresses, and demographic information. This information is collected through forms, cookies, and other tracking technologies. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">2. Use of Information: </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  Personal information collected is used for the following purposes: 
                </p>
                <p class="text-base leading-relaxed m text-gray-500 ">
                  To provide and personalize our services 
                  <br>
                  To communicate with users regarding their inquiries, requests, or transactions 
                  <br>
                  To improve our website and services 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">3. Data Security: </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  We implement reasonable security measures to protect personal information from unauthorized access, disclosure, alteration, or destruction. However, no method of transmission over the internet or electronic storage is 100% secure. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">4. Third-party Disclosure:  </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  We may share personal information with trusted third-party service providers to facilitate services offered through the Website. These third parties are contractually obligated to maintain the confidentiality and security of the information. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">5. Cookies:  </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  Cookies are used to enhance user experience and collect information about usage patterns on the Website. Users have the option to disable cookies in their browser settings, but this may affect the functionality of the Website. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">6. Camera, Microphone, and Screen Activity Access:  </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  The Website may request access to your device's camera, microphone, and screen activity for specific features or functionalities. By granting access, you consent to the recording of video, audio, and screen content as necessary for the intended purpose. We may use this recorded content in accordance with this Privacy Policy. 
                  <br>
                  Please note that you have the option to deny access to your camera, microphone, and screen activity through your device settings or browser preferences. However, certain features of the Website may be limited or unavailable if access is denied. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">7. Data Retention: </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  We retain personal information for as long as necessary to fulfill the purposes outlined in this Agreement, unless a longer retention period is required by law. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">8. Changes to Privacy Policy: </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  We reserve the right to update or modify this Privacy Policy at any time. Any changes will be effective immediately upon posting the revised policy on the Website. 
                </p>
                <h5 class="text-base leading-relaxed font-bold text-gray-700 ">9. Contact Us: </h5>
                <p class="text-base leading-relaxed text-gray-500 ">
                  If you have any questions or concerns about this Privacy Policy or our data practices, please contact us at rtuflexys@rtu.edu.ph. 
                </p>
                  <div class="form-check"  class="text-base leading-relaxed text-gray-600 italic ">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      value=""
                      id="agreeBox"
                    />
                    <label class="form-check-label text-base leading-relaxed text-gray-600 italic " for="">By accessing or using the Website, you acknowledge that you have read, understood, and agree to be bound by this Privacy Policy Agreement. </label>
                  </div>
            </div>
            <!-- Modal footer -->
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b ">
                <button id="acceptButton" data-modal-hide="data-popup-modal" type="button" class="text-white bg-gray-300 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center " style="cursor: not-allowed;">I accept</button>
                <button data-modal-hide="default-modal" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 ">Decline</button>
            </div>
        </div>
    </div>
</div>
<div id="popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full flex" aria-modal="true" role="dialog">
<!-- <div id="popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"> -->
    <div class="relative p-10 py-9 w-full max-w-3xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <a href = "<?php echo $wwwroot.'/mod/quiz/view.php?id=' . $cmid;?>">
                <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center " data-modal-hide="popup-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </a>
            <div class="p-6 md:p-8  text-center">
                <h1 class="mb-2 text-2xl font-semibold text-black ">Multiple Monitor Detected</h1>

                <p class="mb-4 text-base font-normal text-gray-700 text-start ">We have detected multiple monitors. Please disconnect the extra monitor (or devices like Chromecast).</p>
                <p class="mb-8 text-base font-normal text-gray-700 text-start ">If you continue without disconnecting, AutoProctor will store details of the device.</p>
                <button onclick = "haveNotConnMonitor()" id = "have-not-multiple-btn" data-modal-hide="popup-modal" type="button" class="text-white bg-[#6B7280] hover:bg-gray-600 focus:ring-4 focus:outline-none focus:ring-red-300  font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center me-2">
                    Haven’t connected Multiple Monitors
                </button>
            <!-- Modal body -->
            <div class="p-4 md:p-5 text-center">
                <button onclick = "haveRemoveExtMonitor()" id = "have-multiple-btn" data-modal-hide="popup-modal" type="button" class="text-white bg-[#059669] hover:bg-green-600 focus:ring-4 focus:outline-none focus:ring-red-300  font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center me-2">
                    Have removed External Monitor
                </button>
                <button onclick = "continueWithMulMonitor()" id = "continue-multiple-btn" data-modal-hide="popup-modal" type="button" class="text-white  bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-200 focus:z-10 ">Continue with Multiple Monitors</button>
            </div>
            <!-- Modal footer -->


            </div>
        </div>
    </div>
</div>

<div id="cam-view-popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-10 py-9 w-full max-w-3xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <a href = "<?php echo $wwwroot.'/mod/quiz/view.php?id=' . $cmid;?>">
                <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center " data-modal-hide="popup-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </a>
            <div class="p-6 md:p-8  text-center">
                <h1 class="mb-2 text-2xl font-semibold text-black ">Camera View</h1>

                <p class="mb-2 text-md font-normal text-gray-700 ">This is what the selected camera is capturing. If you want to use a different camera, go back to previous step and select a different camera.</p>
            <!-- Modal body -->
            <div class="p-4 md:p-5 space-y-4">
                <!-- ADD IMAGE HERE -->
                <div class="flex justify-center">
                    <video id="camera-preview" alt="Your Image" class="max-w-64 h-auto" autoplay style="display:none;"></video>
                </div>
                <p class="text-base leading-relaxed text-black ">
                    If you see a completely black screen, it is mostly a camera error. Check your device’s camera.
                </p>
                <p class="text-base leading-relaxed text-black ">
                Make sure you are in the middle of the camera preview and in a 
                    well-lit area before starting the exam.
                </p>
            </div>
            <!-- Modal footer -->
<!-- Modal footer -->
<div class="flex justify-between items-center p-4 md:p-5  border-gray-200 rounded-b ">
    <button data-modal-hide="cam-view-popup-modal" data-modal-target="cam-select-popup-modal" data-modal-toggle="cam-select-popup-modal" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-5 focus:outline-none  rounded-lg border border-gray-400 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 ">Previous</button>
    <!-- <button id="nextButton" type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center hidden" onclick = "sendSetupData()">Next</button> -->
    <button onclick = "sendSessionSetupData()" data-modal-hide="cam-select-popup-modal" type="button" data-modal-target="cam-view-popup-modal" data-modal-toggle="cam-view-popup-modal" class=" text-gray-100 bg-blue-700 hover:bg-[#0061A8] focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-00 focus:z-10">Next</button>

</div>

            </div>
        </div>
    </div>
</div>

<div id="cam-select-popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-12 py-16 w-full max-w-3xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <a href = "<?php echo $wwwroot.'/mod/quiz/view.php?id=' . $cmid;?>">
                <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center " data-modal-hide="popup-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </a>
            <div class="p-6 md:p-8 mb-4 text-center">
                <h1 class="mb-2 text-2xl font-semibold text-black " id = "detectedCamHeader">No Camera Detected</h1>

                <h3 class="mb-6 text-md font-normal text-gray-700 "></h3>
                <!-- FOR DROPDOWN -->
                <div  class="inline-flex items-end">
                    <button id="dropdownDefault" data-dropdown-toggle="filter"
                    class="mb-4 sm:mb-0 mr-4 inline-flex items-end text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2.5">
                    Select any camera
                    <svg class="w-4 h-4 ml-2 " aria-hidden="true" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                    <!-- Dropdown menu -->
                    <div id="filter" class="z-10 hidden w-56 p-3 bg-white rounded-lg shadow">
                        <ul class="space-y-2 text-sm" aria-labelledby="dropdownDefault">
                            <li class="flex items-center">            
                                <label for=""
                                    class="ml-2 text-sm font-medium text-gray-900 ">
                                    Select any camera
                                </label>
                            </li>
                            <!-- <li class="flex items-center"> -->
                                <!-- <input type="radio" id="camera_usb" name="camera" > -->
                                <!-- <label for=""
                                    class="ml-2 text-sm font-medium text-gray-900 ">
                                    USB 2.0 HD UVC WebCam
                                </label> -->
                            <!-- </li> -->

                            <!-- <li class="flex items-center"> -->
                                <!-- <input type="radio" id="camera_usb" name="camera"> -->
                                <!-- <label for=""
                                    class="ml-2 text-sm font-medium text-gray-900 ">
                                    OBS Virtual Camera
                                </label> -->
                            <!-- </li> -->

                        </ul>
                    </div>
                </div>
                <!-- END OF DROPDOWN -->
                <button onclick = "startStream()" data-modal-hide="cam-select-popup-modal" type="button" data-modal-target="cam-view-popup-modal" data-modal-toggle="cam-view-popup-modal" class=" text-gray-100 bg-blue-700 hover:bg-[#0061A8] focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-00 focus:z-10">Next</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
<div id = "backdrop" modal-backdrop class="bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40"></div>

<script>
    /*
        - The multiple monitor modal will always prompt if multiple monitors are detected (window.screen.isExtended). It offers three options or buttons:

            - Haven't connected multiple monitors
            - Have removed external monitor
            - Continue with multiple monitors
        - If the user clicks the 'continue with multiple monitors' button, their choice will be recorded in the database or session setup table, and they will then be redirected to the quiz. However, selecting the other two buttons will refresh the page to apply and detect the latest changes in the device setup.

        - The sending of the data to be processed on the server will be determined by the activated autoproctor feature.

        MODAL SEQUENCE LOGIC:

            SEQUENCE:
                1. Multiple Monitor Modal
                2. Camera Select Modal
                3. Camera View Modal

            LOGIC:
                - If all autoproctor features are activated, the selected choices in the Monitor Multiple Modal and Camera Select Modal will be prepared to be sent to the server. Clicking the next button in the Camera Preview Modal will then send the data.
                - If only the tab monitoring is activated and multiple monitors are detected, then selecting 'Continue with multiple monitors' will send the data.
                - If only the tab monitoring is activated and single monitors are detected, then no modal will be prompted; the monitor setup will be automatically initialized and sent.
                - If a single monitor is detected, the Camera Select Modal will be prompted, and clicking the 'Next' button in the Camera View will send the data.
                
            NOTE: After successfully sending and processing the data on the server, the user will proceed to the quiz.
    */

    let chosen_camera_device = null;
    let chosen_monitor_set_up = "single_monitor_detected";
    let monitor_camera_activated = <?php echo $monitor_camera_activated; ?>;
    let monitor_microphone_activated = <?php echo $monitor_microphone_activated; ?>;
    let monitor_tab_switching_activated = <?php echo $monitor_tab_switching_activated; ?>;
    let device_type = device.type;
    let wwwroot = <?php echo json_encode($wwwroot); ?>;
    let cmid = <?php echo json_encode($cmid); ?>;
    let strict_mode_activated = <?php echo json_encode($strict_mode_activated); ?>;
    let chosen_data_pa = <?php echo json_encode($chosen_data_pa); ?>

    var popupModal = document.getElementById("popup-modal");
    var dataPopupMoodal = document.getElementById('data-popup-modal');
    var camSelectPopupModal = document.getElementById("cam-select-popup-modal");
    var have_not_multiple_btn = document.getElementById('have-not-multiple-btn');
    var have_multiple_btn = document.getElementById('have-multiple-btn');
    var continue_multiple_btn = document.getElementById('continue-multiple-btn');

    // Data privacy disabling and enabling the accept button
    const agreeBox = document.getElementById('agreeBox');
    const acceptButton = document.getElementById('acceptButton');

    // Add an event listener to the checkbox to check its status
    agreeBox.addEventListener('change', function() {
    // If checkbox is checked, enable the button; otherwise, disable it
    if (agreeBox.checked) {
            acceptButton.classList.remove("bg-gray-300", "hover:bg-blue-800");
            acceptButton.classList.add("bg-blue-700", "hover:bg-blue-800");
            acceptButton.style.cursor = "pointer";
            acceptButton.removeAttribute('disabled');
        } else {
            acceptButton.classList.remove("bg-blue-700", "hover:bg-blue-800");
            acceptButton.classList.add("bg-gray-300");
            acceptButton.setAttribute('disabled', 'disabled');
            acceptButton.style.cursor = "not-allowed";
        }
    });

    // Initially disable the button
    acceptButton.setAttribute('disabled', 'disabled');
    
    // If user already agreed to the data privacy agreement
    if (chosen_data_pa == 1){
        // Hiding the data privacy modal.
        // dataPopupMoodal.classList.remove("flex");
        // dataPopupMoodal.classList.add("hidden");

        // dataPopupMoodal.classList.remove("hidden");
        // dataPopupMoodal.classList.add("flex");

        if (!window.screen.isExtended){
            // Display the cam select modal.
            camSelectPopupModal.classList.remove("hidden");
            camSelectPopupModal.classList.add("flex");
        }
        else{
            // Display the multiple monitor modal.
            popupModal.classList.remove("hidden");
            popupModal.classList.add("flex");
        }
    }
    else{
        dataPopupMoodal.classList.remove("hidden");
        dataPopupMoodal.classList.add("flex");
    }

    // If data privacy accept button was clicked
    // Add click event listener to the button
    acceptButton.addEventListener('click', function() {
        // Hide the modal by adding a CSS class
        dataPopupMoodal.classList.remove("flex");
        dataPopupMoodal.classList.add('hidden');

        if (!window.screen.isExtended){
            // Display the cam select modal.
            camSelectPopupModal.classList.remove("hidden");
            camSelectPopupModal.classList.add("flex");
        }
        else{
            // Display the multiple monitor modal.
            popupModal.classList.remove("hidden");
            popupModal.classList.add("flex");
        }

        // Send the response to php for server processing
        const data_pa = 1;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', <?php echo json_encode($wwwroot . '/local/auto_proctor/ui/functions/save_data_privacy.php'); ?>, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    console.log('POST request successful');
                    // You can add further actions if needed
                } else {
                    console.error('POST request failed with status: ' + xhr.status);
                    // Handle the error or provide feedback to the user
                }
            }
        };
        xhr.send('userid=' + <?php echo $userid; ?> + '&data_pa=' + data_pa);
        
    });


    // If microphone monitoring is activated then ask user's mic permission
        if (monitor_microphone_activated === 1){
            navigator.mediaDevices.getUserMedia({ audio: true })
            .then(function(stream) {
                // Your code to handle the audio stream
            })
            .catch(function(err) {
                confirm("Please give microphone permission.");
            });

            // Realtime camera permission checker
            navigator.permissions.query({name: 'microphone'}).then(function(permissionStatus) {
                console.log('microphone permission state is ', permissionStatus.state);
                permissionStatus.onchange = function() {
                    if (confirm("Reload this page to apply setup changes.")) {
                        location.reload();
                    }
                };
            });
        }

    // If camera monitoring is activated then ask user's cam permission
        if (monitor_camera_activated === 1){
            navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(stream) {
                console.log('CAMERA GRANTED');
                // Your code to handle the audio stream
            })
            .catch(function(err) {
                console.log('CAMERA DENIED');
                confirm("Please give camera permission.");
            });

            // Realtime microphone permission checker
            navigator.permissions.query({name: 'camera'}).then(function(permissionStatus) {
                console.log('camera permission state is ', permissionStatus.state);
                permissionStatus.onchange = function() {
                    if (confirm("Reload this page to apply setup changes.")) {
                        location.reload();
                    }
                };
            });
        }

    // The multiple monitor modal defaulted to prompt.
    // So, if only a single monitor was detected, we hide the multiple monitor modal.
        if (!window.screen.isExtended){
            // If user already agreed to the data privacy agreement
            if (chosen_data_pa == 1){
                // If camera monitoring is activated then we prompt the camera select modal.
                if (monitor_camera_activated === 1){
                    camSelectPopupModal.classList.remove("hidden");
                    camSelectPopupModal.classList.add("flex");
                    camSelectPopupModal.setAttribute("aria-modal", "true");
                    camSelectPopupModal.setAttribute("role", "dialog");
                }
                
                // If camera monitoring is deactivated and microphone monitoring is activated,
                // then the data will automatically be sent when microphone permission is granted.
                if (monitor_camera_activated !== 1 && monitor_microphone_activated === 1){
                    navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function(stream) {
                        sendSessionSetupData();
                    })
                    .catch(function(err) {
                        confirm("Please give microphone permission.");
                    });
                }
                
                // If only the tab monitoring feature is activated, then data will be automatically sent.
                if (monitor_tab_switching_activated === 1 && monitor_camera_activated !== 1 && monitor_microphone_activated !== 1){
                    sendSessionSetupData();
                }

                // Hiding the multiple monitor modal.
                popupModal.classList.remove("flex");
                popupModal.classList.add("hidden");
            }
        }

    // If the camera monitoring is activated,
    // then make the buttons in the multiple monitor modal open the camera select modal.
    if (monitor_camera_activated === 1) {
        have_not_multiple_btn.setAttribute('data-modal-target', 'cam-select-popup-modal');
        have_not_multiple_btn.setAttribute('data-modal-toggle', 'cam-select-popup-modal');
        have_not_multiple_btn.setAttribute('data-modal-hide', 'popup-modal');

        have_multiple_btn.setAttribute('data-modal-target', 'cam-select-popup-modal');
        have_multiple_btn.setAttribute('data-modal-toggle', 'cam-select-popup-modal');

        continue_multiple_btn.setAttribute('data-modal-target', 'cam-select-popup-modal');
        continue_multiple_btn.setAttribute('data-modal-toggle', 'cam-select-popup-modal');

        // navigator.mediaDevices.getUserMedia({ video: true })
        //     .then((stream) => {
        //         videoElement = document.createElement('video');
        //         const camera = new Camera(videoElement, {onFrame: async () => {
        //             await faceMesh.send({ image: videoElement });
        //         },
        //         width: 1280,
        //         height: 720,
        //         });

        //         camera.start();
        //         videoElement.srcObject = stream;
        //     })
        //     .catch((error) => {
        //         if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
        //         // User denied camera access
        //         console.error('User denied camera access.');
                    
        //         } else {
        //             // Other errors
        //             console.error('Error accessing camera:', error.message);
        //         }
        //     }
        // );
    }

    // Stream the view of selected camera
    function startStream() {

        // Get the selected camera
        var selectedRadio = document.querySelector('input[name="camera"]:checked');
        // If none are selected then, alert the user
        if (!selectedRadio) {
            console.log('mode', strict_mode_activated);
            alert("Please select a camera");
        
            if (strict_mode_activated == 1){
                console.log('strict activated');
                window.location.href = wwwroot + '/mod/quiz/view.php?id=' + cmid;
            }
        }

        var deviceId = selectedRadio.value;
        var constraints = {
            video: { deviceId: { exact: deviceId } }
        };

        // Get the chosen camera device to send with the data for server processing.
        chosen_camera_device = JSON.stringify(constraints);

        // Stream the camera view
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(stream) {
                var videoElement = document.getElementById('camera-preview');
                videoElement.srcObject = stream;
                videoElement.style.display = "block";
            })
            .catch(function(err) {
                console.error('Error accessing camera:', err);
                alert('Error accessing camera: ' + err.message);
        });
    }

    // When the user clicks 'haven't connected multiple monitors',
    // refresh the page to update multiple monitor detection.
    function haveNotConnMonitor(){
        var multiple_modal = document.getElementById('popup-modal');
        monitor_setup = null;

        multiple_modal.setAttribute('class', 'hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full');
        multiple_modal.removeAttribute('aria-modal');
        multiple_modal.removeAttribute('role');

        if (confirm("Reload page to update monitor status?")) {
            location.reload();
        }

        // chosen_monitor_set_up = "have_not_conn_multiple_monitor";
        // console.log('sending this: ', chosen_monitor_set_up);
        // console.log('redirecting to quiz');

        // if (monitor_microphone_activated === 1 && monitor_camera_activated !== 1){
        //     sendSessionSetupData();
        // }
    }

    // When the user clicks 'haven remove extra monitor',
    // refresh the page to update multiple monitor detection.
    function haveRemoveExtMonitor(){
        var multiple_modal = document.getElementById('popup-modal');
        monitor_setup = 

        multiple_modal.setAttribute('class', 'hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full');
        multiple_modal.removeAttribute('aria-modal');
        multiple_modal.removeAttribute('role');

        if (confirm("Reload page to update monitor status?")) {
            location.reload();
        }

        // chosen_monitor_set_up = "have_remove_external_monitor";
        // console.log('sending this: ', chosen_monitor_set_up);
        // console.log('redirecting to quiz');

        // if (monitor_microphone_activated === 1 && monitor_camera_activated !== 1){
        //     sendSessionSetupData();
        // }
    }

    // When the user clicks 'continue with multiple monitors',
    // set the chosen_monitor_setup to 'continue_with_multiple_monitor',
    // which will be sent with the data.
    function continueWithMulMonitor(){
        chosen_monitor_set_up = "continue_with_multiple_monitor";
        var multiple_modal = document.getElementById('popup-modal');

        multiple_modal.setAttribute('class', 'hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full');
        multiple_modal.removeAttribute('aria-modal');
        multiple_modal.removeAttribute('role');

        if (monitor_microphone_activated === 1 && monitor_camera_activated !== 1){
            sendSessionSetupData();
        }

        if (monitor_tab_switching_activated === 1 && monitor_camera_activated !== 1){
            sendSessionSetupData();
        }
    }

    // This is the function that sends the setup data in save_proctor_session_setup.php, 
    // to be processed and saved in the database.
    function sendSessionSetupData(){
        var xhr = new XMLHttpRequest();
        xhr.open('POST', <?php echo json_encode($wwwroot . '/local/auto_proctor/proctor_tools/proctor_setup/save_proctor_session_setup.php'); ?>, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    console.log('POST request successful');
                    window.location.href = <?php echo json_encode($quizattempturl); ?>;
                    // You can add further actions if needed
                } else {
                    console.error('POST request failed with status: ' + xhr.status);
                    // Handle the error or provide feedback to the user
                }
            }
        };
        xhr.send('userid=' + <?php echo $userid; ?> + '&quizid=' + <?php echo $quizid; ?> + '&quizattempt=' + <?php echo $quizattempt; ?> + '&quizattempturl=' + <?php echo json_encode($quizattempturl); ?> + '&chosen_camera_device=' + chosen_camera_device + '&chosen_monitor_set_up=' + chosen_monitor_set_up + '&device_type=' + device_type);
    }

    

    // When windows load
    window.onload = function() {
        //document.querySelector('h1.mb-2.text-2xl.font-semibold.text-black').textContent = "New Content Here";
        var filterElement = document.getElementById('filter');
        var videoInputDeviceCount = 0;
        var cameraHeaderText = document.querySelector('h3.text-gray-700');
        var selectedCameraLabel = "Select any camera";
        var detectedCamModal = document.getElementById('detectedCamHeader');

        // Retrieve all available camera devices to display and
        // make them options in the camera select modal.
        navigator.mediaDevices.enumerateDevices()
        .then(function(devices) {
            devices.forEach(function(device) {
                if (device.kind === 'videoinput') {
                    videoInputDeviceCount++;
                    var option = document.createElement('li');
                    option.className = "flex items-center";
                    var input = document.createElement('input');
                    input.type = "radio";
                    input.id = device.deviceId;
                    input.name = "camera";
                    input.value = device.deviceId;
                    input.addEventListener("change", function() {
                                if (input.checked) {
                                    var selectedCameraLabel = device.label || 'Camera ' + videoInputDeviceCount;
                                    // Display the selected camera label
                                    document.getElementById("dropdownDefault").textContent = "Selected camera: " + selectedCameraLabel;
                                }
                            });
                    var label = document.createElement('label');
                    label.htmlFor = device.deviceId;
                    label.className = "ml-2 text-sm font-medium text-gray-900";
                    label.textContent = device.label || 'Camera ' + (filterElement.options.length + 1);
                    option.appendChild(input);
                    option.appendChild(label);
                    filterElement.querySelector('ul').appendChild(option);
                }
            });

                if (videoInputDeviceCount == 1){
                    detectedCamModal.textContent = "One Camera Detected";
                }

                if (videoInputDeviceCount > 1){
                    detectedCamModal.textContent = "Multiple Camera Detected";
                }
                if (cameraHeaderText) {
                    cameraHeaderText.textContent = "We detected " + videoInputDeviceCount + " cameras. Please select one of them to continue.";
                }
        })
        .catch(function(err) {
            console.error('Error enumerating devices:', err);
        });

        // // Prevent dev mode
        // document.addEventListener("contextmenu", function (e) {
        //     e.preventDefault();
        // }, false);

        // document.addEventListener("keydown", function (e) {
        //     //document.onkeydown = function(e) {
        //     // "I" key
        //     if (e.ctrlKey && e.shiftKey && e.keyCode == 73) {
        //         disabledEvent(e);
        //     }
        //     // "J" key
        //     if (e.ctrlKey && e.shiftKey && e.keyCode == 74) {
        //         disabledEvent(e);
        //     }
        //     // "S" key + macOS
        //     if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
        //         disabledEvent(e);
        //     }
        //     // "U" key
        //     if (e.ctrlKey && e.keyCode == 85) {
        //         disabledEvent(e);
        //     }
        //     // "F12" key
        //     if (event.keyCode == 123) {
        //         disabledEvent(e);
        //     }
        // }, false);

        // function disabledEvent(e) {
        //     if (e.stopPropagation) {
        //         e.stopPropagation();
        //     } else if (window.event) {
        //         window.event.cancelBubble = true;
        //     }
        //     e.preventDefault();
        //     return false;
        // }
        
    };
</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
</body>
</html>