-- Rename theme slugs: old generic names → new evocative names
UPDATE `users` SET `shop_theme` = 'ivory'    WHERE `shop_theme` = 'minimal';
UPDATE `users` SET `shop_theme` = 'obsidian'  WHERE `shop_theme` = 'bold';
UPDATE `users` SET `shop_theme` = 'bloom'     WHERE `shop_theme` = 'fresh';
UPDATE `users` SET `shop_theme` = 'ember'     WHERE `shop_theme` = 'warm';
UPDATE `users` SET `shop_theme` = 'monaco'    WHERE `shop_theme` = 'luxe';
UPDATE `users` SET `shop_theme` = 'volt'      WHERE `shop_theme` = 'neon';
