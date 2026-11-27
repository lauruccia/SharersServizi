-- Effepi Master: correzioni manuali dell'admin sui conteggi (righe bloccate/manuali, fibra MNP forzata) e rettifiche ai compensi.
-- Da lanciare in phpMyAdmin DOPO 2026_11_26_produzione_compensi.sql. Eseguirlo una sola volta.

alter table `production_rows` add `locked` tinyint(1) not null default '0', add `is_manual` tinyint(1) not null default '0', add `fibra_mnp_force` tinyint(1) null, add `edit_note` varchar(255) null;

create table `commission_adjustments` (`id` bigint unsigned not null auto_increment primary key, `dealer_id` bigint unsigned not null, `month` date not null, `amount_cents` int not null, `note` varchar(255) not null, `created_by` bigint unsigned null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `commission_adjustments` add constraint `commission_adjustments_dealer_id_foreign` foreign key (`dealer_id`) references `dealers` (`id`) on delete cascade;
alter table `commission_adjustments` add constraint `commission_adjustments_created_by_foreign` foreign key (`created_by`) references `users` (`id`) on delete set null;
alter table `commission_adjustments` add index `commission_adjustments_dealer_id_month_index`(`dealer_id`, `month`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_26_000003_add_manual_overrides', 5);
