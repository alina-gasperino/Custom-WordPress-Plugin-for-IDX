jQuery(document).ready(function ($) {
    // Define variables for uploader and buttons
    var fileFrame;
    var fileField = $('#header_logo');
    var imagePreview = $('#image-preview img');
    var editButton = $('.edit-button');
    var removeButton = $('.remove-image');

    // Upload button click handler
    $('.upload-button').on('click', function (e) {
        e.preventDefault();

        // If the media frame already exists, reopen it
        if (fileFrame) {
            fileFrame.open();
            return;
        }

        // Create a new media frame
        fileFrame = wp.media({
            title: 'Select or Upload an Image',
            button: {
                text: 'Use this image',
            },
            multiple: false  // Set to false to allow only one file to be selected
        });

        // When an image is selected, run a callback
        fileFrame.on('select', function () {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            fileField.val(attachment.url);
            imagePreview.attr('src', attachment.url).show();
            editButton.show();
            removeButton.show();
        });

        // Finally, open the media frame
        fileFrame.open();
    });

    // Edit button click handler
    editButton.on('click', function (e) {
        e.preventDefault();
        $('.upload-button').click();
    });

    // Remove button click handler
    removeButton.on('click', function (e) {
        e.preventDefault();
        fileField.val('');
        imagePreview.attr('src', '').hide();
        editButton.hide();
        removeButton.hide();
    });
});