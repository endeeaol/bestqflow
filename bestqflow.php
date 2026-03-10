<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/BestQflowEventRepository.php';
require_once __DIR__ . '/classes/BestQflowCartRepository.php';
require_once __DIR__ . '/classes/BestQflowAvailabilityService.php';

class Bestqflow extends Module
{
    public const CONFIG_TICKET_PRODUCT_ID = 'BESTQFLOW_TICKET_PRODUCT_ID';
    public const CONFIG_RESERVATION_WINDOW_MINUTES = 'BESTQFLOW_RESERVATION_WINDOW_MINUTES';
    public const CUSTOM_HOOK_NAME = 'displayBestqflowSelector';

    /** @var BestQflowEventRepository|null */
    protected $eventRepository;

    /** @var BestQflowCartRepository|null */
    protected $cartRepository;

    /** @var BestQflowAvailabilityService|null */
    protected $availabilityService;

    public function __construct()
    {
        $this->name = 'bestqflow';
        $this->tab = 'administration';
        $this->version = '0.5.0';
        $this->author = 'bestlab';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Best Qflow');
        $this->description = $this->l('Obsługa biletów eventowych i eksportów do Qflow.');
        $this->ps_versions_compliancy = [
            'min' => '8.2.0',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->installConfiguration()
            && $this->installCustomHook()
            && $this->registerHook('header');
    }

    public function uninstall()
    {
        return $this->uninstallConfiguration()
            && parent::uninstall();
    }

    protected function installConfiguration(): bool
    {
        return Configuration::updateValue(self::CONFIG_TICKET_PRODUCT_ID, 98)
            && Configuration::updateValue(self::CONFIG_RESERVATION_WINDOW_MINUTES, 30);
    }

    protected function uninstallConfiguration(): bool
    {
        return Configuration::deleteByName(self::CONFIG_TICKET_PRODUCT_ID)
            && Configuration::deleteByName(self::CONFIG_RESERVATION_WINDOW_MINUTES);
    }

    protected function installDb(): bool
    {
        $sql1 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestqflow_event` (
            `id_bestqflow_event` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT UNSIGNED NOT NULL,
            `display_name` VARCHAR(255) NOT NULL DEFAULT \'\',
            `city` VARCHAR(128) NOT NULL,
            `event_date` DATE NOT NULL,
            `event_time` VARCHAR(32) NOT NULL DEFAULT \'\',
            `venue` VARCHAR(255) NOT NULL DEFAULT \'\',
            `address` VARCHAR(255) NOT NULL DEFAULT \'\',
            `stock_limit` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `position` INT UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL,
            PRIMARY KEY (`id_bestqflow_event`),
            KEY `idx_bestqflow_product` (`id_product`),
            KEY `idx_bestqflow_active` (`is_active`),
            KEY `idx_bestqflow_position` (`position`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        $sql2 = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bestqflow_cart_item` (
            `id_bestqflow_cart_item` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_cart` INT UNSIGNED NOT NULL,
            `id_product` INT UNSIGNED NOT NULL,
            `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
            `id_customization` INT UNSIGNED NOT NULL DEFAULT 0,
            `id_bestqflow_event` INT UNSIGNED NOT NULL,
            `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
            `date_add` DATETIME NULL,
            `date_upd` DATETIME NULL,
            PRIMARY KEY (`id_bestqflow_cart_item`),
            KEY `idx_bestqflow_cart_lookup` (`id_cart`, `id_product`, `id_product_attribute`, `id_customization`),
            KEY `idx_bestqflow_event_lookup` (`id_bestqflow_event`),
            KEY `idx_bestqflow_date_upd` (`date_upd`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

        return (bool) Db::getInstance()->execute($sql1)
            && (bool) Db::getInstance()->execute($sql2);
    }

    protected function installCustomHook(): bool
    {
        $hookName = self::CUSTOM_HOOK_NAME;
        $idHook = (int) Hook::getIdByName($hookName);

        if ($idHook <= 0) {
            $hook = new Hook();
            $hook->name = $hookName;
            $hook->title = 'BestQflow selector';
            $hook->description = 'Renderuje selector eventów modułu BestQflow na stronie produktu.';
            $hook->position = true;

            if (!$hook->add()) {
                return false;
            }
        }

        return $this->registerHook($hookName);
    }

    protected function getEventRepository(): BestQflowEventRepository
    {
        if ($this->eventRepository === null) {
            $this->eventRepository = new BestQflowEventRepository();
        }

        return $this->eventRepository;
    }

    protected function getCartRepository(): BestQflowCartRepository
    {
        if ($this->cartRepository === null) {
            $this->cartRepository = new BestQflowCartRepository();
        }

        return $this->cartRepository;
    }

    protected function getAvailabilityService(): BestQflowAvailabilityService
    {
        if ($this->availabilityService === null) {
            $minutes = (int) Configuration::get(self::CONFIG_RESERVATION_WINDOW_MINUTES);
            $this->availabilityService = new BestQflowAvailabilityService(
                $this->getEventRepository(),
                $this->getCartRepository(),
                $minutes > 0 ? $minutes : 30
            );
        }

        return $this->availabilityService;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitBestqflowConfig')) {
            Configuration::updateValue(self::CONFIG_TICKET_PRODUCT_ID, (int) Tools::getValue(self::CONFIG_TICKET_PRODUCT_ID));
            Configuration::updateValue(self::CONFIG_RESERVATION_WINDOW_MINUTES, max(1, (int) Tools::getValue(self::CONFIG_RESERVATION_WINDOW_MINUTES)));
            $output .= $this->displayConfirmation($this->l('Ustawienia zapisane.'));
        }

        if (Tools::isSubmit('submitBestqflowEvent')) {
            $output .= $this->processEventForm();
        }

        if (Tools::isSubmit('deleteBestqflowEvent')) {
            $idEvent = (int) Tools::getValue('id_bestqflow_event');
            if ($idEvent > 0) {
                $output .= Db::getInstance()->delete('bestqflow_event', 'id_bestqflow_event = ' . (int) $idEvent)
                    ? $this->displayConfirmation($this->l('Event został usunięty.'))
                    : $this->displayError($this->l('Nie udało się usunąć eventu.'));
            }
        }

        $output .= $this->renderPlacementInfoBox();
        $output .= $this->renderConfigForm();
        $output .= $this->renderEventForm();
        $output .= $this->renderEventsTable();

        return $output;
    }

    protected function renderPlacementInfoBox(): string
    {
        $hookCode = "{hook h='displayBestqflowSelector' product=\$product}";

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-code"></i> ' . $this->l('Instrukcja osadzenia hooka w theme') . '</h3>';
        $html .= '<div class="alert alert-info" style="margin-bottom:0;">';
        $html .= '<p><strong>' . $this->l('Aby selector eventów pojawił się na karcie produktu, wstaw w wybranym miejscu pliku product-add-to-cart.tpl poniższy hook:') . '</strong></p>';
        $html .= '<pre style="margin:10px 0 0 0;padding:12px;background:#f6f8fa;border:1px solid #d9e1e7;">' . htmlspecialchars($hookCode, ENT_QUOTES, 'UTF-8') . '</pre>';
        $html .= '<p style="margin-top:12px;">' . $this->l('Rekomendowane miejsce: nad sekcją ilość + przycisk „do koszyka”, tylko raz.') . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderConfigForm(): string
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia główne'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('ID produktu biletowego'),
                        'name' => self::CONFIG_TICKET_PRODUCT_ID,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Czas rezerwacji w koszyku (minuty)'),
                        'name' => self::CONFIG_RESERVATION_WINDOW_MINUTES,
                        'required' => true,
                        'desc' => $this->l('Po tym czasie nieopłacona rezerwacja z koszyka przestaje blokować miejsca w liczniku dostępności.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'name' => 'submitBestqflowConfig',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitBestqflowConfig';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->fields_value = [
            self::CONFIG_TICKET_PRODUCT_ID => (int) Configuration::get(self::CONFIG_TICKET_PRODUCT_ID),
            self::CONFIG_RESERVATION_WINDOW_MINUTES => (int) Configuration::get(self::CONFIG_RESERVATION_WINDOW_MINUTES),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    protected function renderEventForm(): string
    {
        $idEvent = (int) Tools::getValue('edit_bestqflow_event');
        $event = [
            'id_bestqflow_event' => 0,
            'city' => '',
            'event_date' => '',
            'stock_limit' => 180,
            'is_active' => 1,
        ];

        if ($idEvent > 0) {
            $loaded = $this->getEventRepository()->getById($idEvent);
            if (is_array($loaded) && !empty($loaded)) {
                $event = $loaded;
            }
        }

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $idEvent > 0 ? $this->l('Edytuj event') : $this->l('Dodaj event'),
                    'icon' => 'icon-calendar',
                ],
                'input' => [
                    ['type' => 'hidden', 'name' => 'id_bestqflow_event'],
                    ['type' => 'text', 'label' => $this->l('Miasto'), 'name' => 'city', 'required' => true],
                    ['type' => 'date', 'label' => $this->l('Data'), 'name' => 'event_date', 'required' => true],
                    ['type' => 'text', 'label' => $this->l('Limit'), 'name' => 'stock_limit', 'required' => true],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Aktywny'),
                        'name' => 'is_active',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'is_active_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'is_active_off', 'value' => 0, 'label' => $this->l('Nie')],
                        ],
                    ],
                ],
                'buttons' => $idEvent > 0 ? [[
                    'title' => $this->l('Anuluj edycję'),
                    'icon' => 'process-icon-cancel',
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ]] : [],
                'submit' => [
                    'title' => $idEvent > 0 ? $this->l('Zapisz zmiany') : $this->l('Dodaj event'),
                    'name' => 'submitBestqflowEvent',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitBestqflowEvent';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->fields_value = [
            'id_bestqflow_event' => (int) $event['id_bestqflow_event'],
            'city' => (string) $event['city'],
            'event_date' => (string) $event['event_date'],
            'stock_limit' => (int) $event['stock_limit'],
            'is_active' => (int) $event['is_active'],
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    protected function processEventForm(): string
    {
        $idProduct = (int) Configuration::get(self::CONFIG_TICKET_PRODUCT_ID);
        if ($idProduct <= 0) {
            return $this->displayError($this->l('Najpierw ustaw ID produktu biletowego.'));
        }

        $idEvent = (int) Tools::getValue('id_bestqflow_event');
        $city = trim((string) Tools::getValue('city'));
        $eventDate = trim((string) Tools::getValue('event_date'));
        $stockLimit = (int) Tools::getValue('stock_limit');
        $isActive = (int) Tools::getValue('is_active');

        if ($city === '' || $eventDate === '' || $stockLimit < 0) {
            return $this->displayError($this->l('Uzupełnij poprawnie formularz eventu.'));
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'id_product' => $idProduct,
            'display_name' => pSQL($city . ', ' . $eventDate),
            'city' => pSQL($city),
            'event_date' => pSQL($eventDate),
            'event_time' => '',
            'venue' => '',
            'address' => '',
            'stock_limit' => $stockLimit,
            'is_active' => $isActive ? 1 : 0,
            'position' => 0,
            'date_upd' => pSQL($now),
        ];

        if ($idEvent > 0) {
            $updated = Db::getInstance()->update('bestqflow_event', $data, 'id_bestqflow_event = ' . (int) $idEvent);
            return $updated ? $this->displayConfirmation($this->l('Event został zaktualizowany.')) : $this->displayError($this->l('Nie udało się zaktualizować eventu.'));
        }

        $data['date_add'] = pSQL($now);
        $inserted = Db::getInstance()->insert('bestqflow_event', $data);

        return $inserted ? $this->displayConfirmation($this->l('Event został dodany.')) : $this->displayError($this->l('Nie udało się dodać eventu.'));
    }

    protected function renderEventsTable(): string
    {
        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'bestqflow_event` ORDER BY event_date ASC, city ASC');

        $html = '<div class="panel">';
        $html .= '<h3>' . $this->l('Eventy / eksport do Qflow') . '</h3>';

        if (!$rows) {
            $html .= '<p>' . $this->l('Brak eventów.') . '</p></div>';
            return $html;
        }

        $html .= '<table class="table">';
        $html .= '<thead><tr>';
        $html .= '<th>' . $this->l('ID') . '</th>';
        $html .= '<th>' . $this->l('Miasto') . '</th>';
        $html .= '<th>' . $this->l('Data') . '</th>';
        $html .= '<th>' . $this->l('Limit') . '</th>';
        $html .= '<th>' . $this->l('Aktywny') . '</th>';
        $html .= '<th>' . $this->l('Dostępne teraz') . '</th>';
        $html .= '<th>' . $this->l('Akcje') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $availability = $this->getAvailabilityService()->getAvailability((int) $row['id_bestqflow_event']);
            $editUrl = AdminController::$currentIndex . '&configure=' . $this->name . '&edit_bestqflow_event=' . (int) $row['id_bestqflow_event'] . '&token=' . Tools::getAdminTokenLite('AdminModules');
            $deleteUrl = AdminController::$currentIndex . '&configure=' . $this->name . '&deleteBestqflowEvent=1&id_bestqflow_event=' . (int) $row['id_bestqflow_event'] . '&token=' . Tools::getAdminTokenLite('AdminModules');

            $html .= '<tr>';
            $html .= '<td>' . (int) $row['id_bestqflow_event'] . '</td>';
            $html .= '<td>' . htmlspecialchars((string) $row['city'], ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars((string) $row['event_date'], ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . (int) $row['stock_limit'] . '</td>';
            $html .= '<td>' . ((int) $row['is_active'] ? $this->l('TAK') : $this->l('NIE')) . '</td>';
            $html .= '<td>' . (int) $availability['available_qty'] . '</td>';
            $html .= '<td>';
            $html .= '<a class="btn btn-default" href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">' . $this->l('Edytuj') . '</a> ';
            $html .= '<a class="btn btn-danger" href="' . htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') . '" onclick="return confirm(\'' . addslashes($this->l('Usunąć event?')) . '\');">' . $this->l('Usuń') . '</a>';
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    public function hookHeader(array $params)
    {
        if (!$this->context || !$this->context->controller) {
            return;
        }

        if ($this->context->controller->php_self !== 'product') {
            return;
        }

        $ticketProductId = (int) Configuration::get(self::CONFIG_TICKET_PRODUCT_ID);
        $currentProductId = (int) Tools::getValue('id_product');

        if ($ticketProductId <= 0 || $currentProductId !== $ticketProductId) {
            return;
        }

        $this->context->controller->registerStylesheet('module-bestqflow-front', 'modules/' . $this->name . '/views/css/front.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('module-bestqflow-front', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 150]);
    }

    public function hookDisplayBestqflowSelector(array $params)
    {
        if (empty($params['product'])) {
            return '';
        }

        $product = $params['product'];
        $idProduct = 0;

        if (is_array($product) && isset($product['id_product'])) {
            $idProduct = (int) $product['id_product'];
        } elseif (is_object($product) && isset($product->id)) {
            $idProduct = (int) $product->id;
        }

        if ($idProduct <= 0) {
            return '';
        }

        $ticketProductId = (int) Configuration::get(self::CONFIG_TICKET_PRODUCT_ID);
        if ($ticketProductId <= 0 || $idProduct !== $ticketProductId) {
            return '';
        }

        $events = $this->getEventRepository()->getActiveByProduct($idProduct);
        if (empty($events)) {
            return '';
        }

        $this->context->smarty->assign([
            'bestqflow_events' => $events,
            'bestqflow_product_id' => $idProduct,
            'bestqflow_hook_name' => self::CUSTOM_HOOK_NAME,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/product-selector.tpl');
    }

    protected function getEventById(int $idEvent): ?array
    {
        return $this->getEventRepository()->getById($idEvent);
    }

    protected function getActiveEventsForProduct(int $productId): array
    {
        return $this->getEventRepository()->getActiveByProduct($productId);
    }

    protected function saveCartItemEvent(
        int $idCart,
        int $idProduct,
        int $idProductAttribute,
        int $idCustomization,
        int $idEvent,
        int $quantity
    ): bool {
        return $this->getCartRepository()->saveCartItemEvent(
            $idCart,
            $idProduct,
            $idProductAttribute,
            $idCustomization,
            $idEvent,
            $quantity
        );
    }

    protected function getCartItemEvent(
        int $idCart,
        int $idProduct,
        int $idProductAttribute = 0,
        int $idCustomization = 0
    ): ?array {
        return $this->getCartRepository()->getCartItemEvent($idCart, $idProduct, $idProductAttribute, $idCustomization);
    }

    protected function getEventLabel(array $event): string
    {
        return $this->getEventRepository()->getLabel($event);
    }
}
