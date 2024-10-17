<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Model;

class UserModel extends Model
{
    /**
     * Get profile data by profile id 
     * 
     * @param int $id
     * @return object
     */
    public function getProfile($id)
    {
        $sql = "SELECT 
                    p.profile_id,
                    p.customer_id,
                    p.first_name,
                    p.last_name,
                    p.middle_name,
                    p.gender,
                    p.maritial_status,
                    p.mother_tongue,
                    CONCAT(
                        UPPER(LEFT(p.guardian_name, 1)), 
                        ' ',
                        SUBSTRING(p.guardian_name, INSTR(p.guardian_name, ' ') + 1)
                    ) AS guardian_full_name,
                    p.dob_day,
                    p.dob_month,
                    p.dob_year,
                    p.education,
                    p.education_extra,
                    p.servicetype,
                    p.service_department,
                    p.present_address,
                    p.email,
                    p.about_me,
                    p.status,
                    p.amount,
                    p.mobile_no,
                    p.mode,
                    r.religion_name,
                    s.state_name,
                    nc.city_name,
                    CASE 
                        WHEN COALESCE(NULLIF(picture1, ''), 
                            NULLIF(picture2, ''), 
                            NULLIF(picture3, '')) 
                        IS NULL 
                    THEN '[]'
                        ELSE CONCAT_WS(',', 
                            NULLIF(picture1, ''), 
                            NULLIF(picture2, ''), 
                            NULLIF(picture3, ''))
                    END AS pictures
                FROM 
                    net_profile p
                LEFT JOIN 
                    net_religion r ON r.religion_id = p.religion
                LEFT JOIN 
                    net_state s ON s.state_id = p.present_state_id
                LEFT JOIN 
                    net_city nc ON nc.city_id = p.present_city_id
                WHERE 
                    p.profile_id = ? ";

        $row = $this->db->query($sql, [$id])->getRow();

        if ($row) {
            if ($row->pictures === '[]') {
                $row->pictures = [];
            } else {
                $row->pictures = explode(',', $row->pictures);
            }
        }

        $sql1 = "SELECT
                    p.plan_id,
                    p.plan_name
                FROM 
                    net_profile np
                JOIN net_plan p ON p.plan_id = np.plan_id
                WHERE np.profile_id = ? ";

        $row->current_plan = $this->db->query($sql1, [$id])->getRow();
        return $row;
    }

    /**
     * Get recent profiles based on gender and added date within the last 1.5 months.
     * 
     * @param string $gender
     * @param string $customer_id
     * @return array[object]
     */
    public function getRecentProfile($gender, $customer_id)
    {
        $currentDate = new \DateTime();
        $currentDate->modify('-1 month');
        $currentDate->modify('-15 days');
        $dateLimit = $currentDate->format('Y-m-d');

        $sql = "SELECT
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.last_name,
                    p.middle_name,
                    p.picture1,
                    p.gender,
                    p.age,
                    p.height,
                    p.mother_tongue,
                    p.add_date,
                    p.status,
                    p.mode,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_like 
                        WHERE 
                            sender_customer_id = ?
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_liked,
                    r.religion_name,
                    c.caste_name,
                    s.state_name,
                    nc.city_name
                FROM 
                    net_profile p
                LEFT JOIN 
                    net_religion r ON r.religion_id = p.religion
                LEFT JOIN  
                    net_caste c ON c.caste_id = p.caste
                LEFT JOIN 
                    net_state s ON s.state_id = p.present_state_id
                LEFT JOIN 
                    net_city nc ON nc.city_id = p.present_city_id
                WHERE 
                    p.gender != ?  
                    AND add_date >= ? 
                    AND p.amount > 0 
                    AND p.status = 'Y'
                GROUP BY p.customer_id
                ORDER BY p.profile_id DESC";

        return $this->db->query($sql, [$customer_id, $gender, $dateLimit])->getResult();
    }

