jQuery(document).ready(function ($) {
  let mediaRecorder;
  let audioChunks = [];
  let isRecording = false;
  let audioContext, analyser, dataArray, bufferLength, source;
  let isVisualizing = false;

  function visualize(stream) {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    analyser = audioContext.createAnalyser();
    source = audioContext.createMediaStreamSource(stream);
    source.connect(analyser);
    analyser.fftSize = 2048;
    bufferLength = analyser.frequencyBinCount;
    dataArray = new Uint8Array(bufferLength);

    const canvas = document.getElementById("visualizer");
    const canvasCtx = canvas.getContext("2d");

    function draw() {
      if (!isVisualizing) return;
      requestAnimationFrame(draw);
      analyser.getByteTimeDomainData(dataArray);

      canvasCtx.clearRect(0, 0, canvas.width, canvas.height);

      const centerY = canvas.height / 2;
      canvasCtx.beginPath();
      canvasCtx.moveTo(0, centerY);
      canvasCtx.lineTo(canvas.width, centerY);
      canvasCtx.strokeStyle = "rgba(0, 0, 0, 0.2)";
      canvasCtx.lineWidth = 1;
      canvasCtx.stroke();

      canvasCtx.lineWidth = 2;
      canvasCtx.strokeStyle = "rgb(0, 0, 0)";

      canvasCtx.beginPath();
      let sliceWidth = (canvas.width * 1.0) / bufferLength;
      let x = 0;

      for (let i = 0; i < bufferLength; i++) {
        let v = dataArray[i] / 128.0;
        let y = (v * canvas.height) / 2;

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

    isVisualizing = true;
    draw();
  }

  function showToast(message, type) {
    $.toast({
      text: message,
      heading: "Note",
      icon: type,
      showHideTransition: "fade",
      allowToastClose: true,
      hideAfter: 3000,
      stack: 3,
      position: "bottom-center",
      textAlign: "left",
      loader: true,
      loaderBg: type === "success" ? "#9EC600" : "#FF0000",
    });
  }

  function updateSelectedCount() {
    let count = $(".select-audio:checked").length;
    $("#selected-count").text(`Selected: ${count}`);
  }

  function updateTableAndPagination(data) {
    const $tableBody = $("#audio-table tbody");
    const $pagination = $(".audio-diary-list__pagination");
    const currentPage = parseInt(data.current_page) || 1;
    const totalPages = parseInt(data.total_pages) || 1;
    const totalFiles = parseInt(data.total_files) || 0;

    // به‌روزرسانی جدول
    $tableBody.empty();
    if (data.files && data.files.length > 0) {
      data.files.forEach((file) => {
        const fileDate = file.file_date;
        const fileTime = file.file_time;
        const fileUrl = file.file_url;
        const fileName = file.file_name;
        $tableBody.append(`
          <tr data-file="${fileName}">
            <td><input type="checkbox" class="audio-diary-list__checkbox select-audio" value="${fileName}"></td>
            <td>${fileDate}</td>
            <td>${fileTime}</td>
            <td><audio class="audio-diary-list__audio" controls src="${fileUrl}"></audio></td>
            <td class="audio-diary-list__actions-cell">
              <button class="audio-diary-list__button audio-diary-list__button--small btn-download download-single" 
                      data-url="${fileUrl}" data-name="${fileName}">Download</button>
              <button class="audio-diary-list__button audio-diary-list__button--small audio-diary-list__button--upload upload-to-drive" 
                      data-name="${fileName}">Upload to Drive</button>
            </td>
          </tr>
        `);
      });
    } else {
      $tableBody.append('<tr><td colspan="5">No audio files found.</td></tr>');
    }

    // به‌روزرسانی ویژگی data-total-files
    $("#audio-table")
      .data("total-files", totalFiles)
      .attr("data-total-files", totalFiles);

    // به‌روزرسانی صفحه‌بندی
    $pagination.empty();
    if (totalPages <= 1) {
      $pagination.hide();
    } else {
      const baseUrl = window.location.pathname + "?page=audio-diary-list";

      // لینک "قبلی"
      if (currentPage > 1) {
        $pagination.append(
          `<a href="#" class="audio-diary-list__pagination-link audio-diary-list__pagination-link--prev" data-page="${
            currentPage - 1
          }">Previous</a>`
        );
      }

      // لینک‌های شماره صفحه
      for (let i = 1; i <= totalPages; i++) {
        const activeClass =
          i === currentPage ? "audio-diary-list__pagination-link--active" : "";
        $pagination.append(
          `<a href="#" class="audio-diary-list__pagination-link ${activeClass}" data-page="${i}">${i}</a>`
        );
      }

      // لینک "بعدی"
      if (currentPage < totalPages) {
        $pagination.append(
          `<a href="#" class="audio-diary-list__pagination-link audio-diary-list__pagination-link--next" data-page="${
            currentPage + 1
          }">Next</a>`
        );
      }

      $pagination.show();
    }

    // اتصال رویدادهای کلیک به لینک‌های صفحه‌بندی
    $(".audio-diary-list__pagination-link").on("click", function (e) {
      e.preventDefault();
      const page = $(this).data("page");
      fetchUpdatedFiles(page);
      // به‌روزرسانی URL بدون رفرش
      const newUrl = page > 1 ? `${baseUrl}&paged=${page}` : baseUrl;
      history.pushState({}, "", newUrl);
    });

    // اتصال رویدادها به عناصر جدید
    $(".download-single").on("click", function () {
      const $button = $(this);
      const fileUrl = $button.data("url");
      const fileName = $button.data("name");
      const downloadLink = document.createElement("a");
      downloadLink.href = fileUrl;
      downloadLink.download = fileName;
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    });

    $(".upload-to-drive").on("click", function () {
      const $button = $(this);
      const fileName = $button.data("name");
      if ($button.hasClass("uploaded")) {
        showToast("This file is already uploaded to Google Drive.", "error");
        return;
      }
      $button.text("Uploading...").prop("disabled", true);
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "upload_to_google_drive",
          file: fileName,
        },
        success: function (response) {
          if (response.success) {
            showToast("File uploaded to Google Drive successfully!", "success");
            $button
              .text("Uploaded")
              .addClass("uploaded")
              .prop("disabled", true);
          } else {
            if (response.data === "File already exists in Google Drive.") {
              showToast("This file already exists in Google Drive.", "error");
              $button
                .text("Uploaded")
                .addClass("uploaded")
                .prop("disabled", true);
            } else {
              showToast("Failed to upload: " + response.data, "error");
              $button.text("Upload to Drive").prop("disabled", false);
            }
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          showToast("Upload Error: " + textStatus, "error");
          $button.text("Upload to Drive").prop("disabled", false);
        },
      });
    });

    // بازنشانی چک‌باکس‌ها و به‌روزرسانی تعداد انتخاب‌شده‌ها
    $(".select-audio").prop("checked", false);
    updateSelectedCount();
  }

  function fetchUpdatedFiles(page) {
    const currentPage =
      page || new URLSearchParams(window.location.search).get("paged") || 1;
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "audio_diary_get_updated_files",
        paged: currentPage,
      },
      success: function (response) {
        if (response.success) {
          updateTableAndPagination(response.data);
        } else {
          showToast(
            "Failed to fetch updated files: " +
              (response.data || "Unknown error"),
            "error"
          );
          // فال‌بک: نمایش پیام در جدول
          $("#audio-table tbody").html(
            '<tr><td colspan="5">Error loading files. Please try again.</td></tr>'
          );
          $(".audio-diary-list__pagination").hide();
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showToast("AJAX Error: " + textStatus + " - " + errorThrown, "error");
        // فال‌بک: نمایش پیام در جدول
        $("#audio-table tbody").html(
          '<tr><td colspan="5">Error loading files. Please try again.</td></tr>'
        );
        $(".audio-diary-list__pagination").hide();
      },
    });
  }

  $(".download-single").on("click", function () {
    var $button = $(this);
    var fileUrl = $button.data("url");
    var fileName = $button.data("name");

    var downloadLink = document.createElement("a");
    downloadLink.href = fileUrl;
    downloadLink.download = fileName;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
  });

  $("#download-zip").on("click", function () {
    var selectedFiles = [];
    $(".select-audio:checked").each(function () {
      selectedFiles.push($(this).val());
    });

    if (selectedFiles.length > 0) {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "download_zip",
          files: selectedFiles,
        },
        success: function (response) {
          if (response.success) {
            showToast("Zip file created successfully", "success");
            var downloadLink = document.createElement("a");
            downloadLink.href = response.data.zip_url;
            downloadLink.download = response.data.zip_url.split("/").pop();
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            $(".select-audio").prop("checked", false);
            updateSelectedCount();
          } else {
            showToast("Failed to create zip file: " + response.data, "error");
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          showToast("AJAX Error: " + textStatus + ": " + errorThrown, "error");
        },
      });
    } else {
      showToast("Please select at least one audio file to download.", "error");
    }
  });

  $("#recording-button").on("click", function () {
    if (!isRecording) {
      navigator.mediaDevices
        .getUserMedia({ audio: true })
        .then((stream) => {
          mediaRecorder = new MediaRecorder(stream);
          audioChunks = [];
          mediaRecorder.start();
          visualize(stream);

          mediaRecorder.ondataavailable = function (event) {
            audioChunks.push(event.data);
          };

          mediaRecorder.onstop = function () {
            stream.getTracks().forEach((track) => track.stop());
            const audioBlob = new Blob(audioChunks, { type: "audio/wav" });
            audioChunks = [];
            const audioUrl = URL.createObjectURL(audioBlob);
            $("#audio-player").attr("src", audioUrl).addClass("show");

            let formData = new FormData();
            formData.append("audio_data", audioBlob, "audio.wav");
            formData.append("action", "save_audio");

            const ajaxUrl =
              typeof audio_diary_vars !== "undefined" &&
              audio_diary_vars.ajaxurl
                ? audio_diary_vars.ajaxurl
                : ajaxurl;

            $.ajax({
              url: ajaxUrl,
              type: "POST",
              data: formData,
              processData: false,
              contentType: false,
              success: function (response) {
                if (response.success) {
                  showToast("Audio saved successfully", "success");
                  fetchUpdatedFiles(); // به‌روزرسانی جدول پس از ضبط جدید
                } else {
                  showToast("Failed to save audio: " + response.data, "error");
                }
              },
              error: function (jqXHR, textStatus, errorThrown) {
                showToast(
                  "AJAX Error: " + textStatus + " - " + errorThrown,
                  "error"
                );
              },
            });
          };
          $("#visualizer").show();
          isRecording = true;
          $("#recording-button").css("background-color", "green");
        })
        .catch((error) => {
          showToast("Unable to access microphone: " + error.message, "error");
        });
    } else {
      mediaRecorder.stop();
      $("#visualizer").hide();
      isVisualizing = false;
      audioContext.close();
      isRecording = false;
      $("#recording-button").css("background-color", "red");
    }
  });

  $("#download-selected").on("click", function () {
    let selectedFiles = [];
    $(".select-audio:checked").each(function () {
      selectedFiles.push($(this).val());
    });

    if (selectedFiles.length > 0) {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "download_zip",
          files: selectedFiles,
        },
        success: function (response) {
          if (response.success) {
            showToast("Zip file created successfully", "success");
            let downloadLink = document.createElement("a");
            downloadLink.href = response.data.zip_url;
            downloadLink.download = response.data.zip_url.split("/").pop();
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            $(".select-audio").prop("checked", false);
            updateSelectedCount();
          } else {
            showToast("Failed to create zip file: " + response.data, "error");
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          showToast("AJAX Error: " + textStatus + ": " + errorThrown, "error");
        },
      });
    }
  });

  $("#select-all").on("click", function () {
    let isSelectAll = $(this).data("select-all");
    if (isSelectAll) {
      $(".select-audio").prop("checked", true);
      $(this).text("Unselect All");
    } else {
      $(".select-audio").prop("checked", false);
      $(this).text("Select All");
    }
    $(this).data("select-all", !isSelectAll);
    updateSelectedCount();
  });

  $("#delete-selected").on("click", function () {
    const selectedFiles = [];
    $(".select-audio:checked").each(function () {
      selectedFiles.push($(this).val());
    });

    if (selectedFiles.length === 0) {
      showToast("Please select at least one audio file to delete.", "error");
      return;
    }

    const confirmDialog = confirm(
      "Are you sure you want to delete " +
        selectedFiles.length +
        " selected file(s)?\nPress OK to delete only locally, or Cancel and choose below."
    );
    if (!confirmDialog) {
      const deleteFromDrive = confirm(
        "Also delete these files from Google Drive?"
      );
      deleteFiles(selectedFiles, deleteFromDrive);
    } else {
      deleteFiles(selectedFiles, false);
    }
  });

  function deleteFiles(files, deleteFromDrive) {
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "audio_diary_delete_selected_audios",
        files: files,
        delete_from_drive: deleteFromDrive,
      },
      success: function (response) {
        if (response.success) {
          showToast(
            "Deleted " +
              (response.data?.deleted_local || 0) +
              " local file(s) and " +
              (response.data?.deleted_drive || 0) +
              " Google Drive file(s) successfully!",
            "success"
          );
          // بازنشانی چک‌باکس‌ها
          $(".select-audio").prop("checked", false);
          // به‌روزرسانی تعداد انتخاب‌شده‌ها
          updateSelectedCount();
          // دریافت لیست به‌روزرسانی‌شده فایل‌ها
          fetchUpdatedFiles();
          if (response.data?.drive_errors) {
            showToast(response.data.drive_errors, "warning");
          }
        } else {
          showToast(
            "Failed to delete files: " + (response.data || "Unknown error"),
            "error"
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showToast("AJAX Error: " + textStatus + " - " + errorThrown, "error");
      },
    });
  }

  $(".upload-to-drive").on("click", function () {
    const $button = $(this);
    const fileName = $button.data("name");

    if ($button.hasClass("uploaded")) {
      showToast("This file is already uploaded to Google Drive.", "error");
      return;
    }

    $button.text("Uploading...").prop("disabled", true);

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "upload_to_google_drive",
        file: fileName,
      },
      success: function (response) {
        if (response.success) {
          showToast("File uploaded to Google Drive successfully!", "success");
          $button.text("Uploaded").addClass("uploaded").prop("disabled", true);
        } else {
          if (response.data === "File already exists in Google Drive.") {
            showToast("This file already exists in Google Drive.", "error");
            $button
              .text("Uploaded")
              .addClass("uploaded")
              .prop("disabled", true);
          } else {
            showToast("Failed to upload: " + response.data, "error");
            $button.text("Upload to Drive").prop("disabled", false);
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showToast("Upload Error: " + textStatus, "error");
        $button.text("Upload to Drive").prop("disabled", false);
      },
    });
  });

  $("button#audioduration").on("click", function () {
    var $button = $(this);
    if ($button.data("loaded")) return;
    var audioUrl = $button.closest("td").find(".audio-duration").data("url");

    if (!audioUrl) {
      $button.text("خطا");
      return;
    }

    $button.text("در حال بارگذاری...");
    var audio = new Audio(audioUrl);

    audio.addEventListener("canplaythrough", function () {
      var duration = audio.duration;

      if (isNaN(duration) || duration <= 0) {
        $button.text("خطا");
        return;
      }

      var minutes = Math.floor(duration / 60);
      var seconds = Math.floor(duration % 60);
      var formattedDuration =
        minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

      $button.text(formattedDuration);
      $button.data("loaded", true);
    });

    audio.addEventListener("error", function () {
      $button.text("خطا");
    });

    audio.load();
  });

  $("button.audioduration").on("click", function () {
    var $button = $(this);
    if ($button.data("loaded")) return;

    var audioUrl = $button.closest("td").find(".audio-duration").data("url");
    if (!audioUrl) {
      $button.text("خطا");
      return;
    }

    $button.text("در حال بارگذاری...");
    var audio = new Audio(audioUrl);

    audio.addEventListener("canplaythrough", function () {
      var duration = audio.duration;

      if (isNaN(duration) || duration <= 0) {
        $button.text("خطا");
        return;
      }

      var minutes = Math.floor(duration / 60);
      var seconds = Math.floor(duration % 60);
      var formattedDuration =
        minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

      $button.text(formattedDuration);
      $button.data("loaded", true);
    });

    audio.addEventListener("error", function () {
      $button.text("خطا");
    });

    audio.load();
  });

  // اتصال رویداد change به‌صورت delegated برای چک‌باکس‌ها
  $(document).on("change", ".select-audio", function () {
    updateSelectedCount();
  });

  // اطمینان از به‌روزرسانی تعداد انتخاب‌شده‌ها و صفحه‌بندی هنگام لود صفحه
  updateSelectedCount();
  fetchUpdatedFiles();
});
