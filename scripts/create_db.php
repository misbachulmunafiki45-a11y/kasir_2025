<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbName = 'kasir_2025';

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    fwrite(STDERR, "MySQL connect error: {$mysqli->connect_error}\n");
    exit(1);
}

$sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!$mysqli->query($sql)) {
    fwrite(STDERR, "MySQL query error: {$mysqli->error}\n");
    exit(1);
}

echo "Database '{$dbName}' created or already exists.\n";