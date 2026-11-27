-- Effepi Master: struttura iniziale del database (MySQL/MariaDB)
-- Da lanciare in phpMyAdmin su un database vuoto. Generato dalle migrazioni.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 0001_01_01_000000_create_users_table.php
create table `users` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `username` varchar(255) not null, `email` varchar(255) not null, `role` varchar(20) not null, `must_change_password` tinyint(1) not null default '0', `active` tinyint(1) not null default '1', `last_login_at` timestamp null, `email_verified_at` timestamp null, `password` varchar(255) not null, `remember_token` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `users` add unique `users_username_unique`(`username`);
alter table `users` add unique `users_email_unique`(`email`);
alter table `users` add index `users_role_index`(`role`);
create table `password_reset_tokens` (`email` varchar(255) not null, `token` varchar(255) not null, `created_at` timestamp null, primary key (`email`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
create table `sessions` (`id` varchar(255) not null, `user_id` bigint unsigned null, `ip_address` varchar(45) null, `user_agent` text null, `payload` longtext not null, `last_activity` int not null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `sessions` add index `sessions_user_id_index`(`user_id`);
alter table `sessions` add index `sessions_last_activity_index`(`last_activity`);

-- 0001_01_01_000001_create_cache_table.php
create table `cache` (`key` varchar(255) not null, `value` mediumtext not null, `expiration` bigint not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `cache` add index `cache_expiration_index`(`expiration`);
create table `cache_locks` (`key` varchar(255) not null, `owner` varchar(255) not null, `expiration` bigint not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `cache_locks` add index `cache_locks_expiration_index`(`expiration`);

-- 2026_11_12_000001_create_network_tables.php
create table `agents` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `phone` varchar(40) null, `region` varchar(60) null, `zones` varchar(255) null, `experience` text null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `agents` add constraint `agents_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `agents` add unique `agents_user_id_unique`(`user_id`);
create table `dealers` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned null, `agent_id` bigint unsigned null, `business_name` varchar(255) not null, `contact_name` varchar(255) null, `phone` varchar(40) null, `email` varchar(255) null, `activity_type` varchar(80) null, `region` varchar(60) null, `municipality` varchar(120) null, `external_code` varchar(60) null, `vat_number` varchar(20) null, `sdi_code` varchar(10) null, `iban` varchar(40) null, `pec` varchar(255) null, `legal_address` varchar(255) null, `operating_address` varchar(255) null, `legal_rep_mobile` varchar(40) null, `billing_regime` varchar(30) null, `registry_completed_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `dealers` add constraint `dealers_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;
alter table `dealers` add constraint `dealers_agent_id_foreign` foreign key (`agent_id`) references `agents` (`id`) on delete set null;
alter table `dealers` add unique `dealers_user_id_unique`(`user_id`);
alter table `dealers` add unique `dealers_external_code_unique`(`external_code`);
create table `registration_requests` (`id` bigint unsigned not null auto_increment primary key, `profile` varchar(10) not null, `status` varchar(20) not null default 'in_attesa', `name` varchar(255) not null, `business_name` varchar(255) null, `phone` varchar(40) not null, `email` varchar(255) not null, `activity_type` varchar(80) null, `region` varchar(60) not null, `municipality` varchar(120) null, `zones` varchar(255) null, `experience` text null, `meeting_at` datetime not null, `meeting_manager_id` bigint unsigned null, `google_event_id` varchar(255) null, `meet_url` varchar(255) null, `privacy_accepted_at` timestamp not null, `decided_by` bigint unsigned null, `decided_at` timestamp null, `user_id` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `registration_requests` add constraint `registration_requests_meeting_manager_id_foreign` foreign key (`meeting_manager_id`) references `users` (`id`) on delete set null;
alter table `registration_requests` add constraint `registration_requests_decided_by_foreign` foreign key (`decided_by`) references `users` (`id`) on delete set null;
alter table `registration_requests` add constraint `registration_requests_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;
alter table `registration_requests` add index `registration_requests_meeting_manager_id_meeting_at_index`(`meeting_manager_id`, `meeting_at`);
alter table `registration_requests` add index `registration_requests_profile_index`(`profile`);
alter table `registration_requests` add index `registration_requests_status_index`(`status`);
create table `registration_messages` (`id` bigint unsigned not null auto_increment primary key, `registration_request_id` bigint unsigned not null, `manager_id` bigint unsigned null, `body` text not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `registration_messages` add constraint `registration_messages_registration_request_id_foreign` foreign key (`registration_request_id`) references `registration_requests` (`id`) on delete cascade;
alter table `registration_messages` add constraint `registration_messages_manager_id_foreign` foreign key (`manager_id`) references `users` (`id`) on delete set null;

-- 2026_11_12_000002_create_promo_and_support_tables.php
create table `promos` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `photo_path` varchar(255) null, `price` decimal(10, 2) not null, `duration_hours` smallint unsigned not null, `starts_at` datetime not null, `ends_at` datetime not null, `created_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `promos` add constraint `promos_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete set null;
alter table `promos` add index `promos_ends_at_index`(`ends_at`);
create table `showcase_products` (`id` bigint unsigned not null auto_increment primary key, `position` tinyint unsigned not null, `name` varchar(255) null, `url` varchar(500) null, `photo_path` varchar(255) null, `updated_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `showcase_products` add constraint `showcase_products_updated_by_foreign` foreign key (`updated_by`) references `users` (`id`) on delete set null;
alter table `showcase_products` add unique `showcase_products_position_unique`(`position`);
create table `settings` (`key` varchar(100) not null, `value` text null, `created_at` timestamp null, `updated_at` timestamp null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
create table `activity_logs` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned null, `action` varchar(80) not null, `subject_type` varchar(80) null, `subject_id` bigint unsigned null, `data` json null, `created_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `activity_logs` add constraint `activity_logs_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;
alter table `activity_logs` add index `activity_logs_action_index`(`action`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_users_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('2026_11_12_000001_create_network_tables', 1),
  ('2026_11_12_000002_create_promo_and_support_tables', 1);
