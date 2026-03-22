-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Мар 22 2026 г., 20:59
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
-- Структура таблицы `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `visibility` enum('private','public') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'private',
  `owner_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `visibility`, `owner_id`, `created_at`) VALUES
(1, 'Website Development', 'Project for building company website', 'private', 1, '2026-02-16 05:47:14'),
(2, 'planer', '', 'public', 1, '2026-03-08 11:51:54'),
(3, 'planer', '', 'public', 1, '2026-03-08 11:53:23'),
(4, 'ПТРПО', 'Учеба', 'public', 1, '2026-03-08 12:18:31'),
(5, 'Завтрак', 'Приготовить', 'public', 6, '2026-03-08 12:25:28'),
(6, 'Обед', 'Приготовить обед', 'public', 6, '2026-03-08 12:33:36'),
(7, 'Жаканов - Планировщик задач', 'Создать полнофункциональный планировщик задач', 'public', 5, '2026-03-21 07:28:44'),
(8, '1', '', 'private', 5, '2026-03-21 12:22:00'),
(9, '2', '', 'private', 5, '2026-03-21 12:22:04'),
(10, '3', '', 'private', 5, '2026-03-21 12:22:08'),
(11, '4', '', 'private', 5, '2026-03-21 12:22:11'),
(12, '5', '', 'private', 5, '2026-03-21 12:22:13'),
(13, '6', '', 'private', 5, '2026-03-21 12:22:16'),
(14, '7', '', 'private', 5, '2026-03-21 12:22:19'),
(15, '8', '', 'private', 5, '2026-03-21 12:22:22'),
(16, '9', '', 'private', 5, '2026-03-21 12:22:25'),
(17, '10', '', 'private', 5, '2026-03-21 12:22:37'),
(18, '11', '', 'private', 5, '2026-03-21 12:22:39'),
(19, '12', '', 'private', 5, '2026-03-21 12:22:42'),
(20, '13', '', 'private', 5, '2026-03-21 12:22:44'),
(21, '14', '', 'private', 5, '2026-03-21 12:22:50'),
(22, '15', '', 'private', 5, '2026-03-21 12:22:52'),
(23, '16', '', 'private', 5, '2026-03-21 12:22:57'),
(24, '17', '', 'private', 5, '2026-03-21 12:23:01'),
(25, '18', '', 'private', 5, '2026-03-21 12:23:05'),
(26, '19', '', 'private', 5, '2026-03-21 12:23:08'),
(27, '20', '', 'private', 5, '2026-03-21 12:23:12'),
(28, '21', '', 'private', 5, '2026-03-21 12:23:15'),
(29, '22', '', 'private', 5, '2026-03-21 12:23:17'),
(30, '23', '', 'private', 5, '2026-03-21 12:23:19'),
(31, '24', '', 'private', 5, '2026-03-21 12:23:23'),
(32, '25', '', 'private', 5, '2026-03-21 12:23:26'),
(33, '26', '', 'private', 5, '2026-03-21 12:23:29'),
(34, '27', '', 'private', 5, '2026-03-21 12:23:31'),
(35, '28', '', 'private', 5, '2026-03-21 12:23:34'),
(36, '29', '', 'private', 5, '2026-03-21 12:23:38'),
(37, '30', '', 'private', 5, '2026-03-21 12:23:41'),
(38, '31', '', 'private', 5, '2026-03-21 12:23:46'),
(39, '32', '', 'private', 5, '2026-03-21 12:23:49'),
(40, '33', '', 'private', 5, '2026-03-21 12:23:53'),
(41, '34', '', 'private', 5, '2026-03-21 12:23:55'),
(42, '35', '', 'private', 5, '2026-03-21 12:23:59'),
(43, '36', '', 'private', 5, '2026-03-21 12:24:01'),
(44, '37', '', 'private', 5, '2026-03-21 12:24:04'),
(45, '38', '', 'private', 5, '2026-03-21 12:24:07'),
(46, '39', '', 'private', 5, '2026-03-21 12:24:11'),
(47, '40', '', 'private', 5, '2026-03-21 12:24:15'),
(48, '41', '', 'private', 5, '2026-03-21 12:24:18'),
(49, '42', '', 'private', 5, '2026-03-21 12:24:20'),
(50, '43', '', 'private', 5, '2026-03-21 12:24:24'),
(51, '44', '', 'private', 5, '2026-03-21 12:24:27'),
(52, '45', '', 'private', 5, '2026-03-21 12:24:29'),
(53, '46', '', 'private', 5, '2026-03-21 12:24:33'),
(54, '47', '', 'private', 5, '2026-03-21 12:24:35'),
(55, '48', '', 'private', 5, '2026-03-21 12:24:37'),
(56, '49', '', 'private', 5, '2026-03-21 12:24:41'),
(57, '50', '', 'private', 5, '2026-03-21 12:24:44'),
(58, '51', '', 'private', 5, '2026-03-21 12:24:48'),
(59, '52', '', 'private', 5, '2026-03-21 12:24:53'),
(60, '53', '', 'private', 5, '2026-03-21 12:24:56'),
(61, '54', '', 'private', 5, '2026-03-21 12:24:58'),
(62, '55', '', 'private', 5, '2026-03-21 12:25:02'),
(63, '56', '', 'private', 5, '2026-03-21 12:25:05'),
(64, '57', '', 'private', 5, '2026-03-21 12:25:09'),
(65, '58', '', 'private', 5, '2026-03-21 12:25:12'),
(66, '59', '', 'private', 5, '2026-03-21 12:25:15'),
(67, '60', '', 'private', 5, '2026-03-21 12:25:19'),
(68, '1', '', 'private', 5, '2026-03-21 12:25:23'),
(69, '1', '', 'private', 5, '2026-03-21 12:25:25'),
(70, '1', '', 'private', 5, '2026-03-21 12:25:27'),
(71, '1', '', 'private', 5, '2026-03-21 12:25:31'),
(72, '1', '', 'private', 5, '2026-03-21 12:25:34'),
(73, '1', '', 'private', 5, '2026-03-21 12:25:36'),
(74, '1', '', 'private', 5, '2026-03-21 12:25:39'),
(75, 'й', '', 'private', 5, '2026-03-21 12:25:42'),
(76, 'ц', '', 'private', 5, '2026-03-21 12:25:44'),
(77, 'у', '', 'private', 5, '2026-03-21 12:25:47'),
(78, 'й', '', 'private', 5, '2026-03-21 12:25:51'),
(79, 'я', '', 'private', 5, '2026-03-21 12:25:55'),
(80, 'ч', '', 'private', 5, '2026-03-21 12:25:57'),
(81, 'с', '', 'private', 5, '2026-03-21 12:26:00'),
(82, 'м', '', 'private', 5, '2026-03-21 12:26:02'),
(83, 'м', '', 'private', 5, '2026-03-21 12:26:05'),
(84, 'и', '', 'public', 5, '2026-03-21 12:26:09'),
(85, 'т', '', 'private', 5, '2026-03-21 12:26:13'),
(86, 'а', '', 'private', 5, '2026-03-21 12:26:16'),
(87, 'п', '', 'private', 5, '2026-03-21 12:26:18'),
(88, 'р', '', 'private', 5, '2026-03-21 12:26:21'),
(89, 'о', '', 'private', 5, '2026-03-21 12:26:25'),
(90, 'н', '', 'private', 5, '2026-03-21 12:26:28'),
(91, 'е', '', 'private', 5, '2026-03-21 12:26:31'),
(92, 'q', '', 'private', 5, '2026-03-21 12:26:34'),
(93, 'w', '', 'private', 5, '2026-03-21 12:26:37'),
(94, 'e', '', 'private', 5, '2026-03-21 12:26:40'),
(95, 'f', '', 'private', 5, '2026-03-21 12:26:42'),
(96, 'd', '', 'private', 5, '2026-03-21 12:26:45'),
(97, 'sdg', '', 'private', 5, '2026-03-21 12:26:47'),
(98, 'af', '', 'private', 5, '2026-03-21 12:26:51'),
(99, 'asdf', '', 'private', 5, '2026-03-21 12:26:54'),
(100, 'adb', '', 'private', 5, '2026-03-21 12:26:57'),
(101, 'asd', '', 'private', 5, '2026-03-21 12:27:00'),
(102, 'dsav', '', 'private', 5, '2026-03-21 12:27:02'),
(103, 'zsffd', '', 'private', 5, '2026-03-21 12:27:05'),
(104, 'wfsxf', '', 'private', 5, '2026-03-21 12:27:07'),
(105, 'egwerhger', '', 'private', 5, '2026-03-21 12:27:11'),
(106, 'азвание проекта обязательно и не должно превышать 200 символовазвание проекта обязательно и не должно превышать 200 символовазвание проекта обязательно и не должно превышать 200 символовазвание проект', '', 'private', 5, '2026-03-21 12:29:59'),
(107, '1', '', 'private', 8, '2026-03-21 12:32:40'),
(108, '2', '', 'private', 8, '2026-03-21 12:32:42'),
(109, '3', '', 'private', 8, '2026-03-21 12:32:45'),
(110, '12', '', 'private', 9, '2026-03-22 10:59:01'),
(111, 'Жесть', '123', 'private', 8, '2026-03-22 13:18:11'),
(112, 'Жеский', 'Очень жески проект', 'public', 8, '2026-03-22 13:27:30'),
(113, '123ыаваыв', 'ыувацу', 'private', 8, '2026-03-22 17:12:34');

