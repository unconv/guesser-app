<?php
$dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'];
$username = $config['db_username'];
$password = $config['db_password'];

try {
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
