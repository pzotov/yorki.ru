<?php

class nc_minishop {
    // уведомление

    const NOTIFY_NONE = 0; // нет
    const NOTIFY_ALERT = 1; // в сплывающем слое
    const NOTIFY_DIV = 2; // через диалоговое окно
    // элемент "положить в корзину"
    const PUT_TEXT = 0; // текст
    const PUT_IMG = 1; // картинка
    const PUT_TEXTIMG = 2; // текст с картинкой
    const PUT_BUTTON = 3; // кнопка
    const PUT_FORM = 4; // кнопка с количесвтом
    // авторизация
    const AUTH_NONE = 0; // не нужна
    const AUTH_SUGGEST = 1; // предлагать
    const AUTH_REQUIRE = 2;

    // требовать

    protected $cart;
    protected $db, $UI_CONFIG;
    protected $MODULE_FOLDER, $MODULE_PATH, $ADMIN_TEMPLATE;
    protected $settings;
    protected $TMPL;
    protected $shop_view;

    public function __construct() {

        @session_start();
        $this->cart = array();
        if (!empty($_SESSION['nc_minicart'])) {
            $this->cart = $_SESSION['nc_minicart'];
        }
        else {
            $_SESSION['nc_minicart'] = array();
        }

        // system superior object
        $nc_core = nc_Core::get_object();

        // global variables to internal
        $this->db = & $nc_core->db;
        $this->MODULE_FOLDER = $nc_core->MODULE_FOLDER;
        $this->MODULE_PATH = str_replace($nc_core->DOCUMENT_ROOT, "", $nc_core->MODULE_FOLDER) . "minishop/";
        $this->TMPL = new nc_minishop_templates($this->MODULE_PATH);

        $this->settings = $nc_core->get_settings('', 'minishop');

        $this->interface = $nc_core->get_interface();

        $this->shop_view = new nc_module_view();
        $this->shop_view->load('minishop', $this->interface);

        $nc_core->event->bind($this, array("updateSubClass,addSubClass" => "update_cc"));
        $nc_core->event->bind($this, array("updateSubdivision" => "update_sub"));
    }

    static public function get_object() {
        static $self;
        if (!$self) {
            $self = new self;
        }
        return $self;
    }

    public function __get($name) {
        if ($name == 'settings') {
            return $this->settings;
        }
        if ($name == 'TMPL') {
            return $this->TMPL;
        }
    }

    protected function is_filemode() {
        return nc_get_file_mode('Class', $this->cart_class_id());
    }

    /**
     * Функция генерации хэша товара по его имени и цене
     *
     * @param array массив с ключами name и price
     * @return string хэш
     */
    public function generate_hash($params) {
        return md5($params['name'] . $params['price'] . substr(nc_Core::get_object()->get_settings('SecretKey'), 10, 15));
    }

    public function put_good_test($name, $price, $hash = '', $quantity = 1, $uri = '') {
        $result = false;
        $_SESSION['nc_minicart'][] = false;
        $this->cart = array();
        if ($name && $price) {
            $price = str_replace(',', '.', $price);
            $params = array('name' => $name, 'price' => $price, 'hash' => $hash, 'quantity' => $quantity ? $quantity : 1, 'uri' => $uri ? $uri : '');
            $this->cart[0] = $params;
            $result = true;
        }
        return $result;
    }

    /**
     * Метод кладет товар в корзину
     *
     * @param string имя товара
     * @param float цена товара
     * @param string хэш товара
     * @param float количество
     * @param string ссылка на товар
     * @return boolean результат операции
     */
    public function put_good($name, $price, $hash, $quantity = 1, $uri = '') {
        $result = false;
        if ($name && $price && $this->generate_hash(array('name' => $name, 'price' => $price)) == $hash) {
            $price = str_replace(',', '.', $price);
            $params = array('name' => $name, 'price' => $price, 'hash' => $hash, 'quantity' => $quantity ? $quantity : 1, 'uri' => $uri ? $uri : '');
            $this->cart[] = $params;
            $_SESSION['nc_minicart'][] = $params;
            $result = true;
        }
        return $result;
    }

