<?php
use Aws\Sns\SnsClient;
use Carbon\Carbon;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;

class ControllerApiPaynow extends Controller
{
    /**
     * Change order status after ICN callback
     */
    public function staging()
    {
        // Get data
        $orderId = $this->arrayGet($_POST, 'order_id', '0');
        $status = $this->arrayGet($_POST, 'status', 'no-action');
        $transactionAmount = $this->arrayGet($_POST, 'transaction_amount', 0);
        $transactionCurrency = $this->arrayGet($_POST, 'transaction_currency', '');

        $canAccess = isset($this->session->data['api_id']);
        if (!$canAccess) {
            $json['error']['warning'] = 'Warning: You do not have permission to access the API!';
            $json['message'] = 'Callback failed.';
        } else if ($orderId == 0 || $status == 'no-action') {
            $json['error']['warning'] = 'Warning: Given data is invalid!';
            $json['message'] = 'Callback failed.';
        } else {
            $this->load->model('checkout/order');
            $this->load->language('extension/payment/paynow');

            if ($status == 'success') {
                $newStatus = $this->config->get('payment_paynow_order_success_status_id');
                $comment = $this->language->get('text_description_order_history_success');

                // Check currency & amount
                $order = $this->model_checkout_order->getOrder($orderId);
                if ($order['currency_code'] != $transactionCurrency || $order['total'] != $transactionAmount) {
                    // Create log
                    $this->logToCloudWatch([
                        'order' => [
                            'id' => $orderId,
                            'transaction_amount'=> $order['total'],
                            'transaction_currency'=> $order['currency_code'],
                        ],
                        'icn' => [
                            'transaction_amount'=> $transactionAmount,
                            'transaction_currency'=> $transactionCurrency,
                        ]
                    ]);
                    $json['message'] = 'Change order status not success.';
                } else {
                    $this->model_checkout_order->addOrderHistory($orderId, $newStatus, $comment, true);
                    $json['message'] = 'Change order status success.';
                }
            } else if ($status == 'failed') {
                $newStatus = $this->config->get('payment_paynow_order_failed_status_id');
                $comment = $this->language->get('text_description_order_history_failed');
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $newStatus, $comment, true);
                $json['message'] = 'Change order status success.';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Change order status after ICN callback
     */
    public function prod()
    {
        // Get data
        $orderId = $this->arrayGet($_POST, 'order_id', '0');
        $status = $this->arrayGet($_POST, 'status', 'no-action');
        $transactionAmount = $this->arrayGet($_POST, 'transaction_amount', 0);
        $transactionCurrency = $this->arrayGet($_POST, 'transaction_currency', '');

        $canAccess = isset($this->session->data['api_id']);
        if (!$canAccess) {
            $json['error']['warning'] = 'Warning: You do not have permission to access the API!';
            $json['message'] = 'Callback failed.';
        } else if ($orderId == 0 || $status == 'no-action') {
            $json['error']['warning'] = 'Warning: Given data is invalid!';
            $json['message'] = 'Callback failed.';
        } else {
            $this->load->model('checkout/order');
            $this->load->language('extension/payment/paynow');

            if ($status == 'success') {
                $newStatus = $this->config->get('payment_paynow_order_success_status_id');
                $comment = $this->language->get('text_description_order_history_success');

                // Check currency & amount
                $order = $this->model_checkout_order->getOrder($orderId);
                $currency = $order['currency_code'];
                $orderTotal = $this->currency->format($order['total'], $currency , false, false);

                if ($order['currency_code'] != $transactionCurrency || $orderTotal != $transactionAmount) {
                    // Create log
                    $this->logToCloudWatch([
                        'order' => [
                            'id' => $orderId,
                            'transaction_amount_real'=> $order['total'],
                            'transaction_amount_currency'=> $orderTotal,
                            'transaction_currency'=> $order['currency_code'],
                        ],
                        'icn' => [
                            'transaction_amount'=> $transactionAmount,
                            'transaction_currency'=> $transactionCurrency,
                        ]
                    ]);
                    $json['message'] = 'Change order status not success.';
                } else {
                    $this->model_checkout_order->addOrderHistory($orderId, $newStatus, $comment, true);
                    $json['message'] = 'Change order status success.';
                }
            } else if ($status == 'failed') {
                $newStatus = $this->config->get('payment_paynow_order_failed_status_id');
                $comment = $this->language->get('text_description_order_history_failed');
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $newStatus, $comment, true);
                $json['message'] = 'Change order status success.';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * This function for lambda to add sg qr to order history.
     */
    public function addSgQrToOrderHistory()
    {
        // Get data
        $orderId = array_key_exists('order_id', $_POST) ? $_POST['order_id'] : 0;
        $sgQrBase64 = array_key_exists('sg_qr_base64', $_POST) ? $_POST['sg_qr_base64'] : '';
        $json = [];

        $canAccess = isset($this->session->data['api_id']);
        if (!$canAccess) {
            $json['error']['warning'] = 'Warning: You do not have permission to access the API!';
            $json['message'] = 'Action failed.';
        } else if ($orderId == 0 || $sgQrBase64 == '') {
            $json['error']['warning'] = 'Warning: Given data is invalid!';
            $json['message'] = 'Action failed.';
        } else {
            $this->load->language('extension/payment/paynow');

            $this->load->model('checkout/order');
            $this->load->model('extension/payment/paynow');

            // Add paynow qr code to order history
            $comment  = $this->language->get('text_instruction') . ":\n\n";
            $comment .= $this->language->get('text_description_order_history_2') . "\n\n";
            $comment .= "<img id='barcode' src='data:image/png;base64, $sgQrBase64' alt='Scan this barcode to pay' title='Scan this barcode to pay' />\n\n";
            $comment .= $this->language->get('text_payment');
            $newStatus = $this->config->get('payment_paynow_order_init_status_id');

            $this->model_checkout_order->addOrderHistory($orderId, $newStatus, $comment, true);

            $json['redirect'] = $this->url->link('checkout/success');
            $json['message'] = 'Action success.';

            // Remove pending generate qy code
            $this->model_extension_payment_paynow->deletePendingOrder($orderId);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Re add sg qr code to order history for order doesn't have qr code
     * This function not yet implemented
     */
    public function reAddSgQrToPendingOrder()
    {
        $this->load->model('extension/payment/paynow');
        $tableName = $this->model_extension_payment_paynow->getTableName();

        // Select pending order by paynow
        $query = $this->db->query("SELECT * FROM `" . $tableName . "` WHERE TIME_TO_SEC(TIMEDIFF(NOW(), date_added)) > 3600");
        $paynowPendingOrders = $query->rows;
        foreach ($paynowPendingOrders as $paynowPendingOrder) {
            $this->publishSns($paynowPendingOrder['order_id']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'message' => 'Success.'
        ]));
    }

    /**
     * Publish sns for trigger lambda to add sg qr code
     * @param $orderId
     */
    private function publishSns($orderId)
    {
        // Get order info
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($orderId);
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
                    'StringValue' => $orderId,
                ],
                'amount' => [
                    'DataType' => 'Number',
                    'StringValue' => $orderAmount,
                ],
                'billReferenceNumber' => [
                    'DataType' => 'String',
                    'StringValue' => SNS_MESSAGE_ATTRIBUTE_3_PREFIX . '' . $orderId,
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
     * Log error to cloudwatch
     * @param $datas
     * @throws Exception
     */
    private function logToCloudWatch($datas)
    {
        require DIR_APP . 'vendor/autoload.php';

        // Config log file
        $logFile = 'log-' . APP_ENV . "-error-paynow.log";
        $appName = "TestApp01";
        $facility = "local0";

        // Config log cloudwatch
        $cwClient = new CloudWatchLogsClient([
            'region' => AWS_DEFAULT_REGION, //Change according to you
            'credentials' => [
                'key'    => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
            'version' => CLOUDWATCH_VERSION
        ]);
        // Log group name, will be created if none
        $cwGroupName = 'organicfolks';
        // Log stream name, will be created if none
        $cwStreamNameInstance = APP_ENV . '-error-paynow';
        // Days to keep logs, 14 by default
        $cwRetentionDays = 90;
        // Log for notice & error
        $cwHandlerInstanceNotice = new CloudWatch($cwClient, $cwGroupName, $cwStreamNameInstance, $cwRetentionDays, 10000, [ 'application' => 'php-testapp01' ],Logger::NOTICE);
        $cwHandlerInstanceError = new CloudWatch($cwClient, $cwGroupName, $cwStreamNameInstance, $cwRetentionDays, 10000, [ 'application' => 'php-testapp01' ],Logger::ERROR);

        $formatter = new LineFormatter(null, null, false, true);
        $syslogFormatter = new LineFormatter("%channel%: %level_name%: %message% %context% %extra%",null,false,true);
        $infoHandler = new StreamHandler(__DIR__."/".$logFile, Logger::INFO);
        $warnHandler = new SyslogHandler($appName, $facility, Logger::WARNING);

        $infoHandler->setFormatter($formatter);
        $warnHandler->setFormatter($syslogFormatter);
        $cwHandlerInstanceNotice->setFormatter($formatter);
        $cwHandlerInstanceError->setFormatter($formatter);

        $logger = new Logger('PHP Logging');
        $logger->pushHandler($warnHandler);
        $logger->pushHandler($infoHandler);
        $logger->pushHandler($cwHandlerInstanceNotice);
        $logger->pushHandler($cwHandlerInstanceError);

        $logger->notice('Error', $datas);
    }

    /**
     * Get value from array
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    function arrayGet($array, $key, $default = null)
    {
        if (is_array($array)) {
            return array_key_exists($key, $array) ? $array[$key] : $default;
        } else {
            return $default;
        }
    }
}
