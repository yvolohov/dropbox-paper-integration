$(document).ready(function () {

    var template = /^https:\/\/paper\.dropbox\.com\/doc\/.{1,}-[A-Za-z0-9]{21,21}$/i;

    $('#download-button').click(function () {
        var href = $('#dropbox-paper-href').val();

        if (!template.test(href)) {
            return;
        }

        var parts = href.split('-');
        var id = parts[parts.length - 1];

        
    });

});
