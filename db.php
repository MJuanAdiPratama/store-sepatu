<?php
    $hostname ='localhost';
    $username = 'root';
    $password = '';
    $dbname = 'db_bukawarung';

    $conn = mysqli_connect($hostname, $username, $password, $dbname) or die ('Failed To Connect To Database')
?>