    /**
     * Get profile suggestion for user 
     * 
     * @param string  $customer_id
     * @param string $gender
     * @param int|null $page
     * @return array[object]
     */
    public function getProfileSuggestion($customer_id, $gender, $page = NULL)
    {
        $sql = "SELECT
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.last_name,
                    p.middle_name,
                    p.picture1,
                    p.gender,
                    p.age,
                    p.height,
                    p.mother_tongue,
                    p.servicetype,
                    p.service_department,
                    p.business_type,
                    p.status,
                    p.mode,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_like 
                        WHERE 
                            sender_customer_id = nst.customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_liked,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_shortlist 
                        WHERE 
                            sender_customer_id = nst.customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_shortlisted,
                    r.religion_name,
                    c.caste_name,
                    s.state_name,
                    nc.city_name
                FROM 
                    net_service_tags nst
                JOIN 
                    net_profile  p ON p.customer_id = nst.tags
                LEFT JOIN 
                    net_religion r ON r.religion_id = p.religion
                LEFT JOIN  
                    net_caste c ON c.caste_id = p.caste
                LEFT JOIN 
                    net_state s ON s.state_id = p.present_state_id
                LEFT JOIN 
                    net_city nc ON nc.city_id = p.present_city_id
                WHERE 
                    nst.customer_id = ? 
                    AND p.gender != ? 
                    AND p.amount > 0 
                    AND p.status = 'Y'
                GROUP BY p.customer_id
                ORDER BY p.profile_id DESC";

        if ($page != '' && $page != NULL) {
            $limit = 10;
            $limitOffset = calculateLimitOffset($limit, $page);
            $params = array_merge([$customer_id, $gender], $limitOffset);
            $sql  .= " LIMIT ? OFFSET ? ";

            $result =  $this->db->query($sql, $params)->getResult();
        } else {
            $result =  $this->db->query($sql, [$customer_id, $gender])->getResult();
        }

        if ($result) {
            foreach ($result as $row) {
                $row->probability = calculateProbability($row->customer_id);
            }
        }

        return $result;
    }

    /**
     * Get profile suggestion total count 
     * 
     * @param string  $customer_id
     * @param string $gender
     * @return object
     */
    public function getSuggestionCount($customer_id, $gender)
    {
        $sql = "SELECT
                    COUNT(nst.customer_id) as total_count
                FROM 
                    net_service_tags nst
                JOIN 
                    net_profile p ON p.customer_id = nst.tags
                WHERE 
                    nst.customer_id = ? 
                    AND p.gender != ? 
                    AND p.amount > 0 
                    AND p.status = 'Y'";


        return $this->db->query($sql, [$customer_id, $gender])->getRow();
    }

    /**
     * Get suggestion profile details by profile id 
     * 
     * @param int $profile_id
     * @return object
     */
    public function getSingleProfileDetails($profile_id)
    {
        $user = authuser();
        $customer_id = $user->customer_id;

        $sql = "SELECT
                    p.*,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_like 
                        WHERE 
                            sender_customer_id = ?
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_liked,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_shortlist 
                        WHERE 
                            sender_customer_id = ?
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_shortlisted,
                    r.religion_name,
                    c.caste_name,
                    g.gon_name,
                    ng.gothram_name,
                    nr.raasi_name,
                    sc.sub_caste_name,
                    s.state_name,
                    nc.city_name
                FROM 
                    net_profile p
                LEFT JOIN net_religion r ON r.religion_id = p.religion
                LEFT JOIN net_caste c ON c.caste_id = p.caste
                LEFT JOIN net_state s ON s.state_id = p.present_state_id
                LEFT JOIN net_city nc ON nc.city_id = p.present_city_id
                LEFT JOIN net_gon g ON g.gon_id = p.gon
                LEFT JOIN net_gothram ng ON ng.gothram_id = p.gothram 
                LEFT JOIN net_raasi nr ON nr.raasi_id = p.raasi
                LEFT JOIN net_sub_caste sc ON sc.sub_caste_id = p.sub_caste
                WHERE p.profile_id = ? 
                ORDER BY p.profile_id DESC";

        $record = $this->db->query($sql, [$customer_id, $customer_id, $profile_id])->getRow();

        $record->first_name = ucfirst(mb_substr($record->first_name, 0, 1));

        return $record;
    }

    /**
     * Get membership plan 
     * 
     * @return array[object]
     */
    public function getPlans()
    {
        $sql = "SELECT
                    *
                FROM 
                    net_plan
                ORDER BY plan_id ASC";

        return $this->db->query($sql)->getResult();
    }

