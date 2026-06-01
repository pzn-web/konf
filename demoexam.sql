-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-5.7
-- Время создания: Июн 01 2026 г., 06:07
-- Версия сервера: 5.7.44
-- Версия PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `demoexam`
--

-- --------------------------------------------------------

--
-- Структура таблицы `request`
--

CREATE TABLE `request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Новая','Мероприятие назначено','Мероприятие завершено') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Новая',
  `room` enum('Аудитория','Коворкинг','Кинозал') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment` enum('Наличными','Переводом на карту') COLLATE utf8mb4_unicode_ci NOT NULL,
  `review` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `request`
--

INSERT INTO `request` (`id`, `user_id`, `date`, `status`, `room`, `payment`, `review`) VALUES
(1, 2, '2026-06-01', 'Новая', 'Аудитория', 'Наличными', NULL),
(2, 2, '2026-06-01', 'Мероприятие назначено', 'Коворкинг', 'Переводом на карту', NULL),
(3, 3, '2026-06-01', 'Новая', 'Кинозал', 'Наличными', NULL),
(4, 3, '2026-06-01', 'Мероприятие завершено', 'Аудитория', 'Переводом на карту', 'Все прошло отлично, спасибо за организацию!'),
(5, 4, '2026-06-01', 'Новая', 'Коворкинг', 'Наличными', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `fullname`, `phone`, `email`, `login`, `password`, `created_at`) VALUES
(1, 'Администратор Системы', '+7(999)-000-00-01', 'admin@conference.ru', 'Admin26', 'Demo20', '2026-06-01 05:42:10'),
(2, 'Иванов Петр Сергеевич', '+7(999)-111-22-33', 'petr.ivanov@mail.ru', 'petr_ivanov', 'qwerty123', '2026-06-01 05:42:10'),
(3, 'Смирнова Елена Андреевна', '+7(999)-444-55-66', 'elena.smirnova@bk.ru', 'elena_s', 'asdfgh456', '2026-06-01 05:42:10'),
(4, 'Косогоров Никита Дмитриевич', '+7(912)345-67-89', 'nkosogorov@mail.ru', 'nkosogorov', '$2y$10$d6fa4gWWXgLXGG18u3nbDuxfWNncm3yTAYenMzENTS5aAaIe9Nugq', '2026-06-01 05:42:10');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `request`
--
ALTER TABLE `request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `request`
--
ALTER TABLE `request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `request`
--
ALTER TABLE `request`
  ADD CONSTRAINT `request_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
