<?php
include('db.php');
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$valid_statuses = ['Новая', 'Мероприятие назначено', 'Мероприятие завершено'];
$status_updated = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'] ?? '';
    if (!in_array($status, $valid_statuses, true)) {
        die('Недопустимый статус заявки');
    }
    $stmt = $con->prepare("UPDATE request SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $request_id);
    if ($stmt->execute()) {
        $status_updated = true;
    }
}

$status_filter = $_GET['status_filter'] ?? '';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$search = $_GET['search'] ?? '';

$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$where = "";
$params = [];
$types = "";
if ($status_filter && in_array($status_filter, $valid_statuses)) {
    $where .= " AND request.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($search) {
    $where .= " AND (users.login LIKE ? OR users.fullname LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$page = (int)($_GET['page'] ?? 1);
$limit = 9;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total FROM request INNER JOIN users ON request.user_id = users.id WHERE 1=1 $where";
$stmt = $con->prepare($count_sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$sql = "SELECT request.*, users.login, users.fullname 
        FROM request 
        INNER JOIN users ON request.user_id = users.id 
        WHERE 1=1 $where 
        ORDER BY request.date $sort_order 
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $con->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$query = $stmt->get_result();

// Статистика заявок
$stats_query = $con->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Новая' THEN 1 ELSE 0 END) as new_requests,
        SUM(CASE WHEN status = 'Мероприятие назначено' THEN 1 ELSE 0 END) as assigned,
        SUM(CASE WHEN status = 'Мероприятие завершено' THEN 1 ELSE 0 END) as completed
    FROM request
");
$stats = $stats_query->fetch_assoc();

$users_query = $con->query("SELECT COUNT(*) as total FROM users WHERE login != 'Admin26'");
$users_count = $users_query->fetch_assoc()['total'];

$temp_requests = [];
if ($query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
        $temp_requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора — Конференции.РФ</title>
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
        
        .stats-wrapper-pc { margin-bottom: 30px; display: flex; justify-content: center; }
        .stats-grid-pc { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        
        .stats-wrapper-mobile {
            margin-bottom: 30px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            display: none;
        }
        .stats-grid-mobile {
            display: flex;
            gap: 16px;
            min-width: min-content;
            padding: 4px 0;
        }
        
        .stat-card {
            background: var(--white);
            padding: 20px 28px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid var(--gray-light);
            flex: 0 0 auto;
        }
        .stat-card-pc { min-width: 140px; }
        .stat-card-mobile { min-width: 130px; }
        .stat-number { font-size: 32px; font-weight: 700; color: var(--green); }
        .stat-label { font-size: 13px; color: #6c757d; margin-top: 6px; font-weight: 500; }
        
        .filters-bar {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--gray-light);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
            justify-content: center;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 12px; font-weight: 500; color: var(--gray-dark); }
        .filter-group select, .filter-group input {
            padding: 10px 12px;
            border: 1px solid var(--gray-light);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Roboto', sans-serif;
            height: 42px;
        }
        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: var(--green);
        }
        .filter-group input { width: 220px; }
        .btn-filter {
            background: var(--green);
            color: var(--white);
            border: none;
            padding: 10px 24px;
            border-radius: 30px;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            font-size: 14px;
            height: 42px;
        }
        .btn-filter:hover { background: #218838; }
        
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .request-item {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--gray-light);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .request-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 12px;
            gap: 10px;
        }
        .request-numbers {
            display: flex;
            gap: 10px;
            align-items: baseline;
            flex-wrap: wrap;
        }
        .request-id-bd {
            background: var(--gray-bg);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 500;
            color: var(--gray-dark);
        }
        .request-id-client {
            background: var(--green);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .status-new { background: rgba(40, 167, 69, 0.12); color: #1e7e34; }
        .status-assigned { background: rgba(40, 167, 69, 0.25); color: #155724; }
        .status-completed { background: rgba(40, 167, 69, 0.45); color: #0a4a1a; }
        
        .user-info { margin-bottom: 12px; }
        .user-info h3 { font-size: 16px; font-weight: 600; }
        .user-info p { font-size: 12px; color: #6c757d; margin-top: 2px; }
        
        .request-content {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin: 8px 0;
        }
        .request-details {
            flex: 2;
            min-width: 140px;
        }
        .status-form {
            flex: 1;
            min-width: 130px;
        }
        .detail-item { margin-bottom: 10px; }
        .detail-label { font-size: 11px; color: #6c757d; text-transform: uppercase; }
        .detail-value { font-size: 14px; margin-top: 2px; font-weight: 500; }
        
        .status-form select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--gray-light);
            border-radius: 12px;
            margin-bottom: 8px;
            font-size: 12px;
            font-family: 'Roboto', sans-serif;
        }
        .btn-save {
            width: 100%;
            padding: 8px;
            background: var(--green);
            color: var(--white);
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            font-size: 12px;
        }
        .btn-save:hover { background: #218838; }
        
        .review-section {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--gray-light);
        }
        .review-label { font-size: 11px; color: #6c757d; text-transform: uppercase; margin-bottom: 6px; }
        .review-text { font-size: 13px; background: var(--gray-bg); padding: 10px; border-radius: 12px; }
        
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
        
        .empty-state { text-align: center; padding: 60px 20px; background: var(--white); border-radius: 16px; border: 1px solid var(--gray-light); }
        
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
        
        @media (max-width: 1000px) {
            .requests-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .header-inner { flex-direction: column; gap: 12px; }
            .header-title { position: static; transform: none; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filter-group input { width: 100%; }
            .btn-filter { width: 100%; }
            .requests-grid { grid-template-columns: 1fr; }
            .stats-wrapper-pc { display: none; }
            .stats-wrapper-mobile { display: block; }
            .request-content { flex-direction: row; flex-wrap: wrap; }
            .request-details { flex: 2; }
            .status-form { flex: 1; min-width: 120px; }
        }
        @media (max-width: 550px) {
            .request-content { flex-direction: column; }
            .status-form { width: 100%; }
            .request-top { flex-direction: column; align-items: flex-start; }
            .request-numbers { width: 100%; justify-content: space-between; }
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
        <div class="header-title">Панель администратора</div>
        <div class="nav-links">
            <a href="?logout=1">Выход</a>
        </div>
    </div>
</header>

<main class="main">
    <div class="container">
        <div class="stats-wrapper-pc">
            <div class="stats-grid-pc">
                <div class="stat-card stat-card-pc"><div class="stat-number"><?= $stats['total'] ?></div><div class="stat-label">Всего бронирований</div></div>
                <div class="stat-card stat-card-pc"><div class="stat-number"><?= $stats['new_requests'] ?></div><div class="stat-label">Новые</div></div>
                <div class="stat-card stat-card-pc"><div class="stat-number"><?= $stats['assigned'] ?></div><div class="stat-label">Мероприятие назначено</div></div>
                <div class="stat-card stat-card-pc"><div class="stat-number"><?= $stats['completed'] ?></div><div class="stat-label">Мероприятие завершено</div></div>
                <div class="stat-card stat-card-pc"><div class="stat-number"><?= $users_count ?></div><div class="stat-label">Всего пользователей</div></div>
            </div>
        </div>
        
        <div class="stats-wrapper-mobile">
            <div class="stats-grid-mobile">
                <div class="stat-card stat-card-mobile"><div class="stat-number"><?= $stats['total'] ?></div><div class="stat-label">Всего бронирований</div></div>
                <div class="stat-card stat-card-mobile"><div class="stat-number"><?= $stats['new_requests'] ?></div><div class="stat-label">Новые</div></div>
                <div class="stat-card stat-card-mobile"><div class="stat-number"><?= $stats['assigned'] ?></div><div class="stat-label">Мероприятие назначено</div></div>
                <div class="stat-card stat-card-mobile"><div class="stat-number"><?= $stats['completed'] ?></div><div class="stat-label">Мероприятие завершено</div></div>
                <div class="stat-card stat-card-mobile"><div class="stat-number"><?= $users_count ?></div><div class="stat-label">Всего пользователей</div></div>
            </div>
        </div>
        
        <div class="filters-bar">
            <div class="filter-group">
                <label>Статус</label>
                <select name="status_filter" id="status_filter">
                    <option value="">Все</option>
                    <option value="Новая" <?= $status_filter == 'Новая' ? 'selected' : '' ?>>Новая</option>
                    <option value="Мероприятие назначено" <?= $status_filter == 'Мероприятие назначено' ? 'selected' : '' ?>>Мероприятие назначено</option>
                    <option value="Мероприятие завершено" <?= $status_filter == 'Мероприятие завершено' ? 'selected' : '' ?>>Мероприятие завершено</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Сортировка</label>
                <select name="sort_order" id="sort_order">
                    <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Сначала новые</option>
                    <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Сначала старые</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Поиск</label>
                <input type="text" id="search" placeholder="Логин или ФИО" value="<?= htmlspecialchars($search) ?>">
            </div>
            <button class="btn-filter" onclick="applyFilters()">Применить</button>
        </div>
        
        <?php if (empty($temp_requests)): ?>
            <div class="empty-state">Заявок не найдено</div>
        <?php else: ?>
            <div class="requests-grid">
                <?php 
                $counter = $total_count - $offset;
                foreach ($temp_requests as $request):
                    $status_class = match($request['status']) {
                        'Новая' => 'status-new',
                        'Мероприятие назначено' => 'status-assigned',
                        'Мероприятие завершено' => 'status-completed',
                        default => 'status-new'
                    };
                    $room = $request['room'] ?? '—';
                ?>
                <div class="request-item">
                    <div class="request-top">
                        <div class="request-numbers">
                            <span class="request-id-bd">№<?= $request['id'] ?></span>
                            <span class="request-id-client">№<?= $counter ?></span>
                        </div>
                        <div>
                            <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($request['status']) ?></span>
                        </div>
                    </div>
                    
                    <div class="user-info">
                        <h3><?= htmlspecialchars($request['login']) ?></h3>
                        <p><?= htmlspecialchars($request['fullname']) ?></p>
                    </div>
                    
                    <div class="request-content">
                        <div class="request-details">
                            <div class="detail-item"><div class="detail-label">Дата</div><div class="detail-value"><?= date('d.m.Y', strtotime($request['date'])) ?></div></div>
                            <div class="detail-item"><div class="detail-label">Тип помещения</div><div class="detail-value"><?= htmlspecialchars($room) ?></div></div>
                            <div class="detail-item"><div class="detail-label">Способ оплаты</div><div class="detail-value"><?= htmlspecialchars($request['payment'] ?? '—') ?></div></div>
                        </div>
                        <div class="status-form">
                            <form method="POST" class="status-update-form">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <select name="status">
                                    <option value="Новая" <?= $request['status'] == 'Новая' ? 'selected' : '' ?>>Новая</option>
                                    <option value="Мероприятие назначено" <?= $request['status'] == 'Мероприятие назначено' ? 'selected' : '' ?>>Мероприятие назначено</option>
                                    <option value="Мероприятие завершено" <?= $request['status'] == 'Мероприятие завершено' ? 'selected' : '' ?>>Мероприятие завершено</option>
                                </select>
                                <button type="submit" class="btn-save">Сохранить</button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if (!empty($request['review'])): ?>
                    <div class="review-section">
                        <div class="review-label">Отзыв клиента</div>
                        <div class="review-text"><?= htmlspecialchars($request['review']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                $counter--;
                endforeach; 
                ?>
            </div>
            
            <?php if ($total_count > $limit): ?>
            <div class="pagination">
                <?php
                $total_pages = ceil($total_count / $limit);
                for ($i = 1; $i <= $total_pages; $i++):
                    $url = "?page=$i&status_filter=" . urlencode($status_filter) . "&sort_order=$sort_order&search=" . urlencode($search);
                ?>
                    <a href="<?= $url ?>" class="page-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php if ($status_updated): ?>
<div class="toast-notification">Статус обновлён</div>
<?php endif; ?>

<script>
    function applyFilters() {
        const status = document.getElementById('status_filter').value;
        const sortOrder = document.getElementById('sort_order').value;
        const search = document.getElementById('search').value;
        let url = '?page=1';
        if (status) url += '&status_filter=' + encodeURIComponent(status);
        if (sortOrder) url += '&sort_order=' + sortOrder;
        if (search) url += '&search=' + encodeURIComponent(search);
        window.location.href = url;
    }
    
</script>
</body>
</html>