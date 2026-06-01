<?php
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

if(!isset($_SESSION['user_id'])) die('Чтобы посмотреть историю бронирований, необходимо войти в аккаунт.');
include('db.php');

$review_success = false;

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review'])) {
    $review = $con->real_escape_string($_POST['review']);
    $user_id = (int)$_SESSION['user_id'];
    $request_id = (int)$_POST['request_id'];
    
    if($con->query("UPDATE request SET review='$review' WHERE id='$request_id' AND user_id='$user_id'")) {
        $review_success = true;
    }
}


$page = (int)($_GET['page'] ?? 1);
$limit = 9;
$offset = ($page - 1) * $limit;

$user_id = (int)$_SESSION['user_id'];
$count_result = $con->query("SELECT COUNT(*) as total FROM request WHERE user_id='$user_id'");
$total_count = $count_result->fetch_assoc()['total'];

$query = $con->query("SELECT * FROM request WHERE user_id='$user_id' ORDER BY date DESC LIMIT $limit OFFSET $offset");
if(!$query) die('Ошибка запроса: ' . $con->error); 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История бронирования — Конференции.РФ</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --gray-dark: #343A40; --gray-light: #DEE2E6; --green: #28A745; --white: #FFFFFF; --gray-bg: #F8F9FA; }
        body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg, var(--gray-bg) 0%, #eef2f5 100%); min-height: 100vh; display: flex; flex-direction: column; }
        
        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-light);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .logo img { height: 36px; width: auto; }
        .logo span { font-size: 18px; font-weight: 700; color: var(--gray-dark); }
        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-dark);
        }
        .nav-links { display: flex; gap: 10px; }
        .nav-links a {
            padding: 6px 16px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            background: transparent;
            border: 1px solid var(--green);
            color: var(--green);
        }
        .nav-links a:hover { background: var(--green); color: var(--white); }
        
        .main { flex: 1; padding: 30px 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #e3f5e8;
            color: #155724;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideInRight 0.3s ease-out, fadeOut 0.3s ease-out 1.7s forwards;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .request {
            border: 1px solid var(--green);
            padding: 20px;
            border-radius: 16px;
            background: var(--white);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .request h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-light);
            color: var(--gray-dark);
        }
        .request b { color: var(--gray-dark); font-weight: 600; }
        .request p { margin: 8px 0; font-size: 14px; }
        
        .review-form { margin-top: 16px; padding-top: 12px; }
        .review-form label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px; color: var(--gray-dark); }
        .review-form input[type="text"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-light);
            border-radius: 12px;
            font-size: 13px;
            font-family: 'Roboto', sans-serif;
            margin-bottom: 10px;
        }
        .review-form input[type="text"]:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        .review-form button {
            width: 100%;
            padding: 8px 16px;
            background: var(--green);
            color: var(--white);
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
        }
        .review-form button:hover { background: #218838; }
        
        .review-text {
            margin-top: 12px;
            padding: 10px 14px;
            background: var(--gray-bg);
            border-radius: 12px;
            font-size: 13px;
        }
        .review-text b { font-weight: 600; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            font-size: 14px;
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--green);
        }
        .empty-state a {
            color: var(--green);
            text-decoration: none;
            font-weight: 500;
        }
        .empty-state a:hover { text-decoration: underline; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }
        .page-link {
            padding: 6px 14px;
            border: 1px solid var(--gray-light);
            border-radius: 30px;
            text-decoration: none;
            color: var(--gray-dark);
            font-size: 14px;
        }
        .page-link.active, .page-link:hover {
            background: var(--green);
            color: var(--white);
            border-color: var(--green);
        }
        
        .footer {
            background: var(--gray-dark);
            padding: 16px 0;
            margin-top: 40px;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }
        .footer-copyright {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }
        
        @media (max-width: 1000px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .header-inner { flex-direction: column; gap: 12px; }
            .header-title { position: static; transform: none; }
            .cards-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-inner">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Логотип">
            <span>Конференции.РФ</span>
        </a>
        <div class="header-title">История бронирования</div>
        <div class="nav-links">
            <a href="create.php">Новая заявка</a>
            <a href="?logout=1">Выход</a>
        </div>
    </div>
</header>

<main class="main">
    <div class="container">
        <?php if ($review_success): ?>
            <div class="toast-notification">Отзыв сохранён</div>
        <?php endif; ?>
        
        <?php
        if($total_count == 0) {
            echo '<div class="empty-state">У вас пока нет бронирований.<br><br><a href="create.php">Забронировать помещение</a></div>';
        } else {
            echo '<div class="cards-grid">';
            $counter = $offset + 1;
            while($request = $query->fetch_assoc()) {
                $room = htmlspecialchars($request['room']);
                
                echo '
                <div class="request">
                    <h3>Бронирование №' . $counter . '</h3>
                    <p><b>Дата и время:</b> ' . date('d.m.Y H:i', strtotime($request['date'])) . '</p>
                    <p><b>Тип помещения:</b> ' . $room . '</p>
                    <p><b>Способ оплаты:</b> ' . htmlspecialchars($request['payment']) . '</p>';
                
                if(!empty($request['review'])) {
                    echo '<div class="review-text"><b>Отзыв:</b> ' . htmlspecialchars($request['review']) . '</div>';
                }
                
                if($request['status'] === 'Мероприятие завершено') {
                    echo '
                    <div class="review-form">
                        <label>Оставить отзыв:</label>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="' . $request['id'] . '">
                            <input type="text" name="review" placeholder="Напишите о ваших впечатлениях..." value="' . htmlspecialchars($request['review'] ?? '') . '">
                            <button type="submit">Отправить отзыв</button>
                        </form>
                    </div>';
                }
                echo '</div>';
                $counter++;
            }
            echo '</div>';
            
            if ($total_count > $limit):
        ?>
        <div class="pagination">
            <?php
            $total_pages = ceil($total_count / $limit);
            for ($i = 1; $i <= $total_pages; $i++):
                $url = "?page=$i";
            ?>
                <a href="<?= $url ?>" class="page-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php
            endif;
        }
        ?>
    </div>
</main>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-copyright">© 2026 Конференции.РФ - Все права защищены.</div>
    </div>
</footer>

</body>
</html>