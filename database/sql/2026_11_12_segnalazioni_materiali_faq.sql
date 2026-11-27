-- Effepi Master: segnalazioni, materiali e FAQ (MySQL/MariaDB)
-- Da lanciare in phpMyAdmin dopo 2026_11_12_ordini_e_anagrafica.sql.

SET NAMES utf8mb4;

create table `tickets` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `dealer_id` bigint unsigned null, `agent_id` bigint unsigned null, `category` varchar(30) not null, `subject` varchar(255) not null, `status` varchar(20) not null default 'open', `last_message_at` datetime not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `tickets` add constraint `tickets_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `tickets` add constraint `tickets_dealer_id_foreign` foreign key (`dealer_id`) references `dealers` (`id`) on delete set null;
alter table `tickets` add constraint `tickets_agent_id_foreign` foreign key (`agent_id`) references `agents` (`id`) on delete set null;
alter table `tickets` add index `tickets_status_index`(`status`);
alter table `tickets` add index `tickets_last_message_at_index`(`last_message_at`);
create table `ticket_messages` (`id` bigint unsigned not null auto_increment primary key, `ticket_id` bigint unsigned not null, `user_id` bigint unsigned null, `is_staff` tinyint(1) not null default '0', `body` text not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `ticket_messages` add constraint `ticket_messages_ticket_id_foreign` foreign key (`ticket_id`) references `tickets` (`id`) on delete cascade;
alter table `ticket_messages` add constraint `ticket_messages_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;
create table `resources` (`id` bigint unsigned not null auto_increment primary key, `kind` varchar(20) not null, `title` varchar(255) not null, `description` varchar(500) null, `file_path` varchar(255) null, `url` varchar(500) null, `position` smallint unsigned not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `resources` add index `resources_kind_index`(`kind`);
create table `faqs` (`id` bigint unsigned not null auto_increment primary key, `question` varchar(255) not null, `answer` text not null, `keywords` varchar(255) null, `position` smallint unsigned not null default '0', `active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_12_000004_create_tickets_and_content_tables', 3);
