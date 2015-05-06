<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Device_uptime extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
        $this->load->model('device_uptime_model');
        $this->device_uptime_model->test();
	}

    public function update($group = '', $device = '', $note = '') {
        //check params
        if (($group == '') || ($device == '')) {
            $this->load->view('device_uptime/error');
            return FALSE;
        }
        if (preg_match('/[0-9A-Za-z\.,\-_]+/', $group, $matches)) {
            $group = $matches[0];
        } else {
            $group = '';
        }
        if (preg_match('/[0-9A-Za-z\.,\-_]+/', $device, $matches)) {
            $device = $matches[0];
        } else {
            $device = '';
        }
        if (preg_match('/[0-9A-Za-z\.,\-_]+/', $note, $matches)) {
            $note = $matches[0];
        } else {
            $note = '';
        }
        //update device info
        $param = array(
            'group' => $group,
            'device' => $device,
            'note' => $note,
        );
        $this->load->model('device_uptime_model');
        if ($this->device_uptime_model->update_device($param)) {
            $this->load->view('device_uptime/success');
        } else {
            $this->load->view('device_uptime/error');
        }

    }

    public function status($group = '') {
        $this->output->set_header('Cache-Control: no-cache, must-revalidate');
        //check params
        if ($group == '') {
            $this->load->view('device_uptime/error');
            return FALSE;
        }
        //get status
        $param = array(
            'group' => $group,
        );
        $this->load->model('device_uptime_model');
        $status = $this->device_uptime_model->get_status($param);
        if ($status) {
            //mobile flag
            $this->load->library('user_agent');
            $mobile_flag = $this->agent->is_mobile();

            $data = array(
                'status' => $status,
                'group' => $group,
                'mobile_flag' => $mobile_flag,
            );

            $this->load->helper('url');
            $this->load->view('device_uptime/status', $data);
        } else {
            $this->load->view('device_uptime/error');
        }

    }
}
