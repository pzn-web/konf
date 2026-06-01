<?php
session_start();
if (!isset($_SESSION['user_id'])) die('Чтобы забронировать помещение для конференции, необходимо войти в аккаунт.');

$error = false;
$error_msg = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $room = $_POST['venue'];
    $payment = $_POST['payment'];
    $status = 'Новая';
    
    include('db.php');
    
    $user_id = (int)$_SESSION['user_id'];
    $room = $con->real_escape_string($room);
    $payment = $con->real_escape_string($payment);
    
    $query = $con->query("INSERT INTO request (date, room, payment, user_id, status) 
                          VALUES ('$date', '$room', '$payment', '$user_id', '$status')");
    
    if (!$query) {
        $error = true;
        $error_msg = 'Ошибка: ' . $con->error;
    } else {
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование помещения — Конференции.РФ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --gray-dark: #343A40; --gray-light: #DEE2E6; --green: #28A745; --white: #FFFFFF; --gray-bg: #F8F9FA; }
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, var(--gray-bg) 0%, #eef2f5 100%); min-height: 100vh; padding: 40px 20px; }
        
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-light);
        }
        
        .nav-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .nav-buttons a {
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            background: transparent;
            border: 1px solid var(--green);
            color: var(--green);
        }
        .nav-buttons a:hover {
            background: var(--green);
            color: var(--white);
        }
        
        .title-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .title-wrapper img {
            height: 36px;
            width: auto;
        }
        h1 {
            color: var(--gray-dark);
            font-size: 26px;
            font-weight: 700;
        }
        
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-dark);
            font-size: 14px;
        }
        form input, form select {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid var(--gray-light);
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Roboto', sans-serif;
            transition: all 0.2s;
            background: var(--white);
        }
        form select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23343A40' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 32px;
        }
        form input:focus, form select:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        form input:hover, form select:hover {
            border-color: var(--green);
        }
        
        .btn-submit {
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
        .btn-submit:hover {
            background: #218838;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 14px;
            border-left: 4px solid #dc3545;
        }
        
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
        
        .hint-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: -12px;
            margin-bottom: 16px;
            display: block;
        }
        
        @media (max-width: 600px) {
            .container { padding: 28px 20px; margin: 0 15px; }
            h1 { font-size: 20px; }
            .title-wrapper img { height: 28px; }
            form input, form select { padding: 10px 14px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-buttons">
            <a href="index.php">Главная</a>
            <a href="history.php">Мои бронирования</a>
        </div>
        
        <div class="title-wrapper">
            <img src="images/logo.png" alt="Логотип">
            <h1>Бронирование помещения</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                Ошибка при бронировании: <?php echo htmlspecialchars($error_msg); ?><br>
                <a href="javascript:history.back()">Попробовать снова</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="requestForm">
            <label for="venue">Тип помещения</label>
            <select id="venue" name="venue" required>
                <option value="Аудитория">Аудитория</option>
                <option value="Коворкинг">Коворкинг</option>
                <option value="Кинозал">Кинозал</option>
            </select>

            <label for="date">Дата и время проведения</label>
            <input id="date" type="datetime-local" name="date" required>
            <span class="hint-text">Выберите удобные дату и время для мероприятия</span>

            <label for="payment">Способ оплаты</label>
            <select id="payment" name="payment" required>
                <option value="Наличными">Наличными</option>
                <option value="Переводом на карту">Переводом на карту</option>
            </select>
            
            <button type="submit" class="btn-submit" id="submitBtn">Забронировать помещение</button>
        </form>
    </div>

    <?php if ($success): ?>
    <div class="toast-notification">Бронирование успешно создано</div>
    <script>
        setTimeout(function() {
            window.location.href = 'history.php';
        }, 2000);
    </script>
    <?php endif; ?>

    <script>
        const form = document.getElementById('requestForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                submitBtn.textContent = 'Отправка...';
                submitBtn.disabled = true;
            });
        }

        const dateInput = document.getElementById('date');
        if (dateInput) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            dateInput.min = minDateTime;
        }
    </script>
</body>
</html>