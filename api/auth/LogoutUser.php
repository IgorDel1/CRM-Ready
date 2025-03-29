<?php

function logoutUser($redirect, $DB, $token = '') {
    unset($_SESSION['token']);

    if ($token) {
        $DB->query("
            UPDATE users SET token = NULL
            WHERE token = '$token'
        ")->fetchAll();
    }

    header("Location: $redirect");
}

?>