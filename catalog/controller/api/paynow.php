<?php
class ControllerApiPaynow extends Controller
{
    /**
     * Callback from paynow
     */
    public function staging()
    {
        // Get data
        $orderId = array_key_exists('order_id', $_POST) ? $_POST['order_id'] : 0;
        $status = array_key_exists('status', $_POST) ? $_POST['status'] : 'no-action';
        $json = [];

        // Security ongoing :)
        $canAccess = true;

        if (!$canAccess) {
            $json['error']['warning'] = 'Hmm...';
        } else {
            // Process if order id exists
            if ($orderId == 0 || $status == 'no-action') {
                $json['error']['warning'] = 'Hmm...';
            } else {
                // Process
                $this->load->model('checkout/order');
                $comment = "Successfully paid";
                $newStatus = $status == 'paid' ? $this->config->get('payment_paynow_order_success_status_id') : $this->config->get('payment_paynow_order_failed_status_id');
                $this->model_checkout_order->addOrderHistory($orderId, $newStatus, $comment, true);

                $json['redirect'] = $this->url->link('checkout/success');
                $json['message'] = 'Success.';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function prod()
    {
        $json = [
            'a' => 'a'
        ];
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
