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
    <script src="jquery.min.js"></script>
    <script src="jquery-ui.min.js"></script>

    <!-- Include main stylesheet -->
    <link rel="stylesheet" href="app.min.css">

    <script>
    // BEGIN DOCUMENT READY HOOK
    $(document).ready(function()
    {

        var taskButtonsWindowOffset = -1;
        // Hook onto Scroll events
        $(document).scroll(function() {

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

        $('#toc-toggle').click(function() {
            if(!$(this).hasClass("open")) {
                $('.toc-parent').removeClass('back');
                $('#toc').addClass("open");
                $(this).addClass('open');
            }
            else {
                $('#toc').removeClass('open');
                $('#toc-toggle').removeClass("open");
                $('.toc-parent').addClass('back');
            }
        });

        // Show a tooltip with the full title on hover
        $('.toc-link').mouseover(function() {
            href = $(this).attr('href');
            ttipid = 'ttip-' + href.substring(0, href.indexOf('.html'));
            ttipcontent = $(this).attr('title');

            ttipelem = $('<div class="tooltip left" id="' + ttipid + '">' + ttipcontent + '</div>');

            // Remove 4px padding top, add 10px margin between toc and tooltip
            ttipelem.css('top', $(this).offset().top - 4);
            ttipelem.css('left', $(this).offset().left + $(this).outerWidth() + 10);
            $('body').append(ttipelem).fadeIn('fast');
        });

        $('.toc-link').mouseout(function() {
            href = $(this).attr('href');
            ttipid = 'ttip-' + href.substring(0, href.indexOf('.html'));

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
        <ul>
            <li><a id="toc-toggle" role="button" aria-label="Toggle Navigation" class="lines-button"><span class="lines"></span></a></li>
            <li><a href="Main_Page.html">{{ trans('ui.frontend.mainpage') }}</a></li>
        </ul>
    </nav>

    <div class="container">
        <article id="wikitext">
            <h1 class="clearfix page-title">
                <span data-name="page-title">{{ $page->title }}</span>
                <small>{{ (strlen($page->slug) > 60) ? substr($page->slug, 0, 60) . "&hellip;" : $page->slug }}</small>
            </h1>

            {!! $page->content !!}
        </article>
    </div>

    <footer>
        <span class="float-left">Generated with ZettlrWiki exporter.</span>

        <a class="float-right brand" target="_blank" href="http://www.zettlr.com">ZettlrWiki | &copy; 2016 <strong>&zeta;</strong> <small>Zettlr</small>.</a>
    </footer>
    <!-- Scroll to top-message -->
    <a id="scroll-button" title="{{ trans('ui.frontend.top') }}"><span class="fa fa-arrow-up fa-lg"></span></a>
    <!-- Table of Contents -->
    <div class="toc-parent">
        <div class="toc" id="toc">
            <ul>
                @if(count($toc) > 0)
                    @foreach($toc as $element)
                        <li class="class-h1">
                            <a href="{{ $element->slug }}.html" title="{{ $element->title }}" class="toc-link">
                                {{ $element->title }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
</body>
</html>
