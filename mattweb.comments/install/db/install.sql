SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Структура таблицы `el_comment`
--

DROP TABLE IF EXISTS `el_comment`;
CREATE TABLE `el_comment` (
  `ID` int NOT NULL,
  `IBLOCK_ID` int NOT NULL,
  `SECT_ID` int DEFAULT '0',
  `ELEM_ID` int NOT NULL,
  `PARENT_ID` int NOT NULL DEFAULT '0',
  `DEPTH_LEVEL` tinyint NOT NULL DEFAULT '1',
  `CREATED_AT` datetime NOT NULL,
  `PUBLISHED` tinyint NOT NULL,
  `DEL_MARKED` tinyint UNSIGNED NOT NULL,
  `USER_ID` int DEFAULT '0',
  `USERNAME` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `NAME` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `HOMEPAGE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT '',
  `IP` varchar(39) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `EMAIL` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `COMMENT` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `SUBSCRIBE` tinyint(1) DEFAULT '0',
  `JC_ID` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

ALTER TABLE `el_comment` ADD PRIMARY KEY (`ID`);
ALTER TABLE `el_comment` MODIFY `ID` int NOT NULL AUTO_INCREMENT;


--
-- Структура таблицы `el_comment_smile`
--

DROP TABLE IF EXISTS `el_comment_smile`;
CREATE TABLE `el_comment_smile` (
  `id` int NOT NULL,
  `code` varchar(39) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `published` tinyint NOT NULL,
  `ordering` int NOT NULL,
  `smile_type_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


--
-- Дамп данных таблицы `el_comment_smile`
--

INSERT INTO `el_comment_smile` (`id`, `code`, `image`, `name`, `published`, `ordering`, `smile_type_id`) VALUES
(1, ':angry:', 'angry.png', 'Angry', 1, 1, 1),
(2, ':blush:', 'blush.png', 'Blush', 1, 2, 1),
(3, ':-?', 'confused.gif', 'Confused 	', 1, 3, 1),
(4, '8)', 'cool.gif', 'Cool', 1, 4, 1),
(5, ':cry:', 'cry.gif', 'Cry', 1, 5, 1),
(6, ':DOH', 'doh.png', 'DOH', 1, 6, 1),
(7, ':dry:', 'dry.png', 'Dry', 1, 7, 1),
(8, ':grin:', 'grin.png', 'Grin', 1, 8, 1),
(9, '(‿)', 'happy.png', 'Happy', 1, 9, 1),
(10, ':huh:', 'huh.png', 'Huh', 1, 10, 1),
(11, ':innocent:', 'innocent.png', 'Innocent', 1, 11, 1),
(12, ';)', 'ironic.png', 'Ironic', 1, 12, 1),
(13, ':kiss:', 'kiss.png', 'Kiss', 1, 13, 1),
(14, ':D', 'laugh.gif', 'Laugh', 1, 14, 1),
(15, ':loveface:', 'love.png', 'Love', 1, 15, 1),
(16, ':ninja:', 'ninja.png', 'Ninja', 1, 16, 1),
(17, ':rofl:', 'rofl.png', 'Rofl', 1, 17, 1),
(18, ':lol:', 'lol.gif', 'Lol', 1, 18, 1),
(19, ':-|', 'normal.gif', 'Normal', 1, 19, 1),
(20, ':oops:', 'redface.gif', 'Redface', 1, 20, 1),
(21, ':roll:', 'rolleyes.gif', 'Rolleyes', 1, 21, 1),
(22, ':sad:', 'sad.gif', 'Sad', 1, 22, 1),
(23, ':eek:', 'shocked.gif', 'Shocked', 1, 23, 1),
(24, ':-x', 'sick.gif', 'Sick', 1, 24, 1),
(25, ':-)', 'smile.gif', 'Smile', 1, 25, 1),
(26, ':zzz', 'sleeping.gif', 'Sleepin', 1, 26, 1),
(27, ':o', 'surprised.gif', 'Surprised 	', 1, 27, 1),
(28, ':P', 'tongue.gif', 'Tongue', 1, 28, 1),
(29, ':sigh:', 'unsure.gif', 'Unsure', 1, 29, 1),
(30, ':wacko:', 'wacko.png', 'Wacko', 1, 30, 1),
(31, ';-)', 'wink.gif', 'Wink', 1, 31, 1),
(32, ':-*', 'whistling.gif', 'Whistling 	', 1, 32, 1),
(33, '&#128513;', '', 'Laugh', 1, 1, 2),
(34, '&#128578;', '', 'Smile', 1, 2, 2),
(35, '&#128521;', '', 'Wink', 1, 3, 2),
(36, '&#128526;', '', 'Cool', 1, 4, 2),
(37, '&#128538;', '', 'Redface', 1, 5, 2),
(38, '&#128577;', '', 'Sad', 1, 6, 2),
(39, '&#128528;', '', 'Normal', 1, 7, 2),
(40, '&#128554;', '', 'Cry', 1, 8, 2),
(41, '&#128558;', '', 'Surprised', 1, 9, 2),
(42, '&#128527;', '', 'Confused', 1, 10, 2),
(43, '&#129298;', '', 'Sick', 1, 11, 2),
(44, '&#128558;', '', 'Shocked', 1, 12, 2),
(45, '&#128564;', '', 'Sleepin', 1, 13, 2),
(46, '&#128523;', '', 'Tongue', 1, 14, 2),
(47, '&#128580;', '', 'Rolleyes', 1, 15, 2),
(48, '&#128533;', '', 'Unsure', 1, 16, 2),
(49, '&#128520;', '', 'Evil', 1, 17, 2),
(50, '&#128536;', '', 'Kiss', 1, 18, 2),
(51, '&#128536;', '', 'Like', 1, 19, 2),
(52, '&#128078;', '', 'Dislike', 1, 20, 2),
(53, '&#128514;', '', 'Lol', 1, 2, 2),
(54, ':D', 'laugh.gif', 'Laugh', 1, 1, 3),
(55, ':lol:', 'lol_ak.gif', 'Lol', 1, 2, 3),
(56, ':-)', 'smile_ak.gif', 'Smile', 1, 3, 3),
(57, ';-)', 'wink_ak.gif', 'Wink', 1, 4, 3),
(58, '8)', 'cool_ak.gif', 'Cool', 1, 5, 3),
(59, ':-|', 'normal_ak.gif', 'Normal', 1, 6, 3),
(60, ':-*', 'whistling_ak.gif', 'Whistling 	', 1, 7, 3),
(61, ':oops:', 'redface_ak.gif', 'Redface', 1, 8, 3),
(62, ':sad:', 'sad_ak.gif', 'Sad', 1, 9, 3),
(63, ':cry:', 'cry_ak.gif', 'Cry', 1, 10, 3),
(64, ':o', 'surprised_ak.gif', 'Surprised 	', 1, 11, 3),
(65, ':-?', 'confused_ak.gif', 'Confused 	', 1, 12, 3),
(66, ':-x', 'sick_ak.gif', 'Sick', 1, 13, 3),
(67, ':eek:', 'shocked_ak.gif', 'Shocked', 1, 14, 3),
(68, ':zzz', 'sleeping_ak.gif', 'Sleepin', 1, 15, 3),
(69, ':P', 'tongue_ak.gif', 'Tongue', 1, 16, 3),
(70, ':roll:', 'rolleyes_ak.gif', 'Rolleyes', 1, 17, 3),
(71, ':sigh:', 'unsure_ak.gif', 'Unsure', 1, 18, 3),
(72, ':like:', 'like_ak.gif', 'Like', 1, 19, 3),
(73, ':dislike:', 'dislike_ak.gif', 'Dislike', 1, 20, 3),
(74, ':D', 'bx_smile_biggrin.png', 'Laugh', 1, 1, 4),
(75, ':-?', 'bx_smile_confuse.png', 'Confused 	', 1, 2, 4),
(76, '8)', 'bx_smile_cool.png', 'Cool', 1, 3, 4),
(77, ':cry:', 'bx_smile_cry.png', 'Cry', 1, 4, 4),
(78, ':eek:', 'bx_smile_eek.png', 'Shocked', 1, 5, 4),
(79, ':evil:', 'bx_smile_evil.png', 'Evil', 1, 6, 4),
(80, ':facepalm:', 'bx_smile_facepalm.png', 'Facepalm', 1, 7, 4),
(81, ':kiss:', 'bx_smile_kiss.png', 'Kiss', 1, 8, 4),
(82, ':-|', 'bx_smile_neutral.png', 'Normal', 1, 9, 4),
(83, ':oops:', 'bx_smile_redface.png', 'Normal', 1, 10, 4),
(84, ':sad:', 'bx_smile_sad.png', 'Sad', 1, 10, 4),
(85, ':-)', 'bx_smile_smile.png', 'Smile', 1, 11, 4),
(86, ';-)', 'bx_smile_wink.png', 'Wink', 1, 12, 4),
(87, ':like:', 'bx_smile_like.png', 'Like', 1, 13, 4),
(88, ':idea:', 'bx_smile_idea.png', 'Idea', 1, 14, 4),
(89, ':exclaim:', 'bx_smile_exclaim.png', 'Exclaim', 1, 15, 4),
(90, ':question:', 'bx_smile_question.png', 'Question', 1, 16, 4),
(92, 'iconsss', 'blush.png', 'Смайлы-иконки 1212', 0, 111, 1);

ALTER TABLE `el_comment_smile` ADD PRIMARY KEY (`id`);
ALTER TABLE `el_comment_smile` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;


--
-- Структура таблицы `el_comment_smile_type`
--

DROP TABLE IF EXISTS `el_comment_smile_type`;
CREATE TABLE `el_comment_smile_type` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `published` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


--
-- Дамп данных таблицы `el_comment_smile_type`
--

INSERT INTO `el_comment_smile_type` (`id`, `name`, `code`, `published`) VALUES
(1, 'Смайлы-иконки', 'icons', 1),
(2, 'Эмодзи (emoji)', 'emoji', 1),
(3, 'Смайлы анимированные', 'animated', 1),
(4, 'Стандартные Битрикс', 'standart', 1);

ALTER TABLE `el_comment_smile_type` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `type_code` (`code`);
ALTER TABLE `el_comment_smile_type` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;


--
-- Структура таблицы `el_comment_vote`
--

DROP TABLE IF EXISTS `el_comment_vote`;
CREATE TABLE `el_comment_vote` (
  `ID` int NOT NULL,
  `COMMENT_ID` int NOT NULL,
  `USER_ID` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `ELEM_ID` int NOT NULL,
  `IP` varchar(39) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `CREATED_AT` datetime NOT NULL,
  `VALUE` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

ALTER TABLE `el_comment_vote` ADD PRIMARY KEY (`ID`), ADD UNIQUE KEY `UNIQUE_COMMENT` (`COMMENT_ID`,`USER_ID`);
ALTER TABLE `el_comment_vote` MODIFY `ID` int NOT NULL AUTO_INCREMENT;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
