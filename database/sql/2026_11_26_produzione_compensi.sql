-- Effepi Master: produzione, compensi, extra gare, aree e ambassador (import ReportDealer).
-- Da lanciare in phpMyAdmin DOPO gli SQL del 12/11 e del 26/11 (ruoli). Eseguirlo una sola volta.

-- 1. Aree e ambassador; collegamento dei dealer.
create table `areas` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `areas` add unique `areas_name_unique`(`name`);
create table `ambassadors` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `area_id` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `ambassadors` add constraint `ambassadors_area_id_foreign` foreign key (`area_id`) references `areas` (`id`) on delete set null;
alter table `ambassadors` add unique `ambassadors_name_unique`(`name`);
alter table `dealers` add `area_id` bigint unsigned null, add `ambassador_id` bigint unsigned null;
alter table `dealers` add constraint `dealers_area_id_foreign` foreign key (`area_id`) references `areas` (`id`) on delete set null;
alter table `dealers` add constraint `dealers_ambassador_id_foreign` foreign key (`ambassador_id`) references `ambassadors` (`id`) on delete set null;

-- 2. Storico degli import e righe di produzione (una per contratto).
create table `production_imports` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned null, `filename` varchar(255) not null, `rows_read` int unsigned not null default '0', `rows_created` int unsigned not null default '0', `rows_updated` int unsigned not null default '0', `rows_skipped` int unsigned not null default '0', `dealers_created` int unsigned not null default '0', `period_from` date null, `period_to` date null, `notes` json null, `created_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `production_imports` add constraint `production_imports_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;

create table `production_rows` (`id` bigint unsigned not null auto_increment primary key, `production_import_id` bigint unsigned null, `dealer_id` bigint unsigned null, `contract_id` varchar(30) not null, `unique_id` varchar(30) null, `external_dealer_id` varchar(30) not null, `dealer_name` varchar(255) null, `customer_name` varchar(255) null, `service_raw` varchar(60) not null, `service_type` varchar(30) not null, `is_consumer` tinyint(1) not null default '1', `signed_at` date not null, `signed_month` date not null, `status` varchar(255) null, `status_at` datetime null, `outcome` varchar(12) not null, `force_count` tinyint(1) null, `discard_reason` varchar(255) null, `cc_status` varchar(30) null, `ccn_note` text null, `friend_code_insert` tinyint(1) not null default '0', `payment_method` varchar(40) null, `invoice_delivery` varchar(40) null, `sims_sold` smallint unsigned not null default '0', `sims_portability` smallint unsigned not null default '0', `sims_paid` tinyint(1) null, `offer_code` varchar(40) null, `activation_type` varchar(40) null, `utility_status` varchar(40) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `production_rows` add constraint `production_rows_production_import_id_foreign` foreign key (`production_import_id`) references `production_imports` (`id`) on delete set null;
alter table `production_rows` add constraint `production_rows_dealer_id_foreign` foreign key (`dealer_id`) references `dealers` (`id`) on delete set null;
alter table `production_rows` add unique `production_rows_contract_id_unique`(`contract_id`);
alter table `production_rows` add index `production_rows_unique_id_index`(`unique_id`);
alter table `production_rows` add index `production_rows_external_dealer_id_index`(`external_dealer_id`);
alter table `production_rows` add index `production_rows_service_type_index`(`service_type`);
alter table `production_rows` add index `production_rows_signed_at_index`(`signed_at`);
alter table `production_rows` add index `production_rows_signed_month_index`(`signed_month`);
alter table `production_rows` add index `production_rows_outcome_index`(`outcome`);
alter table `production_rows` add index `production_rows_dealer_id_signed_month_index`(`dealer_id`, `signed_month`);

-- 3. Piano compensi (piani e scaglioni) ed extra gare.
create table `commission_plans` (`id` bigint unsigned not null auto_increment primary key, `valid_from` date not null, `note` varchar(255) null, `params` json not null, `created_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `commission_plans` add constraint `commission_plans_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete set null;
alter table `commission_plans` add index `commission_plans_valid_from_index`(`valid_from`);
create table `commission_rules` (`id` bigint unsigned not null auto_increment primary key, `commission_plan_id` bigint unsigned not null, `product` varchar(30) not null, `min_qty` smallint unsigned not null, `max_qty` smallint unsigned null, `base_amount` decimal(8, 2) not null, `mnp_amount` decimal(8, 2) not null default '0') default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `commission_rules` add constraint `commission_rules_commission_plan_id_foreign` foreign key (`commission_plan_id`) references `commission_plans` (`id`) on delete cascade;
alter table `commission_rules` add index `commission_rules_commission_plan_id_product_index`(`commission_plan_id`, `product`);
create table `contests` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `metric` varchar(20) not null, `starts_on` date not null, `ends_on` date not null, `target` int unsigned null, `prize` varchar(255) null, `active` tinyint(1) not null default '1', `created_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `contests` add constraint `contests_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete set null;

-- 4. Piano compensi iniziale dal file "compensi negozi.xlsx" (modificabile dall'area manager > Compensi).
INSERT INTO `commission_plans` (`id`, `valid_from`, `note`, `params`, `created_at`, `updated_at`) VALUES
  (1, '2026-01-01', 'Piano iniziale da "compensi negozi.xlsx"', '{"sdd_bonus":15,"email_bonus":5,"sim_scaglione":"totale","fibra_mnp":"tipo_attivazione","sim_695_codes":["SMOB_5G_DE_0926"],"count_pending":false}', NOW(), NOW());
INSERT INTO `commission_rules` (`commission_plan_id`, `product`, `min_qty`, `max_qty`, `base_amount`, `mnp_amount`) VALUES
  (1, 'sim_495', 1, 4, 2.00, 3.00),
  (1, 'sim_495', 5, 9, 7.00, 5.00),
  (1, 'sim_495', 10, 24, 10.00, 8.00),
  (1, 'sim_495', 25, NULL, 12.00, 10.00),
  (1, 'sim_695', 1, 4, 7.00, 8.00),
  (1, 'sim_695', 5, 9, 9.00, 10.00),
  (1, 'sim_695', 10, 24, 11.00, 12.00),
  (1, 'sim_695', 25, NULL, 13.00, 20.00),
  (1, 'fibra', 1, 4, 50.00, 70.00),
  (1, 'fibra', 5, NULL, 130.00, 150.00),
  (1, 'energy_consumer', 1, 4, 40.00, 0.00),
  (1, 'energy_consumer', 5, 9, 65.00, 0.00),
  (1, 'energy_consumer', 10, 19, 70.00, 0.00),
  (1, 'energy_consumer', 20, NULL, 75.00, 0.00),
  (1, 'energy_business', 1, 4, 60.00, 0.00),
  (1, 'energy_business', 5, 9, 80.00, 0.00),
  (1, 'energy_business', 10, 19, 85.00, 0.00),
  (1, 'energy_business', 20, NULL, 90.00, 0.00);

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_26_000002_create_production_tables', 5);
