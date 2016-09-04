/* MEDIA LIBRARY MODAL */

function MediaLibrary (getter, setter, token)
{
    // Set up vars
    this.filesToUpload = [];

    // The URLs we need to get the media from and upload
    this.getterURL = getter;
    this.setterURL = setter;

    // Laravel CSRF-token
    this.token     = token;

    // Will be overwritten by the validate function
    this.isReady   = false;

    // Dropzone object (will be filled in after constructor call)
    this.dz = null;

    // Currently unused
    this.dzelement = '<div class="dz-preview dz-image-preview">\
    <div class="dz-details">blablabla\
    <div class="dz-filename"><span data-dz-name></span></div>\
    <div class="dz-size" data-dz-size></div>\
    <img data-dz-thumbnail />\
    </div>\
    <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>\
    <div class="dz-success-mark"><span>Ok</span></div>\
    <div class="dz-error-mark"><span>Error</span></div>\
    <div class="dz-error-message"><span data-dz-errormessage></span></div>\
    </div>';

    // The actual modal
    this.modal = $('<div></div>', {
        'id' : 'media-library',
        'html' : '    <div id="tabs" style="height:100%;">                                                   \
        <h1>Media library</h1>                                                                                         \
        <!-- Navigation -->                                                                                            \
        <div class="tab-nav">                                                                                          \
        <ul>                                                                                                       \
        <li><a href="#media-library-content">Library</a></li>                                                  \
        <li><a href="#media-library-upload">Upload files</a></li>                                              \
        </ul>                                                                                                      \
        </div>                                                                                                         \
        \
        <div class="tab" id="media-library-content">                                                                   \
        <img src="/img/loading_spinner.gif" class="loading-spinner" id="loading-spinner" alt="Loading &hellip;">   \
        </div>                                                                                                         \
        \
        <!-- Second tab: Uploader -->                                                                                  \
        <div class="tab" id="media-library-upload">                                                                    \
        <div id="dropzone"></div> \
        </div>                                                                                                         \
        <div class="button-bar"><a class="button muted" id="media-library-upload-button">Upload files</a>\
        <a class="button error" id="media-library-close">Close</a></div>                       \
        </div>'
    }).addClass("modal");

    this.modal.style = 'display:none';
}

MediaLibrary.prototype.init = function() {
    this.on("addedfile", function(file) {
        titleElem = Dropzone.createElement('<input type="text" placeholder="Title" title="The file title" value="' + file.name + '" class="dz-img-info title-input-field" name="title[]">');
        file.previewElement.appendChild(titleElem);

        descElem = Dropzone.createElement('<input type="text" title="The file description" placeholder="Description" class="dz-img-info" name="description[]">');
        file.previewElement.appendChild(descElem);

        clearElem = Dropzone.createElement('<div style="clear:both;"></div>');
        file.previewElement.appendChild(clearElem);

        var current_dz_object = this;

        titleElem.addEventListener("keypress", function(e) {
            // Validate all fields. We assume all good, but if one field
            // is empty, we change the assumption and disable the button
            current_dz_object.isReady = true;

            $('.title-input-field').each(function() {
                if($(this).val() === "") {
                    current_dz_object.isReady = false;
                }
            });

            // Now change the upload button accordingly
            if(current_dz_object.isReady) {
                $('#media-library-upload-button').removeClass("muted");
                $('#media-library-upload-button').addClass("success");
            }
            else {
                $('#media-library-upload-button').removeClass("success");
                $('#media-library-upload-button').addClass("muted");
            }
        });

        // Now manually fire the validation event to make sure it gets "pre"-validated
        $('.title-input-field').trigger("keypress");
    });
};

