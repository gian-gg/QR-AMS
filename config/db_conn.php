<?php

$host = "localhost";
$uName = "root";
$pass = "";
$db_name = "qramsDB";

try {
    $connect = new PDO(
        "mysql:host=$host;dbname=$db_name",
        $uName,
        $pass
    );
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed : " . $e->getMessage();
}
