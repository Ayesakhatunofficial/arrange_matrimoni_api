<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

use DateTime;

class User extends BaseController
{
    use ResponseTrait;

    public function profile()
    {
        $user = authuser();

        $model = new UserModel();
        $result = $model->getProfile($user->profile_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result,
            ]);
        }
    }

    public function recentProfile()
    {
        $user = authuser();
        $model = new UserModel();

        $result = $model->getRecentProfile($user->gender, $user->customer_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }


    public function profileSuggestion()
    {
        $page = $this->request->getVar('page');
        $user = authuser();
        $customer_id = $user->customer_id;
        $gender = $user->gender;

        $model = new UserModel();

        $result = $model->getProfileSuggestion($customer_id, $gender, $page);
        $count  = count($result);

        $totalRecords = $model->getSuggestionCount($customer_id, $gender);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function profileDetails()
    {
        $rules = [
            'profile_id' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE profile_id = ?  ", [$value])->getRow();

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

        $profile_id = $this->request->getVar('profile_id');

        $model = new UserModel();
        $result = $model->getSingleProfileDetails($profile_id);

        if ($result) {
            $user = authuser();
            if ($user->middle_name != '' && $user->middle_name != NULL) {
                $name = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
            } else {
                $name = $user->first_name . ' ' . $user->last_name;
            }

            $message = $name . ' ' . 'viewed your profile.';
            addNotification($result->customer_id, $message, 0);

            $result->probability = calculateProbability($result->customer_id);
        }

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result,
            ]);
        }
    }

    public function membershipPlan()
    {
        $model = new UserModel();

        $result = $model->getPlans();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result,
            ]);
        }
    }

    public function latestStories()
    {
        $rules = [
            'page' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');
        $model = new UserModel();
        $result = $model->getLatestStory($page);
        $totalRecords = $model->getLatestStoryCount();
        $count = count($result);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function successStories()
    {
        $rules = [
            'page' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');
        $model = new UserModel();
        $result = $model->getSuccessStory($page);
        $totalRecords = $model->getSuccessStoryCount();
        $count = count($result);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function like()
    {
        $rules = [
            'receiver_customer_id' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE customer_id = ? ", [$value])->getRow();

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

        $receiver_customer_id = $this->request->getVar('receiver_customer_id');
        $user = authuser();

        $sender_customer_id = $user->customer_id;
        if ($user->middle_name != '' && $user->middle_name != NULL) {
            $name = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
        } else {
            $name = $user->first_name . ' ' . $user->last_name;
        }

        $model = new UserModel();

        $result = $model->likeSomeone($receiver_customer_id, $sender_customer_id, $name);

        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        } else {
            return $this->fail([
                'status' => false,
                'message' => 'Something went wrong'
            ], STATUS_SERVER_ERROR);
        }
    }

    public function youLikeSomeone()
    {
        $rules = [
            'page' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');

        $model = new UserModel();
        $user = authuser();
        $customer_id = $user->customer_id;

        $result = $model->getYouLikeSomeoneList($page, $customer_id);
        $count = count($result);
        $totalRecords = $model->getYouLikeSomeoneCount($customer_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function someoneLikeYou()
    {
        $rules = [
            'page' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');

        $model = new UserModel();
        $user = authuser();
        $customer_id = $user->customer_id;

        $result = $model->getSomeoneLikeYouList($page, $customer_id);
        $count = count($result);
        $totalRecords = $model->getSomeoneLikeYouCount($customer_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function shortList()
    {
        $rules = [
            'shortlist_customer_id' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE customer_id = ? ", [$value])->getRow();

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

        $shortlist_customer_id = $this->request->getVar('shortlist_customer_id');

        $user = authuser();

        $customer_id = $user->customer_id;

        if ($user->middle_name != '' && $user->middle_name != NULL) {
            $name = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
        } else {
            $name = $user->first_name . ' ' . $user->last_name;
        }

        $model = new UserModel();

        $result = $model->shortlistSomeone($shortlist_customer_id, $customer_id, $name);

        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        } else {
            return $this->fail([
                'status' => false,
                'message' => 'Something went wrong'
            ], STATUS_SERVER_ERROR);
        }
    }

    public function shortlistList()
    {
        $rules = [
            'page' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');

        $model = new UserModel();
        $user = authuser();
        $customer_id = $user->customer_id;

        $result = $model->getShortlistList($page, $customer_id);
        $count = count($result);
        $totalRecords = $model->getShortlistCount($customer_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' =>  $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function search()
    {
        $rules = [
            'page' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);

        $data = [
            'min_age' => $this->request->getVar('min_age'),
            'max_age' => $this->request->getVar('max_age'),
            'caste_id' => $this->request->getVar('caste_id'),
            'religion_id' => $this->request->getVar('religion_id'),
            'profession' => $this->request->getVar('profession'),
            'maritial_status' => $this->request->getVar('maritial_status')
        ];

        $model = new UserModel();

        $totalRecords = count($model->getSearchResult($data));

        if (!empty($limitOffset)) {
            $result = $model->getSearchResult($data, $limitOffset);
            $count  = count($result);
        }

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'items' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords
                ]
            ]);
        }
    }

    public function notifications()
    {
        $rules = [
            'page' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');
        $user = authuser();
        $customer_id = $user->customer_id;

        $model = new UserModel();
        $result = $model->getNotifications($customer_id, $page);

        $count = count($result);
        $totalRecords = $model->getNotificationCount($customer_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function notificationSeen()
    {
        $model = new UserModel();
        $user = authuser();
        $customer_id = $user->customer_id;
        $result = $model->updateNotification($customer_id);
        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'Success'
            ]);
        } else {
            return $this->fail([
                'status' => false,
                'message' => 'Something went wrong'
            ], STATUS_SERVER_ERROR);
        }
    }

    public function notificationCount()
    {
        $model = new UserModel();
        $user = authuser();
        $customer_id = $user->customer_id;

        $result = $model->getUnseenNotificationCount($customer_id);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function contactUs()
    {
        $rules  = [
            'name' => 'required',
            'mobile' => 'required|integer',
            'email' => 'required|valid_email',
            'message' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }
        $data = $this->request->getVar();
        $model = new UserModel();
        $result = $model->submitContactUsForm($data);

        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'Success'
            ]);
        } else {
            return $this->fail([
                'status' => false,
                'message' => 'Something went wrong'
            ], STATUS_SERVER_ERROR);
        }
    }

    public function profileStatement()
    {
        $rules = [
            'page' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');
        $model = new UserModel();

        $result = $model->getProfileStatement($page);
        $count = count($result);
        $totalRecords = $model->getProfileStatementCount();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function notificationDate()
    {
        $rules = [
            'date' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $date = $this->request->getVar('date');

        $model = new UserModel();
        $result = $model->getNextDate($date);

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function officeTag()
    {
        $rules = [
            'page' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $page = $this->request->getVar('page');

        $model = new UserModel();

        $result = $model->getOfficeTag($page);
        $count = count($result);
        $totalRecords = $model->getOfficeTagCount();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'item' => $result,
                    'item' => $result,
                    'batch_count' => $count,
                    'total_count' => $totalRecords->total_count
                ]
            ]);
        }
    }

    public function profileDeactivate()
    {
        $rules = [
            'customer_id' => [
                'required',
                static function ($value, $data, &$error, $field) {
                    $db = db_connect();

                    $result = $db->query("SELECT * FROM net_profile WHERE customer_id = ?  ", [$value])->getRow();

                    if (is_null($result)) {
                        $error = $value . " Doesn't exist";
                        return false;
                    }

                    return true;
                }
            ],
            'bride_or_groom_name' => 'required',
            'bride_or_groom_father_name' => 'required',
            'marriage_probable_date' => 'required',
            'contact_by_am' => 'required',
            'marriage_photo' => 'uploaded[marriage_photo]|mime_in[marriage_photo,image/jpg,image/jpeg,image/png]|max_size[marriage_photo,500]',
            'invitation_card' => 'uploaded[invitation_card]|mime_in[invitation_card,image/jpg,image/jpeg,image/png]|max_size[invitation_card,500]',
            'marriage_certificate' => 'uploaded[marriage_certificate]|mime_in[marriage_certificate,image/jpg,image/jpeg,image/png]|max_size[marriage_certificate,500]',
            'narration' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $this->validator->getErrors()
            ], STATUS_VALIDATION_ERROR, 'Validation error');
        }

        $data = $this->request->getVar();
        $marriage_photo = $this->request->getFile('marriage_photo');
        $invitation_card = $this->request->getFile('invitation_card');
        $marriage_certificate = $this->request->getFile('marriage_certificate');

        $model = new UserModel();
        $result = $model->profileDeactivate($data, $marriage_photo, $invitation_card, $marriage_certificate);

        if ($result) {
            return $this->respond([
                'status' => true,
                'message' => 'Request Submitted Successfully'
            ]);
        } else {
            return $this->fail([
                'status' => false,
                'message' => 'Something went wrong'
            ], STATUS_SERVER_ERROR);
        }
    }
}
