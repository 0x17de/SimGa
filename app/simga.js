loadCount = 1;
function setLoading(bLoading) {
    loadCount += bLoading ? 1 : -1;
    if (loadCount == 1) {
        $('#loading').show();
    } else {
        $('#loading').hide();
    }
}

function displayImages(data) {
    data.images.sort(function(a, b) {
        var aname = a.name || a.filename;
        var bname = b.name || b.filename;

        if (aname < bname) return -1;
        if (aname > bname) return 1;
        return 0;
    });

    for (var i = 0; i < data.images.length; ++i) {
        var image = data.images[i];
        var preview = $('<div id="image"/>');
        preview.text(image.filename || image.name);
        $('#view').append(preview);
    }
}

$(function() {
    $.ajax("api/images/", {
        complete: function() {
            setLoading(false);
        },
        success: function(data) {
            displayImages(data);
        },
        dataType: 'json',
        error: function(xHr) {
            $('#oopsReason').text(xHr.responseText);
            $('#oops').show();
        }
    });
})
