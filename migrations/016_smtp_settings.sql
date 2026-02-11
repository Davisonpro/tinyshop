INSERT INTO `settings` (`key`, `value`) VALUES
    ('smtp_host', ''),
    ('smtp_port', '587'),
    ('smtp_username', ''),
    ('smtp_password', ''),
    ('smtp_encryption', 'tls'),
    ('mail_from_email', ''),
    ('mail_from_name', 'TinyShop')
ON DUPLICATE KEY UPDATE `key` = `key`;
