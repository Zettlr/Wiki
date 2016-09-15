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

    <!-- Include our helper functions -->
    <script src="{{ url('/') }}/js/zettlr.helper.js"></script>

    <!-- Include main stylesheet -->
    <link rel="stylesheet" href="{{ url('/')}}/css/app.min.css">

    <script>
    /* Before anything let's create the globals we will need (mainly URLs) */
    var zettlrURL = {
        mediaLibraryGetter: "{{ url('/ajax/getMedia') }}",
        mediaLibrarySetter: "{{ url('/ajax/uploadMedia') }}",
        contentToolsGetter: "{{ url('/ajax/getPageContent') }}",
        contentToolsSetter: "{{ url('/edit/json') }}",
        csrfToken:          "{!! csrf_token() !!}"
    };

    // BEGIN DOCUMENT READY HOOK
    $(document).ready(function()
    {
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
        // Enable tab navigation
        if($('#tabs').length) {
            $('#tabs').tabs();
        }

        // Rebuild index
        $('#rebuildButton').click(function() {
            $(this).html("{{ trans('ui.index.rebuilding')}}").removeClass("warning").addClass("muted");
            // Initiate Rebuild process and display the status
            $.get("{{ url('/searchEngine/rebuild/full')}}", function() {})
            .success(function(data) {
                $('#rebuildButton').html("{{ trans('ui.index.rebuildsuccess') }}").removeClass("muted").addClass("success");
                // Now reload the page to display the new values.
                location.reload();
            })
            .fail(function(data) {
                $('#rebuildButton').html("{{ trans('ui.index.rebuildfailed') }}" . data[1]).removeClass("muted").addClass("error");
            });
        });

        // Tooltips
        // Show a tooltip with the full title on hover
        String.prototype.hashCode = function() {
            var hash = 0;
            if (this.length == 0) return hash;
            for (i = 0; i < this.length; i++) {
                char = this.charCodeAt(i);
                hash = ((hash<<5)-hash)+char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return hash;
        }

        String.prototype.breakString = function(charnumber) {
            tmpstring = "";
            for(i = 0; i < this.length; i++) {
                tmpstring += this.charAt(i);

                if((i % charnumber) == 0 && (i > 0)) {
                    // We need to break at a space so iterate until we find one
                    while((this.charAt(i) !== " ") && (i < this.length)) {
                        tmpstring += this.charAt(++i);
                    }
                    if(i < this.length) {
                        tmpstring += "<br>";
                    }
                }
            }
            return tmpstring;
        }

        $('[data-display="tooltip"]').mouseover(function() {
            ttipid = $(this).attr('data-content').hashCode();
            ttipcontent = $(this).attr('data-content');

            ttipcontent = ttipcontent.breakString(30);

            ttipelem = $('<div class="tooltip left" id="' + ttipid + '">' + ttipcontent + '</div>');

            // Remove 4px padding top, add 10px margin between toc and tooltip
            ttipelem.css('top', $(this).offset().top - 4);
            ttipelem.css('left', $(this).offset().left + $(this).outerWidth() + 10);
            $('body').append(ttipelem).fadeIn('fast');
        });

        $('[data-display="tooltip"]').mouseout(function() {
            ttipid = $(this).attr('data-content').hashCode();

            $('#' + ttipid).each(function() {
                $(this).fadeOut('fast', function() {
                    $(this).remove();
                });
            });
        });
    });
    </script>
</head>
<body>
    <nav class="nav">
        <!--<button type="button" id="toc-toggle" role="button" aria-label="Toggle Navigation" class="lines-button x"><span class="lines"></span></button>-->
        <ul>
            <li><a href="{{ url('/') }}" title="{{ trans('ui.backend.backtomain') }}">{{ env('APP_TITLE', 'Back to main page') }}</a></li>
            <li><a href="{{ url('/admin')}}">{{ trans('ui.backend.dashboard') }}</a></li>
            <li><a href="{{ url('/admin/settings') }}">{{ trans('ui.backend.settings') }}</a></li>
            <li><a href="{{ url('/admin/advancedSettings') }}">{{ trans('ui.settings.advanced') }}</a></li>
            <li><a href="{{ url('/admin/logs') }}">{{ trans('ui.backend.logs') }}</a></li>
            <li><a href="{{ url('/admin/token') }}">{{ trans('ui.backend.token') }}</a></li>
            @if(Auth::check())
                <li><a href="{{ url('/admin/account') }}">{{ trans('ui.backend.user.account') }}</a></li>
            @endif
            <li><a href="{{ url('/admin/updates') }}">{{ trans('ui.backend.updates.update') }}</a></li>
        </ul>
    </nav>

    <div class="container">
        @yield('content')
    </div>


    <footer>
        <span class="float-left">@yield('footer-content')</span>

        <a class="float-right brand" target="_blank" href="http://www.zettlr.com">ZettlrWiki | &copy; 2016 <strong>&zeta;</strong> <small>Zettlr</small>.</a>
    </footer>
</body>
</html>
