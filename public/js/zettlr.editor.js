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
            title: $('[data-name="page-title"]').text(),
            content: regions['page-content'],
            slug: $('[data-name="page-content"]').attr("data-slug")
        };

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
