<?php
$conn = mysqli_connect("localhost","root","","sistem_pju");

if(!$conn){
    die("Koneksi gagal : ".mysqli_connect_error());
}
?>