-- --------------------------------------------------------

--
-- Структура таблицы `project_activity`
--

CREATE TABLE `project_activity` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `project_activity`
--

INSERT INTO `project_activity` (`id`, `project_id`, `user_id`, `task_id`, `details`, `created_at`) VALUES
(1, 107, 8, 26, 'создал(а) задачу: 123', '2026-03-22 16:21:26'),
(2, 107, 8, 15, 'добавил(а) комментарий к задаче: йцу', '2026-03-22 16:21:38'),
(3, 107, 8, 27, 'создал(а) задачу: 1243у', '2026-03-22 16:27:23'),
(4, 107, 8, 22, 'добавил(а) комментарий к задаче: 12', '2026-03-22 16:27:33'),
(5, 107, 8, 22, 'изменил(а) статус задачи «12» на «Завершены»', '2026-03-22 16:28:07'),
(6, 107, 8, 22, 'изменил(а) статус задачи «12» на «В процессе»', '2026-03-22 16:28:08'),
(7, 107, 8, 22, 'изменил(а) статус задачи «12» на «Завершены»', '2026-03-22 16:29:11'),
(8, 107, 8, 22, 'изменил(а) статус задачи «12» на «В процессе»', '2026-03-22 16:29:15'),
(9, 107, 8, 20, 'изменил(а) статус задачи «123ыв» на «Завершены»', '2026-03-22 17:03:00'),
(10, 107, 8, 20, 'изменил(а) статус задачи «123ыв» на «В процессе»', '2026-03-22 17:03:01'),
(11, 107, 8, 20, 'изменил(а) статус задачи «123ыв» на «В работе»', '2026-03-22 17:03:01'),
(12, 107, 8, 15, 'изменил(а) статус задачи «йцу» на «В процессе»', '2026-03-22 17:03:02'),
(13, 107, 8, 22, 'изменил(а) статус задачи «12» на «Завершены»', '2026-03-22 17:03:02'),
(14, 107, 8, 19, 'изменил(а) статус задачи «123» на «В работе»', '2026-03-22 17:03:10'),
(15, 107, 8, 24, 'изменил(а) статус задачи «123» на «В работе»', '2026-03-22 17:03:17'),
(16, 107, 8, 25, 'изменил(а) статус задачи «123» на «В работе»', '2026-03-22 17:03:17'),
(17, 107, 8, 27, 'изменил(а) статус задачи «1243у» на «В работе»', '2026-03-22 17:03:19'),
(18, 107, 8, 24, 'изменил(а) статус задачи «123» на «Новые»', '2026-03-22 17:03:20'),
(19, 107, 8, 27, 'изменил(а) статус задачи «1243у» на «В процессе»', '2026-03-22 17:03:20'),
(20, 107, 8, 25, 'изменил(а) статус задачи «123» на «Завершены»', '2026-03-22 17:03:21'),
(21, 107, 8, 22, 'изменил(а) статус задачи «12» на «В процессе»', '2026-03-22 17:03:21'),
(22, 107, 8, 27, 'изменил(а) статус задачи «1243у» на «В работе»', '2026-03-22 17:03:22'),
(23, 107, 8, 19, 'изменил(а) статус задачи «123» на «В процессе»', '2026-03-22 17:03:22'),
(24, 107, 8, 19, 'изменил(а) статус задачи «123» на «Завершены»', '2026-03-22 17:16:24'),
(25, 107, 8, 22, 'отредактировал(а) задачу', '2026-03-22 17:28:44'),
(26, 107, 8, 15, 'изменил(а) статус задачи «йцу» на «В работе»', '2026-03-22 17:31:11'),
(27, 107, 8, 15, 'изменил(а) статус задачи «йцу» на «Завершены»', '2026-03-22 17:51:36'),
(28, 107, 8, 26, 'отредактировал(а) задачу', '2026-03-22 17:52:07'),
(29, 107, 8, 26, 'изменил(а) статус задачи «123» на «Завершены»', '2026-03-22 17:52:26'),
(30, 107, 8, 20, 'отредактировал(а) задачу', '2026-03-22 17:52:46'),
(31, 107, 8, 20, 'изменил(а) статус задачи «123ыв» на «Завершены»', '2026-03-22 17:52:53'),
(32, 107, 8, 24, 'отредактировал(а) задачу', '2026-03-22 17:53:15'),
(33, 107, 8, 24, 'изменил(а) статус задачи «123» на «Завершены»', '2026-03-22 17:53:20');