    /**
     * Get latest story by year
     * 
     * @param int $page
     * @return array[object]
     */
    public function getLatestStory($page)
    {
        $year = date('Y');
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);
        $params = array_merge([$year], $limitOffset);

        $sql = "SELECT 
                    ss.id,
                    (
                       SELECT 
                            first_name
                        FROM 
                            net_profile
                        WHERE 
                            customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_first_name,
                    (
                       SELECT 
                            middle_name
                        FROM net_profile
                        WHERE customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_middle_name,
                    (
                       SELECT 
                            last_name
                        FROM net_profile
                        WHERE customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_last_name,
                    (
                       SELECT 
                            picture1
                        FROM net_profile
                        WHERE customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_photo,
                    (
                       SELECT 
                            first_name
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_first_name,
                    (
                       SELECT 
                            middle_name
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_middle_name,
                    (
                       SELECT 
                            last_name
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_last_name,
                    (
                       SELECT 
                            picture1
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_photo,
                    ss.marriage_date
                FROM 
                    net_success_story ss
                WHERE ss.marriage_date = ? 
                GROUP BY ss.bride_name
                ORDER BY ss.id DESC
                LIMIT ? OFFSET ? ";

        return $this->db->query($sql, $params)->getResult();
    }

    /**
     * Get latest story total count 
     * 
     * @return object
     */
    public function getLatestStoryCount()
    {
        $year = date('Y');
        $sql = "SELECT
                    COUNT(DISTINCT ss.bride_name) as total_count
                FROM 
                    net_success_story ss
                WHERE 
                    ss.marriage_date = ?  ";

        return $this->db->query($sql, [$year])->getRow();
    }

    /**
     * Get success story 
     * 
     * @param int $page
     * @return array[object]
     */
    public function getSuccessStory($page)
    {
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);

        $sql = "SELECT
                    ss.id,
                    (
                       SELECT 
                            first_name
                        FROM 
                            net_profile
                        WHERE 
                            customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_first_name,
                    (
                       SELECT 
                            middle_name
                        FROM 
                            net_profile
                        WHERE customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_middle_name,
                    (
                       SELECT 
                            last_name
                        FROM net_profile
                        WHERE customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_last_name,
                    (
                       SELECT 
                            picture1
                        FROM net_profile
                        WHERE customer_id = ss.bride_name
                        LIMIT 1
                    ) AS bride_photo,
                    (
                       SELECT 
                            first_name
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_first_name,
                    (
                       SELECT 
                            middle_name
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_middle_name,
                    (
                       SELECT 
                            last_name
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_last_name,
                    (
                       SELECT 
                            picture1
                        FROM net_profile
                        WHERE customer_id = ss.groom_name
                        LIMIT 1
                    ) AS groom_photo,
                    ss.marriage_date
                FROM 
                    net_success_story ss
                GROUP BY ss.bride_name
                ORDER BY ss.id DESC
                LIMIT ? OFFSET ? ";

        return $this->db->query($sql, $limitOffset)->getResult();
    }

    public function getSuccessStoryCount()
    {
        $sql = "SELECT
                    COUNT(DISTINCT ss.bride_name) AS total_count
                FROM 
                    net_success_story ss";

        return $this->db->query($sql)->getRow();
    }

    /**
     * liked someone 
     * 
     * @param string $receiver_customer_id
     * @param string $sender_customer_id
     * @param string $name
     * @return bool
     */
    public function likeSomeone($receiver_customer_id, $sender_customer_id, $name)
    {
        try {
            $this->db->transException(true)->transStart();
            $sql = "SELECT 
                    *
                FROM
                    net_like
                WHERE 
                    sender_customer_id = ? 
                    AND receiver_customer_id = ?";
            $check = $this->db->query($sql, [$sender_customer_id, $receiver_customer_id])->getRow();

            $like_status = '';
            if (is_null($check)) {
                $data = [
                    'sender_customer_id' => $sender_customer_id,
                    'receiver_customer_id' => $receiver_customer_id,
                    'add_date' => date('Y-m-d'),
                    'add_time' => date("h:i:s a")
                ];

                $result = $this->db->table('net_like')
                    ->insert($data);

                if ($result) {

                    $sql = " SELECT 
                            *
                        FROM
                            net_like
                        WHERE sender_customer_id = ? 
                        AND receiver_customer_id = ?";

                    $both_check  = $this->db->query($sql, [$receiver_customer_id, $sender_customer_id])->getRow();

                    if (!is_null($both_check)) {
                        $both_like = [
                            'sender_customer_id' => $sender_customer_id,
                            'receiver_customer_id' => $receiver_customer_id,
                            'c_date' => date('Y-m-d'),
                            'c_time' => date("h:i:s a")
                        ];

                        $this->db->table('both_like_tag_generate_tbl')->insert($both_like);
                    }

                    $message = $name . ' ' . 'liked you.';
                    addNotification($receiver_customer_id, $message, 1);

                    $like_status = 'like';
                }
            } else {
                $sql = "DELETE
                    FROM
                        net_like
                    WHERE
                        sender_customer_id = ? 
                        AND receiver_customer_id = ? ";

                $result = $this->db->query($sql, [$sender_customer_id, $receiver_customer_id]);

                if ($result) {

                    $sql = " SELECT 
                            *
                        FROM
                            both_like_tag_generate_tbl
                        WHERE sender_customer_id = ? 
                        AND receiver_customer_id = ?";

                    $both_like_ckeck  = $this->db->query($sql, [$sender_customer_id, $receiver_customer_id])->getRow();

                    if (!is_null($both_like_ckeck)) {
                        $sql = "DELETE
                                    FROM
                                        both_like_tag_generate_tbl
                                    WHERE
                                        sender_customer_id = ? 
                                        AND receiver_customer_id = ? ";

                        $this->db->query($sql, [$sender_customer_id, $receiver_customer_id]);
                    }

                    $like_status = 'dislike';
                }
            }

            $this->db->transComplete();

            return $like_status;
        } catch (DatabaseException $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Get you like someone list
     * 
     * @param int $page
     * @param string $customer_id
     * @return array[object]
     */
    public function getYouLikeSomeoneList($page, $customer_id)
    {
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);
        $params = array_merge([$customer_id], $limitOffset);

        $sql = "SELECT 
                    l.status,
                    l.add_date,
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.last_name,
                    p.middle_name,
                    p.picture1,
                    p.gender,
                    p.age,
                    p.height,
                    p.mother_tongue,
                    p.monthly_income,
                    p.servicetype,
                    p.service_department,
                    p.business_type,
                    p.status,
                    p.mode,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_shortlist 
                        WHERE 
                            sender_customer_id = l.sender_customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_shortlisted
                FROM 
                    net_like l
                JOIN 
                    net_profile p ON p.customer_id = l.receiver_customer_id
                WHERE 
                    l.sender_customer_id = ?
                    AND p.status = 'Y'
                GROUP BY p.customer_id
                ORDER BY l.like_id DESC
                LIMIT ? OFFSET ? ";
        $result =  $this->db->query($sql, $params)->getResult();

        if ($result) {
            foreach ($result as $row) {
                $row->probability = calculateProbability($row->customer_id);
            }
        }

        return $result;
    }

    /**
     * Get you like someone count
     * 
     * @param string $customer_id
     * @return object
     */
    public function getYouLikeSomeoneCount($customer_id)
    {
        $sql = "SELECT 
                    COUNT(p.profile_id) as total_count
                FROM 
                    net_like l
                JOIN 
                    net_profile p ON p.customer_id = l.receiver_customer_id
                WHERE 
                    l.sender_customer_id = ?
                    AND p.status = 'Y'";
        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * Get someone like you list
     * 
     * @param int $page
     * @param string $customer_id
     * @return array[object]
     */
    public function getSomeoneLikeYouList($page, $customer_id)
    {
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);
        $params = array_merge([$customer_id], $limitOffset);

        $sql = "SELECT 
                    l.status,
                    l.add_date,
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.last_name,
                    p.middle_name,
                    p.picture1,
                    p.gender,
                    p.age,
                    p.height,
                    p.mother_tongue,
                    p.monthly_income,
                    p.servicetype,
                    p.service_department,
                    p.status,
                    p.business_type,
                    p.mode,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_shortlist 
                        WHERE 
                            sender_customer_id = l.receiver_customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_shortlisted,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_like 
                        WHERE 
                            sender_customer_id = l.receiver_customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_liked
                FROM 
                    net_like l
                JOIN 
                    net_profile p ON p.customer_id = l.sender_customer_id
                WHERE 
                    l.receiver_customer_id = ?
                    AND p.status = 'Y'
                GROUP BY p.customer_id
                ORDER BY l.like_id DESC
                LIMIT ? OFFSET ? ";
        $result =  $this->db->query($sql, $params)->getResult();

        if ($result) {
            foreach ($result as $row) {
                $row->probability = calculateProbability($row->customer_id);
            }
        }

        return $result;
    }

    /**
     * Get someone like you count
     * 
     * @param string $customer_id
     * @return object
     */
    public function getSomeoneLikeYouCount($customer_id)
    {
        $sql = "SELECT 
                    COUNT(p.profile_id) as total_count
                FROM 
                    net_like l
                JOIN 
                    net_profile p ON p.customer_id = l.sender_customer_id
                WHERE 
                    l.receiver_customer_id = ? 
                    AND p.status = 'Y'";
        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * shortlist someone
     * 
     * @param string $shortlist_customer_id
     * @param string $customer_id
     * @param string $name
     * @return bool
     */
    public function shortlistSomeone($shortlist_customer_id, $customer_id, $name)
    {
        $sql = "SELECT 
                    *
                FROM
                    net_shortlist
                WHERE 
                    sender_customer_id = ? 
                    AND receiver_customer_id = ?";
        $check = $this->db->query($sql, [$customer_id, $shortlist_customer_id])->getRow();

        if (is_null($check)) {
            $data = [
                'sender_customer_id' => $customer_id,
                'receiver_customer_id' => $shortlist_customer_id,
                'add_date' => date('Y-m-d'),
                'add_time' => date("h:i:sa")
            ];

            $result =  $this->db->table('net_shortlist')
                ->insert($data);
            if ($result) {
                $message = $name . ' ' . 'short listed you.';
                addNotification($shortlist_customer_id, $message, 2);

                return 'shortlist';
            } else {
                return false;
            }
        } else {
            $sql = "DELETE
                    FROM
                        net_shortlist
                    WHERE
                        sender_customer_id = ? 
                        AND receiver_customer_id = ? ";

            $result = $this->db->query($sql, [$customer_id, $shortlist_customer_id]);

            if ($result) {
                return 'remove';
            } else {
                return false;
            }
        }
    }

    /**
     * get shortlist list
     * 
     * @param int $page
     * @param string $customer_id
     * @return array[object]
     */
    public function getShortlistList($page, $customer_id)
    {
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);
        $params = array_merge([$customer_id], $limitOffset);

        $sql = "SELECT 
                    s.status,
                    s.add_date,
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.last_name,
                    p.middle_name,
                    p.picture1,
                    p.gender,
                    p.age,
                    p.height,
                    p.mother_tongue,
                    p.monthly_income,
                    p.servicetype,
                    p.service_department,
                    p.status,
                    p.mode,
                    p.business_type,
                    EXISTS(
                        SELECT
                            like_id
                        FROM 
                            net_like 
                        WHERE 
                            sender_customer_id = s.sender_customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_liked
                FROM 
                    net_shortlist s
                JOIN 
                    net_profile p ON p.customer_id = s.receiver_customer_id
                WHERE 
                    s.sender_customer_id = ?
                    AND p.status = 'Y'
                 GROUP BY p.customer_id
                ORDER BY s.add_date DESC
                LIMIT ? OFFSET ? ";
        $result =  $this->db->query($sql, $params)->getResult();

        if ($result) {
            foreach ($result as $row) {
                $row->probability = calculateProbability($row->customer_id);
            }
        }

        return $result;
    }

    /**
     * Get shortlist count
     * 
     * @param string $customer_id
     * @return object
     */
    public function getShortlistCount($customer_id)
    {
        $sql = "SELECT 
                    COUNT(p.profile_id) as total_count
                FROM 
                    net_shortlist s
                JOIN 
                    net_profile p ON p.customer_id = s.receiver_customer_id
                WHERE 
                    s.sender_customer_id = ?
                    AND p.status = 'Y' ";
        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * Get search result 
     * 
     * @param array $data
     * @param array|null $limitOffset
     * @return array[object]|bool
     */
    public function getSearchResult($data, $limitOffset = NULL)
    {
        try {
            $user = authuser();
            $gender = $user->gender;
            $customer_id = $user->customer_id;

            $sql = "SELECT
                p.profile_id,
                p.customer_id,
                UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                p.last_name,
                p.middle_name,
                p.picture1,
                p.picture2,
                p.picture3,
                p.gender,
                p.age,
                p.servicetype,
                p.service_department,
                p.business_type,
                p.status,
                p.maritial_status,
                CASE 
                    WHEN COALESCE(NULLIF(picture1, ''), 
                        NULLIF(picture2, ''), 
                        NULLIF(picture3, '')) 
                    IS NULL 
                THEN '[]'
                    ELSE CONCAT_WS(',', 
                        NULLIF(picture1, ''), 
                        NULLIF(picture2, ''), 
                        NULLIF(picture3, ''))
                END AS pictures,
                p.mode,
                EXISTS(
                    SELECT like_id
                    FROM net_shortlist
                    WHERE 
                        sender_customer_id = '$customer_id'
                        AND receiver_customer_id = p.customer_id
                    LIMIT 1
                ) AS profile_shortlisted,
                EXISTS(
                    SELECT 
                        like_id
                    FROM net_like
                    WHERE 
                        sender_customer_id = '$customer_id'
                        AND receiver_customer_id = p.customer_id
                    LIMIT 1
                ) AS profile_liked,
                p.religion,
                r.religion_name,
                p.caste,
                c.caste_name
            FROM 
                net_profile p
            LEFT JOIN net_religion r ON r.religion_id = p.religion
            LEFT JOIN net_caste c ON c.caste_id = p.caste
            WHERE 
                p.gender != '$gender' 
                AND p.amount > 0 
                AND p.status = 'Y'";

            if (!empty($data['caste_id'])) {
                $sql .= " AND p.caste = " . $data['caste_id'];
            }

            if (!empty($data['religion_id'])) {
                $sql .= " AND p.religion = " . $data['religion_id'];
            }

            if (!empty($data['min_age']) && !empty($data['max_age'])) {
                $sql .= " AND p.age BETWEEN " . $data['min_age'] . " AND " . $data['max_age'];
            }

            if (!empty($data['maritial_status']) && $data['maritial_status'] != 'Any') {
                $m_status = $data['maritial_status'];
                $sql .= " AND p.maritial_status = '$m_status'";
            }

            if (!empty($data['profession'])) {
                $professions = json_decode($data['profession']);

                if (!is_array($professions)) {
                    $professions = [$professions];
                }
                $prof = '';
                foreach ($professions as $profession) {
                    $prof .= "'" . $profession . "',";
                }

                $prof = rtrim($prof, ',');

                $sql .= " AND p.servicetype IN ($prof) ";
            }

            $sql .= "  GROUP BY p.customer_id ORDER BY p.profile_id DESC";

            if (!empty($limitOffset)) {
                $sql .= " LIMIT " . $limitOffset[0] . " OFFSET " . $limitOffset[1];
            }

            $result =  $this->db->query($sql)->getResult();

            if ($result) {
                foreach ($result as $row) {
                    if (isset($row->customer_id) && !empty($row->customer_id)) {
                        $row->probability = calculateProbability($row->customer_id);
                    }

                    if ($row->pictures === '[]') {
                        $row->pictures = [];
                    } else {
                        $row->pictures = explode(',', $row->pictures);
                    }
                }
            }
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Get notification list
     * 
     * @param string $customer_id
     * @param int $page
     * @return array[object]|array[array]
     */
    public function getNotifications($customer_id, $page)
    {
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);
        $params = array_merge([$customer_id], $limitOffset);

        $sql = "SELECT 
                    p.picture1,
                    n.*
                FROM 
                    net_notification_tbl n
                JOIN net_profile p ON p.customer_id = n.c_uid
                WHERE 
                    n.customer_id = ? 
                ORDER BY n.c_date DESC 
                LIMIT ? OFFSET ? ";

        $result =  $this->db->query($sql, $params)->getResult();

        $notifications = [];

        if (!is_null($result)) {

            foreach ($result as $row) {
                if (!isset($notifications[$row->c_date])) {
                    $notifications[$row->c_date] = [];
                }
                $notifications[$row->c_date][] = [
                    'id' => $row->id,
                    'picture' => $row->picture1,
                    'message' => $row->message,
                    'date' => $row->c_date,
                    'seen' => $row->seen,
                ];
            }
        }

        return $notifications;
    }

    /**
     * Get notification count
     * 
     * @param string $customer_id
     * @return object
     */
    public function getNotificationCount($customer_id)
    {
        $sql = "SELECT 
                    COUNT(n.id) as total_count
                FROM 
                    net_notification_tbl n
                WHERE 
                    n.customer_id = ? ";

        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * update notification unseen to seen 
     * 
     * @param string $customer_id 
     * @return bool
     */
    public function updateNotification($customer_id)
    {
        $sql = "UPDATE 
                    net_notification_tbl
                SET 
                    seen = 1
                WHERE 
                    customer_id = ? ";
        return $this->db->query($sql, [$customer_id]);
    }

    /**
     * Get unseen notification count 
     * 
     * @param string $customer_id
     * @return object
     */
    public function getUnseenNotificationCount($customer_id)
    {
        $sql = "SELECT 
                    COUNT(n.id) as unseen_count
                FROM 
                    net_notification_tbl n
                WHERE 
                    n.customer_id = ? 
                    AND seen = 0";

        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * Submit contact us form
     * 
     * @param array $data
     * @return bool
     */
    public function submitContactUsForm($data)
    {
        $user = authuser();

        $input = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'message' => $data['message'],
            'created_by' => $user->customer_id
        ];

        return $this->db->table('net_contact_us')->insert($input);
    }

    /**
     * Get profile statements 
     * 
     * @param int $page
     * @return array[object]
     */
    public function getProfileStatement($page)
    {
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);

        $user = authuser();
        $customer_id = $user->customer_id;

        $params = array_merge([$customer_id], $limitOffset);

        $sql = "SELECT
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.middle_name,
                    p.last_name,
                    p.mobile_no,
                    p.status,
                    p.mode,
                    p.amount,
                    p.gender,
                    p.picture1,
                    p.age,
                    p.servicetype,
                    p.service_department,
                    p.business_type,
                    nto.no_exchange_date,
                    nto.first_sitting_date,
                    nto.second_sitting_date,
                    nto.third_sitting_date,
                    nto.fourth_sitting_date,
                    nto.final_marriage_date
                FROM 
                    net_tagging_office nto
                JOIN 
                    net_profile p ON p.customer_id = nto.tag_customer_id
                WHERE 
                    nto.candidate_customer_id = ? 
                    AND nto.no_exchange_date != '0000-00-00' 
                    AND nto.no_exchange_date IS NOT NULL
                    AND p.status = 'Y'
                 GROUP BY p.customer_id
                ORDER BY nto.tag_id DESC
                LIMIT ? OFFSET ? ";

        return $this->db->query($sql, $params)->getResult();
    }

    /**
     * Profile statement count
     * 
     * @return object
     */
    public function getProfileStatementCount()
    {
        $user = authuser();
        $customer_id = $user->customer_id;

        $sql = "SELECT
                    COUNT(nto.tag_id) as total_count
                FROM 
                    net_tagging_office nto
                JOIN 
                    net_profile p ON p.customer_id = nto.tag_customer_id
                WHERE 
                    nto.candidate_customer_id = ? 
                    AND nto.no_exchange_date != '0000-00-00' 
                    AND nto.no_exchange_date IS NOT NULL 
                    AND p.status = 'Y'";

        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * Get next notification date
     * 
     * @param string $date
     * @return object
     */
    public function getNextDate($date)
    {
        $user = authuser();
        $customer_id = $user->customer_id;

        $sql = "SELECT 
                    MAX(c_date) as next_date
                FROM 
                    net_notification_tbl
                WHERE 
                    c_date < ? AND customer_id = ? ";

        return $this->db->query($sql, [$date, $customer_id])->getRow();
    }

    /**
     * Get office tag list
     * 
     * @param int $page
     * @return array[object]
     */
    public function getOfficeTag($page)
    {
        $user = authuser();
        $limit = 10;
        $limitOffset = calculateLimitOffset($limit, $page);
        $params = array_merge([$user->customer_id], $limitOffset);

        $sql = "SELECT 
                    p.profile_id,
                    p.customer_id,
                    UPPER(SUBSTRING(p.first_name, 1, 1)) as first_name,
                    p.last_name,
                    p.middle_name,
                    p.picture1,
                    p.gender,
                    p.age,
                    p.height,
                    p.mother_tongue,
                    p.monthly_income,
                    p.servicetype,
                    p.service_department,
                    p.status,
                    p.mode,
                    p.business_type,
                    p.present_address,
                    p.mobile_no,
                    nc.city_name,
                    EXISTS(
                        SELECT
                            like_id
                        FROM 
                            net_like 
                        WHERE 
                            sender_customer_id = o.candidate_customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_liked,
                    EXISTS(
                        SELECT 
                            like_id
                        FROM 
                            net_shortlist 
                        WHERE 
                            sender_customer_id = o.candidate_customer_id
                            AND receiver_customer_id = p.customer_id
                    ) AS profile_shortlisted,
                    o.*
                FROM 
                    net_tagging_office o
                JOIN 
                    net_profile p ON p.customer_id = o.tag_customer_id
                LEFT JOIN net_city nc ON nc.city_id = p.present_city_id
                WHERE 
                    o.candidate_customer_id = ?
                    AND o.sent_to_customer_id = 'Y'
                    AND p.status = 'Y'
                 GROUP BY p.customer_id
                ORDER BY o.tag_id DESC
                LIMIT ? OFFSET ? ";
        $result =  $this->db->query($sql, $params)->getResult();

        if ($result) {
            foreach ($result as $row) {
                $row->probability = calculateProbability($row->customer_id);
            }
        }

        return $result;
    }

    /**
     * Office tag count
     * 
     * @return object
     */
    public function getOfficeTagCount()
    {
        $user = authuser();
        $customer_id = $user->customer_id;

        $sql = "SELECT
                    COUNT(nto.tag_id) as total_count
                FROM 
                    net_tagging_office nto
                JOIN 
                    net_profile p ON p.customer_id = nto.tag_customer_id
                WHERE 
                    nto.candidate_customer_id = ?
                    AND p.status = 'Y'
                    AND nto.sent_to_customer_id = 'Y'";

        return $this->db->query($sql, [$customer_id])->getRow();
    }

    /**
     * Profile deactivate form submit 
     * 
     * @param array $data
     * @param string $marriage_photo
     * @param string $invitation_card
     * @param string $marriage_certificate
     * @return bool
     */
    public function profileDeactivate($data, $marriage_photo, $invitation_card, $marriage_certificate)
    {
        $marriage_photo_url = uploadFile($marriage_photo);
        $invitation_card_url = uploadFile($invitation_card);
        $marriage_certificate_url = uploadFile($marriage_certificate);

        $customer_id = $data['customer_id'];

        $user = $this->db->table('net_profile')
            ->where('customer_id', $customer_id)
            ->get()
            ->getRow();

        $customer_name = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
        $insert_data = [
            'customer_name' => $customer_name,
            'customer_father_name' => $user->guardian_name,
            'bride_groom_name' => $data['bride_or_groom_name'],
            'bride_groom_father_name' => $data['bride_or_groom_father_name'],
            'probable_date' => $data['marriage_probable_date'],
            'contact_by_am' => $data['contact_by_am'],
            'upload1' => $marriage_photo_url,
            'upload2' => $invitation_card_url,
            'upload3' =>  $marriage_certificate_url,
            'narration' => $data['narration'],
            'profile_id' => $user->profile_id
        ];

        return $this->db->table('net_deactivate_profile')
            ->insert($insert_data);
    }
}
