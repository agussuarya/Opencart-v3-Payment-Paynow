<?php

use Aws\Sns\SnsClient;
use Carbon\Carbon;

class ControllerExtensionPaymentPayNow extends Controller {

    /**
     * This function called when customer select payment paynow
     */
    public function index() {
        $this->language->load('extension/payment/paynow');

        if ($this->request->server['HTTPS']) {
            $data['store_url'] = HTTPS_SERVER;
        } else {
            $data['store_url'] = HTTP_SERVER;
        }

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_loading'] = $this->language->get('text_loading');

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

            return $this->load->view( 'extension/payment/paynow', $data );
        }
    }

//    public function callback() {
//        if (isset($this->request->post['orderid'])) {
//            $order_id = trim(substr(($this->request->post['orderid']), 6));
//        } else {
//            die('Illegal Access');
//        }
//
//        $this->load->model('checkout/order');
//        $order_info = $this->model_checkout_order->getOrder($order_id);
//
//        if ($order_info) {
//            $data = array_merge($this->request->post,$this->request->get);
//
//            //payment was made successfully
//            if ($data['status'] == 'Y' || $data['status'] == 'y') {
//                // update the order status accordingly
//            }
//        }
//    }


    /**
     * This function called when customer select payment paynow & click confirm button
     */
    public function confirm() {
        $json = array();

        if ($this->session->data['payment_method']['code'] == 'paynow') {
            $this->load->language('extension/payment/paynow');

            $this->load->model('checkout/order');
            $this->load->model('extension/payment/paynow');

            $comment = $this->language->get('text_description_order_history_1');

            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_paynow_order_init_status_id'), $comment, true);
            $this->model_extension_payment_paynow->addPendingOrder($this->session->data['order_id']);

            //$json['redirect'] = $this->url->link('checkout/success');
            $json['redirect'] = $this->url->link('account/order/info&order_id=' . $this->session->data['order_id']);

            // SNS
            $this->publishSns();

            // Clear cart and other session
            $this->checkoutSuccess();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Trigger lambda to add sg qr code to order history
     */
    private function publishSns()
    {
        // Get order info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $currency = $order_info['currency_code'];
        $orderAmount = $this->currency->format($order_info['total'], $currency , false, false);

        require DIR_APP . 'vendor/autoload.php';

        $sns = new SnsClient([
            'region' => AWS_DEFAULT_REGION, //Change according to you
            'version' => SNS_VERSION, //Change according to you
            'credentials' => [
                'key'    => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
            'scheme' => 'http', //disables SSL certification, there was an error on enabling it
        ]);
        $params = [
            'Message' => 'example message',
            'Subject' => 'example subject',
            'MessageAttributes' => [
                'orderId' => [
                    'DataType' => 'Number',
                    'StringValue' => $this->session->data['order_id'],
                ],
                'amount' => [
                    'DataType' => 'Number',
                    'StringValue' => $orderAmount,
                ],
                'billReferenceNumber' => [
                    'DataType' => 'String',
                    'StringValue' => SNS_MESSAGE_ATTRIBUTE_3_PREFIX . '' . $this->session->data['order_id'],
                ],
                'expiryDate' => [
                    'DataType' => 'String',
                    'StringValue' => Carbon::now()->addDays(2)->format('Ymd'),
                ]
            ],
            'TopicArn' => AWS_SNS_TOPIC_ARN,
        ];

        $result = $sns->publish($params);
    }

    /**
     * Clear all session cart
     */
    private function checkoutSuccess()
    {
        $this->cart->clear();

        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['guest']);
        unset($this->session->data['comment']);
        // unset($this->session->data['order_id']);
        unset($this->session->data['coupon']);
        unset($this->session->data['reward']);
        unset($this->session->data['voucher']);
        unset($this->session->data['vouchers']);
        unset($this->session->data['totals']);
    }
}