-- --------------------------------------------------------

--
-- Структура таблицы `project_members`
--

CREATE TABLE `project_members` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('owner','manager','member') DEFAULT 'member',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 6, 6, 'owner', '2026-03-08 12:33:36'),
(2, 7, 5, 'owner', '2026-03-21 07:28:44'),
(3, 8, 5, 'owner', '2026-03-21 12:22:00'),
(4, 9, 5, 'owner', '2026-03-21 12:22:04'),
(5, 10, 5, 'owner', '2026-03-21 12:22:08'),
(6, 11, 5, 'owner', '2026-03-21 12:22:11'),
(7, 12, 5, 'owner', '2026-03-21 12:22:13'),
(8, 13, 5, 'owner', '2026-03-21 12:22:16'),
(9, 14, 5, 'owner', '2026-03-21 12:22:19'),
(10, 15, 5, 'owner', '2026-03-21 12:22:22'),
(11, 16, 5, 'owner', '2026-03-21 12:22:25'),
(12, 17, 5, 'owner', '2026-03-21 12:22:37'),
(13, 18, 5, 'owner', '2026-03-21 12:22:39'),
(14, 19, 5, 'owner', '2026-03-21 12:22:42'),
(15, 20, 5, 'owner', '2026-03-21 12:22:44'),
(16, 21, 5, 'owner', '2026-03-21 12:22:50'),
(17, 22, 5, 'owner', '2026-03-21 12:22:52'),
(18, 23, 5, 'owner', '2026-03-21 12:22:57'),
(19, 24, 5, 'owner', '2026-03-21 12:23:01'),
(20, 25, 5, 'owner', '2026-03-21 12:23:05'),
(21, 26, 5, 'owner', '2026-03-21 12:23:08'),
(22, 27, 5, 'owner', '2026-03-21 12:23:12'),
(23, 28, 5, 'owner', '2026-03-21 12:23:15'),
(24, 29, 5, 'owner', '2026-03-21 12:23:17'),
(25, 30, 5, 'owner', '2026-03-21 12:23:19'),
(26, 31, 5, 'owner', '2026-03-21 12:23:23'),
(27, 32, 5, 'owner', '2026-03-21 12:23:26'),
(28, 33, 5, 'owner', '2026-03-21 12:23:29'),
(29, 34, 5, 'owner', '2026-03-21 12:23:31'),
(30, 35, 5, 'owner', '2026-03-21 12:23:34'),
(31, 36, 5, 'owner', '2026-03-21 12:23:38'),
(32, 37, 5, 'owner', '2026-03-21 12:23:41'),
(33, 38, 5, 'owner', '2026-03-21 12:23:46'),
(34, 39, 5, 'owner', '2026-03-21 12:23:49'),
(35, 40, 5, 'owner', '2026-03-21 12:23:53'),
(36, 41, 5, 'owner', '2026-03-21 12:23:55'),
(37, 42, 5, 'owner', '2026-03-21 12:23:59'),
(38, 43, 5, 'owner', '2026-03-21 12:24:01'),
(39, 44, 5, 'owner', '2026-03-21 12:24:04'),
(40, 45, 5, 'owner', '2026-03-21 12:24:07'),
(41, 46, 5, 'owner', '2026-03-21 12:24:11'),
(42, 47, 5, 'owner', '2026-03-21 12:24:15'),
(43, 48, 5, 'owner', '2026-03-21 12:24:18'),
(44, 49, 5, 'owner', '2026-03-21 12:24:20'),
(45, 50, 5, 'owner', '2026-03-21 12:24:24'),
(46, 51, 5, 'owner', '2026-03-21 12:24:27'),
(47, 52, 5, 'owner', '2026-03-21 12:24:29'),
(48, 53, 5, 'owner', '2026-03-21 12:24:33'),
(49, 54, 5, 'owner', '2026-03-21 12:24:35'),
(50, 55, 5, 'owner', '2026-03-21 12:24:37'),
(51, 56, 5, 'owner', '2026-03-21 12:24:41'),
(52, 57, 5, 'owner', '2026-03-21 12:24:44'),
(53, 58, 5, 'owner', '2026-03-21 12:24:48'),
(54, 59, 5, 'owner', '2026-03-21 12:24:53'),
(55, 60, 5, 'owner', '2026-03-21 12:24:56'),
(56, 61, 5, 'owner', '2026-03-21 12:24:58'),
(57, 62, 5, 'owner', '2026-03-21 12:25:02'),
(58, 63, 5, 'owner', '2026-03-21 12:25:05'),
(59, 64, 5, 'owner', '2026-03-21 12:25:09'),
(60, 65, 5, 'owner', '2026-03-21 12:25:12'),
(61, 66, 5, 'owner', '2026-03-21 12:25:15'),
(62, 67, 5, 'owner', '2026-03-21 12:25:19'),
(63, 68, 5, 'owner', '2026-03-21 12:25:23'),
(64, 69, 5, 'owner', '2026-03-21 12:25:25'),
(65, 70, 5, 'owner', '2026-03-21 12:25:27'),
(66, 71, 5, 'owner', '2026-03-21 12:25:31'),
(67, 72, 5, 'owner', '2026-03-21 12:25:34'),
(68, 73, 5, 'owner', '2026-03-21 12:25:36'),
(69, 74, 5, 'owner', '2026-03-21 12:25:39'),
(70, 75, 5, 'owner', '2026-03-21 12:25:42'),
(71, 76, 5, 'owner', '2026-03-21 12:25:45'),
(72, 77, 5, 'owner', '2026-03-21 12:25:47'),
(73, 78, 5, 'owner', '2026-03-21 12:25:51'),
(74, 79, 5, 'owner', '2026-03-21 12:25:55'),
(75, 80, 5, 'owner', '2026-03-21 12:25:57'),
(76, 81, 5, 'owner', '2026-03-21 12:26:00'),
(77, 82, 5, 'owner', '2026-03-21 12:26:02'),
(78, 83, 5, 'owner', '2026-03-21 12:26:05'),
(79, 84, 5, 'owner', '2026-03-21 12:26:09'),
(80, 85, 5, 'owner', '2026-03-21 12:26:13'),
(81, 86, 5, 'owner', '2026-03-21 12:26:16'),
(82, 87, 5, 'owner', '2026-03-21 12:26:18'),
(83, 88, 5, 'owner', '2026-03-21 12:26:21'),
(84, 89, 5, 'owner', '2026-03-21 12:26:25'),
(85, 90, 5, 'owner', '2026-03-21 12:26:28'),
(86, 91, 5, 'owner', '2026-03-21 12:26:31'),
(87, 92, 5, 'owner', '2026-03-21 12:26:34'),
(88, 93, 5, 'owner', '2026-03-21 12:26:37'),
(89, 94, 5, 'owner', '2026-03-21 12:26:40'),
(90, 95, 5, 'owner', '2026-03-21 12:26:42'),
(91, 96, 5, 'owner', '2026-03-21 12:26:45'),
(92, 97, 5, 'owner', '2026-03-21 12:26:47'),
(93, 98, 5, 'owner', '2026-03-21 12:26:51'),
(94, 99, 5, 'owner', '2026-03-21 12:26:54'),
(95, 100, 5, 'owner', '2026-03-21 12:26:57'),
(96, 101, 5, 'owner', '2026-03-21 12:27:00'),
(97, 102, 5, 'owner', '2026-03-21 12:27:02'),
(98, 103, 5, 'owner', '2026-03-21 12:27:05'),
(99, 104, 5, 'owner', '2026-03-21 12:27:08'),
(100, 105, 5, 'owner', '2026-03-21 12:27:11'),
(101, 106, 5, 'owner', '2026-03-21 12:29:59'),
(102, 107, 8, 'owner', '2026-03-21 12:32:40'),
(103, 108, 8, 'owner', '2026-03-21 12:32:42'),
(104, 109, 8, 'owner', '2026-03-21 12:32:45'),
(105, 107, 7, 'member', '2026-03-22 09:43:58'),
(106, 110, 9, 'owner', '2026-03-22 10:59:01'),
(108, 111, 8, 'owner', '2026-03-22 13:18:11'),
(109, 112, 8, 'owner', '2026-03-22 13:27:30'),
(110, 110, 10, 'member', '2026-03-22 14:17:34'),
(111, 113, 8, 'owner', '2026-03-22 17:12:34');

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
(12, 'Й', 'Й', 'high', NULL, 7, NULL, 'working', '2026-03-21 10:29:16'),
(13, 'йцу', 'йцу', 'medium', '2026-03-23 12:57:00', 7, NULL, 'new', '2026-03-21 10:57:35'),
(15, 'йцу', '', 'high', '2026-03-29 12:07:00', 107, NULL, 'done', '2026-03-22 10:07:57'),
(18, '123', '123', 'low', NULL, 109, NULL, 'new', '2026-03-22 16:18:28'),
(19, '123', '', 'low', NULL, 107, NULL, 'done', '2026-03-22 16:22:41'),
(20, '123ыв', '', 'medium', NULL, 107, 7, 'done', '2026-03-22 16:22:52'),
(22, '12', '332', 'low', NULL, 107, 7, 'progress', '2026-03-22 16:33:25'),
(23, 'd', '', 'low', NULL, 110, 10, 'new', '2026-03-22 17:18:09'),
(24, '123', '', 'low', NULL, 107, 7, 'done', '2026-03-22 19:02:17'),
(25, '123', '', 'low', NULL, 107, NULL, 'done', '2026-03-22 19:02:37'),
(26, '123', '123', 'low', NULL, 107, 8, 'done', '2026-03-22 19:21:26'),
(27, '1243у', '', 'low', NULL, 107, NULL, 'working', '2026-03-22 19:27:23');

