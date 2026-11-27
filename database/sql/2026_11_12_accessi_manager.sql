-- Effepi Master: i due accessi manager.
-- Da lanciare in phpMyAdmin dopo la struttura iniziale. Le password sono provvisorie:
-- al primo accesso il portale obbliga a sceglierne una nuova.
-- Prima di lanciarlo, sostituire le email con quelle reali di Roberto e Stefania.

INSERT INTO `users` (`name`, `username`, `email`, `role`, `must_change_password`, `active`, `password`, `created_at`, `updated_at`) VALUES
  ('Roberto', 'roberto', 'roberto@effepimaster.it', 'manager', 1, 1, '$2y$12$JhvpLKFJcG0oSwLvVeRgC.ACS.ustcMieR2Hp1vWQHH3cKF69F2Ne', NOW(), NOW()),
  ('Stefania', 'stefania', 'stefania@effepimaster.it', 'manager', 1, 1, '$2y$12$upiMAVy8Q2FK7SJrC3tHq.9c8AjKtke3z4nYEKDiBs6xgl6Saf8pK', NOW(), NOW());
