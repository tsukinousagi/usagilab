<?php

class Device_uptime_model extends CI_Model {
    public function update_device($param) {
        $this->load->database();
        //check if this device exists
        $sql = "SELECT `id` FROM `device_uptime` WHERE 1
                AND `group` LIKE '%s' AND `device` LIKE '%s'
                LIMIT 1";
        $sql = sprintf($sql, $param['group'], $param['device']);
        $query = $this->db->query($sql);
        if ($query) {
            $ret = $query->result_array();
            if (sizeof($ret) > 0) {
                $id = $ret[0]['id'];
            } else {
                $id = 0;
            }
        } else {
            return FALSE;
        }

        if ($id > 0) {
            //update device info
            $sql = "UPDATE `device_uptime` SET
                    `GROUP` = '%s',
                    `device` = '%s',
                    `note` = '%s'
                    WHERE `id` = %d";
            $sql = sprintf($sql, $param['group'], $param['device'], $param['note'], $id);
            return $this->db->query($sql);
        } else {
            //create new device
            $sql = "INSERT INTO `device_uptime`
                    (`group`, `device`, `note`) VALUES
                    ('%s', '%s', '%s')";
            $sql = sprintf($sql, $param['group'], $param['device'], $param['note']);
            return $this->db->query($sql);
        }
    }

    public function get_status($param) {
        //offline detection
        $expire = 60 * 15;
        $this->load->database();
        //check if this device exists
        $sql = "SELECT `id`, `device`, `note`, `updated` FROM `device_uptime` WHERE 1
                AND `group` LIKE '%s'
                ORDER BY `updated` ASC
                LIMIT 999";
        $sql = sprintf($sql, $param['group']);
        $query = $this->db->query($sql);
        if ($query) {
            $ret = $query->result_array();
            //mark as offline
            foreach ($ret as $k => $v) {
                if (strtotime($v['updated']) < (time() - $expire)) {
                    $ret[$k]['offline'] = 'Y';
                } else {
                    $ret[$k]['offline'] = 'N';
                }
            }
            return $ret;
        } else {
            return FALSE;
        }
    }

}
