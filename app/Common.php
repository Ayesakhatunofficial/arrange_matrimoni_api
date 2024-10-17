<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

use PhonePe\payments\v1\PhonePePaymentClient;
use PhonePe\Env;
use PhonePe\payments\v1\models\request\builders\PgPayRequestBuilder;
use PhonePe\payments\v1\models\request\builders\InstrumentBuilder;
use PhonePe\common\config\Constants;
use \CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Extract auth token from request header
 * 
 * @param string $authHeader
 * @return string
 */
function getBearerToken($authHeader)
{
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
}


/**
 * @var object $userData
 */
$userData = new stdClass();

/**
 * Set auth user
 * 
 * @return object|null
 */
function authuser()
{
    global $userData;

    return $userData;
}

/**
 * Set auth user
 * 
 * @param object $user
 */
function setAuthUser($user)
{
    global $userData;
    $userData = $user;
}

/**
 * Generate auth tokens
 * 
 * @param mixed $user
 * @return array
 */
function generateAuthToken($user): array
{
    $key = getenv("JWT_SECRET");

    $issuedAt   = new \DateTimeImmutable();
    $expire     = $issuedAt->modify('+60 day')->getTimestamp();  // Add 60 miniutes to expire on production
    $serverName = getenv('app.baseURL');

    $user_id = is_object($user) ? $user->profile_id : $user['profile_id'];

    $payload = [
        'iat'  => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
        'iss'  => $serverName,                       // Issuer
        'nbf'  => $issuedAt->getTimestamp(),         // Not before
        'exp'  => $expire,                           // Expire
        'user_id' =>  $user_id
    ];

    $jwt = \Firebase\JWT\JWT::encode($payload, $key, 'HS256');

    $refresh_token = hash('sha1', uniqid(md5($key .  $user_id)));

    // save fresh token behalf of user
    // $db = db_connect();

    // $db->table('tbl_users')->update([
    //     'refresh_token' => $refresh_token
    // ], ['id' =>  $user_id]);

    return [
        'access_token' => $jwt,
        'refresh_token' => $refresh_token,
        'expire_at' => $expire,
        'expire_at_string' => (new \DateTime())->setTimestamp($expire)->format('Y-m-d H:i:s')
    ];
}

/**
 * Generate customer id 
 * 
 * @return string
 */
function generateCustomerId()
{
    $db = db_connect();

    $sql = "SELECT 
            CONCAT('AM', IFNULL(MAX(profile_id), 0) + 1) AS next_cust_id
        FROM 
            net_profile";

    $result = $db->query($sql)->getRow();

    return $result->next_cust_id;
}

/**
 * Calculate limit and offset based on limit and page no
 * 
 * @param string $limit
 * @param string $page
 * @return array
 */
function calculateLimitOffset($limit, $page)
{
    if (is_null($limit) && is_null($page)) {
        return [10, 0];
    } else {
        $limit = intval($limit);

        // only allow 20 result per set else server will crash
        if ($limit > 20) {
            $limit = 20;
        }

        $offset = (intval($page) - 1) * $limit;

        return [$limit, $offset];
    }
}

/**
 * Send notification 
 * 
 * @param string $customer_id
 * @param string $message 
 * @param int|null $flag_value
 * @return bool
 */
function addNotification($customer_id, $message, $flag_value = NULL)
{
    $db = db_connect();
    $user = authuser();
    $sender_id = $user->customer_id;
    $date = date('Y-m-d');
    $time = date('h:i:s a');
    if ($user->middle_name != '' && $user->middle_name != NULL) {
        $name = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
    } else {
        $name = $user->first_name . ' ' . $user->last_name;
    }

    $data = [
        'customer_id' => $customer_id,
        'message' => $message,
        'seen' => 0,
        'c_date' => $date,
        'c_time' => $time,
        'c_name' => $name,
        'c_uid' => $sender_id,
        'flag_value' => $flag_value
    ];

    return $db->table('net_notification_tbl')->insert($data);
}


