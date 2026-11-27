-- Effepi Master: gerarchia a cinque ruoli (superadmin > admin > capoarea > agente > dealer).
-- Da lanciare in phpMyAdmin DOPO gli SQL del 12/11. Eseguirlo una sola volta.

-- 1. Tabella dei capoarea e collegamento agente -> capoarea.
create table `area_heads` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `phone` varchar(40) null, `region` varchar(60) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `area_heads` add constraint `area_heads_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;
alter table `area_heads` add unique `area_heads_user_id_unique`(`user_id`);
alter table `agents` add `area_head_id` bigint unsigned null after `user_id`;
alter table `agents` add constraint `agents_area_head_id_foreign` foreign key (`area_head_id`) references `area_heads` (`id`) on delete set null;

-- 2. Roberto e Stefania: da "manager" ad "admin".
UPDATE `users` SET `role` = 'admin' WHERE `role` = 'manager';

-- 3. Superadmin (Laura). Prima di lanciarlo, sostituire l'email con quella reale.
-- La password provvisoria è quella che ti ho comunicato in chat: al primo accesso il portale obbliga a cambiarla.
INSERT INTO `users` (`name`, `username`, `email`, `role`, `must_change_password`, `active`, `password`, `created_at`, `updated_at`) VALUES
  ('Laura', 'laura', 'laura@effepimaster.it', 'superadmin', 1, 1, '$2y$12$A5EVfP5liXEpgb9QAkdfW.k8rr.xsg95mQFOsdfjSU7SQ2wkrdEw2', NOW(), NOW());

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_26_000001_create_area_heads_and_roles', 4);
