<?php
require_once 'config.php';
require_once 'functions.php';

session_start();
$isLoggedIn = false;
$userId = null;
if (isset($_COOKIE[session_name()])) {
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
	
	// Проверка CSRF-токена
	if (empty($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
		die('Ошибка CSRF-проверки');
	}

    $errors = validateFormData($fio, $phone, $email, $birth_date, $gender, $languages, $biography, $contract);

    // Сохранение cookies (для неавторизованных)
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

// GET – показ формы
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Анкета</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Форма для регистрации</h1>

    <?php if (!empty($messages)): ?>
        <div class="success">
            <?php foreach ($messages as $msg): ?>
                <?= $msg ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($isLoggedIn): ?>
        <div style="text-align: right; margin-bottom: 15px;"><a href="logout.php">Выйти</a></div>
    <?php else: ?>
        <div style="text-align: right; margin-bottom: 15px;"><a href="login.php">Вход для редактирования</a></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="field">
            <label for="fio">ФИО *</label>
            <input type="text" id="fio" name="fio"
                   class="<?= isset($fieldErrors['fio']) ? 'error' : '' ?>"
                   value="<?= h($fieldValues['fio'] ?? '') ?>" required>
            <?php if (isset($fieldErrors['fio'])): ?>
                <span class="error-msg">ФИО обязательно и должно содержать только буквы, пробелы, дефисы (до 150 символов).</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="phone">Телефон *</label>
            <input type="tel" id="phone" name="phone"
                   class="<?= isset($fieldErrors['phone']) ? 'error' : '' ?>"
                   value="<?= h($fieldValues['phone'] ?? '') ?>" required>
            <?php if (isset($fieldErrors['phone'])): ?>
                <span class="error-msg">Телефон обязателен, допустимы цифры, пробелы, +, -, скобки.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email"
                   class="<?= isset($fieldErrors['email']) ? 'error' : '' ?>"
                   value="<?= h($fieldValues['email'] ?? '') ?>" required>
            <?php if (isset($fieldErrors['email'])): ?>
                <span class="error-msg">Введите корректный e-mail.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="birth_date">Дата рождения *</label>
            <input type="date" id="birth_date" name="birth_date"
                   class="<?= isset($fieldErrors['birth_date']) ? 'error' : '' ?>"
                   value="<?= h($fieldValues['birth_date'] ?? '') ?>" required>
            <?php if (isset($fieldErrors['birth_date'])): ?>
                <span class="error-msg">Дата рождения обязательна и не может быть в будущем.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label>Пол *</label>
            <label><input type="radio" name="gender" value="male" <?= (($fieldValues['gender'] ?? '') === 'male') ? 'checked' : '' ?>> Мужской</label>
            <label><input type="radio" name="gender" value="female" <?= (($fieldValues['gender'] ?? '') === 'female') ? 'checked' : '' ?>> Женский</label>
            <?php if (isset($fieldErrors['gender'])): ?>
                <span class="error-msg">Выберите пол.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label>Любимые языки программирования *</label>
            <select name="languages[]" multiple size="6" class="<?= isset($fieldErrors['languages']) ? 'error' : '' ?>">
                <?php foreach ($allowedLanguages as $lang): ?>
                    <option value="<?= h($lang) ?>" <?= in_array($lang, $fieldValues['languages'] ?? []) ? 'selected' : '' ?>>
                        <?= h($lang) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($fieldErrors['languages'])): ?>
                <span class="error-msg">Выберите хотя бы один язык из списка.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="biography">Биография</label>
            <textarea id="biography" name="biography" rows="5"
                      class="<?= isset($fieldErrors['biography']) ? 'error' : '' ?>"><?= h($fieldValues['biography'] ?? '') ?></textarea>
            <?php if (isset($fieldErrors['biography'])): ?>
                <span class="error-msg">Биография не должна превышать 500 символов.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label><input type="checkbox" name="contract" value="1" <?= (($fieldValues['contract'] ?? 0) == 1) ? 'checked' : '' ?>> Я ознакомлен с контрактом *</label>
            <?php if (isset($fieldErrors['contract'])): ?>
                <span class="error-msg">Необходимо подтвердить ознакомление с контрактом.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <button type="submit">Сохранить</button>
        </div>
		<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    </form>
</div>
</body>
</html>
