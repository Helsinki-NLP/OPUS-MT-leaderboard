<?php
session_start();

foreach ($_SESSION['params'] as $key => $value){
    echo "$key => $value <br/>";
}

echo(sys_get_temp_dir());
echo '<pre>';
system("ls -alh ".sys_get_temp_dir());
echo '</pre>';

?>
