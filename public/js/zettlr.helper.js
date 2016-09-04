/*
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