-- --------------------------------------------------------

--
-- Структура таблицы `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `task_comments`
--

INSERT INTO `task_comments` (`id`, `task_id`, `user_id`, `comment`, `created_at`) VALUES
(6, 20, 8, 'я молодец', '2026-03-22 13:25:16'),
(7, 19, 8, 'я', '2026-03-22 15:58:16'),
(8, 19, 8, 'я', '2026-03-22 15:59:32'),
(9, 19, 8, 'я', '2026-03-22 15:59:38'),
(10, 15, 8, '133', '2026-03-22 16:21:38'),
(11, 22, 8, 'иа', '2026-03-22 16:27:33');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('owner','manager','member') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@test.com', '123456', 'owner', '2026-02-16 05:47:14'),
(2, 'Manager User', 'manager@test.com', '123456', 'manager', '2026-02-16 05:47:14'),
(3, 'Member User', 'member@test.com', '123456', 'member', '2026-02-16 05:47:14'),
(4, 'Темирлан', 'temirlan@gmail.com', '$2y$10$EXZwCNgbY4AeCKd8SNdVl.Lw5bD4neXJCR8m3eVxT/dqEE06KTTH6', 'member', '2026-03-01 16:58:15'),
(5, 'Жаканов', '123@as.com', '$2y$10$0jCF2VihPRa6SPyoAo1QHuMBR2kyHnhH0It2XWloCQhWKVsHzMPyu', 'member', '2026-03-02 15:54:21'),
(6, 'Темирлан', 'zxc@gmail.com', '$2y$10$pRnTRp4QuC6u.C0QLhfZdenr5Aonw5Lz1tURrrgWPz8Srl7pt4ZXW', 'member', '2026-03-08 12:24:59'),
(7, 'Крутой', 'temir@gmail.com', '$2y$10$WmF7YiMzsEHJSowIsd10UuP1DCc7iLhAWqRvdK0Oowg4oDy1pQQym', 'member', '2026-03-21 07:37:19'),
(8, 'я', 'ya@gmail.com', '$2y$10$L6.FxVTh0TQP5Vmz.vYsY.hzJcNbcUxxOH92JxMrmXW8OawnajTt.', 'member', '2026-03-21 12:31:49'),
(9, 'Админ', 'admin@gmail.com', '$2y$10$A.IEqAdFWcAnkQ2xSoYQTuRC1OU7y3Kn/FXCLj7/4BbN7iB2IYAoK', 'member', '2026-03-22 09:52:56'),
(10, 'troltrolevic', 'troltrolevic789@gmail.com', '$2y$10$UGOqEQzDxbcqktDyAzuTLuk.QQ7thT3eUaYSAKM2TDeskppC0NZN.', 'member', '2026-03-22 10:58:22');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Индексы таблицы `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projects_owner` (`owner_id`);

--
-- Индексы таблицы `project_activity`
--
ALTER TABLE `project_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_tasks_assigned` (`assigned_to`),
  ADD KEY `idx_tasks_status` (`status`);

--
-- Индексы таблицы `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_comments_task` (`task_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT для таблицы `project_activity`
--
ALTER TABLE `project_activity`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT для таблицы `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_log_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `project_activity`
--
ALTER TABLE `project_activity`
  ADD CONSTRAINT `project_activity_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_activity_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_assigned_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tasks_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
