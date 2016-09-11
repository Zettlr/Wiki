/* Initialize the contenttools editor */
window.addEventListener('load', function() {
    var editor = ContentTools.EditorApp.get();

    // Initialize the editor on data-editable regions with data-name attribute,
    // no fixture test but WITHOUT ignition ui. Why? Because we have to call
    // edit/save manually for we have to download the raw HTML before entering
    // edit mode.
    editor.init('*[data-editable]', 'data-name', null, false);

    /* Listen for save events and, in that case, upload the new HTML to the server */

    editor.addEventListener('saved', function(e) {
        // Set the editors state to busy while we save our changes
        editor.busy(true);

        regions = e.detail().regions;

        // Save the current DOM contents to server
        newContents = {
            _token: zettlrURL.csrfToken,
            api_token: zettlrURL.api_token,
            title: $('[data-name="page-title"]').text(),
            content: regions['page-content'],
            slug: $('[data-name="page-content"]').attr("data-slug")
        };

console.log('Using API token ' + zettlrURL.api_token);
        $.ajax({
            method: 'POST',
            url: zettlrURL.contentToolsSetter,
            data: newContents,
            success: function(data) {
                // Now we only have to retrieve the parsed content again.
                $.get(zettlrURL.contentToolsGetter +
                    "/" + $('[data-name="page-content"]').attr('data-id'),
                    function() { /* Do nothing */ })
                    .success(function(data) {
                        // On a success insert the HTML to the page (data[1] is the 200 code)
                        // Last but not least: re-assemble the TOC
                        //editor.stop(true);
                        editor.busy(false);
                        $('[data-name="page-content"]').html(data[0]);
                        assembleTOC();
                        $('#contenttoolsEdit').html("<span>" + zettlrTrans.saveSuccess + "</span>").removeClass("warning").addClass("success").removeClass("editing");
                        // Hide cancel button
                        $('#contenttoolsCancel').addClass("hidden");
                        $('#contenttoolsEdit span').first().fadeOut(1200, function() {
                            $(this).text(zettlrTrans.editContentTools).fadeIn(100);
                        });
                    })
                    .fail(function(data) {
                        // On error flash the UI for an error and do nothing
                        new ContentTools.FlashUI('no');
                    });
                },
                error: function(data) {
                    new ContentTools.FlashUI('no');
                    $('#contenttoolsEdit').text(zettlrTrans.saveFail).removeClass("warning").addClass("error");
                }
            });

        });

        // Commence the editing process manually
        $('#contenttoolsEdit').on("click", function() {
            if($(this).hasClass("editing")) {
                // Stop the editor
                $('#contenttoolsEdit')
                .html('<span class="fa fa-cog fa-spin fa-lg"></span>')
                .addClass("warning")
                .removeClass("success");

                editor.stop(true);
            }
            else { // Commence editing
                $('#contenttoolsEdit').html('<span class="fa fa-cog fa-spin fa-lg"></span>').addClass("warning").removeClass("success");
                editor.busy(true);

                // Load unparsed "raw" page contents from server
                $.get(zettlrURL.contentToolsGetter +
                    "/" + $('[data-name="page-content"]').attr('data-id') + "/raw", // Get the data raw
                    function() { /* Do nothing */ })
                    .success(function(data) {
                        // On a success insert the HTML to the page (data[1] is the 200 code)
                        $('[data-name="page-content"]').html(data[0]);
                        // Now re-sync the regions so that the editor hooks up to the new
                        // content
                        editor.syncRegions('[data-name="page-content"]');
                        // We're not busy anymore
                        editor.busy(false);
                        // Start editor
                        editor.start();

                        // Rename our button and show the cancel button
                        $('#contenttoolsEdit').html(zettlrTrans.saveChanges)
                        .addClass("success")
                        .removeClass("warning")
                        .addClass("editing");
                        $('#contenttoolsCancel').removeClass("hidden");
                    })
                    .fail(function(data) {
                        // On error flash the UI for an error and do nothing
                        new ContentTools.FlashUI('no');
                    });
                }
            });

            // Hook on to cancelling events
            $('#contenttoolsCancel').on('click', function() {
                if(!$('#contenttoolsEdit').hasClass("editing")) {
                    // Don't hack me!
                    return;
                }

                $('#contenttoolsCancel').html('<span class="fa fa-cog fa-spin fa-lg"></span>').addClass("warning").removeClass("error");
                // Revert changes and exit editing mode (false means "Don't save")
                editor.busy(true);
                editor.stop(false);

                // Now we have a problem. As we have sync'd our regions AFTER replacing
                // the content we now have to get the content back.
                $.get(zettlrURL.contentToolsGetter +
                    "/" + $('[data-name="page-content"]').attr('data-id'),
                    function() { /* Do nothing */ })
                    .success(function(data) {
                        // On a success insert the HTML to the page (data[1] is the 200 code)
                        $('[data-name="page-content"]').html(data[0]);
                        // We're not busy anymore
                        editor.busy(false);
                        $('#contenttoolsEdit').html(zettlrTrans.editContentTools).removeClass("editing");
                        $('#contenttoolsCancel').addClass("hidden").removeClass("warning").addClass("error").html(zettlrTrans.cancel);

                        // Last but not least: re-assemble the TOC
                        assembleTOC();
                    })
                    .fail(function(data) {
                        // On error flash the UI for an error and do nothing
                        new ContentTools.FlashUI('no');
                    });
                });
            });
            // END APPEND DOCUMENT LOAD CODE
