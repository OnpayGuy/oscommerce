<?php

function get_var($name)
{
    return (isset($_GET[$name])) ? $_GET[$name] : ((isset($_POST[$name])) ? $_POST[$name] : null);
}

require ('includes/application_top.php');
/**
 *  транслитерация
 */
function encodestring($st)
{
    return strtr($st, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' =>
        'd', 'е' => 'e', 'ж' => 'g', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' =>
        'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' =>
        't', 'у' => 'u', 'ф' => 'f', 'ы' => 'i', 'э' => 'e', 'А' => 'A', 'Б' => 'B', 'В' =>
        'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ж' => 'G', 'З' => 'Z', 'И' => 'I', 'Й' =>
        'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' =>
        'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Ы' => 'I', 'Э' => 'E', 'ё' =>
        "yo", 'х' => "h", 'ц' => "ts", 'ч' => "ch", 'ш' => "sh", 'щ' => "shch", 'ъ' =>
        "", 'ь' => "", 'ю' => "yu", 'я' => "ya", 'Ё' => "YO", 'Х' => "H", 'Ц' => "TS",
        'Ч' => "CH", 'Ш' => "SH", 'Щ' => "SHCH", 'Ъ' => "", 'Ь' => "", 'Ю' => "YU", 'Я' =>
        "YA"));
}
/**
 *  XML ответ на check запрос
 */
