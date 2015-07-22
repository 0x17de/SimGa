loadCount = 1;
function setLoading(bLoading) {
    loadCount += bLoading ? 1 : -1;
    if (loadCount >= 1) {
        $('#loading').show();
    } else {
        $('#loading').hide();
    }
}

function showImage(path, image) {
    var imagepath = path + image.filename;

    // Loading notification
    var img = $('<img/>');
    setLoading(true);
    img.load(function() { setLoading(false); });
    img.attr('src', imagepath);

    $('#lightbox_image').css({
        'backgroundImage': 'url('+imagepath+')'
    });
    $('#lightbox').show();
}

function displayImages(data) {
    data.images.sort(function(a, b) {
        var aname = a.name || a.filename;
        var bname = b.name || b.filename;

        if (aname < bname) return -1;
        if (aname > bname) return 1;
        return 0;
    });

    $(data.images).each(function(i, image) {
        var preview = $('<div class="imagebox"/>');
        preview.css({
            backgroundImage:'url('+data.path+'/_thumbs/'+image.filename+')',
        });
        preview.click(function() { showImage(data.path, image); });
        $('#view').append(preview);
    });
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
    $('#lightbox').click(function() { $(this).hide(); });
})