;/*
* Here any potential helper functions (globally) are collected
*/


// The following two are functions I took from https://gist.github.com/CatTail/4174511
// Basically they only encode/decode HTML entities ´, e.g. & -> &amp; and back
var decodeHtmlEntity = function(str) {
    return str.replace(/&#(\d+);/g, function(match, dec) {
        return String.fromCharCode(dec);
    });
};

var encodeHtmlEntity = function(str) {
    var buf = [];
    for (var i=str.length-1;i>=0;i--) {
        buf.unshift(['&#', str[i].charCodeAt(), ';'].join(''));
    }
    return buf.join('');
};

// This function creates the TOC. We have to export it here so that it can be
// called several times (contenttools editing will remove the anchors in the
// headings)
var assembleTOC = function() {
    // First we need to check for already implemented TOCs so that we can
    // remove them first before writing a new one.
    if($('.toc-parent').length) {
        $('.toc-parent').remove();
    }
    if($('#toc-toggle').length) {
        $('#toc-toggle').first().parent().remove();
    }

    var toc = [];
    var i = 0;
    $('#wikitext').find('*').each(function() {
        if($(this).prop("tagName").match(/^h[1-6]$/i)) {
            // First encode the HTML entities to avoid constructions like
            // title="a title "with" quotes"
            txt = encodeHtmlEntity($(this).text());
            toc.push('<li class="class-' +
            $(this).prop("tagName").toLowerCase() + '"><a href="#heading' +
            i + '" title="' + txt + '" class="toc-link">' + txt + '</a></li>');
            $(this).prepend('<a name="heading' + i + '" class="anchor"></a>');
            i++;
        }
    });

    // Now display in the correct container
    if(toc.length > 0)
    {
        mydiv = $('body').append('<div class="toc-parent"><div class="toc" id="toc"><ul></ul></div></div>');
        mydiv = $('#toc ul');

        // Also prepend the toggle
        $('nav.nav ul').prepend('<li><a id="toc-toggle" role="button" aria-label="Toggle Navigation" class="lines-button"><span class="lines"></span></a></li>');

        for(i = 0; i < toc.length; i++)
        {
            mydiv.append(toc[i]);
        }

        // Initially hide the toc-parent:
        $('.toc-parent').addClass('back');

        $('#toc-toggle').click(function() {
            if(!$(this).hasClass("open")) {
                // Show the parent element
                $('.toc-parent').removeClass('back');
                $('#toc').addClass("open");
                $(this).addClass('open');
            }
            else {
                $('#toc').removeClass('open');
                $('#toc-toggle').removeClass("open");
                // Hide the toc parent element, as it will overlay the page
                // preventing clicks
                $('.toc-parent').addClass('back');
            }
        });

        // Also close toc when a heading is selected
        $('#toc').click(function() {
            $('#toc-toggle').click();
        });

        // Show a tooltip with the full title on hover
        $('.toc-link').mouseover(function() {
            ttipid = 'ttip-' + ($(this).attr('href')).substring(1);
            ttipcontent = $(this).attr('title');

            ttipelem = $('<div class="tooltip left" id="' + ttipid + '">' + ttipcontent + '</div>');

            // Remove 4px padding top, add 10px margin between toc and tooltip
            ttipelem.css('top', $(this).offset().top - 4);
            ttipelem.css('left', $(this).offset().left + $(this).outerWidth() + 10);
            $('body').append(ttipelem).fadeIn('fast');
        });

        $('.toc-link').mouseout(function() {
            ttipid = 'ttip-' + ($(this).attr('href')).substring(1);

            $('#' + ttipid).each(function() {
                $(this).fadeOut('fast', function() {
                    $(this).remove();
                });
            });
        });
    }
};
;/* MEDIA LIBRARY MODAL */

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
