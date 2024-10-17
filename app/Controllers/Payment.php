<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\PaymentModel;
use PhonePe\payments\v1\PhonePePaymentClient;
use PhonePe\Env;
use PhonePe\payments\v1\models\request\builders\PgPayRequestBuilder;
use PhonePe\payments\v1\models\request\builders\InstrumentBuilder;
use PhonePe\common\config\Constants;
use DateTime;

class Payment extends BaseController
{
    use ResponseTrait;

    public function initiatePayment()
    {
        $rules = [
            'amount' => 'required',
            'plan_id' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_plan WHERE plan_id = ? ", [$value])->getRow();

                    if (is_null($result)) {
                        $error = $value . " does not exist";
                        return false;
                    }

                    return true;
                }
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $amount = $this->request->getVar('amount');
        $plan_id = $this->request->getVar('plan_id');
        $model = new PaymentModel();
        $result = $model->createOrder($amount, $plan_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function webhook()
    {

        $phonepeClient = new PhonePePaymentClient(getenv("MERCHANTID"), getenv("SALTKEY"), getenv("SALTINDEX"), Env::PRODUCTION, getenv("SHOLDPUBLISHEVENTS"));

        $xVerify =  $this->request->header('x-verify');
        $response = $this->request->getVar();

        $webhookRespone = file_get_contents('php://input');

        $xVerify = str_replace("X-Verify: ", "", $xVerify);
        $isValid = $phonepeClient->verifyCallback($webhookRespone, $xVerify);
        $webhook_data = base64_decode($response->response);
        $data = json_decode($webhook_data);

        $merchant_id = $data->data->merchantId;
        $merchant_transaction_id = $data->data->merchantTransactionId;
        $state = $data->data->state;
        $txn_id = $data->data->transactionId;

        $model = new PaymentModel();
        $result = $model->updateWebhookResponse($merchant_id, $merchant_transaction_id, $state, $webhook_data, $txn_id);
        if ($result) {
            echo 'updated successfully';
        } else {
            echo 'something went wrong';
        }
        die;
    }

    public function checkStatus()
    {
        $rules = [
            'merchant_transaction_id' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_payment_orders WHERE transaction_id = ? ", [$value])->getRow();

                    if (is_null($result)) {
                        $error = $value . " does not exist";
                        return false;
                    }

                    return true;
                }
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $merchant_transaction_id = $this->request->getVar('merchant_transaction_id');

        $model = new PaymentModel();
        $result = $model->getOrderStatus($merchant_transaction_id);

        if (!is_null($result)) {
            if ($result->webhook_response != NULL || $result->webhook_response != '') {
                $response = json_decode($result->webhook_response);
                return $this->respond([
                    'status' => true,
                    'message' => 'Success',
                    'data' => [
                        'transaction_id' => $response->data->transactionId,
                        'status' => $response->data->state
                    ]
                ]);
            } else {
                return $this->respond([
                    'status' => true,
                    'message' => 'Success',
                    'data' => [
                        'status' => $result->status,
                        'transaction_id' => null
                    ]
                ]);
            }
        }
    }

    public function handleRedirect()
    {
        $transactionId = $this->request->getVar('transactionId');
        $model = new PaymentModel();
        $result = $model->getOrderStatus($transactionId);
        $data = [];
        if (!is_null($result)) {
            $data['status'] = $result->status;
        }

        echo view('redirect.php', $data);
    }
}
