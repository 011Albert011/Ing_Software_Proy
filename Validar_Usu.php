<?php 
session_start(); 
$link = mysqli_connect("sql210.infinityfree.com", "if0_41997562", "pG52HDE7T6H", "if0_41997562_sistemasii");


if (!$link) {
    die("Error de conexión: " . mysqli_connect_error());
}

$usu= $_REQUEST['Nombre']; 
$pas= $_REQUEST['Password'];

$result = mysqli_query($link, "SELECT Id_Usuario, Nombre, Correo, Password FROM usuario WHERE Nombre='$usu'");

if ($row = mysqli_fetch_array($result)) {
    if ($row["Password"] == $pas) {
        
        // guardamos las datos de sesion del usuario
        $_SESSION["k_username"]=$row['Nombre'];           
        $_SESSION["id_usuario"]=$row['Id_Usuario'];
        $_SESSION["usuario"]=$row['Nombre'];
        $_SESSION["correo"]=$row['Correo'];
        
        header("Location: IndexPrincipal.php");
        exit();
    } else {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
