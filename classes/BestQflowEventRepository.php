<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BestQflowEventRepository
{
    /** @var Db */
    protected $db;

    public function __construct(?Db $db = null)
    {
        $this->db = $db ?: Db::getInstance();
    }

    public function getById(int $idEvent): ?array
    {
        if ($idEvent <= 0) {
            return null;
        }

        $row = $this->db->getRow(
            'SELECT *
             FROM `' . _DB_PREFIX_ . 'bestqflow_event`
             WHERE id_bestqflow_event = ' . (int) $idEvent
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    public function getActiveByProduct(int $idProduct): array
    {
        if ($idProduct <= 0) {
            return [];
        }

        $sql = new DbQuery();
        $sql->select('id_bestqflow_event, display_name, city, event_date, event_time, venue, address, stock_limit, is_active, position');
        $sql->from('bestqflow_event');
        $sql->where('id_product = ' . (int) $idProduct);
        $sql->where('is_active = 1');
        $sql->orderBy('position ASC, event_date ASC, city ASC');

        $rows = $this->db->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    public function getLabel(array $event): string
    {
        $city = isset($event['city']) ? trim((string) $event['city']) : '';
        $date = isset($event['event_date']) ? trim((string) $event['event_date']) : '';

        if ($city !== '' && $date !== '') {
            return $city . ', ' . $date;
        }

        if (!empty($event['display_name'])) {
            return (string) $event['display_name'];
        }

        return $city;
    }

    public function getStockLimit(int $idEvent): int
    {
        $event = $this->getById($idEvent);

        return $event ? (int) $event['stock_limit'] : 0;
    }
}
