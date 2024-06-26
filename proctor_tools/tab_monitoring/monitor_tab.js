$(document).ready(function () {

    let screenShared = null;
    let screenStream = null;
    let videoElement;
    let stopsSharing = false;

    if (device.type === 'mobile' || device.type === 'tablet'){
        window.addEventListener('focus', handleTabSwitch);
        window.addEventListener('blur', handleTabSwitch);
        function handleTabSwitch() {
            if (document.hasFocus()) {
                    console.log('Tab switched back to focus');
            } 
            else{
                captureAndSaveScreen('tab_switch_screen_not_shared');
                showWarningNotificationForFiveSeconds('We have detected that you Tab switched');
                console.log('Tab switched');                                     
            }
        }
    }
                            
    function startScreenSharing() {
        // Check if user device has mutiple monitor
        if (window.screen.isExtended){
            console.log('Multiple screen');
        }
        else{
            console.log('Single screen');
        }
        
        navigator.mediaDevices.getDisplayMedia({ video: true })
            .then(stream => {
                let displaySurface = stream.getVideoTracks()[0].getSettings().displaySurface;
                console.log('sharing', );
                if (displaySurface !== 'monitor' || displaySurface === 'browser') {
                    if (confirm("You need to share the entire screen.")) {
                        location.reload();
                    }
                }
            videoElement = document.createElement('video');
            videoElement.srcObject = stream;
            videoElement.autoplay = true;

            screenStream = stream;
            screenShared = true;

            screenStream.getVideoTracks()[0].onended = () => {
                stopsSharing = true;
                screenShared = false;
                console.log('Screen sharing stopped by the student.');
                // Send an AJAX request to your server to indicate screen sharing stopped
                //sendScreenSharingStatus(2); // stops sharing
                captureAndSaveScreen('stops_sharing_screen');
            };

            captureAndSaveScreen('shared_screen'); // Capture the shared screen
            //sendScreenSharingStatus(1); // shared screen
            console.log('Consent:', 1);
        })
        .catch(error => {
            console.error('Error starting screen sharing:', error);
            screenShared =  false;
            // Send an AJAX request to your server to indicate screen sharing error
            //sendScreenSharingStatus(0); // 0 indicates screen sharing stopped
            captureAndSaveScreen('did_not_share_screen');
        });
                                    
            //document.addEventListener('visibilitychange', handleVisibilityChange);
            window.addEventListener('focus', handleTabSwitch);
            window.addEventListener('blur', handleTabSwitch);
    }
                            
    function handleTabSwitch() {
        if (document.hasFocus()) {
            console.log('Tab switched back to focus');
        } 
        else {
            console.log('Tab switched');
            if (screenShared === true && stopsSharing === false) {
                // If user shared screen and continously sharing it
                // Capture and save the shared screen when the tab is switched
                captureAndSaveScreen('tab_switch');
                showWarningNotificationForFiveSeconds('We have detected that you Tab switched');
            }
            else if(screenShared === false || stopsSharing === true){
                // If user did not share screen or when user shared screen but stop it
                // Will not capture but will still be reported in the acitivity table
                showWarningNotificationForFiveSeconds('We have detected that you Tab switched');
                captureAndSaveScreen('tab_switch_screen_not_shared');
            }                                        
        }
    }

    function sendScreenSharingStatus(screen_activity, filename, activity_type) {
        // Send an AJAX request to your server to record screen sharing status
        console.log('Sending screen_activity:', screen_activity);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', jsdata.wwwroot + '/local/auto_proctor/proctor_tools/tab_monitoring/save_screen_activity.php', true); // Replace with the actual path
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        // ==== DEBUGGING =====
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    console.log('POST request successful');
                        // If strict mode was activated
                        if (jsdata.strict_mode_activated == 1){
                            // If student stops sharing screen or did not share screen then redirect to quiz attempt review page
                            if (activity_type == 'stops_sharing_screen' || activity_type == 'did_not_share_screen'){
                                console.log('stops sharing must redirect to review attempt quiz page');
                                window.location.href = jsdata.wwwroot + '/mod/quiz/view.php?id=' + jsdata.cmid;
                            }
                        }
                } else {
                    console.error('POST request failed with status: ' + xhr.status);
                    // Handle the error or provide feedback to the user
                }
            }
        };
        xhr.send('screen_activity=' + screen_activity + '&userid=' + jsdata.userid + '&quizid=' + jsdata.quizid + '&quizattempt=' + jsdata.quizattempt + '&filename=' + encodeURIComponent(filename) + '&activity_type=' + activity_type);
    }

    function generateTimestamp() {
        const now = new Date();
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
            timeZoneName: 'short',
        };

        const formatter = new Intl.DateTimeFormat('en-US', options);
        const timestamp = formatter.format(now);

        return { timestamp, milliseconds: now.getMilliseconds() };
    }
                            
    function captureAndSaveScreen(evidence_name_type) {
        if (evidence_name_type !== 'tab_switch_screen_not_shared' && evidence_name_type !== 'did_not_share_screen' && evidence_name_type !== 'stops_sharing_screen'){
            setTimeout(() => {
            const canvas = document.createElement('canvas');
            
            const ASPECT_RATIO = 16 / 9;

            // Define desired height
            const desiredHeight = 550; // Adjust as needed

            // Calculate width based on aspect ratio
            const desiredWidth = Math.round(desiredHeight * ASPECT_RATIO);

            // Adjust size of the video for capturing
            if (videoElement.videoHeight >= 1080 && videoElement.videoWidth >= 1620) {
              canvas.width = desiredWidth;
              canvas.height = desiredHeight;
            }
            else{
              canvas.width = videoElement.videoWidth;
              canvas.height = videoElement.videoHeight;

            }

            const ctx = canvas.getContext('2d');
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                                
            const { timestamp, milliseconds } = generateTimestamp();
            const filename = 'EVD_USER_' + jsdata.userid + '_QUIZ_' + jsdata.quizid + '_ATTEMPT_' + jsdata.quizattempt + '_' + timestamp.replace(/[/:, ]/g, '') + '_' + milliseconds + '_' + evidence_name_type + '.png'; // Custom filename with evidenceType
                                
            const dataUrl = canvas.toDataURL('image/png');
            
            fetch(jsdata.wwwroot + '/local/auto_proctor/proctor_tools/tab_monitoring/save_screen_capture.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'dataUri=' + encodeURIComponent(dataUrl) + '&filename=' + encodeURIComponent(filename),
            })
            .then(response => response.json())
                .then(data => {
                    console.log('Screen captured and saved as: ' + data.filename);
                    sendScreenSharingStatus(4, filename, evidence_name_type);
                })
                .catch(error => {
                    console.error('Error saving screen capture:', error);
                });
            }, 500);
        }
        else{
            sendScreenSharingStatus(1, 0,evidence_name_type);
        }
    }
                                                                       
    // Start screen sharing when the script is loaded
    startScreenSharing();

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

    // // PREVENT SCREENSHOT
    //     var styleTag = document.createElement('style');
    //     styleTag.id = 'styles';
    //     document.head.appendChild(styleTag);

    //     // Add CSS rules to the style tag
    //     document.getElementById('styles').textContent = `
    //         body {
    //             margin: 0;
    //             overflow: hidden;
    //         }

    //         #overlay {
    //             position: fixed;
    //             top: 0;
    //             left: 0;
    //             width: 100vw;
    //             height: 100vh;
    //             background-color: black; /* Semi-transparent background */
    //             z-index: 9999;
    //             display: none;
    //             display: flex;
    //             justify-content: center;
    //             align-items: center;
    //         }

    //         #warning {
    //             color: white;
    //             font-size: 24px;
    //             text-align: center;
    //             z-index: 10000;
    //             margin-bottom: 20px; /* Add some spacing between warning and button */
    //         }
    //     `;

    //     // Function to show the overlay
    //     function showOverlay() {
    //         var overlay = document.createElement('div');
    //         overlay.id = 'overlay';
    //         overlay.addEventListener('click', hideOverlay); // Add click event listener to remove overlay
    //         var warning = document.createElement('div');
    //         warning.id = 'warning';
    //         warning.textContent = "Warning: Don't take a screenshot!";

    //         overlay.appendChild(warning);
    //         document.body.appendChild(overlay);

    //         overlay.style.display = 'flex';
    //     }

    //     // Function to hide the overlay
    //     function hideOverlay() {
    //         var overlay = document.getElementById('overlay');
    //         overlay.style.display = 'none';
    //         overlay.remove();
    //     }

    //     // Event listener to trigger the overlay when a screenshot is taken
    //     document.addEventListener('keyup', function (event) {
    //         if (event.key === 'PrintScreen' || 
    //             (event.ctrlKey && event.altKey && event.key === 'PrintScreen') ||
    //             (event.key === 'Shift' && event.key === 'S' && event.getModifierState("Meta") && event.getModifierState("Shift"))) {
    //             showOverlay();
    //         }
    //     });
});