function uc_onpay_answer($type, $code, $pay_for, $order_amount, $order_currency,
    $text, $key)
{
    $md5 = strtoupper(md5("$type;$pay_for;$order_amount;$order_currency;$code;$key"));
    $text = encodestring($text);
    echo iconv('cp1251', 'utf-8', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n<pay_for>$pay_for</pay_for>\n<comment>$text</comment>\n<md5>$md5</md5>\n</result>");
    exit;
}
/**
 *  XML ответ на pay запрос
 */
function uc_onpay_answerpay($type, $code, $pay_for, $order_amount, $order_currency,
    $text, $onpay_id, $key)
{
    $md5 = strtoupper(md5("$type;$pay_for;$onpay_id;$pay_for;$order_amount;$order_currency;$code;$key"));
    $text = encodestring($text);
    echo iconv('cp1251', 'utf-8', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n <comment>$text</comment>\n<onpay_id>$onpay_id</onpay_id>\n <pay_for>$pay_for</pay_for>\n<order_id>$pay_for</order_id>\n<md5>$md5</md5>\n</result>");
    exit;
}
/**
 *  ONPAY API
 */

if (empty($_REQUEST['type']))
    exit;
$login = MODULE_PAYMENT_ONPAY_LOGIN; //Ваше "Имя пользователя" (логин) в системе OnPay.ru
$key = MODULE_PAYMENT_ONPAY_PASSWORD1; //Ваш "Секретный ключ для API IN" в системе OnPay.ru
//Ответ на запрос check от OnPay
if ($_REQUEST['type'] == 'check') {
    $order_amount = $amount = $_REQUEST['order_amount'];
    $order_currency = $_REQUEST['order_currency'];
    $order_id = $pay_for = $_REQUEST['pay_for'];
    $sum = floor(100*floatval($order_amount)/MODULE_PAYMENT_ONPAY_KURS)*0.01;
    $order_id = intval($order_id); //Код должен быть целым числом


    $orders_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL .
        " where orders_id = '" . $order_id . "' and class = 'ot_total' limit 1");
    $orders = @tep_db_fetch_array($orders_query);
    $order_summ = @floatval($orders['value']);
    unset($orders_query, $orders);


    $res = "";
    if (empty($order_summ)) {
        $res = 'ERROR 13: NO ORDER';
    } elseif ($order_summ != $sum) {
        $res = 'ERROR 14: ORDER SUM HACKED';
    }
    if ($res != "") {
        uc_onpay_answer($_REQUEST['type'], 2, $pay_for, $order_amount, $order_currency,
            $res, $key);
    }
    // можно принимать деньги
    uc_onpay_answer($_REQUEST['type'], 0, $pay_for, $order_amount, $order_currency,
        'OK', $key);
}
//Ответ на запрос pay от OnPay
elseif ($_REQUEST['type'] == "pay") {
    $onpay_id = $_REQUEST['onpay_id'];
    $order_id = $code = $pay_for = $_REQUEST['pay_for'];
    $amount = $order_amount = $_REQUEST['order_amount'];
    $order_currency = $_REQUEST['order_currency'];
    $balance_amount = $_REQUEST['balance_amount'];
    $balance_currency = $_REQUEST['balance_currency'];
    $exchange_rate = $_REQUEST['exchange_rate'];
    $paymentDateTime = $_REQUEST['paymentDateTime'];
    $md5 = $_REQUEST['md5'];
    $error = '';
    //Проверка входных данных
    if (preg_replace('/[^0-9]/ismU', '', $onpay_id) != $onpay_id)
        $error = "ERROR 1: NO ID";
    elseif (strlen($onpay_id) < 1 or strlen($onpay_id) > 32)
        $error = "ERROR 2: NO ID";
    elseif (preg_replace('/[^0-9a-z]/ismU', '', $pay_for) != $pay_for)
        $error = "ERROR 3: NO ORDER ID";
    elseif (strlen($pay_for) < 1 or strlen($pay_for) > 32)
        $error = "ERROR 4: NO ORDER ID";
    elseif (preg_replace('/[^0-9\.]/ismU', '', $order_amount) != $order_amount)
        $error = "ERROR 5: NO ORDER SUM";
    elseif (floatval($order_amount) <= 0)
        $error = "ERROR 6: NO ORDER SUM";
    elseif (preg_replace('/[^0-9\.]/ismU', '', $balance_amount) != $balance_amount)
        $error = "ERROR 7: NO ORDER SUM";
    elseif (floatval($balance_amount) <= 0)
        $error = "ERROR 8: NO ORDER SUM";
    elseif (strlen($order_currency) != 3)
        $error = "ERROR 9: NO ORDER CURRENCY";
    elseif (strlen($balance_currency) != 3)
        $error = "ERROR 10: NO ORDER CURRENCY";
    elseif (preg_replace('/[^0-9a-z\.]/ismU', '', $exchange_rate) != $exchange_rate)
        $error = "ERROR 11: NO ORDER SUM";
    elseif (strlen($exchange_rate) < 1 or strlen($exchange_rate) > 10)
        $error = "ERROR 12: NO ORDER SUM";
    // произошла ошибка, не разрешаем платеж
    if ($error != '')
        uc_onpay_answerpay($_REQUEST['type'], 3, $pay_for, $order_amount, $order_currency,
            $error, $onpay_id, $key);
    $order_id = intval($order_id);
    $sum = floor(100*floatval($order_amount)/MODULE_PAYMENT_ONPAY_KURS)*0.01;
    

    $orders_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL .
        " where orders_id = '" . $order_id . "' and class = 'ot_total' limit 1");
    $orders = @tep_db_fetch_array($orders_query);
    $order_summ = @floatval($orders['value']);
    unset($orders_query, $orders);


    $res = "";
    if (empty($order_summ)) {
        $res = 'ERROR 13: NO ORDER';
    } elseif ($order_summ != $sum) {
        $res = 'ERROR 14: ORDER SUM HACKED';
    } elseif (strtoupper(md5($_REQUEST['type'] . ";" . $pay_for . ";" . $onpay_id .
    ";" . $order_amount . ";" . $order_currency . ";" . $key . "")) != $_REQUEST['md5']) {
        $res = 'ERROR 15: MD5 SIGN HACKED';
        uc_onpay_answerpay($_REQUEST['type'], 7, $pay_for, $order_amount, $order_currency,
            $res, $onpay_id, $key);
    }
    if ($res != "") {
        // произошла ошибка, не разрешаем платеж
        uc_onpay_answerpay($_REQUEST['type'], 3, $pay_for, $order_amount, $order_currency,
            $res, $onpay_id, $key);
    }
    // зачисляем платеж
    $sql_data_array = array('orders_status' => MODULE_PAYMENT_ONPAY_ORDER_STATUS);
    tep_db_perform('orders', $sql_data_array, 'update', "orders_id='" . $order_id .
        "'");

    $sql_data_arrax = array('orders_id' => $order_id, 'orders_status_id' =>
        MODULE_PAYMENT_ONPAY_ORDER_STATUS, 'date_added' => 'now()', 'customer_notified' =>
        '0', 'comments' => 'Onpay accepted this order payment');
    tep_db_perform('orders_status_history', $sql_data_arrax);
    uc_onpay_answerpay($_REQUEST['type'], 0, $pay_for, $order_amount, $order_currency,
        'OK', $onpay_id, $key);
}

?>
