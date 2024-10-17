<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    /**
     * create order for payment
     * 
     * @param int $amount
     * @param int $plan_id
     * @return string 
     */
    public function createOrder($amount, $plan_id)
    {
        try {
            $user = authuser();
            $user_id = $user->profile_id;
            $mobile = $user->mobile_no;

            $merchantTransactionId = 'AMPAY' . date("ymdHis");

            $plan_details = $this->db->table('net_plan')
                ->where('plan_id', $plan_id)
                ->get()
                ->getRow();

            $order_data = [
                'merchant_id' => getenv("MERCHANTID"),
                'mobile_number' => $mobile,
                'transaction_id' => $merchantTransactionId,
                'amount' => $amount,
                'plan_id' => $plan_id,
                'plan_name' => $plan_details->plan_name,
                'plan_amount' => $plan_details->amount,
                'user_id' => $user_id,
                'status' => 'PENDING',
                'created_by' => $user_id
            ];

            $this->db->table('net_payment_orders')->insert($order_data);

            $order_url = initPayment($mobile, $amount, $user_id, $merchantTransactionId);

            if ($order_url) {
                return ['url' => $order_url, 'merchant_transaction_id' => $merchantTransactionId];
            }
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            print_r($e->getMessage());
        }
    }

    /**
     * Update webhook response
     * 
     * @param string $merchant_id
     * @param string $merchantTransactionId
     * @param object $data
     * @param string $txn_id
     * @return  bool
     */
    public function updateWebhookResponse($merchant_id, $merchantTransactionId, $state, $data, $txn_id)
    {
        try {
            $webhook_data = [
                'webhook_response' => $data,
                'status' => $state,

            ];
            $update = $this->db->table('net_payment_orders')
                ->where('merchant_id', $merchant_id)
                ->where('transaction_id', $merchantTransactionId)
                ->update($webhook_data);

            if ($update) {
                $current_plan = $this->db->table('net_payment_orders')
                    ->where('transaction_id', $merchantTransactionId)
                    ->get()
                    ->getRow();
                if (!is_null($current_plan)) {

                    $profile_data = $this->db->table('net_profile')
                        ->where('profile_id', $current_plan->user_id)
                        ->get()
                        ->getRow();

                    $insert_data = [
                        'customer_id' => $profile_data->customer_id,
                        'amount' => $current_plan->amount,
                        'txnid' => $txn_id,
                        'add_date' => date('Y-m-d'),
                        'status' => $state,
                        'datetime' => date('Y-m-d H:i:s a')
                    ];

                    $insert = $this->db->table('net_member_pay')
                        ->insert($insert_data);

                    if ($insert) {
                        if ($state == 'COMPLETED') {
                            $data = [
                                'plan_id' => $current_plan->plan_id,
                                'amount' => $current_plan->amount,
                                'got_payment' => 'Y'
                            ];

                            $result = $this->db->table('net_profile')
                                ->where('profile_id', $current_plan->user_id)
                                ->update($data);

                            if ($result) {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    } else {
                        return false;
                    }
                }
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    /**
     * Get payment order by transaction id
     * 
     * @param string $transactionId
     * @return object
     */
    public function getOrderStatus($merchant_transaction_id)
    {
        return $this->db->table('net_payment_orders')
            ->where('transaction_id', $merchant_transaction_id)
            ->get()
            ->getRow();
    }
}
