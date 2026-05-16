<?php

/**
 * Site-wide session and language cookie/session init.
 * Must be included before any output (HTML, whitespace, warnings) so
 * session_start() and optional ?lang= redirects work.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$available_languages = ['en' => 'English', 'fr' => 'Français'];

if (!isset($_SESSION['current_language'])) {
    $_SESSION['current_language'] = 'en';
}

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['current_language'] = $_GET['lang'];
    $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $url = strtok($requestUri, '?');
    if ($url === false || $url === '') {
        $url = '/';
    }
    header('Location: ' . $url);
    exit;
}
