<?php
class ModelExtensionPaymentPayNow extends Model {
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
}