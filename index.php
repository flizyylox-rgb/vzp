ШПАРГАЛКА ДЛЯ ЭКЗАМЕНА 
====================================================

СТРУКТУРА ПАПОК И ФАЙЛОВ
========================

public1/
  database.sql
  index.php
  login.php
  logout.php

  class/
    ConnectDB.php

  function/
    function.php

  include/
    header.php
    footer.php

  register/
    index.php

  application/
    index.php

  profile/
    index.php

  review/
    index.php

  admin/
    index.php

  assets/
    style/
      style.css
    bootstrap/
      css/bootstrap.min.css
      js/bootstrap.bundle.min.js
    media/
      logo.png
      slide-1.jpg
      slide-2.jpg
      slide-3.jpg
      slide-4.webp
      course.jpg



1. Папка assets/bootstrap
   Ее просто скопируй в проект.
   В коде она подключается в двух местах:
   - include/header.php:
     <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
   - include/footer.php:
     <script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

2. Папка assets/media
   В нее просто положи картинки с нужными именами.
   Где они используются:
   - include/header.php:
     /assets/media/logo.png
   - index.php:
     /assets/media/slide-1.jpg
     /assets/media/slide-2.jpg
     /assets/media/slide-3.jpg
     /assets/media/slide-4.webp
   - application/index.php:
     /assets/media/course.jpg


ПОРЯДОК РАБОТЫ НА ЭКЗАМЕНЕ
==========================

1. Создай базу данных в phpMyAdmin с именем database.
2. Импортируй файл database.sql.
3. Создай структуру папок как выше.
4. Скопируй готовую папку assets/bootstrap.
5. Положи картинки в assets/media с такими же именами, как в структуре выше.
6. Добавь PHP-файлы.
7. Добавь style.css.
8. Проверь подключение к базе в class/ConnectDB.php.
9. Открой сайт в браузере.

====================================================


====================================================
ФАЙЛ: class/ConnectDB.php
Назначение: подключение к базе данных.
====================================================

<?php

class ConnectDB
{
    public static function connect()
    {
        static $db = null;

        // Подключаемся к MySQL один раз за загрузку страницы.
        // Если соединение уже есть, повторно его не создаем.
        if ($db === null) {
            // database - имя базы в phpMyAdmin.
            // Если на экзамене база называется иначе, поменяй четвертый параметр.
            $db = new mysqli('localhost', 'root', '', 'database');

            // Если база не отвечает или имя базы неверное, останавливаем страницу.
            if ($db->connect_errno) {
                die('Ошибка подключения к базе данных.');
            }

            // Кодировка нужна, чтобы русский текст сохранялся без ошибок.
            $db->set_charset('utf8mb4');
        }

        return $db;
    }
}


====================================================
ФАЙЛ: function/function.php
Назначение: общие функции проекта, которые используются на всех страницах.
====================================================

<?php
session_start();

require_once __DIR__ . '/../class/ConnectDB.php';

// Списки ниже используются в формах и при проверке данных.
// Курсы, которые можно выбрать при создании заявки.
$COURSES = array(
    'Основы алгоритмизации и программирования',
    'Основы веб-дизайна',
    'Основы проектирования баз данных'
);

// Способы оплаты из формы заявки.
$PAYMENTS = array(
    'Наличными',
    'Переводом по номеру телефона'
);

// Статусы заявки. Менять статус может только администратор.
$STATUSES = array(
    'Новая',
    'Идет обучение',
    'Обучение завершено'
);

// Короткий доступ к базе данных.
function db()
{
    return ConnectDB::connect();
}

// Готовим SQL-запрос и сразу показываем понятную ошибку, если запрос написан неверно.
function prepare_sql($sql)
{
    $stmt = db()->prepare($sql);

    if (!$stmt) {
        die('Ошибка SQL-запроса: ' . h(db()->error));
    }

    return $stmt;
}

