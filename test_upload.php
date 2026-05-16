<?php
header('Content-Type: application/json');

// Log the request method
file_put_contents(__DIR__ . '/post_test.log', 
    date('Y-m-d H:i:s') . ' - Method: ' . $_SERVER['REQUEST_METHOD'] . PHP_EOL, 
    FILE_APPEND
);

// Check if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_data = !empty($_POST) ? array_keys($_POST) : [];
    $files_data = !empty($_FILES) ? array_keys($_FILES) : [];
    
    echo json_encode([
        'status' => 'success',
        'message' => 'POST request received',
        'post_data' => $post_data,
        'files_data' => $files_data,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'none'
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Not a POST request',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
}
exit;