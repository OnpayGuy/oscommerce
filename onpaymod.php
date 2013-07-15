<?php

class onpay
{
    var $code, $title, $description, $enabled;

    // class constructor
    function onpay()
    {
        global $order;

        $this->code = 'onpay';
        $this->title = MODULE_PAYMENT_ONPAY_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_ONPAY_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_ONPAY_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_ONPAY_STATUS == 'Да') ? true : false);

    }

    // class methods
    function update_status()
    {
        return false;
    }

    function javascript_validation()
    {
        return false;
    }

    function selection()
    {
        return array('id' => $this->code, 'module' => $this->title);
    }

    function pre_confirmation_check()
    {
        return false;
    }

    function confirmation()
    {
        return false;
    }

    function process_button()
    {

        return false;
    }

    function before_process()
    {
        return false;
    }

    function after_process()
    {
        global $insert_id, $cart, $order;
            

        $login = MODULE_PAYMENT_ONPAY_LOGIN;
        $key = MODULE_PAYMENT_ONPAY_PASSWORD1;
        $order_id = $insert_id;
        $sum = MODULE_PAYMENT_ONPAY_KURS * $order->info['total'];
        $user_email = @$order->customer["email_address"];
        $sum = ceil(100*$sum)*0.01;
        $sum_for_md5 = (strpos($sum, ".") ? round($sum, 2) : $sum . ".0");


        $path1 = MODULE_PAYMENT_ONPAY_SUCCESS;
        $path2 = MODULE_PAYMENT_ONPAY_FAIL;
        // md5 подпись
        $md5check = md5("fix;$sum_for_md5;RUR;$order_id;yes;$key");
        $sitename = STORE_NAME;
        $desc = 'Оплата заказа №' . $order_id . ' в магазине ' . $sitename;
        // платежная ссылка
        $url = "http://secure.onpay.ru/pay/$login?pay_mode=fix&pay_for=$order_id&price=$sum&currency=RUR&convert=yes&md5=$md5check&user_email=$user_email&url_success=$path1&url_fail=$path2&note=$desc";


        $cart->reset(true);
        tep_session_unregister('sendto');
        tep_session_unregister('billto');
        tep_session_unregister('shipping');
        tep_session_unregister('payment');
        tep_session_unregister('comments');
        tep_redirect($url);
    }

    function output_error()
    {
        return false;
    }

    function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " .
                TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_ONPAY_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install()
    {
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Включить модуль', 'MODULE_PAYMENT_ONPAY_STATUS', 'Да', 'Активировать прием платежей через систему OnPay.ru', '6', '3', 'tep_cfg_select_option(array(\'Да\', \'Нет\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Логин в системе OnPay.ru', 'MODULE_PAYMENT_ONPAY_LOGIN', '', 'Ваше Имя пользователя в системе OnPay.ru', '6', '4', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ключ API IN', 'MODULE_PAYMENT_ONPAY_PASSWORD1', '', 'Секретный ключ API IN, указанный в личном кабинете OnPay.ru', '6', '5', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Курс валюты сайта', 'MODULE_PAYMENT_ONPAY_KURS', '1', 'Отношение валюты, используемой на сайте, к валюте приема платежей через OnPay.ru (рублю)', '6', '6', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Статус заказа', 'MODULE_PAYMENT_ONPAY_ORDER_STATUS', '0', 'Статус заказа после успешной оплаты', '6', '7', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Очередность при сортировке', 'MODULE_PAYMENT_ONPAY_SORT_ORDER', '0', 'Порядок вывода. Чем число меньше, тем больше приоритет.', '6', '8', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('URL скрипта для API-запросов', 'MODULE_PAYMENT_ONPAY_RESULT', '" .
            HTTP_SERVER . DIR_WS_CATALOG . "onpay.php', 'Параметр \"URL API\" в личном кабинете OnPay.ru', '6', '9', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Адрес при успешной оплате', 'MODULE_PAYMENT_ONPAY_SUCCESS', '" .
            HTTP_SERVER . DIR_WS_CATALOG .
            "checkout_success.php', 'URL страницы для перехода после успешной оплаты', '6', '10', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION .
            " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Адрес при ошибке оплаты', 'MODULE_PAYMENT_ONPAY_FAIL', '" .
            HTTP_SERVER . DIR_WS_CATALOG .
            "checkout_payment.php', 'URL страницы для перехода при неуспешной оплате', '6', '11', now())");
    }

    function remove()
    {
        tep_db_query("delete from " . TABLE_CONFIGURATION .
            " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys()
    {
        return array('MODULE_PAYMENT_ONPAY_STATUS', 'MODULE_PAYMENT_ONPAY_LOGIN',
            'MODULE_PAYMENT_ONPAY_PASSWORD1', 'MODULE_PAYMENT_ONPAY_KURS',
            'MODULE_PAYMENT_ONPAY_ORDER_STATUS', 'MODULE_PAYMENT_ONPAY_SORT_ORDER',
            'MODULE_PAYMENT_ONPAY_RESULT', 'MODULE_PAYMENT_ONPAY_SUCCESS',
            'MODULE_PAYMENT_ONPAY_FAIL');
    }
}
?>
