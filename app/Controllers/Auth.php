<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AuthModel;
use CodeIgniter\API\ResponseTrait;

use DateTime;

class Auth extends BaseController
{
    use ResponseTrait;

    public function register()
    {
        $rules = [
            'marital_status' => 'required',
            'gender' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'mobile' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE mobile_no = ? OR login_id = ?  ", [$value, $value])->getRow();

                    if (!is_null($result)) {
                        $error = $value . " already exist";
                        return false;
                    }

                    return true;
                }
            ],
            'email' => [
                'required',
                'valid_email'
            ],
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $data = $this->request->getVar();

        $model = new AuthModel();

        $result  = $model->register($data);

        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'Registration successful'
            ]);
        } else {
            return $this->fail([
                'status' => false,
                'message' => 'Registration not successful'
            ], STATUS_SERVER_ERROR);
        }
    }

    public function login()
    {
        $rules = [
            'mobile' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE mobile_no = ? OR login_id = ? ", [$value, $value])->getRow();

                    if (is_null($result)) {
                        $error = $value . " Doesn't exist";
                        return false;
                    }

                    return true;
                }
            ],

            'password' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $user = $db->query("SELECT * FROM net_profile WHERE mobile_no = ? OR login_id = ? ", [$data['mobile'], $data['mobile']])->getRow();

                    if (!is_null($user)) {
                        if ($user->password != $value) {
                            $error = $value . " Password does not match";
                            return false;
                        }
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

        $mobile = $this->request->getVar('mobile');

        $model = new AuthModel();
        $user = $model->getUser($mobile);

        $auth_token = generateAuthToken($user);

        return $this->respond([
            'status' => true,
            'data' => $auth_token,
            'message' => 'Login successful'
        ]);
    }

    public function otpVerify()
    {
        $rules = [
            'mobile' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE mobile_no = ? OR login_id = ? ", [$value, $value])->getRow();

                    if (is_null($result)) {
                        $error = $value . " Doesn't exist";
                        return false;
                    }

                    return true;
                }
            ],
            'otp' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();
                    $mobile = $data['mobile'];
                    $result = $db->query("SELECT * FROM net_profile WHERE mobile_no = ? OR login_id = ? ", [$mobile, $mobile])->getRow();

                    if (!is_null($result)) {
                        if ($result->login_otp == $data['otp']) {
                            $valid_time = $result->otp_valid_till;
                            $current_time = date('Y-m-d H:i:s');

                            if ($current_time >= $valid_time) {
                                $error = $data['otp'] . " Time Expired";
                                return false;
                            }
                        } else {
                            $error = $data['otp'] . " Invalid";
                            return false;
                        }
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

        $mobile = $this->request->getVar('mobile');

        $model = new AuthModel();
        $user = $model->getUser($mobile);

        $auth_token = generateAuthToken($user);

        return $this->respond([
            'status' => true,
            'message' => 'Verify Success',
            'data' => $auth_token
        ]);
    }


    public function sendOtp()
    {
        $rules = [
            'mobile' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE mobile_no = ? OR login_id = ? ", [$value, $value])->getRow();

                    if (is_null($result)) {
                        $error = $value . " Doesn't exist";
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
        $mobile = $this->request->getVar('mobile');

        $model = new AuthModel();
        $result = $model->sendSms($mobile);

        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'OTP Send Successfully'
            ]);
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }

    public function sendRegisterOtp()
    {
        $rules = [
            'mobile' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $mobile = $this->request->getVar('mobile');
        $model = new AuthModel();
        $result = $model->sendOtpForRegister($mobile);
        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'OTP Send Successfully',
                'otp' => $result['otp']
            ]);
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }
}