// Выполняем простой SQL-запрос без параметров.
function query_sql($sql)
{
    $result = db()->query($sql);

    if (!$result) {
        die('Ошибка SQL-запроса: ' . h(db()->error));
    }

    return $result;
}

// Защита текста перед выводом на страницу.
// Эта функция помогает не сломать HTML и защищает от вывода опасных символов.
function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Переход на другую страницу.
function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

// Проверяем, что форма отправлена методом POST.
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Получаем поле из POST и сразу убираем лишние пробелы.
function post($name)
{
    return trim($_POST[$name] ?? '');
}

// Собираем ФИО в одну строку.
function full_name($user)
{
    return trim($user['surname'] . ' ' . $user['name'] . ' ' . $user['patronymic']);
}

// Берем пользователя из сессии.
function current_user()
{
    if (empty($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];
    $user['is_admin'] = $user['role'] === 'admin';
    return $user;
}

// Пускаем только обычного пользователя.
// Если не авторизован - отправляем на вход.
// Если админ - отправляем в админку.
function require_user()
{
    $user = current_user();

    if (!$user) {
        redirect('/login.php');
    }

    if ($user['is_admin']) {
        redirect('/admin/');
    }

    return $user;
}

// Пускаем только администратора.
function require_admin()
{
    $user = current_user();

    if (!$user || !$user['is_admin']) {
        redirect('/login.php');
    }

    return $user;
}

// Сохраняем в сессию только нужные данные пользователя.
function save_user_session($user)
{
    $_SESSION['user'] = array(
        'id' => (int)$user['id'],
        'login' => $user['login'],
        'surname' => $user['surname'],
        'name' => $user['name'],
        'patronymic' => $user['patronymic'],
        'role' => $user['role']
    );
}

// Красивый вывод ошибок или сообщений.
function alert_html($messages, $type)
{
    if (!$messages) {
        return '';
    }

    if (!is_array($messages)) {
        $messages = array($messages);
    }

    return '<div class="alert alert-' . h($type) . '">' . implode('<br>', array_map('h', $messages)) . '</div>';
}

// Помощник для выпадающих списков: добавляет selected к выбранному варианту.
function selected($current, $value)
{
    return $current === $value ? 'selected' : '';
}

// Помощник для radio-кнопок: добавляет checked к выбранному варианту.
function checked($current, $value)
{
    return $current === $value ? 'checked' : '';
}

// Цвет статуса заявки.
function status_class($status)
{
    if ($status === 'Новая') {
        return 'text-bg-primary';
    }

    if ($status === 'Идет обучение') {
        return 'text-bg-warning';
    }

    return 'text-bg-success';
}

// Проверяем, завершено ли обучение по заявке.
function is_finished($status)
{
    return $status === 'Обучение завершено';
}

// Дату из базы показываем пользователю в привычном виде: день.месяц.год.
function format_date($date)
{
    return date('d.m.Y', strtotime($date));
}

// Проверяем дату и возвращаем ее в формате для SQL.
function date_to_sql($value)
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($value), $m)) {
        return null;
    }

    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $value : null;
}

// Подключаем верхнюю часть сайта.
function render_header($title, $active = '')
{
    $GLOBALS['pageTitle'] = $title;
    $GLOBALS['activePage'] = $active;
    include __DIR__ . '/../include/header.php';
}

// Подключаем нижнюю часть сайта.
function render_footer()
{
    include __DIR__ . '/../include/footer.php';
}


====================================================
ФАЙЛ: include/header.php
Назначение: общая шапка сайта и меню.
====================================================

<?php
$user = current_user();
$title = $GLOBALS['pageTitle'] ?? 'Корочки.есть';
$active = $GLOBALS['activePage'] ?? '';

