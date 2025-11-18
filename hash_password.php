<?php
$password_mentah = '123456789'; // GANTI dengan password yang Anda inginkan (misal: 'rahasia123')
$password_hash = password_hash($password_mentah, PASSWORD_DEFAULT);
echo $password_hash;
?>