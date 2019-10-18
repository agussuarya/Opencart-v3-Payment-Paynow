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

        $data['heading_title'] = $this->language->get('heading_title');

        $data['entry_text_config_one'] = $this->language->get('text_config_one');
        $data['entry_text_config_two'] = $this->language->get('text_config_two');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = $this->language->get('text_button_save');
        $data['button_cancel'] = $this->language->get('text_button_cancel');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['action'] = $this->url->link('extension/payment/paynow', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/paynow', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->request->post['payment_paynow_text_config_one'])) {
            $data['payment_paynow_text_config_one'] = $this->request->post['payment_paynow_text_config_one'];
        } else {
            $data['payment_paynow_text_config_one'] = $this->config->get('payment_paynow_text_config_one');
        }

        if (isset($this->request->post['payment_paynow_text_config_two'])) {
            $data['payment_paynow_text_config_two'] = $this->request->post['payment_paynow_text_config_two'];
        } else {
            $data['payment_paynow_text_config_two'] = $this->config->get('payment_paynow_text_config_two');
        }

        if (isset($this->request->post['payment_paynow_status'])) {
            $data['payment_paynow_status'] = $this->request->post['payment_paynow_status'];
        } else {
            $data['payment_paynow_status'] = $this->config->get('payment_paynow_status');
        }

        if (isset($this->request->post['payment_paynow_order_status_id'])) {
            $data['payment_paynow_order_status_id'] = $this->request->post['payment_paynow_order_status_id'];
        } else {
            $data['payment_paynow_order_status_id'] = $this->config->get('payment_paynow_order_status_id');
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/paynow', $data));
    }
}