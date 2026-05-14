-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2023 at 09:44 AM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.0.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `brand_kit`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads_setting`
--

CREATE TABLE `ads_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ads_setting`
--

INSERT INTO `ads_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'ads_network', 'Admob', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(2, 'publisher_id', '33BE2250B43518CCDA7DE426D04EE231', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(3, 'banner_ads_id', 'ca-app-pub-3940256099942544/6300978111', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(4, 'app_open_ads_id', 'ca-app-pub-3940256099942544/3419835294', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(5, 'native_ads_id', 'ca-app-pub-3940256099942544/2247696110', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(6, 'interstitial_ads_id', 'ca-app-pub-3940256099942544/1033173712', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(7, 'interstitial_ads_click', '5', '2022-10-08 08:38:45', '2022-10-08 09:33:08'),
(8, 'banner_ads_enable', '1', '2022-10-08 08:39:45', '2022-10-08 09:33:08'),
(9, 'app_opens_ads_enable', '0', '2022-10-08 08:39:46', '2022-10-08 09:33:08'),
(10, 'native_ads_enable', '0', '2022-10-08 08:39:46', '2022-10-08 09:33:08'),
(11, 'interstitial_ads_enable', '1', '2022-10-08 08:39:46', '2022-10-08 09:33:08'),
(12, 'ads_enable', '1', '2022-10-08 09:33:08', '2022-10-08 09:33:08'),
(13, 'rewarded_ads_enable', '1', '2023-03-30 10:28:52', '2023-03-30 10:31:50'),
(14, 'rewarded_ads_id', 'ABV', '2023-03-30 10:29:05', '2023-03-30 10:31:50'),
(15, 'daily_limit_rewarded', '5', '2023-04-28 09:32:23', '2023-04-28 09:32:23');

-- --------------------------------------------------------

--
-- Table structure for table `api_setting`
--

CREATE TABLE `api_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `api_setting`
--

INSERT INTO `api_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'category_order_type', 'name', '2022-09-24 04:26:17', '2022-10-08 10:46:02'),
(2, 'category_order_by', 'asc', '2022-09-24 04:26:17', '2022-10-08 10:46:02'),
(3, 'news_order_type', 'title', '2022-09-24 04:35:44', '2022-10-08 10:46:02'),
(4, 'news_order_by', 'asc', '2022-09-24 04:36:26', '2022-10-08 10:46:02'),
(5, 'festival_order_type', 'festivals_date', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(6, 'festival_order_by', 'asc', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(7, 'custom_order_type', 'name', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(8, 'custom_order_by', 'desc', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(9, 'business_order_type', 'created_at', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(10, 'business_order_by', 'asc', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(11, 'story_order_type', 'created_at', '2022-10-08 10:05:34', '2022-10-08 10:46:02'),
(12, 'story_order_by', 'asc', '2022-10-08 10:05:34', '2022-10-08 10:46:02');

-- --------------------------------------------------------

--
-- Table structure for table `app_setting`
--

CREATE TABLE `app_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `app_setting`
--

INSERT INTO `app_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'app_title', 'Brand Kit', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(2, 'author', 'Brand 365', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(3, 'description', '<p><span style=\"color: rgb(77, 81, 86); font-family: Poppins; font-size: 14px;\">Lorazepam, sold under the brand name Ativan among others, is a benzodiazepine medication. It is used to treat anxiety disorders, trouble sleeping, severe agitation, active seizures including status epilepticus, alcohol withdrawal, and chemotherapy-induced nausea and vomiting.</span><br></p>', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(4, 'app_version', '1.0.0', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(5, 'contact', '(+91) 9685748548', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(6, 'email', 'brand@gmail.com', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(7, 'website', 'www.brand365.com', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(8, 'developed_by', 'Illiptam Infotech', '2022-07-25 01:21:43', '2023-01-20 09:20:02'),
(9, 'app_logo', '4a03a2d7-a0d9-4bad-ac17-9bf1c59cd39f.png', '2022-07-25 01:23:51', '2022-09-02 04:28:35'),
(10, 'admin_favicon', 'cddf5e68-151c-4e54-b002-3107d51ee232.png', '2022-07-25 01:23:51', '2022-09-02 04:28:35'),
(11, 'api_key', '123456', '2022-08-05 02:55:56', '2023-01-20 09:20:02'),
(12, 'app_timezone', 'Asia/Calcutta', '2022-08-26 08:52:55', '2023-01-20 09:20:02'),
(13, 'product_enable', '1', '2022-12-23 04:47:54', '2023-01-20 09:20:02'),
(14, 'whatsapp_contact_enable', '0', '2023-01-24 04:37:34', '2023-01-24 09:20:15'),
(15, 'whatsapp_number', '9565546544', '2023-01-24 04:37:52', '2023-01-24 09:20:15'),
(16, 'licence_active', '0', '2023-03-24 07:58:12', '2023-03-24 07:58:12'),
(17, 'currency', 'INR', '2023-03-29 11:28:06', '2023-03-29 11:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `app_update_setting`
--

CREATE TABLE `app_update_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `app_update_setting`
--

INSERT INTO `app_update_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'update_popup_show', '1', '2022-07-26 03:42:27', '2023-01-20 09:20:33'),
(2, 'new_app_version_code', '1.8', '2022-07-26 03:42:27', '2023-01-20 09:20:33'),
(3, 'description', 'Our free mobile-friendly tool offers a variety of randomly generated keys and passwords you can use to secure any application, service or device. Simply click to copy a password or press the \'Generate\' button for an entirely new set.', '2022-07-26 03:42:27', '2023-01-20 09:20:33'),
(4, 'app_link', 'https://play.google.com/store/apps/', '2022-07-26 03:42:27', '2023-01-20 09:20:33'),
(5, 'cancel_option', '1', '2022-07-26 03:42:27', '2023-01-20 09:20:33');

-- --------------------------------------------------------

--
-- Table structure for table `business`
--

CREATE TABLE `business` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile_no` bigint(20) DEFAULT NULL,
  `website` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `business_category_id` int(11) DEFAULT NULL,
  `is_default` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `business_card`
--

CREATE TABLE `business_card` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `business_card`
--

INSERT INTO `business_card` (`id`, `name`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'vCard1', 'card1.png', 1, '2022-10-22 04:31:25', '2022-10-22 05:40:48'),
(2, 'vCard2', 'card2.png', 1, '2022-11-01 12:56:22', '2022-11-01 12:58:40'),
(3, 'vCard3', 'card3.png', 1, '2022-11-01 12:56:49', '2022-11-01 12:56:49'),
(4, 'vCard4', 'card4.png', 1, '2022-11-01 12:57:04', '2022-11-01 12:57:04'),
(5, 'vCard5', 'card5.png', 1, '2022-11-01 12:57:25', '2022-11-01 12:58:38'),
(6, 'vCard6', 'card6.png', 1, '2022-11-01 12:57:37', '2022-11-01 12:57:37'),
(7, 'vCard7', 'card7.png', 1, '2022-11-01 12:57:50', '2022-11-01 12:57:51'),
(8, 'vCard8', 'card8.png', 1, '2022-11-01 12:58:07', '2022-11-01 12:58:43'),
(9, 'vCard9', 'card9.png', 1, '2022-11-01 12:58:20', '2022-11-01 12:58:20'),
(10, 'vCard10', 'card10.png', 1, '2022-11-01 12:58:33', '2022-11-01 12:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `business_category`
--

CREATE TABLE `business_category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `business_frame`
--

CREATE TABLE `business_frame` (
  `id` int(11) NOT NULL,
  `business_category_id` int(11) DEFAULT NULL,
  `business_sub_category_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `frame_image` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `paid` int(11) DEFAULT NULL,
  `height` int(11) NOT NULL DEFAULT 1024,
  `width` int(11) NOT NULL DEFAULT 1024,
  `image_type` varchar(255) NOT NULL DEFAULT 'square',
  `aspect_ratio` varchar(255) NOT NULL DEFAULT '1:1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `business_sub_category`
--

CREATE TABLE `business_sub_category` (
  `id` int(11) NOT NULL,
  `business_category_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `category_frame`
--

CREATE TABLE `category_frame` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `frame_image` text DEFAULT NULL,
  `paid` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 1024,
  `width` int(11) NOT NULL DEFAULT 1024,
  `image_type` varchar(255) NOT NULL DEFAULT 'square',
  `aspect_ratio` varchar(255) NOT NULL DEFAULT '1:1',
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_code`
--

CREATE TABLE `coupon_code` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `discount` varchar(255) DEFAULT NULL,
  `limit` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_code_store`
--

CREATE TABLE `coupon_code_store` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `custom_frame`
--

CREATE TABLE `custom_frame` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `frame_image` text DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `height` int(11) NOT NULL DEFAULT 1024,
  `width` int(11) NOT NULL DEFAULT 1024,
  `image_type` varchar(255) NOT NULL DEFAULT 'square',
  `aspect_ratio` varchar(255) NOT NULL DEFAULT '1:1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `custom_post`
--

CREATE TABLE `custom_post` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `custom_post_frame`
--

CREATE TABLE `custom_post_frame` (
  `id` int(11) NOT NULL,
  `custom_frame_type` varchar(255) DEFAULT 'simple',
  `custom_post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `zip_name` varchar(255) DEFAULT NULL,
  `frame_image` varchar(255) DEFAULT NULL,
  `paid` int(11) DEFAULT NULL,
  `height` int(11) NOT NULL DEFAULT 1024,
  `width` int(11) NOT NULL DEFAULT 1024,
  `image_type` varchar(255) NOT NULL DEFAULT 'square',
  `aspect_ratio` varchar(255) NOT NULL DEFAULT '1:1',
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `earning_history`
--

CREATE TABLE `earning_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` bigint(20) DEFAULT NULL,
  `amount_type` int(11) DEFAULT NULL,
  `refer_user` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `email_setting`
--

CREATE TABLE `email_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `email_setting`
--

INSERT INTO `email_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'smtp_host', 'smtp.mailtrap.io', '2022-07-27 04:18:10', '2022-10-20 10:41:07'),
(2, 'username', '9e2ac0ac04788f', '2022-07-27 04:18:10', '2022-10-20 10:41:07'),
(3, 'password', 'e4f6fef9cb1512', '2022-07-27 04:18:10', '2022-10-20 10:41:07'),
(4, 'encryption', 'tls', '2022-07-27 04:18:10', '2022-10-20 10:41:07'),
(5, 'port', '2525', '2022-07-27 04:18:10', '2022-10-20 10:41:07');

-- --------------------------------------------------------

--
-- Table structure for table `email_verify`
--

CREATE TABLE `email_verify` (
  `user_id` int(11) DEFAULT NULL,
  `code` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `entry`
--

CREATE TABLE `entry` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile_no` bigint(20) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feature_post`
--

CREATE TABLE `feature_post` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `festival_id` int(11) DEFAULT NULL,
  `custom_id` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `festivals`
--

CREATE TABLE `festivals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `festivals_date` varchar(255) DEFAULT NULL,
  `activation_date` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `festivals_frame`
--

CREATE TABLE `festivals_frame` (
  `id` int(11) NOT NULL,
  `festivals_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `frame_image` text DEFAULT NULL,
  `paid` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 1024,
  `width` int(11) NOT NULL DEFAULT 1024,
  `image_type` varchar(255) NOT NULL DEFAULT 'square',
  `aspect_ratio` varchar(255) NOT NULL DEFAULT '1:1',
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `inquiry`
--

CREATE TABLE `inquiry` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile_no` varchar(255) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2022_08_10_202111_create_permission_tables', 2);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_permissions`
--

INSERT INTO `model_has_permissions` (`permission_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 2),
(1, 'App\\Models\\User', 3),
(2, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(2, 'App\\Models\\User', 3),
(3, 'App\\Models\\User', 1),
(3, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 1),
(4, 'App\\Models\\User', 2),
(4, 'App\\Models\\User', 3),
(5, 'App\\Models\\User', 1),
(5, 'App\\Models\\User', 2),
(5, 'App\\Models\\User', 3),
(6, 'App\\Models\\User', 1),
(6, 'App\\Models\\User', 2),
(6, 'App\\Models\\User', 3),
(7, 'App\\Models\\User', 1),
(7, 'App\\Models\\User', 2),
(7, 'App\\Models\\User', 3),
(8, 'App\\Models\\User', 1),
(8, 'App\\Models\\User', 2),
(8, 'App\\Models\\User', 3),
(9, 'App\\Models\\User', 1),
(9, 'App\\Models\\User', 2),
(9, 'App\\Models\\User', 3),
(10, 'App\\Models\\User', 1),
(10, 'App\\Models\\User', 2),
(10, 'App\\Models\\User', 3),
(11, 'App\\Models\\User', 1),
(11, 'App\\Models\\User', 2),
(11, 'App\\Models\\User', 3),
(12, 'App\\Models\\User', 1),
(12, 'App\\Models\\User', 2),
(12, 'App\\Models\\User', 3),
(14, 'App\\Models\\User', 1),
(14, 'App\\Models\\User', 2),
(14, 'App\\Models\\User', 3),
(15, 'App\\Models\\User', 1),
(15, 'App\\Models\\User', 2),
(15, 'App\\Models\\User', 3),
(16, 'App\\Models\\User', 1),
(16, 'App\\Models\\User', 2),
(16, 'App\\Models\\User', 3),
(17, 'App\\Models\\User', 1),
(17, 'App\\Models\\User', 2),
(17, 'App\\Models\\User', 3),
(18, 'App\\Models\\User', 1),
(18, 'App\\Models\\User', 2),
(18, 'App\\Models\\User', 3),
(19, 'App\\Models\\User', 1),
(19, 'App\\Models\\User', 2),
(19, 'App\\Models\\User', 3),
(20, 'App\\Models\\User', 1),
(20, 'App\\Models\\User', 2),
(20, 'App\\Models\\User', 3),
(22, 'App\\Models\\User', 1),
(22, 'App\\Models\\User', 2),
(22, 'App\\Models\\User', 3),
(23, 'App\\Models\\User', 1),
(23, 'App\\Models\\User', 2),
(23, 'App\\Models\\User', 3),
(24, 'App\\Models\\User', 1),
(24, 'App\\Models\\User', 2),
(24, 'App\\Models\\User', 3),
(25, 'App\\Models\\User', 1),
(25, 'App\\Models\\User', 2),
(25, 'App\\Models\\User', 3),
(26, 'App\\Models\\User', 1),
(26, 'App\\Models\\User', 2),
(26, 'App\\Models\\User', 3),
(27, 'App\\Models\\User', 1),
(27, 'App\\Models\\User', 2),
(27, 'App\\Models\\User', 3),
(28, 'App\\Models\\User', 1),
(28, 'App\\Models\\User', 2),
(28, 'App\\Models\\User', 3),
(29, 'App\\Models\\User', 1),
(29, 'App\\Models\\User', 2),
(29, 'App\\Models\\User', 3),
(30, 'App\\Models\\User', 1),
(30, 'App\\Models\\User', 2),
(30, 'App\\Models\\User', 3),
(31, 'App\\Models\\User', 1),
(31, 'App\\Models\\User', 2),
(31, 'App\\Models\\User', 3),
(32, 'App\\Models\\User', 1),
(32, 'App\\Models\\User', 2),
(32, 'App\\Models\\User', 3),
(33, 'App\\Models\\User', 1),
(33, 'App\\Models\\User', 2),
(33, 'App\\Models\\User', 3),
(34, 'App\\Models\\User', 1),
(34, 'App\\Models\\User', 2),
(34, 'App\\Models\\User', 3),
(35, 'App\\Models\\User', 1),
(35, 'App\\Models\\User', 2),
(35, 'App\\Models\\User', 3),
(36, 'App\\Models\\User', 1),
(36, 'App\\Models\\User', 2),
(36, 'App\\Models\\User', 3),
(37, 'App\\Models\\User', 1),
(37, 'App\\Models\\User', 2),
(37, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notification_setting`
--

CREATE TABLE `notification_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notification_setting`
--

INSERT INTO `notification_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'one_signal_app_id', NULL, '2022-07-26 01:48:19', '2022-07-28 04:52:06'),
(2, 'one_signal_rest_key', NULL, '2022-07-26 01:48:19', '2022-07-28 04:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `offer`
--

CREATE TABLE `offer` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `other_setting`
--

CREATE TABLE `other_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `other_setting`
--

INSERT INTO `other_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'privacy_policy', NULL, '2022-07-26 03:56:26', '2022-07-27 06:44:57'),
(2, 'refund_policy', NULL, '2022-07-26 03:56:32', '2022-07-26 04:09:59'),
(3, 'terms_condition', NULL, '2022-07-26 03:56:35', '2022-07-26 04:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_setting`
--

CREATE TABLE `payment_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payment_setting`
--

INSERT INTO `payment_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'razorpay_key_id', NULL, '2022-07-26 02:00:23', '2023-03-23 05:11:22'),
(2, 'razorpay_key_secret', NULL, '2022-07-26 02:00:23', '2023-03-23 05:11:22'),
(3, 'cashfree_key_id', NULL, '2022-12-05 09:41:10', '2023-03-23 05:11:22'),
(4, 'cashfree_key_secret', NULL, '2022-12-05 09:41:10', '2023-03-23 05:11:22'),
(5, 'razorpay_enable', '1', '2022-12-23 08:57:06', '2023-03-23 05:11:22'),
(6, 'cashfree_enable', '1', '2023-03-23 04:56:06', '2023-03-23 05:11:22'),
(7, 'offline_enable', '1', '2023-03-23 04:56:30', '2023-03-23 05:11:22'),
(8, 'offline_payment_details', 'Bank Name: SBI\r\nBank Account NO: 968574859688\r\nIFSC Code: SBIN012555\r\nBranch Code: SBI1235\r\nBranch Address: Surat', '2023-03-23 04:56:48', '2023-03-23 05:11:22'),
(9, 'stripe_enable', '1', '2023-03-29 04:47:45', '2023-03-30 12:53:57'),
(10, 'stripe_publishable_Key', NULL, '2023-03-29 04:48:00', '2023-03-30 12:53:57'),
(11, 'stripe_secret_key', NULL, '2023-03-29 04:48:24', '2023-03-30 12:53:57'),
(12, 'paytm_enable', '1', '2023-03-29 05:27:21', '2023-03-30 12:53:57'),
(13, 'paytm_merchant_id', NULL, '2023-03-29 05:28:28', '2023-03-30 12:53:57'),
(14, 'paytm_merchant_key', NULL, '2023-03-29 05:28:50', '2023-03-30 12:53:57'),
(15, 'cashfree_type', 'Test', '2023-03-30 10:01:24', '2023-03-30 12:53:57'),
(16, 'paytm_type', 'Test', '2023-03-30 12:52:46', '2023-03-30 12:53:57'),
(17, 'phonepe_enable', '0', '2023-03-29 05:27:21', '2023-03-30 12:53:57'),
(18, 'phonepe_merchant_id', NULL, '2023-03-29 05:28:28', '2023-03-30 12:53:57'),
(19, 'phonepe_salt_key', NULL, '2023-03-29 05:28:50', '2023-03-30 12:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Language', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(2, 'Category', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(3, 'CategoryFrame', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(4, 'Festival', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(5, 'FestivalFrame', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(6, 'CustomCategory', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(7, 'CustomFrame', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(8, 'News', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(9, 'Stories', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(10, 'Users', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(11, 'Businesses', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(12, 'SubscriptionPlan', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(13, 'Transactions', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(14, 'Entry', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(15, 'Subject', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(16, 'Notification', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(17, 'UserRoleManagement', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(18, 'Settings', 'web', '2022-08-12 07:53:01', '2022-08-12 07:53:01'),
(19, 'BusinessCategory', 'web', '2022-08-13 06:45:47', '2022-08-13 06:45:47'),
(20, 'BusinessFrame', 'web', '2022-08-13 06:45:47', '2022-08-13 06:45:47'),
(21, 'Financial Statistics', 'web', '2022-08-13 07:38:11', '2022-08-13 07:38:11'),
(22, 'FinancialStatistics', 'web', '2022-08-13 07:38:39', '2022-08-13 07:38:39'),
(23, 'Video', 'web', '2022-10-11 04:58:42', '2022-10-11 04:58:42'),
(24, 'CouponCode', 'web', '2022-10-11 04:58:42', '2022-10-11 04:58:42'),
(25, 'BusinessCard', 'web', '2022-10-22 05:53:26', '2022-10-22 05:53:26'),
(26, 'StickerCategory', 'web', '2022-11-03 11:40:45', '2022-11-03 11:40:45'),
(27, 'Sticker', 'web', '2022-11-03 11:40:45', '2022-11-03 11:40:45'),
(28, 'Offer', 'web', '2022-11-04 06:42:14', '2022-11-04 06:42:14'),
(29, 'ProductCategory', 'web', '2022-12-05 11:06:12', '2022-12-05 11:06:12'),
(30, 'Product', 'web', '2022-12-05 11:06:12', '2022-12-05 11:06:12'),
(31, 'Inquiry', 'web', '2022-12-05 11:06:12', '2022-12-05 11:06:12'),
(32, 'PosterMaker', 'web', '2022-12-09 05:41:39', '2022-12-09 05:41:39'),
(33, 'ReferralSystem', 'web', '2022-12-10 08:49:51', '2022-12-10 08:49:51'),
(34, 'WithdrawRequest', 'web', '2022-12-23 07:24:15', '2022-12-23 07:24:15'),
(35, 'BusinessSubCategory', 'web', '2023-01-18 07:53:34', '2023-01-18 07:53:34'),
(36, 'PosterCategory', 'web', '2023-01-22 11:39:08', '2023-01-22 11:39:08'),
(37, 'WhatsAppMessage', 'web', '2023-03-24 13:10:37', '2023-03-24 13:10:37');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poster_category`
--

CREATE TABLE `poster_category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `poster_maker`
--

CREATE TABLE `poster_maker` (
  `id` int(11) NOT NULL,
  `poster_category_id` int(11) DEFAULT NULL,
  `template_type` varchar(255) DEFAULT NULL,
  `zip_name` varchar(255) DEFAULT NULL,
  `post_thumb` varchar(255) DEFAULT NULL,
  `paid` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `discount_price` varchar(255) DEFAULT NULL,
  `product_category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `referral_register`
--

CREATE TABLE `referral_register` (
  `id` int(11) NOT NULL,
  `referral_code` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subscription` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `referral_system`
--

CREATE TABLE `referral_system` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `referral_system`
--

INSERT INTO `referral_system` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'register_point', '100', '2022-12-10 04:58:26', '2022-12-23 04:53:43'),
(2, 'subscription_point', '50', '2022-12-10 04:58:26', '2022-12-23 04:53:43'),
(3, 'referral_system_enable', '1', '2022-12-22 06:06:27', '2022-12-23 04:53:43'),
(4, 'withdrawal_limit', '200', '2022-12-22 06:06:54', '2022-12-23 04:53:43');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2022-08-12 07:57:11', '2022-08-12 07:57:11');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(14, 1),
(15, 1),
(16, 1),
(18, 1),
(19, 1),
(20, 1),
(25, 1),
(28, 1),
(29, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sticker`
--

CREATE TABLE `sticker` (
  `id` int(11) NOT NULL,
  `sticker_category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sticker_category`
--

CREATE TABLE `sticker_category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storage_setting`
--

CREATE TABLE `storage_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `storage_setting`
--

INSERT INTO `storage_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'storage', 'local', '2023-01-03 08:34:37', '2023-03-25 09:16:54'),
(2, 'digitalOcean_space_name', NULL, '2023-01-03 08:34:58', '2023-03-25 09:16:54'),
(3, 'digitalOcean_key', NULL, '2023-01-03 08:35:25', '2023-03-25 09:16:54'),
(4, 'digitalOcean_secret', NULL, '2023-01-03 08:35:40', '2023-03-25 09:16:54'),
(5, 'digitalOcean_bucket_region', NULL, '2023-01-03 08:35:56', '2023-03-25 09:16:54'),
(6, 'digitalOcean_endpoint', NULL, '2023-01-03 08:36:10', '2023-03-25 09:16:54');

-- --------------------------------------------------------

--
-- Table structure for table `story`
--

CREATE TABLE `story` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `story_type` varchar(255) DEFAULT NULL,
  `festival_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `custom_category_id` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `external_link_title` varchar(255) DEFAULT NULL,
  `external_link` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plan`
--

CREATE TABLE `subscription_plan` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `duration_type` varchar(255) DEFAULT NULL,
  `plan_price` float(10,2) DEFAULT NULL,
  `discount_price` float(10,2) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `plan_detail` text DEFAULT NULL,
  `google_product_enable` int(11) DEFAULT NULL,
  `google_product_id` varchar(255) DEFAULT NULL,
  `business_limit` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `timezones`
--

CREATE TABLE `timezones` (
  `id` int(11) NOT NULL,
  `name` varchar(44) DEFAULT NULL,
  `timezone` varchar(30) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `timezones`
--

INSERT INTO `timezones` (`id`, `name`, `timezone`) VALUES
(1, '(GMT-11:00) Pacific/Midway ', 'Pacific/Midway'),
(2, '(GMT-11:00) Pacific/Samoa', 'Pacific/Samoa'),
(3, '(GMT-10:00) Pacific/Honolulu', 'Pacific/Honolulu'),
(4, '(GMT-09:00) America/Anchorage', 'America/Anchorage'),
(5, '(GMT-08:00) America/Los_Angeles', 'America/Los_Angeles'),
(6, '(GMT-08:00) America/Tijuana', 'America/Tijuana'),
(7, '(GMT-07:00) America/Chihuahua', 'America/Chihuahua'),
(9, '(GMT-07:00) America/Mazatlan', 'America/Mazatlan'),
(10, '(GMT-07:00) America/Denver', 'America/Denver'),
(11, '(GMT-06:00) America/Managua ', 'America/Managua'),
(12, '(GMT-06:00) America/Chicago', 'America/Chicago'),
(13, '(GMT-06:00) America/Mexico_City', 'America/Mexico_City'),
(15, '(GMT-06:00) America/Monterrey', 'America/Monterrey'),
(16, '(GMT-05:00) America/Bogota', 'America/Bogota'),
(17, '(GMT-05:00) America/New_York', 'America/New_York'),
(18, '(GMT-05:00) America/Lima', 'America/Lima'),
(20, '(GMT-04:00) Canada/Atlantic', 'Canada/Atlantic'),
(21, '(GMT-04:30) America/Caracas', 'America/Caracas'),
(22, '(GMT-04:00) America/La_Paz', 'America/La_Paz'),
(23, '(GMT-04:00) America/Santiago', 'America/Santiago'),
(24, '(GMT-03:30) America/St_Johns', 'America/St_Johns'),
(25, '(GMT-03:00) America/Sao_Paulo', 'America/Sao_Paulo'),
(26, '(GMT-03:00) America/Argentina/Buenos_Aires', 'America/Argentina/Buenos_Aires'),
(28, '(GMT-03:00) America/Godthab', 'America/Godthab'),
(29, '(GMT-02:00) America/Noronha ', 'America/Noronha'),
(30, '(GMT-01:00) Atlantic/Azores', 'Atlantic/Azores'),
(31, '(GMT-01:00) Atlantic/Cape_Verde ', 'Atlantic/Cape_Verde'),
(32, '(GMT+00:00) Africa/Casablanca', 'Africa/Casablanca'),
(33, '(GMT+00:00) Europe/London', 'Europe/London'),
(34, '(GMT+00:00) Europe/Dublin ', 'Europe/Dublin'),
(35, '(GMT+00:00) Europe/Lisbon', 'Europe/Lisbon'),
(37, '(GMT+00:00) Africa/Monrovia', 'Africa/Monrovia'),
(38, '(GMT+00:00) UTC ', 'UTC'),
(39, '(GMT+01:00) Europe/Amsterdam', 'Europe/Amsterdam'),
(40, '(GMT+01:00) Europe/Belgrade', 'Europe/Belgrade'),
(41, '(GMT+01:00) Europe/Berlin', 'Europe/Berlin'),
(43, '(GMT+01:00) Europe/Bratislava', 'Europe/Bratislava'),
(44, '(GMT+01:00) Europe/Brussels', 'Europe/Brussels'),
(45, '(GMT+01:00) Europe/Budapest', 'Europe/Budapest'),
(46, '(GMT+01:00) Europe/Copenhagen', 'Europe/Copenhagen'),
(47, '(GMT+01:00) Europe/Ljubljana', 'Europe/Ljubljana'),
(48, '(GMT+01:00) Europe/Madrid', 'Europe/Madrid'),
(49, '(GMT+01:00) Europe/Paris', 'Europe/Paris'),
(50, '(GMT+01:00) Europe/Prague', 'Europe/Prague'),
(51, '(GMT+01:00) Europe/Rome', 'Europe/Rome'),
(52, '(GMT+01:00) Europe/Sarajevo', 'Europe/Sarajevo'),
(53, '(GMT+01:00) Europe/Skopje', 'Europe/Skopje'),
(54, '(GMT+01:00) Europe/Stockholm', 'Europe/Stockholm'),
(55, '(GMT+01:00) Europe/Vienna', 'Europe/Vienna'),
(56, '(GMT+01:00) Europe/Warsaw', 'Europe/Warsaw'),
(57, '(GMT+01:00) Africa/Lagos', 'Africa/Lagos'),
(58, '(GMT+01:00) Europe/Zagreb', 'Europe/Zagreb'),
(59, '(GMT+02:00) Europe/Athens', 'Europe/Athens'),
(60, '(GMT+02:00) Europe/Bucharest', 'Europe/Bucharest'),
(61, '(GMT+02:00) Africa/Cairo', 'Africa/Cairo'),
(62, '(GMT+02:00) Africa/Harare', 'Africa/Harare'),
(63, '(GMT+02:00) Europe/Helsinki', 'Europe/Helsinki'),
(64, '(GMT+02:00) Europe/Istanbul', 'Europe/Istanbul'),
(65, '(GMT+02:00) Asia/Jerusalem', 'Asia/Jerusalem'),
(67, '(GMT+02:00) Africa/Johannesburg', 'Africa/Johannesburg'),
(68, '(GMT+02:00) Europe/Riga', 'Europe/Riga'),
(69, '(GMT+02:00) Europe/Sofia', 'Europe/Sofia'),
(70, '(GMT+02:00) Europe/Tallinn', 'Europe/Tallinn'),
(71, '(GMT+02:00) Europe/Vilnius', 'Europe/Vilnius'),
(72, '(GMT+03:00) Asia/Baghdad', 'Asia/Baghdad'),
(73, '(GMT+03:00) Asia/Kuwait', 'Asia/Kuwait'),
(74, '(GMT+03:00) Europe/Minsk', 'Europe/Minsk'),
(75, '(GMT+03:00) Africa/Nairobi', 'Africa/Nairobi'),
(76, '(GMT+03:00) Asia/Riyadh', 'Asia/Riyadh'),
(77, '(GMT+03:00) Europe/Volgograd', 'Europe/Volgograd'),
(78, '(GMT+03:30) Asia/Tehran', 'Asia/Tehran'),
(79, '(GMT+04:00) Asia/Muscat', 'Asia/Muscat'),
(80, '(GMT+04:00) Asia/Baku', 'Asia/Baku'),
(81, '(GMT+04:00) Europe/Moscow', 'Europe/Moscow'),
(82, '(GMT+04:00) Asia/Muscat', 'Asia/Muscat'),
(84, '(GMT+04:00) Asia/Tbilisi', 'Asia/Tbilisi'),
(85, '(GMT+04:00) Asia/Yerevan', 'Asia/Yerevan'),
(86, '(GMT+04:30) Asia/Kabul', 'Asia/Kabul'),
(87, '(GMT+05:00) Asia/Karachi', 'Asia/Karachi'),
(89, '(GMT+05:00) Asia/Tashkent', 'Asia/Tashkent'),
(90, '(GMT+05:30) Asia/Calcutta', 'Asia/Calcutta'),
(95, '(GMT+05:45) Asia/Katmandu', 'Asia/Katmandu'),
(96, '(GMT+06:00) Asia/Almaty', 'Asia/Almaty'),
(97, '(GMT+06:00) Asia/Dhaka', 'Asia/Dhaka'),
(99, '(GMT+06:00) Asia/Yekaterinburg', 'Asia/Yekaterinburg'),
(100, '(GMT+06:30) Asia/Rangoon', 'Asia/Rangoon'),
(101, '(GMT+07:00) Asia/Bangkok', 'Asia/Bangkok'),
(103, '(GMT+07:00) Asia/Jakarta', 'Asia/Jakarta'),
(104, '(GMT+07:00) Asia/Novosibirsk', 'Asia/Novosibirsk'),
(106, '(GMT+08:00) Asia/Chongqing', 'Asia/Chongqing'),
(107, '(GMT+08:00) Asia/Hong_Kong ', 'Asia/Hong_Kong'),
(108, '(GMT+08:00) Asia/Krasnoyarsk', 'Asia/Krasnoyarsk'),
(109, '(GMT+08:00) Asia/Kuala_Lumpur', 'Asia/Kuala_Lumpur'),
(110, '(GMT+08:00) Australia/Perth', 'Australia/Perth'),
(111, '(GMT+08:00) Asia/Singapore', 'Asia/Singapore'),
(112, '(GMT+08:00) Asia/Taipei', 'Asia/Taipei'),
(113, '(GMT+08:00) Asia/Ulan_Bator', 'Asia/Ulan_Bator'),
(114, '(GMT+08:00) Asia/Urumqi', 'Asia/Urumqi'),
(115, '(GMT+09:00) Asia/Irkutsk', 'Asia/Irkutsk'),
(116, '(GMT+09:00) Asia/Tokyo', 'Asia/Tokyo'),
(118, '(GMT+09:00) Asia/Seoul', 'Asia/Seoul'),
(120, '(GMT+09:30) Australia/Adelaide', 'Australia/Adelaide'),
(121, '(GMT+09:30) Australia/Darwin', 'Australia/Darwin'),
(122, '(GMT+10:00) Australia/Brisbane', 'Australia/Brisbane'),
(123, '(GMT+10:00) Australia/Canberra', 'Australia/Canberra'),
(124, '(GMT+10:00) Pacific/Guam', 'Pacific/Guam'),
(125, '(GMT+10:00) Australia/Hobart', 'Australia/Hobart'),
(126, '(GMT+10:00) Australia/Melbourne', 'Australia/Melbourne'),
(127, '(GMT+10:00) Pacific/Port_Moresby', 'Pacific/Port_Moresby'),
(128, '(GMT+10:00) Australia/Sydney', 'Australia/Sydney'),
(129, '(GMT+10:00) Asia/Yakutsk', 'Asia/Yakutsk'),
(130, '(GMT+11:00) Asia/Vladivostok', 'Asia/Vladivostok'),
(131, '(GMT+12:00) Pacific/Auckland', 'Pacific/Auckland'),
(132, '(GMT+12:00) Pacific/Fiji', 'Pacific/Fiji'),
(133, '(GMT+12:00) Pacific/Kwajalein', 'Pacific/Kwajalein'),
(134, '(GMT+12:00) Asia/Kamchatka', 'Asia/Kamchatka'),
(138, '(GMT+12:00) Asia/Magadan', 'Asia/Magadan'),
(139, '(GMT+12:00) Pacific/Auckland', 'Pacific/Auckland'),
(140, '(GMT+13:00) Pacific/Tongatapu', 'Pacific/Tongatapu');

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `total_paid` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Completed',
  `payment_receipt` varchar(255) DEFAULT NULL,
  `referral_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` bigint(20) DEFAULT NULL,
  `mobile_no` bigint(20) DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_type` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_subscribe` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `subscription_start_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscription_end_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_limit` int(11) DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_balance` bigint(20) NOT NULL DEFAULT 0,
  `total_balance` bigint(20) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `api_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `mobile_no`, `image`, `user_type`, `login_type`, `is_subscribe`, `subscription_id`, `subscription_start_date`, `subscription_end_date`, `business_limit`, `email_verified_at`, `password`, `remember_token`, `referral_code`, `current_balance`, `total_balance`, `status`, `api_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'sunil@gmail.com', 919685747485, '3c992df7-41f8-4db3-9b04-1726185b362e.png', 'Super Admin', 'normal', NULL, NULL, NULL, NULL, 1, '2022-07-07 11:13:40', '$2y$10$E4BvbkM83IuRXewhkONXiOZ3VujE0L6GDCj7KCUoaHuCFF9UIHF66', 'dL1ZQLMhECsiXuGEQ6Sz2oTLwv06wAvf2EuEHieNLIsa2J4stC9bokFqmrQ8', '0CE6UWG4', 0, 0, 1, NULL, '2022-07-07 11:13:40', '2023-03-24 13:10:47'),
(2, 'Demo', 'demo@gmail.com', 9685747485, '911065a3-c074-43db-9fae-c4f94a5d754a.png', 'Demo', 'normal', NULL, NULL, NULL, NULL, 1, '2022-08-29 10:31:54', '$2y$10$LIoFqXHyyKv0bdjlG.kxy.D9AS5XRIMNP10AlDFGUE3p88U0Gikdy', NULL, 'JDWESNWC', 0, 0, 1, 'plG1rZsLLZPhQkfbC7zXoFxmzOLBrJQ5eOOVPRHyFlbhPJPfRGAizWiFW68Z', '2022-08-29 10:31:54', '2022-12-10 09:04:13'),
(3, 'Demo User', 'demo2023@gmail.com', 334567890, '911065a3-c074-43db-9fae-c4f94a5d754a.png', 'Super Admin', 'normal', NULL, NULL, NULL, NULL, 1, '2023-01-29 10:07:11', '$2y$10$SS1wPvCI8MsMEQmyVQBMoOMIcyufEbTCVqsJeuJAx0BGZ7JVK3Wxe', NULL, 'YDT9OMOHEQ', 0, 0, 1, 'o8sWhQlHyO5RjjXG1yAU1me3JWuO1J0icZ9vZC8exmpqQEAX766qHAL3m3KA', '2023-01-29 10:07:11', '2023-01-29 10:09:55');

-- --------------------------------------------------------

--
-- Table structure for table `video`
--

CREATE TABLE `video` (
  `id` int(11) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `festival_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `business_category_id` int(11) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `paid` int(11) DEFAULT 1,
  `language_id` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_message`
--

CREATE TABLE `whatsapp_message` (
  `id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `btn1` varchar(255) DEFAULT NULL,
  `btn1_type` varchar(255) DEFAULT NULL,
  `btn1_value` varchar(255) DEFAULT NULL,
  `btn2` varchar(255) DEFAULT NULL,
  `btn2_type` varchar(255) DEFAULT NULL,
  `btn2_value` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `footer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_setting`
--

CREATE TABLE `whatsapp_setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `whatsapp_setting`
--

INSERT INTO `whatsapp_setting` (`id`, `key_name`, `key_value`, `created_at`, `updated_at`) VALUES
(1, 'api_key', NULL, '2023-03-23 07:08:06', '2023-03-24 06:00:21'),
(2, 'instance_id', NULL, '2023-03-23 07:08:25', '2023-03-24 06:00:21'),
(3, 'whatsapp_auth_enable', '1', '2023-04-27 07:08:06', '2023-04-27 06:00:21'),
(4, 'whatsapp_otp_message', "[OTP] is your One Time Password(OTP) to login to your account on BrandKit. Please enter the OTP to proceed.", '2023-04-27 07:08:06', '2023-04-27 06:00:21');

-- --------------------------------------------------------

--
-- Table structure for table `withdraw_request`
--

CREATE TABLE `withdraw_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL,
  `withdraw_amount` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads_setting`
--
ALTER TABLE `ads_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `api_setting`
--
ALTER TABLE `api_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_setting`
--
ALTER TABLE `app_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `app_update_setting`
--
ALTER TABLE `app_update_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business`
--
ALTER TABLE `business`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_card`
--
ALTER TABLE `business_card`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_category`
--
ALTER TABLE `business_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_frame`
--
ALTER TABLE `business_frame`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_sub_category`
--
ALTER TABLE `business_sub_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category_frame`
--
ALTER TABLE `category_frame`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupon_code`
--
ALTER TABLE `coupon_code`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupon_code_store`
--
ALTER TABLE `coupon_code_store`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_frame`
--
ALTER TABLE `custom_frame`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_post`
--
ALTER TABLE `custom_post`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_post_frame`
--
ALTER TABLE `custom_post_frame`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `earning_history`
--
ALTER TABLE `earning_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_setting`
--
ALTER TABLE `email_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `entry`
--
ALTER TABLE `entry`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feature_post`
--
ALTER TABLE `feature_post`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `festivals`
--
ALTER TABLE `festivals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `festivals_frame`
--
ALTER TABLE `festivals_frame`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiry`
--
ALTER TABLE `inquiry`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_setting`
--
ALTER TABLE `notification_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offer`
--
ALTER TABLE `offer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `other_setting`
--
ALTER TABLE `other_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `payment_setting`
--
ALTER TABLE `payment_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `poster_category`
--
ALTER TABLE `poster_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `poster_maker`
--
ALTER TABLE `poster_maker`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `referral_register`
--
ALTER TABLE `referral_register`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `referral_system`
--
ALTER TABLE `referral_system`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sticker`
--
ALTER TABLE `sticker`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sticker_category`
--
ALTER TABLE `sticker_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storage_setting`
--
ALTER TABLE `storage_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `story`
--
ALTER TABLE `story`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_plan`
--
ALTER TABLE `subscription_plan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timezones`
--
ALTER TABLE `timezones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `video`
--
ALTER TABLE `video`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_message`
--
ALTER TABLE `whatsapp_message`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_setting`
--
ALTER TABLE `whatsapp_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdraw_request`
--
ALTER TABLE `withdraw_request`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads_setting`
--
ALTER TABLE `ads_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `api_setting`
--
ALTER TABLE `api_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `app_setting`
--
ALTER TABLE `app_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `app_update_setting`
--
ALTER TABLE `app_update_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `business`
--
ALTER TABLE `business`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `business_card`
--
ALTER TABLE `business_card`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `business_category`
--
ALTER TABLE `business_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `business_frame`
--
ALTER TABLE `business_frame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `business_sub_category`
--
ALTER TABLE `business_sub_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `category_frame`
--
ALTER TABLE `category_frame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `coupon_code`
--
ALTER TABLE `coupon_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `coupon_code_store`
--
ALTER TABLE `coupon_code_store`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_frame`
--
ALTER TABLE `custom_frame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `custom_post`
--
ALTER TABLE `custom_post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `custom_post_frame`
--
ALTER TABLE `custom_post_frame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `earning_history`
--
ALTER TABLE `earning_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `email_setting`
--
ALTER TABLE `email_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `entry`
--
ALTER TABLE `entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feature_post`
--
ALTER TABLE `feature_post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `festivals`
--
ALTER TABLE `festivals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `festivals_frame`
--
ALTER TABLE `festivals_frame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `inquiry`
--
ALTER TABLE `inquiry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `language`
--
ALTER TABLE `language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `notification_setting`
--
ALTER TABLE `notification_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `offer`
--
ALTER TABLE `offer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `other_setting`
--
ALTER TABLE `other_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_setting`
--
ALTER TABLE `payment_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poster_category`
--
ALTER TABLE `poster_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `poster_maker`
--
ALTER TABLE `poster_maker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `product_category`
--
ALTER TABLE `product_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `referral_register`
--
ALTER TABLE `referral_register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `referral_system`
--
ALTER TABLE `referral_system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sticker`
--
ALTER TABLE `sticker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `sticker_category`
--
ALTER TABLE `sticker_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `storage_setting`
--
ALTER TABLE `storage_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `story`
--
ALTER TABLE `story`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `subscription_plan`
--
ALTER TABLE `subscription_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `timezones`
--
ALTER TABLE `timezones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `video`
--
ALTER TABLE `video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `whatsapp_message`
--
ALTER TABLE `whatsapp_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `whatsapp_setting`
--
ALTER TABLE `whatsapp_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `withdraw_request`
--
ALTER TABLE `withdraw_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
