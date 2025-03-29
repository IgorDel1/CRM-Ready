<?php

function getUserType ($DB) {
    $token = $_SESSION['token'];
    $userType = $DB->query("
        SELECT type FROM users WHERE token = '$token' 
        ")->fetchAll()[0]['type'];
    return $userType;
}

?>