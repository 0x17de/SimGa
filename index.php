<?php


// === REST configuration

// Things that are handled
$restApi = array(
    'images' => array('GET' => 'loadImages')
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

// === REST handler

function loadImages($other) {
    // Only allow specific paths
    if (!preg_match('/^([a-zA-Z0-9_-][a-zA-Z0-9\/_-]*|)$/', $other)) {
        http_response_code(400);
        die("Forbidden path: $other");
    }

    // Add trailing slash
    $path = "./images/$other";
    if ($path[strlen($path)-1] !== "/")
        $path += "/";

    // Folder can be found?
    if (!is_dir($path)) {
        http_response_code(404);
        die("The selected folder was not found: $other");
    }

    $result = array('path' => $path, 'images' => array());

    // List all images in directory with selected properties
    $dir = opendir($path);
    while($filename = readdir($dir)) {
        $filepath = "$path$filename";
        if (!is_file($filepath))
            continue;
        if (!preg_match("/(jpg|png)$/i", $filename))
            continue;
        array_push($result['images'], array('filename' => $filename, 'EXIF' => null, 'name' => null));
    }

    // @TODO: put EXIF information into array

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
        die("Unknown api request");
    }
} else {
    // If no rest request, then just echo the template
    readfile("template.html");
}


?>