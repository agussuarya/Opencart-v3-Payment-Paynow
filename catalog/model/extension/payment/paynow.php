<?php
class ModelExtensionPaymentPayNow extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/paynow');

        $method_data = array(
            'code'     => 'paynow',
            'title'    => $this->language->get('text_title'),
            'sort_order' => $this->config->get('paynow_sort_order')
        );

        return $method_data;
    }
}