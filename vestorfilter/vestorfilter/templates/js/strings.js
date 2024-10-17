jQuery(document).ready(function ($) {
    $(".datatable__subrow ul li").each(function() {
        var txt = $(this).text()
        var spacedText = txt.replace(/([A-Z])/g, ' $1').trim();
        $(this).text(spacedText)
    })
})