<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BestQflowCartRepository
{
    /** @var Db */
    protected $db;

    public function __construct(?Db $db = null)
    {
        $this->db = $db ?: Db::getInstance();
    }

    public function saveCartItemEvent(
        int $idCart,
        int $idProduct,
        int $idProductAttribute,
        int $idCustomization,
        int $idEvent,
        int $quantity
    ): bool {
        if ($idCart <= 0 || $idProduct <= 0 || $idEvent <= 0) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $existingId = (int) $this->db->getValue(
            'SELECT id_bestqflow_cart_item
             FROM `' . _DB_PREFIX_ . 'bestqflow_cart_item`
             WHERE id_cart = ' . (int) $idCart . '
               AND id_product = ' . (int) $idProduct . '
               AND id_product_attribute = ' . (int) $idProductAttribute . '
               AND id_customization = ' . (int) $idCustomization . '
             LIMIT 1'
        );

        $data = [
            'id_cart' => $idCart,
            'id_product' => $idProduct,
            'id_product_attribute' => $idProductAttribute,
            'id_customization' => $idCustomization,
            'id_bestqflow_event' => $idEvent,
            'quantity' => max(1, $quantity),
            'date_upd' => pSQL($now),
        ];

        if ($existingId > 0) {
            return (bool) $this->db->update(
                'bestqflow_cart_item',
                $data,
                'id_bestqflow_cart_item = ' . (int) $existingId
            );
        }

        $data['date_add'] = pSQL($now);

        return (bool) $this->db->insert('bestqflow_cart_item', $data);
    }

    public function getCartItemEvent(
        int $idCart,
        int $idProduct,
        int $idProductAttribute = 0,
        int $idCustomization = 0
    ): ?array {
        $row = $this->db->getRow(
            'SELECT ci.*, e.city, e.event_date, e.display_name, e.stock_limit
             FROM `' . _DB_PREFIX_ . 'bestqflow_cart_item` ci
             INNER JOIN `' . _DB_PREFIX_ . 'bestqflow_event` e
                 ON e.id_bestqflow_event = ci.id_bestqflow_event
             WHERE ci.id_cart = ' . (int) $idCart . '
               AND ci.id_product = ' . (int) $idProduct . '
               AND ci.id_product_attribute = ' . (int) $idProductAttribute . '
               AND ci.id_customization = ' . (int) $idCustomization . '
             LIMIT 1'
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    public function sumReservedQtyByEvent(int $idEvent, int $minutes = 30, int $excludeCartId = 0): int
    {
        if ($idEvent <= 0) {
            return 0;
        }

        $sql = 'SELECT SUM(quantity)
                FROM `' . _DB_PREFIX_ . 'bestqflow_cart_item`
                WHERE id_bestqflow_event = ' . (int) $idEvent . '
                  AND date_upd >= DATE_SUB(NOW(), INTERVAL ' . (int) $minutes . ' MINUTE)';

        if ($excludeCartId > 0) {
            $sql .= ' AND id_cart != ' . (int) $excludeCartId;
        }

        return (int) $this->db->getValue($sql);
    }
}
