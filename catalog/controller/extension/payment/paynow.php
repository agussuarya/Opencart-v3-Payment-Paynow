<?php

class ControllerExtensionPaymentPayNow extends Controller {
    public function index() {
        $this->language->load('extension/payment/paynow');

        if ($this->request->server['HTTPS']) {
            $data['store_url'] = HTTPS_SERVER;
        } else {
            $data['store_url'] = HTTP_SERVER;
        }

        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['action'] = 'https://yourpaymentgatewayurl';

        // Get order info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Get order billing country
        $this->load->model('localisation/country');
        $country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);

        // We will use this owner info to send Paynow from client side
        $data['billing_details'] = array(
            'billing_details' => array(
                'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                'email' => $order_info['email'],
                'address' => array(
                    'line1'	=> $order_info['payment_address_1'],
                    'line2'	=> $order_info['payment_address_2'],
                    'city'	=> $order_info['payment_city'],
                    'state'	=> $order_info['payment_zone'],
                    'postal_code' => $order_info['payment_postcode'],
                    'country' => $country_info['iso_code_2']
                )
            )
        );

        if ($order_info) {
            $data['text_config_one'] = trim($this->config->get('payment_paynow_config_one'));
            $data['text_config_two'] = trim($this->config->get('payment_paynow_config_two'));
            $data['orderid'] = date('His') . $order_info['order_id'];
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

            // Generate barcode
            $data['barcode_paynow'] = 'Yasa test';

            return $this->load->view( 'extension/payment/paynow', $data );
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

    public function confirm() {
        $json = array();

        if ($this->session->data['payment_method']['code'] == 'paynow') {
            $this->load->language('extension/payment/paynow');

            $this->load->model('checkout/order');

            $comment  = $this->language->get('text_instruction') . ":\n\n";
            $comment .= $this->language->get('text_description_order_history') . "\n\n";
            $comment .= "<img id='barcode' src='https://api.qrserver.com/v1/create-qr-code/?data=xxx&amp;size=100x100' alt='Scan this barcode to pay' title='Scan this barcode to pay' width='100' height='100' />\n\n";
            $comment .= $this->language->get('text_payment');

            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_paynow_order_init_status_id'), $comment, true);

            $json['redirect'] = $this->url->link('checkout/success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
