<?php
session_start();

foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}

?>
