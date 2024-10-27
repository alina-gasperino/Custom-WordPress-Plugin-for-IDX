jQuery(document).ready(function ($) {
    $('#formidable-xml-import-form').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'upload_formidable_xml'); // Set AJAX action

        // Perform AJAX request
        $.ajax({
            url: ajaxurl, // WordPress global AJAX handler
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('#formidable-import-response').html('<p style="color:green;">' + response.data + '</p>');
                } else {
                    $('#formidable-import-response').html('<p style="color:red;">' + response.data + '</p>');
                }
            },
            error: function () {
                $('#formidable-import-response').html('<p style="color:red;">Error during AJAX request.</p>');
            }
        });
    });
});
