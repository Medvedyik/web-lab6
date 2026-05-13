<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // показать форму входа
} else {
    // проверить логин/пароль через таблицу users_lab5
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users_lab5 WHERE login = ?");
    $stmt->execute([$_POST['login']]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['pass'], $user['password_hash'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $_POST['login'];
        header('Location: index.php');
    } else {
        echo 'Неверный логин или пароль';
    }
}
