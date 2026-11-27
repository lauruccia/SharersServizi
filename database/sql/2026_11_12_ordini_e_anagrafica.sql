-- Effepi Master: ordini e anagrafica dealer (MySQL/MariaDB)
-- Da lanciare in phpMyAdmin dopo 2026_11_12_struttura_iniziale.sql.

SET NAMES utf8mb4;

alter table `dealers`
  add `registry_submitted_at` timestamp null,
  add `registry_submitted_by` bigint unsigned null,
  add `expected_sim` int unsigned null,
  add `expected_energy` int unsigned null,
  add `expected_fiber` int unsigned null,
  add `privacy_confirmed_at` timestamp null;
alter table `dealers` add constraint `dealers_registry_submitted_by_foreign` foreign key (`registry_submitted_by`) references `users` (`id`) on delete set null;

create table `dealer_documents` (`id` bigint unsigned not null auto_increment primary key, `dealer_id` bigint unsigned not null, `type` varchar(30) not null, `path` varchar(255) not null, `original_name` varchar(255) not null, `uploaded_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `dealer_documents` add constraint `dealer_documents_dealer_id_foreign` foreign key (`dealer_id`) references `dealers` (`id`) on delete cascade;
alter table `dealer_documents` add constraint `dealer_documents_uploaded_by_foreign` foreign key (`uploaded_by`) references `users` (`id`) on delete set null;
create table `orders` (`id` bigint unsigned not null auto_increment primary key, `number` varchar(20) null, `type` varchar(20) not null, `user_id` bigint unsigned null, `dealer_id` bigint unsigned null, `agent_id` bigint unsigned null, `business_name` varchar(255) not null, `delivery_address` varchar(255) not null, `customer_email` varchar(255) not null, `agent_name` varchar(255) null, `order_date` date not null, `goods_type` varchar(255) null, `shipping_cost` decimal(10, 2) null, `total` decimal(10, 2) not null, `vat_note` varchar(255) null, `payment_method` varchar(20) not null, `payment_status` varchar(20) not null default 'in_attesa', `paid_at` timestamp null, `paid_marked_by` bigint unsigned null, `stripe_session_id` varchar(255) null, `notes` text null, `emailed_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `orders` add constraint `orders_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;
alter table `orders` add constraint `orders_dealer_id_foreign` foreign key (`dealer_id`) references `dealers` (`id`) on delete set null;
alter table `orders` add constraint `orders_agent_id_foreign` foreign key (`agent_id`) references `agents` (`id`) on delete set null;
alter table `orders` add constraint `orders_paid_marked_by_foreign` foreign key (`paid_marked_by`) references `users` (`id`) on delete set null;
alter table `orders` add unique `orders_number_unique`(`number`);
alter table `orders` add index `orders_type_index`(`type`);
alter table `orders` add index `orders_payment_status_index`(`payment_status`);
create table `order_items` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `description` varchar(255) not null, `quantity` int unsigned not null, `unit_price` decimal(10, 2) not null, `amount` decimal(10, 2) not null, `note` varchar(255) null, `promo_id` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `order_items` add constraint `order_items_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;
alter table `order_items` add constraint `order_items_promo_id_foreign` foreign key (`promo_id`) references `promos` (`id`) on delete set null;

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_12_000003_create_orders_and_registry_tables', 2);
