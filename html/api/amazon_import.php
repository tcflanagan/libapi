<?php

function importBook($isbn) {
    ini_set("display_errors", 1);
    ini_set("track_errors", 1);
    ini_set("html_errors", 1);
    error_reporting(E_ALL);
    header("Content-type: application/json");
    if (isset($_GET['format']) && $_GET['format']) {
        $fmt = " -f \"" . escapeshellarg($_GET['format']) . "\"";
    }
    else {
        $fmt = "";
    }
    $command = escapeshellcmd(("python3 -O scraper2.py " . escapeshellarg($isbn) . $fmt));
    $output = shell_exec($command);
    echo $output;
}