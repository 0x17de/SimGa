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
        $path .= "/";
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
function getCoverForFolder($folder) {
    $dir = opendir($folder);
    while ($filename = readdir($dir)) {
        if (is_file($folder.'/'.$filename) && hasKnownImageExtension($filename))
            return $filename;
    }
    return null;
}

// === REST handler

function loadImageDetails($other) {
    // Only allow specific paths
    assertAllowedFilePath($other);
    $filepath = getBaseImagePath().$other;
    assertIsFile($filepath);
    if (!hasKnownImageExtension($filepath)) {
        http_response_code(500);
        die("Type of file '$other' not supported.");
    }

    $exif = exif_read_data($filepath);
    echo json_encode($exif);
}

function loadImages($other) {
    assertAllowedFilePath($other);
    $path = addTrailingSlash(getBaseImagePath().$other);
    assertIsDir($path);

    $result = array('path' => $path, 'images' => array(), 'folders' => array());

    // List all images in directory with selected properties
    $dir = opendir($path);
    while ($filename = readdir($dir)) {
        if (strlen($filename) == 0 || $filename[0] == "." || $filename[0] == "_") continue;

        $filepath = $path.$filename;
        if (is_file($filepath)) {
            if (!hasKnownImageExtension($filename))
                continue;
            $size = getimagesize($filepath);
            array_push($result['images'], array('filename' => $filename, 'width' => $size[0], 'height' => $size[1], 'name' => null));
        } elseif (is_dir($filepath)) {
            $coverFilename = getCoverForFolder($filepath);
            if ($coverFilename != null)
                array_push($result['folders'], array('name' => $filename, 'cover' => $coverFilename)); // @TODO: add first or configured image as folder image
        }
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