<?php
session_start();
include('db.php');

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    if (empty($login) || empty($password)) {
        $error = true;
        $error_message = 'Пожалуйста, заполните все поля';
    } else {
        $stmt = $con->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = true;
            $error_message = 'Неверный логин или пароль';
        } else {
            $user = $result->fetch_assoc();
            $password_valid = false;
            
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            } elseif ($password === $user['password']) {
                $password_valid = true;
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $con->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed, $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            if (!$password_valid) {
                $error = true;
                $error_message = 'Неверный логин или пароль';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_fullname'] = $user['fullname'];
                
                if ($user['login'] == 'Admin26') {
                    $_SESSION['admin'] = true;
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Конференции.РФ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --gray-dark: #343A40; --gray-light: #DEE2E6; --green: #28A745; --white: #FFFFFF; --gray-bg: #F8F9FA; }
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, var(--gray-bg) 0%, #eef2f5 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .login-card {
            background: var(--white);
            border-radius: 24px;
            padding: 48px 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-light);
        }
        .login-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .login-header img {
            height: 40px;
            width: auto;
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-dark);
        }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: var(--gray-dark); }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-light);
            border-radius: 12px;
            font-size: 16px;
            transition: 0.2s;
            font-family: inherit;
        }
        .form-group input:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(40,167,69,0.1); }
        .form-group input::placeholder { color: #adb5bd; font-size: 14px; }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--green);
            color: var(--white);
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 8px;
        }
        .btn-login:hover { background: #218838; }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
        }
        .register-link { text-align: center; margin-top: 24px; font-size: 14px; color: #6c757d; }
        .register-link a { color: var(--green); text-decoration: none; font-weight: 500; transition: 0.2s; }
        .register-link a:hover { text-decoration: none; }
        .back-link { text-align: center; margin-top: 16px; }
        .back-link a { color: #6c757d; text-decoration: none; font-size: 13px; }
        .back-link a:hover { color: var(--green); }
        
        @media (max-width: 550px) {
            .login-card { padding: 32px 24px; }
            .login-title { font-size: 22px; }
            .login-header img { height: 32px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="images/logo.png" alt="Логотип Конференции.РФ">
            <h1 class="login-title">Вход в личный кабинет</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" placeholder="Введите ваш логин" value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['login']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
            </div>
            <button type="submit" class="btn-login">Войти</button>
        </form>
        
        <div class="register-link">
            Еще не зарегистрированы? <a href="register.php">Регистрация</a>
        </div>
        <div class="back-link">
            <a href="index.php">Вернуться на главную</a>
        </div>
    </div>
</body>
</html>