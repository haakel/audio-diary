jQuery(document).ready(function($) {
    let mediaRecorder;
    let audioChunks = [];
    let isRecording = false;
    let audioContext, analyser, dataArray, bufferLength, source;

    function visualize(stream) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        analyser = audioContext.createAnalyser();
        source = audioContext.createMediaStreamSource(stream);
        source.connect(analyser);
        analyser.fftSize = 2048;
        bufferLength = analyser.frequencyBinCount;
        dataArray = new Uint8Array(bufferLength);

        const canvas = document.getElementById('visualizer');
        const canvasCtx = canvas.getContext('2d');

        function draw() {
            requestAnimationFrame(draw);

            analyser.getByteTimeDomainData(dataArray);

            canvasCtx.fillStyle = 'rgb(0, 0, 0)';
            canvasCtx.fillRect(0, 0, canvas.width, canvas.height);

            canvasCtx.lineWidth = 2;
            canvasCtx.strokeStyle = 'rgb(255, 255, 255)';

            canvasCtx.beginPath();

            let sliceWidth = canvas.width * 1.0 / bufferLength;
            let x = 0;

            for(let i = 0; i < bufferLength; i++) {
                let v = dataArray[i] / 128.0;
                let y = v * canvas.height / 2;

                if(i === 0) {
                    canvasCtx.moveTo(x, y);
                } else {
                    canvasCtx.lineTo(x, y);
                }

                x += sliceWidth;
            }

            canvasCtx.lineTo(canvas.width, canvas.height / 2);
            canvasCtx.stroke();
        }

        draw();
    }

    $('#recording-button').on('click', function() {
        if (!isRecording) {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    mediaRecorder = new MediaRecorder(stream);
                    mediaRecorder.start();
                    visualize(stream);
                    
                    mediaRecorder.ondataavailable = function(event) {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = function() {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                        audioChunks = [];
                        const audioUrl = URL.createObjectURL(audioBlob);
                        $('#audio-player').attr('src', audioUrl).addClass('show');

                        let formData = new FormData();
                        formData.append('audio_data', audioBlob, 'audio.wav');
                        formData.append('action', 'save_audio');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    $.toast({
                                        text: "Audio saved successfully", // Text that is to be shown in the toast
                                        heading: 'Note', // Optional heading to be shown on the toast
                                        icon: 'success', // Type of toast icon
                                        showHideTransition: 'fade', // fade, slide or plain
                                        allowToastClose: true, // Boolean value true or false
                                        hideAfter: 3000, // false to make it sticky or number representing the miliseconds as time after which toast needs to be hidden
                                        stack: 3, // false if there should be only one toast at a time or a number representing the maximum number of toasts to be shown at a time
                                        position: 'bottom-left', // bottom-left or bottom-right or bottom-center or top-left or top-right or top-center or mid-center or an object representing the left, right, top, bottom values
                                        
                                        
                                        
                                        textAlign: 'left',  // Text alignment i.e. left, right or center
                                        loader: true,  // Whether to show loader or not. True by default
                                        loaderBg: '#9EC600',  // Background color of the toast loader
                                    });
                                        
                                } else {
                                    alert('Failed to save audio');
                                }
                            }
                        });
                    };

                    $('#visualizer').show();
                    isRecording = true;
                    $('#recording-button').css('background-color', 'green'); // تغییر رنگ دکمه به سبز برای نشان دادن حالت ضبط
                });
        } else {
            mediaRecorder.stop();
            $('#visualizer').hide();
            isRecording = false;
            $('#recording-button').css('background-color', 'red'); // بازگشت به رنگ قرمز
        }
    });
});