// Маленькая функция только для меню: подсвечивает текущую страницу.
// Например, на странице входа пункт "Вход" получит класс active.
function menu_active($page, $active)
{
    return $page === $active ? ' active' : '';
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h($title); ?> - Корочки.есть</title>
  <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/style/style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container-fluid px-4">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="/">
        <img src="/assets/media/logo.png" alt="">
        Корочки.есть
      </a>

      <!-- Кнопка появляется на маленьких экранах и раскрывает меню. -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Меню">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <!-- Меню для обычного авторизованного пользователя. -->
          <?php if ($user && !$user['is_admin']) { ?>
            <li class="nav-item"><a class="nav-link<?php echo menu_active('profile', $active); ?>" href="/profile/">Мои заявки</a></li>
            <li class="nav-item"><a class="nav-link<?php echo menu_active('application', $active); ?>" href="/application/">Новая заявка</a></li>
          <?php } ?>

          <!-- Меню для администратора. -->
          <?php if ($user && $user['is_admin']) { ?>
            <li class="nav-item"><a class="nav-link<?php echo menu_active('admin', $active); ?>" href="/admin/">Админ-панель</a></li>
          <?php } ?>

          <!-- Если пользователь не вошел, показываем вход и регистрацию. -->
          <?php if (!$user) { ?>
            <li class="nav-item"><a class="nav-link<?php echo menu_active('login', $active); ?>" href="/login.php">Вход</a></li>
            <li class="nav-item"><a class="nav-link<?php echo menu_active('register', $active); ?>" href="/register/">Регистрация</a></li>
          <?php } else { ?>
            <li class="nav-item"><a class="btn btn-outline-secondary btn-sm mt-1 ms-lg-2" href="/logout.php">Выйти</a></li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </nav>


====================================================
ФАЙЛ: include/footer.php
Назначение: общий подвал сайта и подключение Bootstrap JavaScript.
====================================================

  <!-- Общий футер подключается на всех страницах через render_footer(). -->
  <footer class="site-footer p-3 bg-dark text-light">
    <div class="container-fluid px-4">
      <p class="mb-1">Корочки.есть? Корочек нет!</p>
      <p class="mb-0">Есть демонстрационный экзамен. Готовимся...</p>
    </div>
  </footer>
  <script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>


====================================================
ФАЙЛ: index.php
Назначение: главная страница сайта.
====================================================

<?php
require __DIR__ . '/function/function.php';

// Данные для слайдера вынесены в массив.
// Так легче добавить новую картинку: достаточно добавить одну строку в массив.
$slides = array(
    array('file' => 'slide-1.jpg', 'alt' => 'Учебный кабинет'),
    array('file' => 'slide-2.jpg', 'alt' => 'Практический кабинет'),
    array('file' => 'slide-3.jpg', 'alt' => 'Лекционная аудитория'),
    array('file' => 'slide-4.webp', 'alt' => 'Компьютерный класс')
);

$cards = array(
    array('title' => 'Алгоритмизация', 'text' => 'Базовые алгоритмы, логика программ и практические задания.'),
    array('title' => 'Веб-дизайн', 'text' => 'Верстка страниц, оформление интерфейсов и адаптивность.'),
    array('title' => 'Базы данных', 'text' => 'Проектирование таблиц, связи и работа с данными.')
);

render_header('Главная', 'home');
?>
  <main class="page-wrap">
    <div class="container">
      <section class="hero p-3 p-md-4 mb-4">
        <div class="row align-items-center g-4">
          <div class="col-lg-5">
            <h1 class="hero-title mb-3">Запись на курсы дополнительного образования</h1>
            <p class="lead mb-4">Портал помогает выбрать программу, отправить заявку и отслеживать ее статус.</p>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-primary" href="/application/">Оставить заявку</a>
              <a class="btn btn-outline-primary" href="/register/">Регистрация</a>
            </div>
          </div>

          <div class="col-lg-7">
            <div id="mainSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
              <div class="carousel-inner rounded">
                <!-- Цикл выводит все картинки слайдера. -->
                <?php foreach ($slides as $i => $slide) { ?>
                  <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <img class="carousel-img" src="/assets/media/<?php echo h($slide['file']); ?>" alt="<?php echo h($slide['alt']); ?>">
                  </div>
                <?php } ?>
              </div>

              <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev" aria-label="Назад">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next" aria-label="Вперед">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <section class="row g-3">
        <!-- Цикл выводит три карточки курсов. -->
        <?php foreach ($cards as $card) { ?>
          <div class="col-md-4">
            <div class="section-card h-100 p-3">
              <h2 class="h5"><?php echo h($card['title']); ?></h2>
              <p class="mb-0"><?php echo h($card['text']); ?></p>
            </div>
          </div>
        <?php } ?>
      </section>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: register/index.php
Назначение: регистрация нового пользователя.
====================================================

<?php
require __DIR__ . '/../function/function.php';

$errors = array();
$values = array(
    'login' => '',
    'surname' => '',
    'name' => '',
    'patronymic' => '',
    'phone' => '',
    'email' => ''
);

if (is_post()) {
    // Получаем значения формы. Поля названы так же, как name у input.
    foreach ($values as $field => $value) {
        $values[$field] = post($field);
    }
    $password = $_POST['password'] ?? '';

    // Проверяем логин: только латинские буквы и цифры, минимум 5 символов.
    if (!preg_match('/^[A-Za-z0-9]{5,}$/', $values['login'])) {
        $errors[] = 'Логин: латиница и цифры, минимум 5 символов.';
    }

    // Пароль в учебном проекте проверяем только по длине.
    if (strlen($password) < 8) {
        $errors[] = 'Пароль должен быть не короче 8 символов.';
    }

    // Фамилия, имя и отчество должны быть написаны кириллицей.
    foreach (array('surname' => 'Фамилия', 'name' => 'Имя', 'patronymic' => 'Отчество') as $field => $label) {
        if (!preg_match('/^[А-Яа-яЁё\s-]{2,}$/u', $values[$field])) {
            $errors[] = $label . ' заполняется кириллицей.';
        }
    }

    // Телефон принимаем в одном формате, чтобы в базе данные были одинаковыми.
    if (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $values['phone'])) {
        $errors[] = 'Телефон укажите в формате 8(999)999-99-99.';
    }

    // FILTER_VALIDATE_EMAIL проверяет, похожа ли строка на email.
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректную электронную почту.';
    }

    // Не разрешаем регистрацию с уже занятым логином.
    $stmt = prepare_sql('SELECT id FROM users WHERE login = ? LIMIT 1');
    $stmt->bind_param('s', $values['login']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $errors[] = 'Такой логин уже занят.';
    }

    if (!$errors) {
        // Создаем нового пользователя.
        $role = 'user';
        $stmt = prepare_sql('INSERT INTO users (login, password, surname, name, patronymic, phone, email, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssssss', $values['login'], $password, $values['surname'], $values['name'], $values['patronymic'], $values['phone'], $values['email'], $role);
        $stmt->execute();

        // После регистрации сразу авторизуем пользователя.
        $values['id'] = db()->insert_id;
        $values['role'] = $role;
        save_user_session($values);
        redirect('/profile/');
    }
}

