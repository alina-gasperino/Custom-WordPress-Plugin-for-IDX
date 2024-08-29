jQuery(document).ready(function ($) {
    // Function to handle media upload for each specific field
    function handleMediaUpload(button) {
        var fileFrame;
        var wrapper = button.closest('.file-upload-wrapper');
        var fileField = wrapper.find('input[type="hidden"]'); // Finds the related hidden input field
        var imagePreview = wrapper.find('#image-preview img'); // Finds the related image preview
        var editButton = wrapper.find('.edit-button'); // Finds the related edit button
        var removeButton = wrapper.find('.remove-image'); // Finds the related remove button

        // Upload button click handler
        button.on('click', function (e) {
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
                multiple: false // Set to false to allow only one file to be selected
            });

            // When an image is selected, run a callback
            fileFrame.on('select', function () {
                var attachment = fileFrame.state().get('selection').first().toJSON();
                fileField.val(attachment.url); // Sets the selected image URL in the hidden input field
                imagePreview.attr('src', attachment.url).show(); // Shows the selected image
                editButton.show(); // Shows the edit button
                removeButton.show(); // Shows the remove button
            });

            // Finally, open the media frame
            fileFrame.open();
        });

        // Edit button click handler
        editButton.on('click', function (e) {
            e.preventDefault();
            button.click(); // Trigger the upload button to reopen the media uploader
        });

        // Remove button click handler
        removeButton.on('click', function (e) {
            e.preventDefault();
            fileField.val(''); // Clears the value in the hidden input field
            imagePreview.attr('src', '').hide(); // Hides the image preview
            editButton.hide(); // Hides the edit button
            removeButton.hide(); // Hides the remove button
        });
    }

    // Apply the media upload function to each instance of the upload button
    $('.upload-button').each(function () {
        handleMediaUpload($(this));
    });
});