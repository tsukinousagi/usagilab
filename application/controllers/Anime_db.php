<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Anime_db extends CI_Controller {

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
        $this->load->helper('url');
        $this->load->model('anime_db/anime_db_model');

        $this->load->view('anime_db/index');
	}

	public function maintain()
	{
        $this->load->helper('url');
        $this->load->model('anime_db/anime_db_model');

        $this->load->view('anime_db/maintain');
	}

    public function flush_test() {
        $this->load->model('anime_db/anime_db_model');
        $this->anime_db_model->flush_test();

    }

    public function fetch_title() {
        $this->load->model('anime_db/anime_db_model');
        $this->anime_db_model->fetch_title();

    }

}