    /**
     * Метод проверяет наличие товара в корзине, так же может поменять его количество
     * и удалить товар из корзины
     *
     * @param string имя товара
     * @param float цена товара
     * @param float новое количество товара ( -1 - не менять, 0 - удлаить )
     * @return boolean
     */
    public function in_cart($name, $price, $newvalue = -1) {
        $result = false;
        if (!empty($this->cart)) {
            foreach ($this->cart as $key => &$params) {
                if ($params['name'] == $name && $params['price'] == $price) {
                    if ($newvalue == 0) {
                        unset($this->cart[$key]);
                    }
                    if ($newvalue > 0) {
                        $params['quantity'] = $newvalue;
                    }
                    $_SESSION['nc_minicart'] = $this->cart;
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Функция возвращает html-код для помещения в коризну
     * или информер, что товар в корзине
     *
     * @param string название товара
     * @param int цена товара
     * @param string ссылка на товар
     * @param int индетификатор товра в случае массового добавления, 0 - добавление одного товара
     * @param int 0 - принудительно показать кнопку добавления,
     *            1 - принудительно показать, что товар в корзине
     *            -1 - по наличию товара в корзине
     * @return string html-код
     */
    public function show_put_button($name, $price, $uri = '', $mass_id = 0, $incart = -1) {
        $nc_core = nc_Core::get_object();
        $file_mode = $this->is_filemode();

        if ($uri === 'this') {
            $uri = $_SERVER['REQUEST_URI'];
        }

        if (!$price) {
            if ($file_mode) {
                ob_start();
                include($this->shop_view->get_field_path('no_price_text'));
                $result = ob_get_clean();
            }
            else {
                $template = $this->settings['no_price_text'];
                eval("\$result = \"" . $template . "\";");
            }
            return $result;
        }

        // ключ настройки с альтер.кнопкой
        $s_key = $mass_id ? 'mass_put_alternate' : 'put_button_alternate';
        $t_key = $mass_id ? 'template' : ($this->settings['put_button'] + 0);

        // товар не в корзине
        if (!$incart || ($incart == -1 && !$this->in_cart($name, $price))) {
            $hash = $this->generate_hash(array('name' => $name, 'price' => $price));

            static $ajax;
            if ($ajax !== 0 && $ajax !== 1) {
                $ajax = $nc_core->get_settings('ajax', 'minishop') ? 1 : 0;
            }

            if (!$nc_core->NC_UNICODE && $ajax) {
                $name = $nc_core->utf8->win2utf($name);
            }

            $name = rawurlencode($name);
            $price = rawurlencode($price);
            $uri = rawurlencode($uri);
            $id = $mass_id ? $mass_id : 1;

            if ($file_mode) {
                $put_btn_path = $this->shop_view->get_field_path($s_key);
                if (file_get_contents($put_btn_path)) {
                    ob_start();
                    include($put_btn_path);
                    $result = ob_get_clean();
                }
            }
            elseif (!$file_mode && $this->settings[$s_key]) {
                $template = $this->settings[$s_key];
            }
            if (!$result && !$template) {
                $template = $this->TMPL->templates[0][($mass_id ? 'mass' : '') . 'put'][$t_key];
            }
        }

        if ($incart == 1 || ($incart == -1 && $this->in_cart($name, $price))) { // товар уже в корзине            
            if ($file_mode) {
                $alt_in_cart_path = $this->shop_view->get_field_path('already_in_cart_alternate');
                if (file_get_contents($alt_in_cart_path)) {
                    ob_start();
                    include($alt_in_cart_path);
                    $result = ob_get_clean();
                }
            }
            elseif (!$file_mode && $this->settings['already_in_cart_alternate']) {
                $template = $this->settings['already_in_cart_alternate'];
            }
            if (!$result && !$template) {
                $template = $this->TMPL->templates[0]['incart'][$this->settings['already_in_cart'] + 0];
            }
        }

        if (!$file_mode || $template) {
            eval("\$result = \"" . $template . "\";");
        }

        return $result;
    }

    /**
     * Метод возвращает "хэдер"  области массового добавления товаров
     *
     * @return string html-код
     */
    public function mass_put_header() {
        $template = $this->TMPL->templates[0]['massput']['header'];
        eval("\$result = \"" . $template . "\";");
        return $result;
    }

    /**
     * Метод возвращает "футер" области массового добавления товаров
     *
     * @return string html-код
     */
    public function mass_put_footer() {
        $template = $this->TMPL->templates[0]['massput']['button'];
        eval("\$result = \"" . $template . "\";");
        return $result;
    }

    /**
     * Метод возвращает html-код, который используется для нотификации
     *
     * @return string html-код
     */
    public function get_notify() {
        $nc_core = nc_Core::get_object();
        if ($this->settings['notify'] == self::NOTIFY_NONE) {
            return array('type' => $this->settings['notify'], 'text' => '');
        }
        $key = $this->settings['notify'] == self::NOTIFY_ALERT ? 'notify_alert' : 'notify_div';
        $template = $this->settings[$key];
        if (!$template) {
            $template = $this->TMPL->templates[0]['notify'][$this->settings['notify']];
        }

        $carturl = $this->cart_url();
        $orderurl = $this->addorder_url();

        if ($this->is_filemode()) {
            ob_start();
            include($this->shop_view->get_field_path($key));
            $result = ob_get_clean();
        }
        else {
            eval("\$result = \"" . $template . "\";");
        }

        if ($this->settings['notify'] == self::NOTIFY_DIV) {
            $result = "
                <div id='nc_mslayer' style='display:none;'>
                    " . $result . "
                    <span class='simplemodal-close'></span>
                </div>

                <script type='text/javascript'>
                    jQuery('#nc_mslayer').modal({
                            containerId: 'mslayer_simplemodal_container',
                            onShow: function () {
                                jQuery('.simplemodal-close').css({left: '-10px', top: '3px'});
                            }
                    });
                </script>";
        }

        return array('type' => $this->settings['notify'], 'text' => $result);
    }

    /**
     * Количество товаров в корзине
     *
     * @return int количество товаров
     */
    public function cart_count() {
        return count($this->cart);
    }

    /**
     * Содержимое корзины
     *
     * @return array двухмерный массив, ключи: name, price, quantity
     */
    public function cart_content() {
        return $this->cart;
    }

    /**
     * Сумма товаров в корзине
     *
     * @param bool учитывать скидки или нет
     * @return float сумма товаров
     */
    public function cart_sum($with_discount = 0) {
        $sum = 0;
        foreach ($this->cart as $v) {
            $sum += $v['price'] * $v['quantity'];
        }
        if ($with_discount) {
            $sum = (100 - $this->suitable_discount()) * $sum / 100;
        }
        return $sum;
    }

    /**
     * Очистка корзины
     */
    public function cart_clear() {
        $this->cart = $_SESSION['nc_minicart'] = array();
    }

    /**
     * Ссылка на корзину
     *
     * @return string ссылка
     */
    public function cart_url() {
        $url = $this->settings['cart_url'];
        if (!$url) {
            $this->update_urls();
        }
        $url = $this->settings['cart_url'];

        return $url;
    }

    /**
     * Ссылка на добавление заказа
     *
     * @return string ссылка
     */
    public function addorder_url() {
        $url = $this->settings['addorder_url'];
        if (!$url) {
            $this->update_urls();
        }
        $url = $this->settings['addorder_url'];
        return $url;
    }

    /**
     * Ссылка на страницу с заказами
     *
     * @return string ссылка
     */
    public function order_url() {
        $url = $this->settings['order_url'];
        if (!$url) {
            $this->update_urls();
        }
        $url = $this->settings['order_url'];
        return $url;
    }

    public function cart_class_id() {
        return $this->settings['cart_class_id'];
    }

    public function order_class_id() {
        return $this->settings['order_class_id'];
    }

    public function order_addfrom() {
        global $nc_core;
        $component = new nc_Component($this->order_class_id());
        $info = nc_Core::get_object()->sub_class->get_by_id($this->settings['order_cc']);
        $add_form = $component->add_form($info['Catalogue_ID'], $info['Subdivision_ID'], $info['Sub_Class_ID'], 0);

        //env for template
        $catalogue = $info['Catalogue_ID'];
        $sub = $info['Subdivision_ID'];
        $cc = $info['Sub_Class_ID'];

        if ($this->is_filemode()) {
            ob_start();
            include_once($add_form);
            $addForm = ob_get_clean();
        }
        else {
            eval("\$addForm = \"" . $add_form . "\";");
        }

        return $addForm;
    }

    /**
     * Метод возвращает html-код состояния корзины
     *
     * @return string html-код
     */
    public function show_cart_state() {
        if (empty($this->cart)) {
            $template = $this->settings['cart_empty'] ?
                $this->settings['cart_empty'] :
                $this->TMPL->templates[0]['cart']['empty'];
            $template_name = 'cart_empty';
        }
        else {
            $cartcount = count($this->cart);
            $cartsum = $this->cart_sum(1);
            $cartcurrency = $this->get_currency();
            $carturl = $this->cart_url();
            $orderurl = $this->addorder_url();

            $template = $this->settings['cart_full'] ?
                $this->settings['cart_full'] :
                $this->TMPL->templates[0]['cart']['nonempty'];
            $template_name = 'cart_full';
        }

        if ($this->is_filemode()) {
            ob_start();
            include($this->shop_view->get_field_path($template_name));
            $result = ob_get_clean();
        }
        else {
            eval("\$result = \"" . $template . "\";");
        }

        return "<div id='nc_minishop_cart'>" . $result . "</div>";

    }

    /**
     * Валюта магазина
     *
     * @return <type>
     */
    public function get_currency() {
        $cartcurrency = $this->settings['currency'] == '1' ? $this->settings['currency_name'] : NETCAT_MODULE_MINISHOP_CURRENCY_RUB;
        return $cartcurrency;
    }

    /**
     * Метод ищет подходящую скидку под заданную стоимость.
     * Если стоимость не задана - то берется сумма из корзины
     *
     * @param float сумма заказа
     * @return float скидка в процентах
     */
    public function suitable_discount($sum = 0) {
        if (!$this->settings['discount_enabled']) {
            return 0;
        }
        if (!$sum) {
            $sum = $this->cart_sum();
        }

        $d = array();
        $d_str = $this->settings['discounts'];
        if ($d_str) {
            $d = unserialize($d_str);
        }
        foreach ($d as $v) {
            if ($v['from'] <= $sum && $sum <= $v['to']) {
                return $v['value'];
            }
        }
        return 0;
    }

    public function save_order($order_id) {
        $order = new nc_minishop_order($order_id);
        $order->put_goods($this->cart_content());
        $order->apply_discount($this->suitable_discount());

        $this->cart_clear();
    }

    public function edit_order($order_id, $goods) {
        $order = new nc_minishop_order($order_id);
        $order->put_goods($goods, 0);
    }

    public function get_mail($to, $order_id) {
        $order = new nc_minishop_order($order_id);
        $useremail = $order->get('Email');

        // позиции заказа
        $content = '';
        foreach ($order->content() as $v) {
            $content .= $v['name'] . " - " . $v['quantity'] . NETCAT_MODULE_MINISHOP_PIECES . " - " . $v['price'] . " " . $this->get_currency() . "\r\n<br/>";
        }

        $macro = array('SHOP_NAME' => $this->settings['shopname'],
            'SITE_URL' => $_SERVER['HTTP_HOST'],
            'ORDER_NUM' => $order_id,
            'FINAL_COST' => $order->get('FinalCost'),
            'USER_NAME' => $order->get('Name'),
            'CONTENT' => $content,
            'DISCOUNT' => $order->get('Discount'));
        // шаблоны писем
        $subject = $this->settings['mail_subject_' . $to];
        $body = $this->settings['mail_body_' . $to];
        $ishtml = $this->settings['mail_ishtml_' . $to];
        // замена макропеременных
        foreach ($macro as $k => $v) {
            $subject = str_replace('%' . $k, $v, $subject);
            $body = str_replace('%' . $k, $v, $body);
        }

        return array('subject' => $subject, 'body' => $body, 'html' => $is_hrml);
    }

    public function get_afterorder_text($order_id) {
        if ($this->is_filemode()) {
            ob_start();
            include($this->shop_view->get_field_path('orderfrom_text'));
            $result = ob_get_clean();
        }

        if (!$this->is_filemode() || !$result) {
            $template = $this->settings['orderfrom_text'];
            eval("\$result = \"" . $template . "\";");
        }

        return $result;
    }

    public function update_urls() {
        $nc_core = nc_Core::get_object();
        $cart_class_id = $this->settings['cart_class_id'];
        $order_class_id = $this->settings['order_class_id'];

        $routing_module_enabled = nc_module_check_by_keyword('routing');

        // обновление ссылок, связанных с заказами
        // берется первый раздел с компонентом Заказы, желательно, если в нем не используется шаблон компонента
        $order_info = $this->db->get_row(
            "SELECT cc.`Sub_Class_ID`, sub.`Hidden_URL`, cc.`EnglishName`, cc.`Subdivision_ID`
               FROM `Sub_Class` as cc,
                    `Subdivision` as sub
               WHERE cc.`Class_ID` = '" . intval($order_class_id) . "'
                 AND cc.`Subdivision_ID` = sub.`Subdivision_ID`
               ORDER BY cc.`Class_Template_ID`
               LIMIT 1",
            ARRAY_A);

        if ($order_info) {
            $nc_core->set_settings('order_cc', $order_info['Sub_Class_ID'], 'minishop');
            if ($routing_module_enabled) {
                $nc_core->set_settings('order_url', nc_routing::get_folder_path($order_info['Subdivision_ID']), 'minishop');
                $nc_core->set_settings('addorder_url', nc_routing::get_infoblock_path($order_info['Sub_Class_ID'], 'add'), 'minishop');
            }
            else {
                $nc_core->set_settings('order_url', $nc_core->SUB_FOLDER . $order_info['Hidden_URL'], 'minishop');
                $nc_core->set_settings('addorder_url', $nc_core->SUB_FOLDER . $order_info['Hidden_URL'] . 'add_' . $order_info['EnglishName'] . '.html', 'minishop');
            }
        }

        // обновление ссылок с корзиной
        $cart_info = $this->db->get_row(
            "SELECT cc.`Sub_Class_ID`, sub.`Hidden_URL`, cc.`EnglishName`, cc.`Subdivision_ID`
               FROM `Sub_Class` as cc, `Subdivision` as sub
              WHERE cc.`Class_ID` = '" . intval($cart_class_id) . "'
                AND cc.`Subdivision_ID` = sub.`Subdivision_ID`
              ORDER BY cc.`Class_Template_ID`
              LIMIT 1",
            ARRAY_A);

        if ($cart_info) {
            $nc_core->set_settings('cart_cc', $cart_info['Sub_Class_ID'], 'minishop');
            $nc_core->set_settings('cart_url',
                ($routing_module_enabled
                    ? nc_routing::get_folder_path($cart_info['Subdivision_ID'])
                    : $nc_core->SUB_FOLDER . $cart_info['Hidden_URL']
                ),
                'minishop');
        }

        $this->settings = $nc_core->get_settings('', 'minishop');
    }

    public function update_cc() {
        // количество аргументов заранее не известно
        $args = func_get_args();
        $class_id = $args[3];
        if ($this->settings['cart_class_id'] == $class_id || $this->settings['order_class_id'] == $class_id) {
            $this->update_urls();
        }
    }

    public function update_sub($cat_id, $sub_id) {
        if (!is_array($sub_id)) {
            $sub_id = array($sub_id);
        }
        $sub_id = array_map('intval', $sub_id);

        $r = $this->db->get_col("SELECT `Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` IN (" . join(',', $sub_id) . ") ");
        if (!$r) {
            return false;
        }
        if (in_array($this->settings['cart_class_id'], $r) || in_array($this->settings['order_class_id'], $r)) {
            $this->update_urls();
        }
    }

    public function __destruct() {
        // пересчет корзины, чтобы индексы шли по порядку (0,1,2...)
        $tmp = array();
        if ($this->cart) {
            foreach ($this->cart as $v)
                $tmp[] = $v;
        }
        $_SESSION['nc_minicart'] = $tmp;
    }

}