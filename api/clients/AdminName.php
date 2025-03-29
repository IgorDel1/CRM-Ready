<?php 

function AdminName($token, $DB) {
    $admin = $DB->query("
        SELECT name, surname FROM users WHERE token='$token'
    ")->fetchAll()[0];
    $name = $admin['name'];
    $surname = $admin['surname'];

    return "$name $surname";
}

?>