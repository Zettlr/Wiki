<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <!-- Enable mobile friendly design -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">

    <link rel="icon" type="image/png" href="/img/favicon.png">

    <title>{{ $page->title or "" }} | {{ env('APP_TITLE', 'zettlrWiki') }}</title>

    <!-- jQuery and jQuery UI -->
    <script src="{{ url('/') }}/js/jquery.min.js"></script>
    <script src="{{ url('/') }}/js/jquery-ui.min.js"></script>

    <!-- Include CodeMirror plugin -->
    <script src="{{ url('/') }}/js/codemirror.js"></script>
    <!-- Bugfix that helps the textarea to be rendered successfully -->
    <script src="{{ url('/') }}/js/codemirror_addons/overlay.js"></script>
    <link rel="stylesheet" href="{{ url('/') }}/css/codemirror.css">
    <!-- Include Markdown and its addon GithubFlavoredMarkdown -->
    <script src="{{ url('/') }}/js/codemirror_modes/markdown.js"></script>
    <script src="{{ url('/') }}/js/codemirror_modes/gfm.js"></script>

    <!-- Include our own functions -->
    <script src="{{ url('/') }}/js/zettlrWiki.min.js"></script>

    <!-- ContentTools inline editing w/ stylesheet -->
    <link rel="stylesheet" href="{{ url('/') }}/contenttools/content-tools.min.css">
    <script src="{{ url('/') }}/contenttools/content-tools.min.js"></script>

    <!-- Include main stylesheet -->
    <link rel="stylesheet" href="{{ url('/')}}/css/app.min.css">

    <script>
    /* Before anything let's create the globals we will need (mainly URLs) */
    var zettlrURL = {
        mediaLibraryGetter: "{{ url('/ajax/getMedia') }}",
        mediaLibrarySetter: "{{ url('/ajax/uploadMedia') }}",
        contentToolsGetter: "{{ url('/ajax/getPageContent') }}",
        contentToolsSetter: "{{ url('/edit/json') }}",
        csrfToken: "{!! csrf_token() !!}"
    };

    // Also we will need some translation strings
    var zettlrTrans = {
        saveSuccess: "{{ trans('javascript.contenttools.savesuccess')}}",
        saveFail: "{{ trans('javascript.contenttools.savefail')}}",
        saveChanges: "{{ trans('javascript.contenttools.savechanges')}}",
        cancel: "{{ trans('javascript.contenttools.cancel')}}",
        editContentTools: "{{ trans('javascript.contenttools.edit')}}"
    };

    // BEGIN DOCUMENT READY HOOK
    $(document).ready(function()
    {
        // Load a possible slug from the server (in case you don't want to
        // bother yourself with this)
        if($('#slug').length && $('#title').length)
        {
            $('#propose-slug').click(function(data) {
                // Request a new slug from the server
                var jqxhr = $.get( "{{ url('/sluggify') }}/" + $('#title').val(), function() { })
                .done(function(data) {
                    // On success replace the value
                    $('#slug').val(data.slug);
                })
                .fail(function() {
                    alert("{{ trans('javascript.ajax.slug.fail') }}");
                });
            })
        }

        // Render potential markdown insert fields
        // DEPRECATED - to be removed once contenttools work properly
        if($("#gfm-code").length)
        {
            // Add a formatting overlay for wikilinks ([[Link]])
            // This will be styled via cm-gfm-wiki class
            CodeMirror.defineMode("gfm-wiki", function(config, parserConfig) {
                var gfmWikiOverlay = {
                    token: function(stream, state) {
                        var ch;
                        if (stream.match("[[")) {
                            while ((ch = stream.next()) != null)
                            if (ch == "]" && stream.next() == "]") {
                                stream.eat("]");
                                return "gfm-wiki";
                            }
                        }
                        while (stream.next() != null && !stream.match("[[", false)) {}
                        return null;
                    }
                };
                return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/x-gfm"), gfmWikiOverlay);
            });

            var editor = CodeMirror.fromTextArea(document.getElementById("gfm-code"), {
                mode: 'gfm-wiki',
                lineNumbers: false,
                viewportMargin: Infinity,
                theme: "default",
                lineWrapping: true,
                // Set Tab to false to focus next input
                // And let Shift-Enter submit the form.
                extraKeys: { "Tab": false }
            });
        }

        // Create TOC
        assembleTOC();

        /*
        * TAB NAVIGATION
        */

        // Enable tab navigation
        if($('#tabs').length) {
            $('#tabs').tabs();
        }

        var taskButtonsWindowOffset = -1;
        // Hook onto Scroll events
        $(document).scroll(function() {
            // Fix Toolbox to top
            if(taskButtonsWindowOffset == -1) {
                // 40 is the height of the navbar
                taskButtonsWindowOffset = $('.task-buttons').offset().top - $('.task-buttons').outerHeight() + 40;
                console.log(taskButtonsWindowOffset);
            }
            if($(this).scrollTop() > taskButtonsWindowOffset) {
                $('.task-buttons').addClass("fixed");
            }
            else {
                $('.task-buttons').removeClass("fixed");
            }

            // Show/hide button
            if($(this).scrollTop() > 300)
            {
                $('#scroll-button').show();
            }
            else {
                $('#scroll-button').hide();
            }
        });

        // Event handler for the scroll button
        $('#scroll-button').click(function() {
            // Scroll back to top
            $('html, body').animate({scrollTop:0}, 'slow');
        });

        // Enable on-enter searching
        $('#search-input').keypress(function(e) {
            if(e.which == 13) { // Classic: Return keycode!
                document.location = "{{ url('/') }}/search/" + encodeURIComponent($(this).val());
            }
        });

        /* MEDIA LIBRARY */
        $('#tester').click(function() {

            var library = new MediaLibrary(zettlrURL.mediaLibraryGetter, zettlrURL.mediaLibrarySetter);

            library.show();

            // Don't forget to watch for media on the server
            library.getMedia();
        });

        // Hide/show menu bar when on show pages (indicated by a class zen-view on the container)
        $('.zen-view').click(function() {
            if($(this).hasClass('zen-view-enabled')) {
                $("nav.nav").show(400);
                $('footer').show(400);
                $(this).removeClass('zen-view-enabled');
            }
            else {
                $("nav.nav").hide(400);
                $('footer').hide(400);
                $(this).addClass('zen-view-enabled');
            }
        });
    });
    </script>
