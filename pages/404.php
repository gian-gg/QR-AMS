<?php

$error = $_SERVER["REDIRECT_STATUS"];

$error_title = '';
$error_message = '';

if ($error == 404) {
    $error_title = '404 Page Not Found';
    $error_message = 'The Document/File Requested was not found on this Server.';
}

?>

<!-- 404.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $error_title ?></title>
</head>

<body>
    <h1><?php $error_title ?></h1>
    <p><?php $error_message ?></p>
</body>

</html>