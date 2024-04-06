$(document).ready(function () {
    let susCounter = 0;
    let duration = 0;
    let intervalId;
    let speech_detected = false;
    let evidence_name_type;
    let filename;
    let mediaRecorder;
    let chunks = [];

    // Check microphone permission
    setTimeout(function() {
        navigator.permissions.query({name: 'microphone'}).then(function(permissionStatus) {
            console.log('microphone permission state is ', permissionStatus.state);

            if (permissionStatus.state === 'denied') {
                if (jsdata.strict_mode_activated == 1){
                    console.log('microphone denied must redirect to review attempt quiz page');
                    window.location.href = jsdata.wwwroot + '/mod/quiz/view.php?id=' + jsdata.cmid;
                }
            }
            permissionStatus.onchange = function() {
                console.log('microphone permission state has changed to ', this.state);

                // If microphone permission is denied, record in database.
                if (this.state = 'denied'){
                    evidence_name_type = 'microphone_permission_denied_during_quiz';
                    sendActivityRecord();

                    // Check if strict mode was activated
                    // If strict mode was activated then forcefully exit quiz.
                    if (jsdata.strict_mode_activated == 1){
                        console.log('microphone denied must redirect to review attempt quiz page');
                        window.location.href = jsdata.wwwroot + '/mod/quiz/view.php?id=' + jsdata.cmid;
                    }
                }
            };
        });
    }, 5000); // 5000 milliseconds = 5 seconds
  
    navigator.mediaDevices.getUserMedia({ audio: true })
      .then(function(stream) {

        // Media recorder instance to record audio stream
        mediaRecorder = new MediaRecorder(stream);

        // Audio context instance for processing audio node
        const audioContext = new AudioContext();

        // Analyser node for frequency analysis
        const analyser = audioContext.createAnalyser();

        // Set the FFT (Fast Fourier Transform) size for frequency analysis
        // FFT size determines the number of data points used in the Fourier Transform calculation,
        // which affects the frequency resolution of the analysis.
        // 256 frequency bins to analyze
        analyser.fftSize = 256;

        // MediaStreamAudioSourceNode to connect the microphone stream to the AnalyserNode
        const microphone = audioContext.createMediaStreamSource(stream);
  
        // Connect the microphone stream to the AnalyserNode
        microphone.connect(analyser);
  
        // Gain node using the createGain() method of the audioContext object.
        // For debugging
        const feedbackGain = audioContext.createGain();
        feedbackGain.gain.value = 0; // Initially muted this is for debugging
  
        // Analyze the feedback gain
        // output of the analyser node will be fed into the feedbackGain node,
        // allowing to manipulate the volume or gain of the audio signal analyzed by the analyser.
        analyser.connect(feedbackGain);

        // Audio destination represents the final output destination for the audio graph.
        // The feedback that will be output into the device audio output.
        feedbackGain.connect(audioContext.destination);

        // Speech Recognition
            // Accessing what WebSpeech API the browser uses.
            window.SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            const recognition = new SpeechRecognition();

            // This will get immediate feedback when speech is detected during the recognition process.
            recognition.interimResults = true;

            // Variable for the transcript
            let transcript = '';

            // When speech is detected from the audio input
            recognition.addEventListener('result', e => {
                
                noiseDetected('speech_detected');

                // Set the var speech_detected to true for recording purposes
                speech_detected = true;
                console.log('Speech detected: ', speech_detected);
                const interimTranscript = Array.from(e.results)
                    .map(result => result[0].transcript)
                    .join('');

                if (e.results[0].isFinal) {
                    transcript += interimTranscript;
                    console.log('Final Transcript:', transcript);
                    // Reset transcript for new speech recognition
                    transcript = '';
                } else {
                    console.log('Interim Transcript:', interimTranscript);
                }
            });

            recognition.addEventListener('end', () => {
                noiseDetected('send_the_activity');
                speech_detected = false;
                console.log('Speech detected: ', speech_detected);
                recognition.start();
            });

            recognition.start();
  
        // Loud noise
            setInterval(() => {

                // Uint8Array = is an array-like object that is used to represent an array of 8-bit unsigned integers.
                const dataArray = new Uint8Array(analyser.frequencyBinCount);

                // Populates the dataArray with frequency data obtained from the analyser node.
                // Data represents the frequency spectrum of the audio signal currently being analyzed.
                analyser.getByteFrequencyData(dataArray);
        
                // Calculates the average value of all elements in the dataArray
                // reduce() method to sum up all the values in the array and then divides the sum by the length of the array to obtain the average value.
                // average = overall energy level or volume of the audio signal.
                const average = dataArray.reduce((a, b) => a + b, 0) / dataArray.length;

                // Rounds the average value 
                const volume = Math.round(average);
        
                // If speech is not detected and volume is greater than 80 then noiseDetected('loud_noise'),
                // else noiseDetected('send_the_activity') this sends the activity record for the previous detected and recorded activity.
                if (!speech_detected){
                    if (volume > 80){
                        noiseDetected('loud_noise');
                    }
                    else{
                        feedbackGain.gain.value = 0;
                        noiseDetected('send_the_activity');
                    }
                }
            }, 100); // Update display every 100 milliseconds

        // Collect recorded data will be push to array chunks
        mediaRecorder.ondataavailable = function(e) {
            chunks.push(e.data);
        };

        // Save recorded audio
        mediaRecorder.onstop = function(e) {

            let timeLimit;

            if (jsdata.monitor_camera_activated){
                timeLimit = 500;
            }
            else{
                timeLimit = 1000;
            }

            if (duration >= timeLimit){

                // Create a Blob object from e.data
                // chunks is an array containing binary data representing audio. 
                const blob = new Blob(chunks, { 'type' : 'audio/wav' });

                // Generate a timestamp
                const { timestamp, milliseconds } = generateTimestamp();

                // Generate unique filename
                filename = 'EVD_USER_' + jsdata.userid + '_QUIZ_' + jsdata.quizid +'_ATTEMPT_' + jsdata.quizattempt + '_' + timestamp.replace(/[/:, ]/g, '') + '_' + milliseconds + '_' + evidence_name_type +'_.wav'; // Custom filename for audio

                // Send blob and the generate filename to server for saving
                const formData = new FormData();
                formData.append('audio', blob, filename);

                fetch(jsdata.wwwroot + '/local/auto_proctor/proctor_tools/microphone_monitoring/save_mic_capture.php', {
                    method: 'POST',
                    body: formData
                })

                // After successfully sending the wav file call sendActivityRecord() for saving the activity in the activity_report_table.
                .then(response => {
                    console.log('Audio saved successfully:', response);
                    sendActivityRecord();
                })
                .catch(error => {
                    console.error('Error saving audio:', error);
                });
            }

            // Clear the chunks
            chunks = [];
        };

      })
      
      // Error accessing microphone
      .catch(function(err) {

        console.error('Error capturing audio:', err);

        // Set evidence_name_type
        evidence_name_type = 'microphone_permission_denied';

        // If microphone permission is denied, record in database.
        sendActivityRecord();

        // If strict mode was activated then forcefully exit quiz.
        if (jsdata.strict_mode_activated == 1){
            console.log('microphone denied must redirect to review attempt quiz page');
                window.location.href = jsdata.wwwroot + '/mod/quiz/view.php?id=' + jsdata.cmid;
        }
    });

    // Function that handles the process when noise or speech is detected.
    function noiseDetected(activity_type){
        // When susCounter is 0 and activity_type is "speech_detected" or "loud_noise",
        // then start the media recording, set the variable evidence_name_type as equal to the value of activity_type from the noiseDetected(activity_type),
        // and also start the timer. Iterate susCounter
        if (susCounter === 0){
            if (activity_type === "speech_detected" || activity_type === "loud_noise"){
                // Play the feedback in here for debugging
                mediaRecorder.start();
                evidence_name_type = activity_type;
                console.log('start recording');
                const intervalId = startTimer();
                if (activity_type === "speech_detected"){
                    showWarningNotificationForFiveSeconds('We have detected that you make Speech Noise');
                }
                else{
                    showWarningNotificationForFiveSeconds('We have detected that you make Loud Noise');
                }
                //feedbackGain.gain.value = 1;

                susCounter++;
            }
        }

        // When susCounter is 1 and activity_type is "send_the_activity" reset or set the susCounter to 0, stop timer and media recording.
        else if (susCounter === 1 && activity_type === "send_the_activity"){
            susCounter = 0;
            stopTimer();
            mediaRecorder.stop();
            console.log('stop recording');
        }
    }


    // Function to update the timer
    function updateTimer(milliseconds) {
        duration = milliseconds;
    }

    // Function to start the timer for the duration
    function startTimer() {
        let milliseconds = 0;
        updateTimer(milliseconds);

        // Update the timer every 10 milliseconds
        intervalId = setInterval(function () {
            milliseconds += 10;
            //console.log(milliseconds);
        updateTimer(milliseconds);
        }, 10);
    }

    // Function to stop timer.
    function stopTimer() {
        clearInterval(intervalId);
    }

    // Send the evidence_name_type, filename, userid, quizid, and quizattempt to server for saving the activity in the activity_report_table (save_mic_activity.php).
    function sendActivityRecord() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', jsdata.wwwroot + '/local/auto_proctor/proctor_tools/microphone_monitoring/save_mic_activity.php', true); // Replace with the actual path
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    console.log('POST request successful');
                } else {
                    console.error('POST request failed with status: ' + xhr.status);
                    // Handle the error or provide feedback to the user
                }
            }
        };
        xhr.send('evidence_name_type=' + evidence_name_type + '&filename=' + encodeURIComponent(filename) + '&userid=' + jsdata.userid + '&quizid=' + jsdata.quizid + '&quizattempt=' + jsdata.quizattempt);
    }
    
    // Function to generate timestamp
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