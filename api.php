<?php


// === REST configuration

// Things that are handled
$restApi = array(
    'images' => array('GET' => 'loadImages'),
    'image_details' => array('GET' => 'loadImageDetails')
);
// Probe for handler
function handleRestApi($root, $other, $method) {
    global $restApi;
    foreach ($restApi as $restRequest => $methodHandler) {
        // Is requested api?
        if ($root == $restRequest) {
            // Requested method (GET/POST/PUT/DELETE) exists?
            if (!$methodHandler[$method])
                return false;
            call_user_func($methodHandler[$method], $other);
            return true;
        }
    }
    return false;
}

// === Utils

function getBaseImagePath() {
    return "./images/";
}
function addTrailingSlash($path) {
    // Add trailing slash
    if ($path[strlen($path)-1] !== "/")
        $path += "/";
    return $path;
}
function hasKnownImageExtension($filename) {
    return preg_match("/(jpg|png)$/i", $filename);
}
// == Assertions
function assertAllowedFilePath($name) {
    if (!preg_match('/^([a-zA-Z0-9_-]([a-zA-Z0-9\/_-]\.?)*|)$/', $name)) {
        http_response_code(400);
        die("Forbidden path: $name");
    }
}
function assertIsDir($path) {
    // Folder can be found?
    if (!is_dir($path)) {
        http_response_code(404);
        die("The selected folder was not found: $path");
    }
}
function assertIsFile($path) {
    // Folder can be found?
    if (!is_file($path)) {
        http_response_code(404);
        die("The selected file was not found: $path");
    }
}

// === REST handler

function loadImageDetails($other) {
    // Only allow specific paths
    assertAllowedFilePath($other);
    $filepath = getBaseImagePath().$other;
    assertIsFile($filepath);
    if (!hasKnownImageExtension($filepath)) {
        http_response_code(500);
        die("Type of file '$name' not supported.");
    }

    $exif = exif_read_data($filepath);
    echo json_encode($exif);
}

function loadImages($other) {
    assertAllowedFilePath($other);
    $path = addTrailingSlash(getBaseImagePath().$other);
    assertIsDir($path);

    $result = array('path' => $path, 'images' => array());

    // List all images in directory with selected properties
    $dir = opendir($path);
    while($filename = readdir($dir)) {
        $filepath = $path.$filename;
        if (!is_file($filepath))
            continue;
        if (!hasKnownImageExtension($filename))
            continue;
        $size = getimagesize($filepath);
        array_push($result['images'], array('filename' => $filename, 'width' => $size[0], 'height' => $size[1], 'name' => null));
    }

    echo json_encode($result);
}

// === Main method

if (isset($_GET['rest'])) {
    $rest = $_GET['rest']; // Rest calls are hidden via rewrite rules

    // find top level handler
    $rootEnd = strpos($rest, "/");
    if ($rootEnd === false) {
        http_response_code(400);
        die("Invalid api request");
    }

    // split handler from rest
    $root = substr($rest, 0, $rootEnd);
    $other = substr($rest, $rootEnd + 1);

    // handle request otherwise abort
    if (!handleRestApi($root, $other, 'GET')) {
        http_response_code(400);
        die("Unknown GET api request");
    }
} else {
    http_response_code(400);
    die("Unknown api request");
}


?>