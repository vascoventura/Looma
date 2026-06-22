<?php
header('Content-Type: text/plain; charset=utf-8');

$serverName = $_SERVER['SERVER_NAME'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
$serverPort = $_SERVER['SERVER_PORT'] ?? '';

echo "SERVER_NAME: {$serverName}\n";
echo "HTTP_HOST:   {$httpHost}\n";
echo "SERVER_ADDR: {$serverAddr}\n";
echo "SERVER_PORT: {$serverPort}\n";
?>
