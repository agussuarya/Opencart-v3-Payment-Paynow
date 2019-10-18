<?php

class ControllerExtensionPaymentPayNow extends Controller {
    public function index() {
        $this->language->load('extension/payment/paynow');

        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['action'] = 'https://yourpaymentgatewayurl';

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {
            $data['text_config_one'] = trim($this->config->get('text_config_one'));
            $data['text_config_two'] = trim($this->config->get('text_config_two'));
            $data['orderid'] = date('His') . $this->session->data['order_id'];
            $data['callbackurl'] = $this->url->link('payment/paynow/callback');
            $data['orderdate'] = date('YmdHis');
            $data['currency'] = $order_info['currency_code'];
            $data['orderamount'] = $this->currency->format($order_info['total'], $data['currency'] , false, false);
            $data['billemail'] = $order_info['email'];
            $data['billphone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
            $data['billaddress'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
            $data['billcountry'] = html_entity_decode($order_info['payment_iso_code_2'], ENT_QUOTES, 'UTF-8');
            $data['billprovince'] = html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');;
            $data['billcity'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
            $data['billpost'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
            $data['deliveryname'] = html_entity_decode($order_info['shipping_firstname'] . $order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8');
            $data['deliveryaddress'] = html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8');
            $data['deliverycity'] = html_entity_decode($order_info['shipping_city'], ENT_QUOTES, 'UTF-8');
            $data['deliverycountry'] = html_entity_decode($order_info['shipping_iso_code_2'], ENT_QUOTES, 'UTF-8');
            $data['deliveryprovince'] = html_entity_decode($order_info['shipping_zone'], ENT_QUOTES, 'UTF-8');
            $data['deliveryemail'] = $order_info['email'];
            $data['deliveryphone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
            $data['deliverypost'] = html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/paynow')){
                return $this->load->view( $this->config->get('config_template' ) . '/template/extension/payment/paynow',
                    $data );
            } else {
                return $this->load->view( 'extension/payment/paynow', $data );
            }
        }
    }

    public function callback() {
        if (isset($this->request->post['orderid'])) {
            $order_id = trim(substr(($this->request->post['orderid']), 6));
        } else {
            die('Illegal Access');
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            $data = array_merge($this->request->post,$this->request->get);

            //payment was made successfully
            if ($data['status'] == 'Y' || $data['status'] == 'y') {
                // update the order status accordingly
            }
        }
    }
}