render_header('Регистрация', 'register');
?>
  <main class="page-wrap">
    <div class="container">
      <form class="form-box" method="post">
        <h1 class="h3 mb-3">Регистрация</h1>
        <?php echo alert_html($errors, 'danger'); ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="login">Логин</label>
            <input class="form-control" id="login" name="login" value="<?php echo h($values['login']); ?>" required>
            <div class="small-note mt-1">Латиница и цифры, от 5 символов.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="password">Пароль</label>
            <input class="form-control" id="password" name="password" type="password" required>
            <div class="small-note mt-1">Минимум 8 символов.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="surname">Фамилия</label>
            <input class="form-control" id="surname" name="surname" value="<?php echo h($values['surname']); ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="name">Имя</label>
            <input class="form-control" id="name" name="name" value="<?php echo h($values['name']); ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="patronymic">Отчество</label>
            <input class="form-control" id="patronymic" name="patronymic" value="<?php echo h($values['patronymic']); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="phone">Телефон</label>
            <input class="form-control" id="phone" name="phone" placeholder="8(999)999-99-99" value="<?php echo h($values['phone']); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="email">Электронная почта</label>
            <input class="form-control" id="email" name="email" type="email" value="<?php echo h($values['email']); ?>" required>
          </div>
        </div>

        <button class="btn btn-primary w-100 mt-4" type="submit">Зарегистрироваться</button>
        <div class="mt-3 text-center">
          <a href="/login.php">Уже зарегистрированы? Войти</a>
        </div>
      </form>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: login.php
