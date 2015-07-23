loadCount = 1;
function setLoading(bLoading) {
    loadCount += bLoading ? 1 : -1;
    if (loadCount >= 1) {
        $('#loading').show();
    } else {
        $('#loading').hide();
    }
}

function setPath(path) {
    var location = $('#location');
    location.children().remove();

    if (path.length == 0)
        path = []
    else
        path = path.split('/');
    var fullPath = ["HOME"].concat(path);

    $(fullPath).each(function(i, p) {
        var item = $('<li/>');
        item.text(p);
        item.click(function() { loadImagesFromPath(fullPath.slice(1,i+1).join("/")); });
        location.append(item);
        if (i != path.length)
            location.append('<li>&gt;</li>');
    });
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

function updateView(data) {
    var view = $('#view');
    view.children().remove();

    // Display folders first
    data.folders.sort(function(a, b) {
        var aname = a.name || a.filename;
        var bname = b.name || b.filename;

        if (aname < bname) return -1;
        if (aname > bname) return 1;
        return 0;
    });
    $(data.folders).each(function(i, folder) {
        var preview = $('<div class="folderbox"/>');
        preview.css({
            backgroundImage:'url('+data.path+'/'+folder.name+'/_thumbs/'+folder.cover+')'
        });
        var title = $('<div class="foldertitle"/>');
        title.text("["+folder.name+"]");
        preview.append(title);

        preview.click(function() { loadImagesFromPath(data.path.substr('./images/'.length)+folder.name); });
        view.append(preview);
    });

    // Display images
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
            backgroundImage:'url('+data.path+'/_thumbs/'+image.filename+')'
        });
        preview.click(function() { showImage(data.path, image); });
        view.append(preview);
    });
}

function loadImagesFromPath(path) {
    setLoading(true);
    $.ajax("api/images/" + path, {
        complete: function() {
            setLoading(false);
        },
        success: function(data) {
            updateView(data);
            setPath(path);
        },
        dataType: 'json',
        error: function(xHr) {
            $('#oopsReason').text(xHr.responseText);
            $('#oops').show();
        }
    });
}

$(function() {
    loadImagesFromPath("");
    $('#lightbox').click(function() { $(this).hide(); });
    setLoading(false);
})
