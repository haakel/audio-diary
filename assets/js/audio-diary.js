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

            canvasCtx.clearRect(0, 0, canvas.width, canvas.height);

            const centerY = canvas.height / 2;
            canvasCtx.beginPath();
            canvasCtx.moveTo(0, centerY);
            canvasCtx.lineTo(canvas.width, centerY);
            canvasCtx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
            canvasCtx.lineWidth = 1;
            canvasCtx.stroke();

            canvasCtx.lineWidth = 2;
            canvasCtx.strokeStyle = 'rgb(0, 0, 0)';

            canvasCtx.beginPath();
            let sliceWidth = canvas.width * 1.0 / bufferLength;
            let x = 0;

            for (let i = 0; i < bufferLength; i++) {
                let v = dataArray[i] / 128.0;
                let y = v * canvas.height / 2;

                if (i === 0) {
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

        
        $('#download-zip').on('click', function() {
        var selectedFiles = [];
        $('.select-audio:checked').each(function() {
            selectedFiles.push($(this).val());
        });

        if (selectedFiles.length > 0) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            selectedFiles.forEach(function(file) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_files[]';
                input.value = file;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        } else {
            $.toast({
                text: "Please select at least one audio file to download.",
                heading: 'Note',
                icon: 'error',
                showHideTransition: 'fade',
                allowToastClose: true,
                hideAfter: 3000,
                stack: 3,
                position: 'bottom-center',
                textAlign: 'left',
                loader: true,
                loaderBg: '#FF0000',
            });
        }
    });



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
                                        text: "Audio saved successfully",
                                        heading: 'Note',
                                        icon: 'success',
                                        showHideTransition: 'fade',
                                        allowToastClose: true,
                                        hideAfter: 3000,
                                        stack: 3,
                                        position: 'bottom-center',
                                        textAlign: 'left',
                                        loader: true,
                                        loaderBg: '#9EC600',
                                    });
                                } else {
                                    $.toast({
                                        text: "Failed to save audio",
                                        heading: 'Note',
                                        icon: 'error',
                                        showHideTransition: 'fade',
                                        allowToastClose: true,
                                        hideAfter: 3000,
                                        stack: 3,
                                        position: 'bottom-center',
                                        textAlign: 'left',
                                        loader: true,
                                        loaderBg: '#9EC600',
                                    });
                                }
                            }
                        });
                    };

                    $('#visualizer').show();
                    isRecording = true;
                    $('#recording-button').css('background-color', 'green');
                });
        } else {
            mediaRecorder.stop();
            $('#visualizer').hide();
            isRecording = false;
            $('#recording-button').css('background-color', 'red');
        }
    });

    $('#download-selected').on('click', function() {
        let selectedFiles = [];
        $('.select-audio:checked').each(function() {
            selectedFiles.push($(this).val());
        });
    
        if (selectedFiles.length > 0) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'download_zip',
                    files: selectedFiles
                },
                success: function(response) {
                    if (response.success) {
                        $.toast({
                            text: "Zip file created successfully",
                            heading: 'Note',
                            icon: 'success',
                            showHideTransition: 'fade',
                            allowToastClose: true,
                            hideAfter: 3000,
                            stack: 3,
                            position: 'bottom-center',
                            textAlign: 'left',
                            loader: true,
                            loaderBg: '#9EC600',
                        });
    
                        // ایجاد لینک دانلود
                        let downloadLink = document.createElement('a');
                        downloadLink.href = response.data.zip_url;
                        downloadLink.download = response.data.zip_url.split('/').pop();
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
    
                        // برداشتن انتخاب فایل‌های صوتی
                        $('.select-audio').prop('checked', false);
                    } else {
                        $.toast({
                            text: "Failed to create zip file: " + response.data,
                            heading: 'Error',
                            icon: 'error',
                            showHideTransition: 'fade',
                            allowToastClose: true,
                            hideAfter: 3000,
                            stack: 3,
                            position: 'bottom-center',
                            textAlign: 'left',
                            loader: true,
                            loaderBg: '#FF0000',
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX Error: ' + textStatus + ': ' + errorThrown);
                    $.toast({
                        text: "AJAX Error: " + textStatus + ": " + errorThrown,
                        heading: 'Error',
                        icon: 'error',
                        showHideTransition: 'fade',
                        allowToastClose: true,
                        hideAfter: 3000,
                        stack: 3,
                        position: 'bottom-center',
                        textAlign: 'left',
                        loader: true,
                        loaderBg: '#FF0000',
                    });
                }
            });
        }
    });

    $('#select-all').on('click', function() {
        let isSelectAll = $(this).data('select-all');
        
        if (isSelectAll) {
            $('.select-audio').prop('checked', true);
            $(this).text('Unselect All');
        } else {
            $('.select-audio').prop('checked', false);
            $(this).text('Select All');
        }
        
        // تغییر حالت دکمه
        $(this).data('select-all', !isSelectAll);
    });
    
    
    // حذف فایل‌های انتخاب‌شده
    $('#delete-selected').on('click', function() {
        let selectedFiles = [];
        $('.select-audio:checked').each(function() {
            selectedFiles.push($(this).val());
        });
    
        if (selectedFiles.length > 0 && confirm("Are you sure you want to delete selected audio files?")) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_selected_audios',
                    files: selectedFiles
                },
                success: function(response) {
                    if (response.success) {
                        $.toast({
                            text: "Selected audio files deleted successfully",
                            heading: 'Note',
                            icon: 'success',
                            showHideTransition: 'fade',
                            allowToastClose: true,
                            hideAfter: 3000,
                            stack: 3,
                            position: 'bottom-center',
                            textAlign: 'left',
                            loader: true,
                            loaderBg: '#9EC600',
                        });
    
                        // پس از حذف موفق فایل‌ها، لیست را به‌روزرسانی کنید
                        selectedFiles.forEach(function(fileName) {
                            $('.delete-audio[data-file="' + fileName + '"]').closest('tr').fadeOut(400, function() {
                                $(this).remove();
                            });
                        });
                    } else {
                        $.toast({
                            text: "Failed to delete selected audio files: " + response.data,
                            heading: 'Error',
                            icon: 'error',
                            showHideTransition: 'fade',
                            allowToastClose: true,
                            hideAfter: 3000,
                            stack: 3,
                            position: 'bottom-center',
                            textAlign: 'left',
                            loader: true,
                            loaderBg: '#FF0000',
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX Error: ' + textStatus + ': ' + errorThrown);
                    $.toast({
                        text: "AJAX Error: " + textStatus + ": " + errorThrown,
                        heading: 'Error',
                        icon: 'error',
                        showHideTransition: 'fade',
                        allowToastClose: true,
                        hideAfter: 3000,
                        stack: 3,
                        position: 'bottom-center',
                        textAlign: 'left',
                        loader: true,
                        loaderBg: '#FF0000',
                    });
                }
            });
        } else {
            $.toast({
                text: "Please select at least one audio file to delete.",
                heading: 'Error',
                icon: 'error',
                showHideTransition: 'fade',
                allowToastClose: true,
                hideAfter: 3000,
                stack: 3,
                position: 'bottom-center',
                textAlign: 'left',
                loader: true,
                loaderBg: '#FF0000',
            });
        }
    });
});