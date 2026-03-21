-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Мар 21 2026 г., 10:45
-- Версия сервера: 8.0.30
-- Версия PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `task_manager`
--

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `priority` enum('low','medium','high') NOT NULL,
  `deadline` datetime DEFAULT NULL,
  `project_id` int NOT NULL,
  `assigned_to` int DEFAULT NULL,
  `status` enum('new','working','progress','done') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `priority`, `deadline`, `project_id`, `assigned_to`, `status`, `created_at`) VALUES
(1, 'Приготовить обед', 'Пожарить яишницу', 'high', '2026-03-07 13:48:00', 1, NULL, 'new', '2026-03-08 11:48:16'),
(2, '123', '123', 'high', '2026-03-14 13:55:00', 1, NULL, 'new', '2026-03-08 11:55:55'),
(3, '123213', '1232123', 'high', '2026-03-09 14:14:00', 1, NULL, 'new', '2026-03-08 12:14:23'),
(4, 'Йоу', 'Как то', 'low', NULL, 1, NULL, 'new', '2026-03-08 13:04:06'),
(5, '125346', '', 'low', NULL, 1, NULL, 'new', '2026-03-08 13:10:25'),
(6, 'Hey', '123ddg', 'high', '2026-03-14 10:00:00', 2, NULL, 'new', '2026-03-08 15:04:28'),
(7, 'Выполнить ПР-45', '', 'high', '2026-03-09 17:19:00', 4, NULL, 'new', '2026-03-08 15:19:09'),
(8, 'Взбить яичницу', '', 'high', '2026-03-08 17:34:00', 5, NULL, 'new', '2026-03-08 15:29:38'),
(9, 'Нарезать мясо', '', 'high', '2026-03-08 17:00:00', 6, NULL, 'new', '2026-03-08 15:34:07'),
(10, '123', '214', 'low', NULL, 6, NULL, 'new', '2026-03-08 15:36:10'),
(11, '124124', '', 'low', NULL, 6, NULL, 'new', '2026-03-08 15:36:17'),
(12, 'Й', 'Й', 'high', NULL, 7, NULL, 'working', '2026-03-21 10:29:16');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_tasks_assigned` (`assigned_to`),
  ADD KEY `idx_tasks_status` (`status`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
