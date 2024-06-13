jQuery(document).ready(function($) {
    var mediaRecorder;
    var audioChunks = [];

    $('#start-recording').on('click', function() {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.start();

                mediaRecorder.addEventListener("dataavailable", event => {
                    audioChunks.push(event.data);
                });

                mediaRecorder.addEventListener("stop", () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    const audioUrl = URL.createObjectURL(audioBlob);
                    const audio = document.getElementById('audio-player');
                    audio.src = audioUrl;

                    var reader = new FileReader();
                    reader.readAsDataURL(audioBlob);
                    reader.onloadend = function() {
                        var base64data = reader.result;
                        $.ajax({
                            url: audioDiaryJsObject.ajax_url,
                            method: 'POST',
                            data: {
                                action: 'save_audio',
                                audio_data: base64data,
                                _wpnonce: audioDiaryJsObject.nonce
                            },
                            success: function(response) {
                                console.log('Audio saved successfully.');
                            },
                            error: function() {
                                console.error('Failed to save audio.');
                            }
                        });
                    };
                });

                $('#stop-recording').prop('disabled', false);
                $('#start-recording').prop('disabled', true);
            });
    });

    $('#stop-recording').on('click', function() {
        mediaRecorder.stop();
        $('#stop-recording').prop('disabled', true);
        $('#start-recording').prop('disabled', false);
    });
});
