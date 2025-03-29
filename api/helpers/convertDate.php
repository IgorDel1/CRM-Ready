<?php
function convertDate($date) {
    $timestamp = strtotime($date);
    return date('d.m.Y H:i', $timestamp);
}
?> 