/**
*  Displays the medialibrary modal by adding it to the document body and
*  registering all necessary event handlers
*
*  @return  {void}
*/
MediaLibrary.prototype.show = function() {
    // Add the dim class to the body and display the MediaLibrary
    $('body').addClass('dim');
    $('body').append(this.modal);

    // Finally display the modal
    $('#media-library').show();

    // Want to quit the modal? Press escape ...
    $(document).keydown($.proxy(function(e) {
        if(e.which == 27) {
            this.close(e);
        }
    }, this));

    // ... or click the close button
    $('#media-library-close').click($.proxy(this.close, this));

    // Inject dropzone code into the page
    $('head').append('<script src="js/dropzone.js" id="dzjs"></script>');

    // Activate dropzone
    this.dz = new Dropzone('div#dropzone', {
        url: this.setterURL, // Where to upload files to
        paramName: "file", // The $request-index
        maxFilesize: 8, // remember to make automatically depending on the wiki settings
        uploadMultiple: true, // More than one media file in one batch
        addRemoveLinks: true, // To cancel upload or remove element
        clickable: true, // Make the DZ-element clickable for non-d&d
        acceptedFiles: 'image/jpg,image/jpeg,image/png,image/gif,image/svg,image/bmp', // Only accept image files
        autoProcessQueue: false, // Don't let dropzone upload immediately, for information is needed
        previewElement: this.dzelement, // The preview container
        // Overwrite the init function to for every file add fields like title etc.
        init: this.init,
    });

    // Event handler for tracking changes on the file inputs for validation
    // $('#media-library media-library-file-list').on('change', 'input', $.proxy(this.validate, this));

    // Tabbify
    $('#media-library').tabs();

    // Initially hide the upload button as at first the library itself will be
    // shown, not the upload form
    $('#media-library-upload-button').hide();

    // Hook into tab selection to hide/show the upload button
    $('.tab-nav').bind("click", function(e) {
        if($('#media-library').tabs("option", "active") == 1) { // Second tab
            $('#media-library-upload-button').show();
        }
        else {
            $('#media-library-upload-button').hide();
        }
    });

    // Trigger the upload button
    var current_medialibrary_object = this;
    $('#media-library-upload-button').click(function(e) {
        // First determine if we are all set
        if($(this).is(':visible') && current_medialibrary_object.dz.isReady) {
            // Commence upload
            current_medialibrary_object.dz.processQueue();
        }
    });

    // Append title and description to file on upload
    this.dz.on('sending', function(file, xhr, formData) {
        // TODO
        formData.append('userName', 'bob');
        // file.previewElement;
    });
};

/**
*  Closes the media library by removing it from the document body
*
*  @param   {Event}  e  The event generated by the user to close
*
*  @return  {void}
*/
MediaLibrary.prototype.close = function(e) {
    // Only close when element is visible
    if($('#media-library').is(':visible')) {
        // $('#media-library').hide();
        $('body').removeClass('dim');

        // Remove the modal alltogether, to be sure to leave no trace when the
        // object gets destroyed and another one built after some time.
        $('#media-library').remove();

        // Also remove the script.
        $('#dzjs').remove();

        // Now every trace of the media library should be removed from the page
    }
};

MediaLibrary.prototype.getMedia = function() {
    // This function refills the tab with loaded images from the server
    $.get( this.getterURL, function() { /* Do nothing */ })
    .done(function(data) {
        // On success display the media
        for(var i = 0; i < data.responseJSON.length; i++)
        {
            // For now: Do nothing. First have to create uploading.
        }
    })
    .fail(function(data) {
        $('#media-library-content').html('<div class="alert primary">' + data.responseJSON.message + '</div>');
    });
};

MediaLibrary.prototype.processSelection = function() {
    // TODO when Dropzone works
    if(this.validate) {
        // Then we can safely call processQueue to start uploading
        this.dz.processQueue();
    }
};

/**
*  Validates all inputs to make sure we are set to go uploading (i.e. activate
*  the submit button)
*
*  @param   {Event}  event  The original event
*
*  @return  {bool}          Returns false to make sure stopPropagation is called
*/
MediaLibrary.prototype.validate = function(event) {
    // Basic functionality: go through all inputs, validate and either activate
    // or deactivate the upload button
    console.log("Validating ...");

    // Set the trigger
    disabled = true;

    // Our three elements each have classes called media-library-file-title,
    // -description and -copyright
    $('.media-library-file').each(function() {

    });

    // Finally, after validation, disable or enable the upload button
    if(disabled) {
        $('#media-library-submit').attr('disabled', 'disabled');
    }
    else {
        $('#media-library-submit').removeAttr('disabled');
    }

    return false;
};

// For uploading files using ajax, see http://blog.teamtreehouse.com/uploading-files-ajax

/* END MEDIA LIBRARY MODAL */
