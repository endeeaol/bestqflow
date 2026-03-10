
<?php
$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestqflow_event` (
  `id_bestqflow_event` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product` INT UNSIGNED NOT NULL,
  `display_name` VARCHAR(255) NOT NULL,
  `city` VARCHAR(128) NOT NULL,
  `event_date` DATE NOT NULL,
  `event_time` VARCHAR(64) NOT NULL DEFAULT "",
  `venue` VARCHAR(255) NOT NULL DEFAULT "",
  `address` VARCHAR(255) NOT NULL DEFAULT "",
  `stock_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `position` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_bestqflow_event`),
  KEY `idx_bestqflow_event_product` (`id_product`),
  KEY `idx_bestqflow_event_date` (`event_date`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestqflow_cart_selection` (
  `id_bestqflow_cart_selection` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cart` INT UNSIGNED NOT NULL,
  `id_product` INT UNSIGNED NOT NULL,
  `id_bestqflow_event` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id_bestqflow_cart_selection`),
  UNIQUE KEY `uniq_bestqflow_cart_product` (`id_cart`,`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestqflow_ticket` (
  `id_bestqflow_ticket` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order` INT UNSIGNED NOT NULL,
  `id_order_detail` INT UNSIGNED NOT NULL,
  `id_product` INT UNSIGNED NOT NULL,
  `id_customer` INT UNSIGNED NOT NULL,
  `id_bestqflow_event` INT UNSIGNED NOT NULL,
  `ticket_index` INT UNSIGNED NOT NULL DEFAULT 1,
  `ticket_code` VARCHAR(64) NOT NULL,
  `barcode` VARCHAR(128) NOT NULL,
  `customer_firstname` VARCHAR(128) NOT NULL,
  `customer_lastname` VARCHAR(128) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `payment_allowed` TINYINT(1) NOT NULL DEFAULT 0,
  `process_status` VARCHAR(32) NOT NULL DEFAULT "pending_payment",
  `pdf_generated` TINYINT(1) NOT NULL DEFAULT 0,
  `email_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `exported_csv` TINYINT(1) NOT NULL DEFAULT 0,
  `pdf_file` VARCHAR(255) NOT NULL DEFAULT "",
  `email_error` TEXT NULL,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id_bestqflow_ticket`),
  UNIQUE KEY `uniq_bestqflow_barcode` (`barcode`),
  KEY `idx_bestqflow_ticket_order` (`id_order`),
  KEY `idx_bestqflow_ticket_event` (`id_bestqflow_event`),
  KEY `idx_bestqflow_ticket_email` (`customer_email`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestqflow_log` (
  `id_bestqflow_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_bestqflow_ticket` INT UNSIGNED NOT NULL DEFAULT 0,
  `action` VARCHAR(128) NOT NULL,
  `request_excerpt` MEDIUMTEXT NULL,
  `response_excerpt` MEDIUMTEXT NULL,
  `http_code` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL,
  `date_add` DATETIME NOT NULL,
  PRIMARY KEY (`id_bestqflow_log`),
  KEY `idx_bestqflow_log_order` (`id_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
return true;