/**
 * Calculate probability
 * 
 * @param string $customer_id
 * @return int|null 
 */
function calculateProbability($customer_id)
{
    $user = authuser();
    $current_customer_id = $user->customer_id;

    $db = db_connect();

    $current_user_pv = getPV($current_customer_id);
    $profile_pv = getPV($customer_id);

    if ($current_user_pv != '' && $current_user_pv != NULL && $profile_pv != '' && $profile_pv != NULL) {

        $avg_pv = intval($current_user_pv) - intval($profile_pv);
    }

    if (isset($avg_pv) && $avg_pv != '' && $avg_pv != NULL) {

        $sql = "SELECT 
                * 
            FROM 
                net_probability_percentage
            WHERE (pv_from IS NULL OR pv_from >= ? ) AND (pv_to IS NULL OR pv_to <= ?)";

        $probability = $db->query($sql, [$avg_pv, $avg_pv])->getRow();

        if (!is_null($probability)) {
            return $probability->percentage;
        } else {
            return null;
        }
    } else {
        return null;
    }
}

/**
 * Get PV by customer id 
 * 
 * @param string $customer_id
 * @return int|null
 */
function getPV($customer_id)
{
    $db = db_connect();

    $result =  $db->table('net_par')
        ->where('customer_id', $customer_id)
        ->get()
        ->getRow();
    if (!is_null($result)) {
        return $result->total;
    }
}

/**
 * intitiate payment 
 * 
 * @param int $mobile 
 * @param int $user_id
 * @param int $amount
 * 
 * @return string
 */
function initPayment($mobile, $amount, $user_id, $merchantTransactionId)
{
    $phonePePaymentsClient = new PhonePePaymentClient(getenv("MERCHANTID"), getenv("SALTKEY"), getenv("SALTINDEX"), Env::PRODUCTION, getenv("SHOLDPUBLISHEVENTS"));

    $request = PgPayRequestBuilder::builder()
        ->mobileNumber($mobile)
        ->callbackUrl(getenv("WEBHOOK_URL"))
        ->merchantId(getenv("MERCHANTID"))
        ->merchantUserId($user_id)
        ->amount($amount * 100)
        ->merchantTransactionId($merchantTransactionId)
        ->redirectUrl('https://uat.ehostingguru.com/matrimony/payment/redirect')
        ->redirectMode("POST")
        // ->deviceContext(Constants::IOS)
        ->paymentInstrument(InstrumentBuilder::buildPayPageInstrument())
        ->build();

    $response = $phonePePaymentsClient->pay($request);
    $url = $response->getInstrumentResponse()->getRedirectInfo()->getUrl();
    return $url;
}


/**
 * Upload a file
 * 
 * @param \CodeIgniter\HTTP\Files\UploadedFile|string $file
 * @return null|string
 */
function uploadFile(UploadedFile|string $file)
{
    if ($file instanceof UploadedFile) {
        if (!$file->isValid()) {
            return "Not a validate file";
        }

        if ($file->hasMoved()) {
            return;
        }

        $newFileName = $file->getRandomName();
        $status = $file->move(UPLOAD_PATH, $newFileName);

        if ($status == false) {
            return;
        }

        $file_url = base_url('uploads/') . $newFileName;

        return $file_url;
    } else {
        return NULL;
    }
}


/**
 * Send SMS to User mobile
 * 
 * @param array $numbers
 * @param string $sender
 * @param string $message
 * return 
 */
function sendSms($numbers, $sender, $message)
{
    $apiKey = urlencode(API_KEY);
    $numbers = implode(',', $numbers);

    // Prepare data for POST request
    $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

    // Send the POST request with cURL
    $ch = curl_init('https://api.textlocal.in/send/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Process your response here
    return json_decode($response);
}


/**
 * Generate OTP
 * @param int $n
 * @return int
 */
function generateNumericOTP($n)
{
    $generator = "1357902468";
    $result = "";

    for ($i = 1; $i <= $n; $i++) {
        $result .= substr($generator, (rand() % (strlen($generator))), 1);
    }

    return $result;
}
