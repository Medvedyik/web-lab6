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
</form>
