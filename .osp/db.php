<?php
$con = mysqli_connect('MySQL-8.4', 'root', '', 'demoexam');
if(!$con) die('Ошибка подключения к базе данных: ' . mysqli_connect_error());
mysqli_set_charset($con, 'utf8mb4');
?>