</head>
<body>
    <nav class="nav">
        <ul>
            <li><a href="{{ url('/') }}">{{ trans('ui.frontend.mainpage') }}</a></li>
            <li><a href="{{ url('/trash') }}">{{ trans('ui.frontend.trash') }}</a></li>
            <li><a href="{{ url('/index') }}">{{ trans('ui.frontend.index') }}</a></li>
            <li><a href="{{ url('/create') }}">{{ trans('ui.frontend.create') }}</a></li>
            <li><a href="{{ url('/admin') }}">{{ trans('ui.frontend.backend') }}</a></li>
            <li class="nav-search"><input type="text" role="search" class="nav-search" id="search-input" placeholder="{{ trans('ui.frontend.search') }}" /></li>
        </ul>
    </nav>

    <div class="container">
        @yield('content')
    </div>


    <footer>
        <span class="float-left">@yield('footer-content')</span>
        <!--<a id="tester">Test modal</a>-->

        <a class="float-right brand" target="_blank" href="http://www.zettlr.com">ZettlrWiki | &copy; 2016 <strong>&zeta;</strong> <small>Zettlr</small>.</a>
    </footer>
    <!-- Scroll to top-message -->
    <a id="scroll-button" title="{{ trans('ui.frontend.top') }}"><span class="fa fa-arrow-up fa-lg"></span></a>
</body>
</html>
