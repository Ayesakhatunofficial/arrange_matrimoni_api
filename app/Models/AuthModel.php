<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthModel extends Model
{
    /**
     * Register user 
     * 
     * @param array $post_data
     * @return bool
     */
    public function register($post_data)
    {
        $cust_id = generateCustomerId();

        $data = [
            'first_name' => $post_data['first_name'],
            'middle_name' => $post_data['middle_name'],
            'last_name' => $post_data['last_name'],
            'email' => $post_data['email'],
            'gender' => $post_data['gender'],
            'maritial_status' => $post_data['marital_status'],
            'login_id' => $post_data['mobile'],
            'customer_id' => $cust_id,
            'mobile_no' => $post_data['mobile'],
            'password' => $post_data['password'],
            'add_date' => date('Y-m-d'),
            'amount' => 0,
            'mode' => 'online'
        ];

        return $this->db->table('net_profile')
            ->insert($data);
    }

    /**
     * Get user data by mobile 
     * 
     * @param int $mobile
     * @return object
     */
    public function getUser($mobile)
    {
        return $this->db->table('net_profile')
            ->where('mobile_no', $mobile)
            ->orWhere('login_id', $mobile)
            ->get()
            ->getRow();
    }

    /**
     * Get user data by user id 
     * 
     * @param int $id
     * @return object
     */
    public function findUserByUserId($id)
    {
        return $this->db->table('net_profile')
            ->where('profile_id', $id)
            ->get()
            ->getRow();
    }

    /**
     * Send SMS for OTP to user mobile number
     * 
     * @param int $mobile
     * @return bool
     */
    public function sendSms($mobile)
    {
        try {
            $n = 6;
            $otp = generateNumericOTP($n);

            $data = [
                'login_otp' => $otp,
                'otp_valid_till' => date('Y-m-d H:i:s', time() + (10 * 60))
            ];

            $update = $this->db->table('net_profile')
                ->where('mobile_no', $mobile)
                ->update($data);
            if ($update) {
                // Message details
                $numbers = array($mobile);
                $sender = urlencode('ARRANG');

                $message = rawurlencode(" $otp is your OTP in Arrange Matrimony for mobile Verification, please do not share to anyone.");

                $result = sendSms($numbers, $sender, $message);

                if ($result->status == 'success') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP for mobile number verification
     * 
     * @param int $mobile 
     * @return array|bool
     */
    public function sendOtpForRegister($mobile)
    {
        try {
            $n = 6;
            $otp = generateNumericOTP($n);


            // Message details
            $numbers = array($mobile);
            $sender = urlencode('ARRANG');

            $message = rawurlencode(" $otp is your OTP in Arrange Matrimony for mobile Verification, please do not share to anyone.");

            $result = sendSms($numbers, $sender, $message);

            if ($result->status == 'success') {
                return  [
                    'otp' => $otp
                ];
            } else {
                return false;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }
}
