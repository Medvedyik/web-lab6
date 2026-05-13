<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход для редактирования</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Вход для редактирования</h2>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users_lab5 WHERE login = ?");
        $stmt->execute([$_POST['login']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($_POST['pass'], $user['password_hash'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $_POST['login'];
            header('Location: index.php');
            exit;
        } else {
            echo '<div class="error" style="margin-bottom: 15px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;">Неверный логин или пароль</div>';
        }
    }
    ?>
    <form method="post">
        <div class="field">
            <label>Логин</label>
            <input type="text" name="login" required>
        </div>
        <div class="field">
            <label>Пароль</label>
            <input type="password" name="pass" required>
        </div>
        <button type="submit">Войти</button>
    </form>
    <p><a href="index.php">На главную</a></p>
</div>
</body>
</html>
