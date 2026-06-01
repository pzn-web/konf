<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конференции.РФ — бронирование помещений</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --gray-dark: #343A40; --gray-light: #DEE2E6; --green: #28A745; --white: #FFFFFF; --gray-bg: #F8F9FA; }
        body { 
            font-family: 'Roboto', sans-serif; 
            background-color: var(--gray-bg); 
            color: var(--gray-dark); 
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header { background: var(--white); border-bottom: 1px solid var(--gray-light); padding: 10px 0; position: sticky; top: 0; z-index: 100; }
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
        .header-inner { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo img { height: 40px; width: auto; }
        .logo span { font-size: 20px; font-weight: 700; color: var(--gray-dark); }
        .nav-links { display: flex; gap: 10px; flex-wrap: wrap; }
        .nav-links a { padding: 6px 16px; border-radius: 30px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .nav-links .link-light { background: transparent; border: 1px solid var(--gray-light); color: var(--gray-dark); }
        .nav-links .link-light:hover { border-color: var(--green); color: var(--green); }
        .nav-links .link-green { background: var(--green); color: var(--white); border: none; }
        .nav-links .link-green:hover { background: #218838; }
        .nav-links .link-outline { background: transparent; border: 1px solid var(--green); color: var(--green); }
        .nav-links .link-outline:hover { background: var(--green); color: var(--white); }
        
        .main { flex: 1; padding: 24px 0; }
        .hero { text-align: center; max-width: 900px; margin: 0 auto 32px; }
        .hero h1 { font-size: 36px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.5px; }
        .hero p { font-size: 16px; color: #6c757d; margin-bottom: 20px; }
        .hero-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 8px 24px; border-radius: 40px; font-size: 14px; font-weight: 500; text-decoration: none; transition: 0.2s; display: inline-block; }
        .btn-primary { background: var(--green); color: var(--white); }
        .btn-primary:hover { background: #218838; }
        .btn-outline { background: transparent; border: 1px solid var(--gray-light); color: var(--gray-dark); }
        .btn-outline:hover { border-color: var(--green); color: var(--green); }
        
        .slider-section { margin-bottom: 30px; scroll-margin-top: 80px; }
        .slider-title { font-size: 22px; font-weight: 600; text-align: center; margin-bottom: 20px; }
        .slider-wrapper { position: relative; max-width: 1000px; margin: 0 auto; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .slider-slide { display: none; width: 100%; animation: fadeEffect 0.6s ease-in-out; }
        @keyframes fadeEffect { from { opacity: 0.8; } to { opacity: 1; } }
        .slider-slide img { width: 100%; height: 380px; object-fit: cover; display: block; }
        .slider-caption { 
            position: absolute; 
            bottom: 10px; 
            left: 50%;
            transform: translateX(-50%);
            text-align: center; 
            color: var(--white); 
            font-size: 18px; 
            font-weight: 600; 
            background: rgba(52, 58, 64, 0.7);
            padding: 8px 20px; 
            border-radius: 40px;
            white-space: nowrap;
        }
        .slider-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.4); color: var(--white); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: 0.2s; font-size: 18px; display: flex; align-items: center; justify-content: center; z-index: 10; }
        .slider-prev { left: 12px; }
        .slider-next { right: 12px; }
        .slider-btn:hover { background: rgba(0, 0, 0, 0.7); }
        .slider-dots { text-align: center; padding: 12px 0 6px; background: transparent; }
        .dot { display: inline-block; width: 8px; height: 8px; margin: 0 5px; background: var(--gray-light); border-radius: 50%; cursor: pointer; transition: 0.2s; }
        .dot.active { background: var(--green); }
        .dot:hover { background: var(--green); }
        
        .cards-section { margin-bottom: 30px; max-width: 1000px; margin-left: auto; margin-right: auto; scroll-margin-top: 80px; }
        .cards-title { font-size: 22px; font-weight: 600; text-align: center; margin-bottom: 8px; }
        .cards-subtitle { text-align: center; color: #6c757d; margin-bottom: 28px; font-size: 14px; }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .cards-slider-mobile {
            display: none;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            gap: 16px;
            padding: 4px 0 12px;
        }
        .card-mobile {
            flex: 0 0 85%;
            scroll-snap-align: start;
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .card-mobile .card-image { width: 100%; height: 140px; object-fit: cover; }
        .card-mobile .card-content { padding: 16px 18px 20px; }
        .card-mobile h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
        .card-mobile p { font-size: 13px; color: #6c757d; margin-bottom: 14px; line-height: 1.5; }
        .card-mobile .card-link { color: var(--green); text-decoration: none; font-weight: 500; font-size: 13px; }
        .card-mobile .card-link:hover { text-decoration: none; }
        
        .card {
            background: var(--white);
            border-radius: 16px;
            transition: all 0.25s;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .card-image { width: 100%; height: 140px; object-fit: cover; }
        .card-content { padding: 16px 18px 20px; }
        .card h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
        .card p { font-size: 13px; color: #6c757d; margin-bottom: 14px; line-height: 1.5; }
        .card-link { color: var(--green); text-decoration: none; font-weight: 500; font-size: 13px; }
        .card-link:hover { text-decoration: none; }
        
        .footer { background: var(--gray-dark); padding: 10px 0; margin-top: 0px; }
        .footer-content { max-width: 1000px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 30px; }
        .footer-col { flex: 1; min-width: 150px; }
        .footer-col h4 { color: var(--white); font-size: 14px; font-weight: 600; margin-bottom: 8px; opacity: 0.9; }
        .footer-col p, .footer-col a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; line-height: 1.6; display: block; }
        .footer-col a:hover { color: var(--white); text-decoration: underline; }
        .copyright { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 8px; }

        .social-wrapper { position: relative; display: inline-block; line-height: 0; }
        .social-img { width: 80px; height: auto; display: block; margin-top: 0; }
        .social-links-map { position: absolute; top: 0; left: 0; width: 80px; height: 100%; display: flex; }
        .social-links-map a { flex: 1; height: 100%; text-indent: -9999px; }
        
        @media (max-width: 900px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .hero h1 { font-size: 28px; }
            .hero { max-width: 100%; }
            .slider-slide img { height: 280px; }
            .slider-caption { font-size: 14px; bottom: 30px; padding: 6px 16px; white-space: nowrap; }
            .footer-content { flex-direction: column; text-align: center; align-items: center; }
            .footer-col { text-align: center; }
            .social-img { margin: 0 auto; }
        }
        
        @media (max-width: 768px) {
            .cards-grid { display: none; }
            .cards-slider-mobile { display: flex; gap: 16px; }
            .header-inner { flex-direction: column; text-align: center; gap: 8px; }
            .nav-links { justify-content: center; gap: 8px; }
            .nav-links a { padding: 5px 12px; font-size: 12px; }
            .logo { justify-content: center; }
            .logo img { height: 32px; }
            .logo span { font-size: 18px; }
            .slider-slide img { height: 200px; }
            .slider-btn { width: 32px; height: 32px; font-size: 14px; }
            .slider-caption { font-size: 12px; bottom: 20px; padding: 4px 12px; white-space: nowrap; }
            .hero h1 { font-size: 24px; }
            .hero-buttons { gap: 8px; }
            .btn { padding: 6px 16px; font-size: 12px; }
        }
        
        @media (max-width: 650px) {
            .card-mobile { flex: 0 0 90%; }
        }
        @media (max-width: 480px) {
            .card-mobile { flex: 0 0 95%; }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-inner">
            <a href="index.php" class="logo">
                <img src="images/logo.png" alt="Логотип Конференции.РФ">
                <span>Конференции.РФ</span>
            </a>
            <div class="nav-links">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="link-light">Войти</a>
                    <a href="register.php" class="link-green">Регистрация</a>
                <?php elseif ($is_admin): ?>
                    <a href="admin.php" class="link-outline">Панель администратора</a>
                    <a href="?logout=1" class="link-outline">Выход</a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <a href="history.php" class="link-outline">Мои бронирования</a>
                    <a href="create.php" class="link-outline">Новая заявка</a>
                    <a href="?logout=1" class="link-outline">Выход</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main class="main">
    <div class="container">
        <div class="hero">
            <h1>Забронируйте помещение для конференции</h1>
            <p>Актовые залы, коворкинги и кинозалы для мероприятий любого масштаба</p>
            <div class="hero-buttons">
                <a href="#slider" class="btn btn-primary">Популярные площадки</a>
                <a href="#rooms" class="btn btn-outline">Типы помещений</a>
            </div>
        </div>

        <div id="slider" class="slider-section">
            <h2 class="slider-title">Популярные площадки</h2>
            <div class="slider-wrapper">
                <div class="slider-slide">
                    <img src="images/aydit.webp" alt="Аудитория">
                    <div class="slider-caption">Просторная аудитория</div>
                </div>
                <div class="slider-slide">
                    <img src="images/komvok.jpg" alt="Коворкинг">
                    <div class="slider-caption">Современный коворкинг</div>
                </div>
                <div class="slider-slide">
                    <img src="images/kuno.jpg" alt="Кинозал">
                    <div class="slider-caption">Оборудованный кинозал</div>
                </div>
                <div class="slider-slide">
                    <img src="images/plan_zal.jpg" alt="Пленарный зал">
                    <div class="slider-caption">Вместительный планёрный зал</div>
                </div>
                <button class="slider-btn slider-prev" id="prevBtn">❮</button>
                <button class="slider-btn slider-next" id="nextBtn">❯</button>
            </div>
            <div class="slider-dots" id="sliderDots">
                <span class="dot" data-index="0"></span>
                <span class="dot" data-index="1"></span>
                <span class="dot" data-index="2"></span>
                <span class="dot" data-index="3"></span>
            </div>
        </div>

        <div id="rooms" class="cards-section">
            <h2 class="cards-title">Выберите тип помещения</h2>
            <p class="cards-subtitle">Подходящий вариант для проведения конференции любого уровня</p>
            
            <?php $link = $is_logged_in ? 'create.php' : 'register.php'; ?>
            
            <div class="cards-grid">
                <div class="card">
                    <img class="card-image" src="images/aydit2.jpg" alt="Аудитория">
                    <div class="card-content">
                        <h3>Аудитория</h3>
                        <p>Вместимость до 60 гостей. Проектор, акустика, удобные кресла.</p>
                        <a href="<?= $link ?>" class="card-link">Забронировать</a>
                    </div>
                </div>
                <div class="card">
                    <img class="card-image" src="images/komvok2.jpg" alt="Коворкинг">
                    <div class="card-content">
                        <h3>Коворкинг</h3>
                        <p>Пространство для командной работы. Wi-Fi, кухня, зона отдыха.</p>
                        <a href="<?= $link ?>" class="card-link">Забронировать</a>
                    </div>
                </div>
                <div class="card">
                    <img class="card-image" src="images/kuno2.jpg" alt="Кинозал">
                    <div class="card-content">
                        <h3>Кинозал</h3>
                        <p>Панорамный экран, объёмный звук, мягкие кресла.</p>
                        <a href="<?= $link ?>" class="card-link">Забронировать</a>
                    </div>
                </div>
            </div>
            
            <div class="cards-slider-mobile" id="cardsSlider">
                <div class="card-mobile">
                    <img class="card-image" src="images/aydit2.jpg" alt="Аудитория">
                    <div class="card-content">
                        <h3>Аудитория</h3>
                        <p>Вместимость до 60 гостей. Проектор, акустика, удобные кресла.</p>
                        <a href="<?= $link ?>" class="card-link">Забронировать</a>
                    </div>
                </div>
                <div class="card-mobile">
                    <img class="card-image" src="images/komvok2.jpg" alt="Коворкинг">
                    <div class="card-content">
                        <h3>Коворкинг</h3>
                        <p>Пространство для командной работы. Wi-Fi, кухня, зона отдыха.</p>
                        <a href="<?= $link ?>" class="card-link">Забронировать</a>
                    </div>
                </div>
                <div class="card-mobile">
                    <img class="card-image" src="images/kuno2.jpg" alt="Кинозал">
                    <div class="card-content">
                        <h3>Кинозал</h3>
                        <p>Панорамный экран, объёмный звук, мягкие кресла.</p>
                        <a href="<?= $link ?>" class="card-link">Забронировать</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-col">
            <p>&copy; 2026 Конференции.РФ</p>
            <div class="copyright">Все права защищены</div>
        </div>
        <div class="footer-col">
            <h4>Контакты</h4>
            <p>+7 (495) 123-45-67</p>
            <p>info@konferencii.ru</p>
        </div>
        <div class="footer-col">
            <h4>Наши соцсети</h4>
            <div class="social-wrapper">
                <img src="images/social.png" alt="Социальные сети" class="social-img">
                <div class="social-links-map">
                    <a href="https://vk.com" target="_blank" rel="noopener noreferrer"></a>
                    <a href="https://ok.ru" target="_blank" rel="noopener noreferrer"></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slider-slide');
    const dots = document.querySelectorAll('.dot');
    let autoTimer;

    function showSlide(n) {
        slideIndex = n;
        if (slideIndex >= slides.length) slideIndex = 0;
        if (slideIndex < 0) slideIndex = slides.length - 1;
        slides.forEach(s => s.style.display = 'none');
        dots.forEach(d => d.classList.remove('active'));
        slides[slideIndex].style.display = 'block';
        dots[slideIndex].classList.add('active');
    }

    function nextSlide() { showSlide(slideIndex + 1); resetTimer(); }
    function prevSlide() { showSlide(slideIndex - 1); resetTimer(); }
    function resetTimer() { clearInterval(autoTimer); autoTimer = setInterval(nextSlide, 3000); }

    document.getElementById('nextBtn').onclick = nextSlide;
    document.getElementById('prevBtn').onclick = prevSlide;
    dots.forEach((dot, i) => dot.onclick = () => { showSlide(i); resetTimer(); });

    const wrapper = document.querySelector('.slider-wrapper');
    wrapper.onmouseenter = () => clearInterval(autoTimer);
    wrapper.onmouseleave = () => { autoTimer = setInterval(nextSlide, 3000); };

    showSlide(0);
    autoTimer = setInterval(nextSlide, 3000);
</script>
</body>
</html>