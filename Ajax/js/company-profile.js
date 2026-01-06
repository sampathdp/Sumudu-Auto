/**
 * Company Profile Management JavaScript
 * Handles logo/favicon uploads and form submission
 */

$(document).ready(function () {
  const companyId = $('#companyProfileForm input[name="id"]').val();

  /**
   * Upload logo with preview
   */
  $("#logoUpload").on("change", function () {
    const file = this.files[0];
    if (!file) return;

    // Validate file type
    const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
    if (!validTypes.includes(file.type)) {
      Swal.fire({
        icon: "error",
        title: "Invalid File Type",
        text: "Please upload a JPG, PNG, or GIF image.",
      });
      return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({
        icon: "error",
        title: "File Too Large",
        text: "Logo file size must be less than 5MB.",
      });
      return;
    }

    const formData = new FormData();
    formData.append("logo", file);
    formData.append("id", companyId);
    formData.append("action", "upload_logo");

    // Show loading
    Swal.fire({
      title: "Uploading...",
      text: "Please wait while we upload your logo",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    $.ajax({
      url: "../../Ajax/php/company-profile.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: res.message,
            timer: 1500,
            showConfirmButton: false,
          });

          // Update preview
          const previewImg = $("#logoPreview");
          previewImg.attr("src", "../../uploads/company/" + res.filename);
          previewImg.removeClass("d-none");
        } else {
          Swal.fire({
            icon: "error",
            title: "Upload Failed",
            text: res.message,
          });
        }
      },
      error: function (xhr, status, error) {
        Swal.fire({
          icon: "error",
          title: "Upload Failed",
          text: "Failed to upload logo. Please try again.",
        });
        console.error("Logo upload error:", error);
      },
    });
  });

  /**
   * Upload favicon with preview
   */
  $("#faviconUpload").on("change", function () {
    const file = this.files[0];
    if (!file) return;

    // Validate file type
    const validTypes = [
      "image/x-icon",
      "image/vnd.microsoft.icon",
      "image/png",
      "image/jpeg",
    ];
    if (!validTypes.includes(file.type)) {
      Swal.fire({
        icon: "error",
        title: "Invalid File Type",
        text: "Please upload an ICO or PNG file.",
      });
      return;
    }

    // Validate file size (1MB)
    if (file.size > 1 * 1024 * 1024) {
      Swal.fire({
        icon: "error",
        title: "File Too Large",
        text: "Favicon file size must be less than 1MB.",
      });
      return;
    }

    const formData = new FormData();
    formData.append("favicon", file);
    formData.append("id", companyId);
    formData.append("action", "upload_favicon");

    // Show loading
    Swal.fire({
      title: "Uploading...",
      text: "Please wait while we upload your favicon",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    $.ajax({
      url: "../../Ajax/php/company-profile.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: res.message,
            timer: 1500,
            showConfirmButton: false,
          });

          // Update preview
          const previewImg = $("#faviconPreview");
          previewImg.attr("src", "../../uploads/company/" + res.filename);
          previewImg.removeClass("d-none");
        } else {
          Swal.fire({
            icon: "error",
            title: "Upload Failed",
            text: res.message,
          });
        }
      },
      error: function (xhr, status, error) {
        Swal.fire({
          icon: "error",
          title: "Upload Failed",
          text: "Failed to upload favicon. Please try again.",
        });
        console.error("Favicon upload error:", error);
      },
    });
  });

  /**
   * Handle company profile form submission
   */
  $("#companyProfileForm").on("submit", function (e) {
    e.preventDefault();

    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span>Saving...'
      );

    $.post(
      "../../Ajax/php/company-profile.php",
      $(this).serialize(),
      function (res) {
        if (res.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: res.message,
            timer: 1500,
            showConfirmButton: false,
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Update Failed",
            text: res.message || "Failed to update company profile",
          });
        }
        submitBtn.prop("disabled", false).html(originalText);
      },
      "json"
    ).fail(function (xhr, status, error) {
      Swal.fire({
        icon: "error",
        title: "Network Error",
        text: "Failed to connect to server. Please check your connection.",
      });
      submitBtn.prop("disabled", false).html(originalText);
      console.error("Form submit error:", error);
    });
  });

  /**
   * Enable/disable tax fields based on VAT toggle
   */
  $("#isVat")
    .on("change", function () {
      const isEnabled = $(this).is(":checked");
      $("#taxNumber, #taxPercentage").prop("disabled", !isEnabled);

      if (isEnabled) {
        $("#taxNumber, #taxPercentage")
          .closest(".mb-3")
          .removeClass("opacity-50");
      } else {
        $("#taxNumber, #taxPercentage").closest(".mb-3").addClass("opacity-50");
      }
    })
    .trigger("change");

  /**
   * Preview uploaded images before upload
   */
  function showImagePreview(input, previewId) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        $(previewId).attr("src", e.target.result).removeClass("d-none");
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Optional: Add client-side preview before upload
  $("#logoUpload").on("change", function () {
    showImagePreview(this, "#logoPreview");
  });

  $("#faviconUpload").on("change", function () {
    showImagePreview(this, "#faviconPreview");
  });
});
