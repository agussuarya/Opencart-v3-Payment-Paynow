<?php

class ControllerExtensionPaymentPayNow extends Controller {
    private $error = array();

    public function index() {
        $this->language->load('extension/payment/paynow');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->model_setting_setting->editSetting('payment_paynow', $this->request->post);

            $this->session->data['success'] = 'Saved.';

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['heading_title'] = $this->language->get('heading_title');

        $data['action'] = $this->url->link('extension/payment/paynow', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/payment/paynow', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        // Config status
        if (isset($this->request->post['payment_paynow_status'])) {
            $data['payment_paynow_status'] = $this->request->post['payment_paynow_status'];
        } else if($this->config->has('payment_paynow_status')){
            $data['payment_paynow_status'] = $this->config->get('payment_paynow_status');
        } else {
            $data['payment_paynow_status'] = 0;
        }

        //Config debug
        if (isset($this->request->post['payment_paynow_debug'])) {
            $data['payment_paynow_debug'] = $this->request->post['payment_paynow_debug'];
        } else if($this->config->has('payment_paynow_debug')){
            $data['payment_paynow_debug'] = (int)$this->config->get('payment_paynow_debug');
        } else {
            $data['payment_paynow_debug'] = 0;
        }

        // Config order success status
        if (isset($this->request->post['payment_paynow_order_success_status_id'])) {
            $data['payment_paynow_order_success_status_id'] = $this->request->post['payment_paynow_order_success_status_id'];
        } else if($this->config->has('payment_paynow_order_success_status_id')){
            $data['payment_paynow_order_success_status_id'] = $this->config->get('payment_paynow_order_success_status_id');
        } else {
            $data['payment_paynow_order_success_status_id'] = '';
        }

        // Config order failed status
        if (isset($this->request->post['payment_paynow_order_failed_status_id'])) {
            $data['payment_paynow_order_failed_status_id'] = $this->request->post['payment_paynow_order_failed_status_id'];
        } else if($this->config->has('payment_paynow_order_failed_status_id')){
            $data['payment_paynow_order_failed_status_id'] = $this->config->get('payment_paynow_order_failed_status_id');
        } else {
            $data['payment_paynow_order_failed_status_id'] = '';
        }

        // Config sort order
        if (isset($this->request->post['payment_paynow_sort_order'])) {
            $data['payment_paynow_sort_order'] = $this->request->post['payment_paynow_sort_order'];
        } else if($this->config->has('payment_paynow_sort_order')){
            $data['payment_paynow_sort_order'] = (int)$this->config->get('payment_paynow_sort_order');
        } else {
            $data['payment_paynow_sort_order'] = 0;
        }

        // Config text one
        if (isset($this->request->post['payment_paynow_config_one'])) {
            $data['payment_paynow_config_one'] = $this->request->post['payment_paynow_config_one'];
        } else if($this->config->has('payment_paynow_config_one')){
            $data['payment_paynow_config_one'] = $this->config->get('payment_paynow_config_one');
        } else {
            $data['payment_paynow_config_one'] = '';
        }

        // Config text two
        if (isset($this->request->post['payment_paynow_config_two'])) {
            $data['payment_paynow_config_two'] = $this->request->post['payment_paynow_config_two'];
        } else if($this->config->has('payment_paynow_config_two')){
            $data['payment_paynow_config_two'] = $this->config->get('payment_paynow_config_two');
        } else {
            $data['payment_paynow_config_two'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/paynow', $data));
    }
}