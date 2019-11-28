<?php
class ModelExtensionPaymentPayNow extends Model {
    private $TABLE_PAYNOW_PENDING_ORDER = DB_PREFIX . "order_pending_by_paynow";

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/paynow');

        $status = true;

        // Paynow does not allow payment for 0 amount
        if($total <= 0) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'paynow',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_paynow_sort_order')
            );
        }

        return $method_data;
    }

    public function log($file, $line, $caption, $message){

        if(!$this->config->get('payment_paynow_debug')){
            return;
        }

        $iso_time = date('c');
        $filename = 'paynow-'.strstr($iso_time, 'T', true).'.log';

        $log = new Log($filename);
        $msg = "[" . $iso_time . "] ";
        $msg .= "<" . $file . "> ";
        $msg .= "#" . $line . "# ";
        $msg .= "~" . $caption . "~ ";

        if(is_array($message)){
            $msg .= print_r($message, true);
        } else {
            $msg .= PHP_EOL . $message;
        }

        $msg .= PHP_EOL . PHP_EOL;
        $log->write($msg);
    }

    public function addPendingOrder($orderId)
    {
        // Get data order details
        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($orderId);

        // Store pending order
        if ($order) {
            $this->db->query("INSERT INTO " . $this->TABLE_PAYNOW_PENDING_ORDER . " SET order_id = '" . (int)$order['order_id'] . "', total = '" . $order['total'] . "', currency_id = '" . (int)$order['currency_id'] . "', currency_code = '" . $order['currency_code'] . "', currency_value = '" . $order['currency_value'] . "', date_added = NOW(), date_modified = NOW()");
        }
    }

    public function deletePendingOrder($orderId)
    {
        $this->db->query("DELETE FROM " . $this->TABLE_PAYNOW_PENDING_ORDER . " WHERE order_id = '" . (int)$orderId . "'");
    }

    /**
     * Run this sql query
     */
    private function importSqlTable()
    {
        $this->db->query("
            CREATE TABLE `oc_order_pending_by_paynow` (
                `order_id` int(11) NOT NULL AUTO_INCREMENT,
              `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
              `currency_id` int(11) NOT NULL,
              `currency_code` varchar(3) NOT NULL,
              `currency_value` decimal(15,8) NOT NULL DEFAULT '1.00000000',
              `date_added` datetime NOT NULL,
              `date_modified` datetime NOT NULL,
              PRIMARY KEY (`order_id`)
            ) ENGINE=MyISAM AUTO_INCREMENT=171 DEFAULT CHARSET=utf8;
        ");

    }

    public function getTableName()
    {
        return $this->TABLE_PAYNOW_PENDING_ORDER;
    }
}