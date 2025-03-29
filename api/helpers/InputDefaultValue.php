<?php

function InputDefaultValue($field, $defaultValue, $isSelect = false) {
    if (isset($_GET[$field])) {
        $input = !empty($_GET[$field]) ? $_GET[$field] : $defaultValue;
        
        if ($isSelect) {
            echo $input == $defaultValue ? 'selected' : '';
        } else {
            echo "value='$input'";
        }
    } else {
        echo '';
    }
}

?>