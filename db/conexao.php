<?php
include("config.php");

$conexao = mysqli_connect(SERVIDOR, USUARIO, SENHA, BANCO) or die("Erro ao conectar ao banco: " . mysqli_connect_error());
?>