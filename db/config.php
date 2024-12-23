<?php
// Verifica se as constantes já estão definidas, se não estiverem, define-as.
if (!defined('SERVIDOR')) {
    define('SERVIDOR', 'localhost'); // ou seu servidor de banco de dados
}

if (!defined('USUARIO')) {
    define('USUARIO', 'root'); // ou seu usuário de banco de dados
}

if (!defined('SENHA')) {
    define('SENHA', ''); // ou sua senha de banco de dados
}

if (!defined('BANCO')) {
    define('BANCO', 'treinamentos'); // ou seu banco de dados
}
?>