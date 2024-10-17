<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterModel extends Model
{
    /**
     * Get caste 
     * 
     * @return array[object]
     */
    public function getCaste()
    {
        return $this->db->table('net_caste')
            ->get()
            ->getResult();
    }

    /**
     * Get gothram
     * 
     * @return array[object]
     */
    public function getGothram()
    {
        return $this->db->table('net_gothram')
            ->get()
            ->getResult();
    }

    /**
     * Get gon
     * 
     * @return array[object]
     */
    public function getGon()
    {
        return $this->db->table('net_gon')
            ->get()
            ->getResult();
    }

    /**
     * Get religion 
     * 
     * @return array[object]
     */
    public function getReligion()
    {
        return $this->db->table('net_religion')
            ->get()
            ->getResult();
    }

    /**
     * Get dashboard banners
     * 
     * @return array[object]
     */
    public function getDashboardBanners()
    {
        return $this->db->table('net_dashboard_banners')
            ->where('is_active', 1)
            ->get()
            ->getResult();
    }

    /**
     * Get dashboard videos
     * 
     * @return array[object]
     */
    public function getDashboardVideos()
    {
        return $this->db->table('net_dashboard_video')
            ->get()
            ->getResult();
    }
}
