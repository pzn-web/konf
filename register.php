<?php
session_start();
include('db.php');

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {
        header('Location: admin.php');
    } else {
        header('Location: create.php');
    }
    exit;
}

$error = false;
$error_message = '';
$success = false;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $form_data = compact('login', 'fullname', 'phone', 'email');
    
    $errors = [];
    
    if (empty($login)) {
        $errors[] = 'Логин обязателен для заполнения';
    } elseif (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
        $errors[] = 'Логин должен содержать только латиницу и цифры, минимум 6 символов';
    }
    
    if (empty($password)) {
        $errors[] = 'Пароль обязателен для заполнения';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать минимум 8 символов';
    }
    
    if (empty($fullname)) {
        $errors[] = 'ФИО обязательно для заполнения';
    } elseif (strlen($fullname) < 5) {
        $errors[] = 'Введите полное ФИО';
    }
    
    if (empty($phone)) {
        $errors[] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors[] = 'Телефон должен быть в формате +7(XXX)XXX-XX-XX';
    }
    
    if (empty($email)) {
        $errors[] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email';
    }
    
    if (empty($errors)) {
        $stmt = $con->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = true;
            $error_message = 'Пользователь с таким логином уже существует';
            $stmt->close();
        } else {
            $stmt->close();
            
            $stmt2 = $con->prepare("SELECT id FROM users WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            if ($result2->num_rows > 0) {
                $error = true;
                $error_message = 'Пользователь с таким email уже существует';
                $stmt2->close();
            } else {
                $stmt2->close();
                
                $stmt3 = $con->prepare("INSERT INTO users (login, password, fullname, phone, email) VALUES (?, ?, ?, ?, ?)");
                $stmt3->bind_param("sssss", $login, $password, $fullname, $phone, $email);
                
                if ($stmt3->execute()) {
                    $success = true;
                } else {
                    $error = true;
                    $error_message = 'Ошибка при регистрации: ' . $con->error;
                }
                $stmt3->close();
            }
        }
    } else {
        $error = true;
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — Конференции.РФ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --gray-dark: #343A40; --gray-light: #DEE2E6; --green: #28A745; --white: #FFFFFF; --gray-bg: #F8F9FA; }
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, var(--gray-bg) 0%, #eef2f5 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .register-card {
            background: var(--white);
            border-radius: 24px;
            padding: 48px 40px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-light);
        }
        .register-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .register-header img {
            height: 40px;
            width: auto;
        }
        .register-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-dark);
        }
        .form-group { margin-bottom: 20px; }
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
        .form-group input:focus { 
            outline: none; 
            border-color: var(--green); 
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1); 
        }
        .form-group input::placeholder { color: #adb5bd; font-size: 14px; }
        
        .btn-register {
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
        .btn-register:hover { background: #218838; }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
        }
        
        /* Уведомление справа сверху */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #e3f5e8;
            color: #155724;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideInRight 0.3s ease-out, fadeOut 0.3s ease-out 1.7s forwards;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }
        
        .login-link { text-align: center; margin-top: 24px; font-size: 14px; color: #6c757d; }
        .login-link a { color: var(--green); text-decoration: none; font-weight: 500; }
        .login-link a:hover { text-decoration: none; }
        .back-link { text-align: center; margin-top: 16px; }
        .back-link a { color: #6c757d; text-decoration: none; font-size: 13px; }
        .back-link a:hover { color: var(--green); }
        
        @media (max-width: 550px) {
            .register-card { padding: 32px 24px; }
            .register-title { font-size: 24px; }
            .register-header img { height: 32px; }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <img src="images/logo.png" alt="Логотип Конференции.РФ">
            <h1 class="register-title">Регистрация</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="fullname">ФИО</label>
                <input type="text" id="fullname" name="fullname" placeholder="Введите ваше ФИО" value="<?= htmlspecialchars($form_data['fullname'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" placeholder="+7(XXX)XXX-XX-XX" maxlength="16" value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Введите вашу действующую почту" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="login">Логин</label>
				<input type="text" id="login" name="login" placeholder="Не менее 6 символов" value="<?= htmlspecialchars($form_data['login'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Не менее 8 символов" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Подтверждение пароля</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Введите пароль ещё раз" required>
            </div>
            
            <button type="submit" class="btn-register" id="submitBtn">Зарегистрироваться</button>
        </form>
        
        <div class="login-link">
            Уже зарегистрированы? <a href="login.php">Войти в личный кабинет</a>
        </div>
        <div class="back-link">
            <a href="index.php">Вернуться на главную</a>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="toast-notification">Регистрация успешна</div>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000);
    </script>
    <?php endif; ?>
    
    <script>
        const form = document.getElementById('registerForm');
        if (form) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            const phone = document.getElementById('phone');
            
            form.addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    showInlineError('Пароли не совпадают');
                    confirmPassword.style.borderColor = '#dc3545';
                    return false;
                }
                if (password.value.length < 8) {
                    e.preventDefault();
                    showInlineError('Пароль должен быть не менее 8 символов');
                    password.style.borderColor = '#dc3545';
                    return false;
                }
                const phonePattern = /^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/;
                if (!phonePattern.test(phone.value)) {
                    e.preventDefault();
                    showInlineError('Укажите телефон в формате +7(XXX)XXX-XX-XX');
                    phone.style.borderColor = '#dc3545';
                    return false;
                }
                const loginPattern = /^[a-zA-Z0-9]{6,}$/;
                const login = document.getElementById('login');
                if (!loginPattern.test(login.value)) {
                    e.preventDefault();
                    showInlineError('Логин: только латиница и цифры, минимум 6 символов');
                    login.style.borderColor = '#dc3545';
                    return false;
                }
                submitBtn.innerHTML = 'Обработка...';
                submitBtn.disabled = true;
            });
        }
        
        function showInlineError(message) {
            const existingError = document.querySelector('.error-message');
            if (existingError) existingError.remove();
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = message;
            const formHeader = document.querySelector('.register-header');
            formHeader.insertAdjacentElement('afterend', errorDiv);
            setTimeout(() => { errorDiv.style.opacity = '0'; setTimeout(() => errorDiv.remove(), 300); }, 3000);
        }
        
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() { this.style.borderColor = '#DEE2E6'; });
        });
        
        const phone = document.getElementById('phone');
        if (phone) {
            phone.addEventListener('input', function(e) {
                let value = this.value;
                if (value.length === 1 && value !== '+') this.value = '+' + value;
            });
        }
    </script>
</body>
</html>