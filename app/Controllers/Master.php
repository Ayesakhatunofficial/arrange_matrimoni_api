<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MasterModel;
use CodeIgniter\API\ResponseTrait;

use DateTime;

class Master extends BaseController
{
    use ResponseTrait;

    public function caste()
    {
        $model = new MasterModel();

        $result = $model->getCaste();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function gothram()
    {
        $model = new MasterModel();

        $result = $model->getGothram();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function gon()
    {
        $model = new MasterModel();

        $result = $model->getGon();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function religion()
    {
        $model = new MasterModel();

        $result = $model->getReligion();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function dashboardBanners()
    {
        $model = new MasterModel();

        $result = $model->getDashboardBanners();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function dashboardVideo()
    {
        $model = new MasterModel();

        $result = $model->getDashboardVideos();

        if (!is_null($result)) {
            return $this->respond([
                'status' => true,
                'message' => 'Success',
                'data' => $result
            ]);
        }
    }

    public function contactDetails()
    {
        $data = [
            [
                'office' => 'Head Office (Coochbehar)(W.B.)',
                'address' => 'Sarju Building, 2nd Floor, Near Canara Bank, R.R.N. Road, Coochbehar, West Bengal, India - 736101.',
                'mobile' => '+' . 918145129992,
                'missed_call' => '+' . 918670026700
            ],
            [
                'office' => 'Branch Office (Jalpaiguri)(W.B.)',
                'address' => 'Laxmi Abason, Kadamtala, Jalpaiguri, West Bengal, India - 735101.',
                'mobile' => '+' . 919064868338,
                'missed_call' => NULL
            ]
        ];

        return $this->respond([
            'status' => true,
            'message' => 'Success',
            'data' => $data
        ]);
    }
}
