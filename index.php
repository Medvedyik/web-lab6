<?php
require_once 'config.php';
require_once 'functions.php';

session_start();
$isLoggedIn = false;
$userId = null;
if (isset($_COOKIE[session_name()])) {
    session_start();
    if (!empty($_SESSION['user_id'])) {
        $isLoggedIn = true;
        $userId = $_SESSION['user_id'];
    }
}

$errors = [];
$fieldValues = [];

// Обработка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fio = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $languages = $_POST['languages'] ?? [];
    $biography = trim($_POST['biography'] ?? '');
    $contract = isset($_POST['contract']) ? 1 : 0;

    $errors = validateFormData($fio, $phone, $email, $birth_date, $gender, $languages, $biography, $contract);

    // Сохранение cookies
    $oneYear = time() + 365*24*60*60;
    setcookie('value_fio', $fio, $oneYear, '/');
    setcookie('value_phone', $phone, $oneYear, '/');
    setcookie('value_email', $email, $oneYear, '/');
    setcookie('value_birth_date', $birth_date, $oneYear, '/');
    setcookie('value_gender', $gender, $oneYear, '/');
    setcookie('value_languages', json_encode($languages), $oneYear, '/');
    setcookie('value_biography', $biography, $oneYear, '/');
    setcookie('value_contract', $contract, $oneYear, '/');

    if (!empty($errors)) {
        foreach (array_keys($errors) as $field) setcookie("error_$field", '1', 0, '/');
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }

    try {
        if ($isLoggedIn) {
            updateApplication($userId, $fio, $phone, $email, $birth_date, $gender, $languages, $biography, $contract);
            setcookie('save', '1', time()+30, '/');
        } else {
            $creds = saveNewApplication($fio, $phone, $email, $birth_date, $gender, $languages, $biography, $contract);
            setcookie('save', '1', time()+30, '/');
            setcookie('generated_login', $creds['login'], time()+30, '/');
            setcookie('generated_pass', $creds['pass'], time()+30, '/');
        }
        // Удаляем error cookies
        $errorFields = ['fio','phone','email','birth_date','gender','languages','biography','contract'];
        foreach ($errorFields as $field) setcookie("error_$field", '', time()-3600, '/');
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    } catch (PDOException $e) {
        setcookie('error_db', '1', 0, '/');
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }
}

// GET: вывод формы
$messages = [];
$fieldErrors = [];

if (isset($_COOKIE['save'])) {
    $messages[] = 'Данные успешно сохранены!';
    setcookie('save', '', time()-3600, '/');
    if (isset($_COOKIE['generated_login']) && isset($_COOKIE['generated_pass'])) {
        $login = h($_COOKIE['generated_login']);
        $pass = h($_COOKIE['generated_pass']);
        $messages[] = "Вы можете войти для изменения данных с логином <strong>$login</strong> и паролем <strong>$pass</strong>.";
        setcookie('generated_login', '', time()-3600, '/');
        setcookie('generated_pass', '', time()-3600, '/');
    }
}

$errorFieldsList = ['fio','phone','email','birth_date','gender','languages','biography','contract'];
foreach ($errorFieldsList as $field) {
    if (isset($_COOKIE["error_$field"])) {
        $fieldErrors[$field] = true;
        setcookie("error_$field", '', time()-3600, '/');
    }
}
if (isset($_COOKIE['error_db'])) {
    $messages[] = 'Ошибка БД. Попробуйте позже.';
    setcookie('error_db', '', time()-3600, '/');
}

if ($isLoggedIn) {
    // Загружаем данные пользователя из БД
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT fio, phone, email, birth_date, gender, biography, contract_accepted FROM applications_lab5 WHERE user_id = ?");
    $stmt->execute([$userId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        $fieldValues = $data;
        $stmtLang = $pdo->prepare("SELECT pl.name FROM application_languages_lab5 al JOIN programming_languages_lab5 pl ON al.language_id = pl.id WHERE al.application_id = (SELECT id FROM applications_lab5 WHERE user_id = ?)");
        $stmtLang->execute([$userId]);
        $fieldValues['languages'] = $stmtLang->fetchAll(PDO::FETCH_COLUMN);
    }
    $messages[] = "Вы вошли как " . h($_SESSION['login']);
} else {
    $valueFields = ['fio','phone','email','birth_date','gender','biography','contract'];
    foreach ($valueFields as $f) $fieldValues[$f] = $_COOKIE["value_$f"] ?? '';
    $fieldValues['languages'] = isset($_COOKIE['value_languages']) ? json_decode($_COOKIE['value_languages'], true) : [];
    $fieldValues['contract'] = (int)($fieldValues['contract'] ?? 0);
}

include 'form_template.php';
?>
