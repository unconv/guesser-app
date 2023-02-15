<?php
session_start();
$_SESSION['guess'] = "";

require_once( __DIR__ . "/vendor/autoload.php" );
?><!DOCTYPE html>
<html>
<head>
    <title>GuesserApp</title>
    <link rel="stylesheet" href="style.css" type="text/css" />
</head>
<body>
    <div id="wrapper">
        <h1>GuesserApp</h1>
        <div id="chat-messages"></div>
        <div id="buttons">
            <button id="start-over">Start over</button>
            <button id="yes">Yes</button>
            <button id="no">No</button>
            <button id="idk">I don't know</button>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