Назначение: вход пользователя или администратора.
====================================================

<?php
require __DIR__ . '/function/function.php';

$errors = array();

if (is_post()) {
    // Получаем логин и пароль из формы входа.
    $login = post('login');
    $password = $_POST['password'] ?? '';

    // Ищем пользователя по логину.
    $stmt = prepare_sql('SELECT * FROM users WHERE login = ? LIMIT 1');
    $stmt->bind_param('s', $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Для учебной базы пароль сравнивается как обычный текст.
    // В настоящем проекте здесь нужен password_verify().
    if (!$user || $password !== $user['password']) {
        $errors[] = 'Неверный логин или пароль.';
    } else {
        // Если данные верные, запоминаем пользователя в сессии.
        save_user_session($user);
        redirect($user['role'] === 'admin' ? '/admin/' : '/profile/');
    }
}

render_header('Вход', 'login');
?>
  <main class="page-wrap">
    <div class="container">
      <form class="form-box" method="post">
        <h1 class="h3 mb-3">Вход</h1>
        <?php echo alert_html($errors, 'danger'); ?>

        <div class="mb-3">
          <label class="form-label" for="login">Логин</label>
          <input class="form-control" id="login" name="login" value="<?php echo h($_POST['login'] ?? ''); ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Пароль</label>
          <input class="form-control" id="password" name="password" type="password" required>
        </div>

        <button class="btn btn-primary w-100" type="submit">Войти</button>
        <div class="mt-3 text-center">
          <a href="/register/">Еще не зарегистрированы? Регистрация</a>
        </div>
      </form>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: logout.php
Назначение: выход из аккаунта.
====================================================

<?php
require __DIR__ . '/function/function.php';

// Удаляем авторизацию и возвращаем пользователя на страницу входа.
// После session_destroy() сайт больше не считает пользователя вошедшим.
session_destroy();
redirect('/login.php');


====================================================
ФАЙЛ: application/index.php
Назначение: создание новой заявки на курс.
====================================================

<?php
require __DIR__ . '/../function/function.php';

$user = require_user();
$errors = array();
$values = array('course' => '', 'start_date' => '', 'payment' => '');

if (is_post()) {
    // Забираем выбранные значения из формы заявки.
    $values['course'] = post('course');
    $values['start_date'] = post('start_date');
    $values['payment'] = post('payment');
    $startDate = date_to_sql($values['start_date']);

    // Проверяем курс по готовому списку, чтобы нельзя было отправить чужое значение.
    if (!in_array($values['course'], $COURSES, true)) {
        $errors[] = 'Выберите курс из списка.';
    }

    // Дата должна существовать и не быть раньше сегодняшнего дня.
    if (!$startDate || $startDate < date('Y-m-d')) {
        $errors[] = 'Выберите сегодняшнюю или будущую дату начала.';
    }

    // Способ оплаты тоже должен быть только из разрешенного списка.
    if (!in_array($values['payment'], $PAYMENTS, true)) {
        $errors[] = 'Выберите способ оплаты.';
    }

    if (!$errors) {
        // Новая заявка всегда получает начальный статус.
        $status = 'Новая';
        $stmt = prepare_sql('INSERT INTO application (user_id, course, start_date, payment, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $user['id'], $values['course'], $startDate, $values['payment'], $status);
        $stmt->execute();
        redirect('/profile/');
    }
}

render_header('Новая заявка', 'application');
?>
  <main class="page-wrap">
    <div class="container">
      <div class="row g-4 align-items-start">
        <div class="col-lg-5">
          <div class="section-card p-3">
            <img class="course-img mb-3" src="/assets/media/course.jpg" alt="Обучение">
            <h1 class="h3">Формирование заявки</h1>
            <p class="mb-1">Заявитель: <strong><?php echo h(full_name($user)); ?></strong></p>
            <p class="small-note mb-0">После отправки заявка появится со статусом «Новая».</p>
          </div>
        </div>

        <div class="col-lg-7">
          <form class="form-box" method="post">
            <?php echo alert_html($errors, 'danger'); ?>

            <div class="mb-3">
              <label class="form-label" for="course">Курс</label>
              <select class="form-select" id="course" name="course" required>
                <option value="">Выберите курс</option>
                <?php foreach ($COURSES as $course) { ?>
                  <option <?php echo selected($values['course'], $course); ?>><?php echo h($course); ?></option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="start_date">Желаемая дата начала</label>
              <input class="form-control" id="start_date" name="start_date" type="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo h($values['start_date']); ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Способ оплаты</label>
              <?php foreach ($PAYMENTS as $i => $payment) { ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="payment" id="payment<?php echo $i; ?>" value="<?php echo h($payment); ?>" <?php echo checked($values['payment'], $payment); ?> required>
                  <label class="form-check-label" for="payment<?php echo $i; ?>"><?php echo h($payment); ?></label>
                </div>
              <?php } ?>
            </div>

            <button class="btn btn-primary w-100" type="submit">Отправить</button>
          </form>
        </div>
      </div>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: profile/index.php
Назначение: личный кабинет пользователя со списком его заявок.
====================================================

<?php
require __DIR__ . '/../function/function.php';

$user = require_user();
$message = isset($_GET['review']) ? 'Отзыв сохранен.' : '';

// Получаем заявки только вошедшего пользователя.
$stmt = prepare_sql('SELECT * FROM application WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$apps = $stmt->get_result();

render_header('Мои заявки', 'profile');
?>
  <main class="page-wrap">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h1 class="h3 mb-1">Мои заявки</h1>
          <div class="small-note"><?php echo h(full_name($user)); ?></div>
        </div>
        <a class="btn btn-primary" href="/application/">Новая заявка</a>
      </div>

      <?php echo alert_html($message, 'success'); ?>

      <div class="section-card p-2 p-md-3">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Курс</th>
                <th>Дата</th>
                <th>Оплата</th>
                <th>Статус</th>
                <th>Отзыв</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($apps->num_rows === 0) { ?>
                <tr><td colspan="6" class="text-center text-muted py-4">У вас пока нет заявок.</td></tr>
              <?php } ?>

              <?php while ($app = $apps->fetch_assoc()) { ?>
                <tr>
                  <td><?php echo h($app['course']); ?></td>
                  <td><?php echo h(format_date($app['start_date'])); ?></td>
                  <td><?php echo h($app['payment']); ?></td>
                  <td><span class="badge status-badge <?php echo h(status_class($app['status'])); ?>"><?php echo h($app['status']); ?></span></td>
                  <td class="review-cell">
                    <?php if ($app['review']) { ?>
                      <span class="review-text"><?php echo h($app['review']); ?></span>
                    <?php } else { ?>
                      <span class="text-muted">Нет отзыва</span>
                    <?php } ?>
                  </td>
                  <td>
                    <?php if (is_finished($app['status'])) { ?>
                      <a class="btn btn-sm btn-outline-primary" href="/review/?id=<?php echo (int)$app['id']; ?>">
                        <?php echo $app['review'] ? 'Изменить отзыв' : 'Оставить отзыв'; ?>
                      </a>
                    <?php } else { ?>
                      <span class="text-muted">После завершения</span>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: review/index.php
Назначение: добавление или изменение отзыва по завершенной заявке.
====================================================

<?php
require __DIR__ . '/../function/function.php';

$user = require_user();
$id = (int)($_GET['id'] ?? post('id'));
$errors = array();

// Пользователь может открыть только свою заявку.
$stmt = prepare_sql('SELECT * FROM application WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();

if (!$application) {
    redirect('/profile/');
}

// Отзыв разрешен только после завершения обучения.
if (!is_finished($application['status'])) {
    $errors[] = 'Отзыв можно оставить только после завершения обучения.';
}

$review = $application['review'];

if (is_post() && !$errors) {
    // Получаем текст отзыва из textarea.
    $review = post('review');

    if (!$review) {
        $errors[] = 'Введите текст отзыва.';
    } else {
        // Сохраняем новый текст отзыва.
        $stmt = prepare_sql('UPDATE application SET review = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('sii', $review, $id, $user['id']);
        $stmt->execute();
        redirect('/profile/?review=1');
    }
}

render_header('Отзыв', 'profile');
?>
  <main class="page-wrap">
    <div class="container">
      <form class="form-box" method="post">
        <h1 class="h3 mb-3">Отзыв о курсе</h1>
        <?php echo alert_html($errors, 'danger'); ?>

        <input type="hidden" name="id" value="<?php echo (int)$application['id']; ?>">

        <div class="mb-3">
          <label class="form-label">Курс</label>
          <input class="form-control" value="<?php echo h($application['course']); ?>" disabled>
        </div>

        <div class="mb-3">
          <label class="form-label" for="review">Текст отзыва</label>
          <textarea class="form-control" id="review" name="review" rows="5" required><?php echo h($review); ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit" <?php echo $errors ? 'disabled' : ''; ?>>Сохранить</button>
          <a class="btn btn-outline-secondary" href="/profile/">Назад</a>
        </div>
      </form>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: admin/index.php
Назначение: админ-панель для просмотра всех заявок и изменения статусов.
====================================================

<?php
require __DIR__ . '/../function/function.php';

require_admin();
$message = '';

if (is_post()) {
    // Из формы приходит номер заявки и выбранный администратором статус.
    $id = (int)post('application_id');
    $status = post('status');

    // Администратор может выбрать только статус из списка.
    if (in_array($status, $STATUSES, true)) {
        $stmt = prepare_sql('UPDATE application SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $message = 'Статус заявки обновлен.';
    }
}

// Для администратора показываем все заявки вместе с ФИО пользователей.
$apps = query_sql('SELECT a.*, u.login, u.surname, u.name, u.patronymic
    FROM application a
    INNER JOIN users u ON u.id = a.user_id
    ORDER BY a.id DESC');

render_header('Админ-панель', 'admin');
?>
  <main class="page-wrap">
    <div class="container">
      <h1 class="h3 mb-3">Заявки пользователей</h1>

      <?php echo alert_html($message, 'success'); ?>

      <div class="section-card p-2 p-md-3">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Пользователь</th>
                <th>Курс</th>
                <th>Дата</th>
                <th>Оплата</th>
                <th>Статус</th>
                <th>Отзыв</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($apps->num_rows === 0) { ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Заявок нет.</td></tr>
              <?php } ?>

              <?php while ($app = $apps->fetch_assoc()) { ?>
                <tr>
                  <td>
                    <?php echo h(full_name($app)); ?><br>
                    <span class="small-note"><?php echo h($app['login']); ?></span>
                  </td>
                  <td><?php echo h($app['course']); ?></td>
                  <td><?php echo h(format_date($app['start_date'])); ?></td>
                  <td><?php echo h($app['payment']); ?></td>
                  <td>
                    <form method="post">
                      <input type="hidden" name="application_id" value="<?php echo (int)$app['id']; ?>">
                      <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                        <?php foreach ($STATUSES as $status) { ?>
                          <option <?php echo selected($app['status'], $status); ?>><?php echo h($status); ?></option>
                        <?php } ?>
                      </select>
                    </form>
                  </td>
                  <td class="review-cell">
                    <?php if ($app['review']) { ?>
                      <span class="review-text"><?php echo h($app['review']); ?></span>
                    <?php } else { ?>
                      <span class="text-muted">Нет</span>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
<?php render_footer(); ?>


====================================================
ФАЙЛ: assets/style/style.css
Назначение: собственные стили сайта поверх Bootstrap.
====================================================

body {
  /* Делаем страницу высотой минимум с экран, чтобы футер был внизу. */
  min-height: 100vh;
  background: #f5f7fb;
  color: #1f2937;
  display: flex;
  flex-direction: column;
}

.navbar-brand img {
  /* Логотип в шапке. */
  width: 50px;
  height: 50px;
  object-fit: contain;
}

.page-wrap {
  /* Основной блок растягивается и отодвигает футер вниз. */
  flex: 1;
  padding: 32px 0 48px;
}

.site-footer {
  /* Футер остается внизу даже на коротких страницах. */
  margin-top: auto;
  min-height: 12vh;
}

.hero {
  /* Белый блок на главной странице. */
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.hero-title {
  font-size: 2.2rem;
  line-height: 1.15;
}

.carousel-img {
  /* Картинки слайдера обрезаются ровно по размеру блока. */
  width: 100%;
  height: 340px;
  object-fit: cover;
  background: #e5e7eb;
}

.section-card {
  /* Общий стиль карточек на сайте. */
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.form-box {
  /* Общий стиль всех форм: вход, регистрация, заявка, отзыв. */
  max-width: 720px;
  margin: 0 auto;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 28px;
}

.small-note {
  /* Маленький серый поясняющий текст. */
  color: #6b7280;
  font-size: 0.92rem;
}

.status-badge {
  /* Значки статусов делаем одинаковой ширины. */
  display: inline-flex;
  align-items: center;
  min-width: 150px;
  justify-content: center;
}

.table td,
.table th {
  vertical-align: middle;
}

.review-cell {
  /* Ограничиваем колонку отзыва, чтобы длинный текст не растягивал таблицу. */
  max-width: 360px;
}

.review-text {
  /* Переносим даже длинные слова без пробелов. */
  display: block;
  white-space: normal;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.course-img {
  /* Картинка на странице создания заявки. */
  max-height: 190px;
  width: 100%;
  object-fit: cover;
  border-radius: 8px;
}

.invalid-text {
  display: none;
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 4px;
}

.was-checked .invalid-text {
  display: block;
}

@media (max-width: 576px) {
  /* Настройки для телефона. */
  .page-wrap {
    padding: 18px 0 32px;
  }

  .hero-title {
    font-size: 1.65rem;
  }

  .carousel-img {
    height: 230px;
  }

  .form-box {
    padding: 18px;
  }

  .status-badge {
    min-width: auto;
  }
}


КРАТКО 
================================

1. session_start() запускает сессию, чтобы сайт запоминал вошедшего пользователя.
2. require подключает общий файл function.php.
3. mysqli подключается к MySQL.
4. prepare_sql(), bind_param() и execute() используются для безопасных SQL-запросов.
5. h() экранирует текст перед выводом на страницу.
6. require_user() защищает страницы обычного пользователя.
7. require_admin() защищает админ-панель.
8. role в таблице users определяет права: user или admin.
9. Таблица application связана с users через user_id.
10. Статус "Обучение завершено" нужен, чтобы пользователь мог оставить отзыв.
