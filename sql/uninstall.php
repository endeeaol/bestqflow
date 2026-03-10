
<?php
$sql = [];
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bestqflow_log`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bestqflow_ticket`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bestqflow_cart_selection`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'bestqflow_event`';
foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
return true;
