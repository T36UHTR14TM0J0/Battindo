<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Menu extends CI_Controller {

    function __construct(){
        parent::__construct();

        (!$this->session->userdata('battindo_ses_isLogin') ? redirect('contact') : '');

        $this->load->model('m_master');

        $this->contents = 'menu/';
        $this->template = 'layouts/v_backoffice';
        $this->data     = array();
		
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$this->arr_Akses	= $this->m_master->check_menu($controller);
    }

    function index(){
		if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}
        // MAIN MENU
        $this->data['menu']         = $this->m_master->read('menus', 'weight', 'ASC', 'id, name, path, icon,icon_notify','','');
        
        $this->data['title']        = 'DAFTAR MENU';
		$this->data['akses_menu']	= $this->arr_Akses;
        $this->data['contents']     = $this->contents . 'v_menu';

        $this->load->view($this->template, $this->data);
    }

    function up($id){
		if($this->arr_Akses['update'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('menu');
		}
		
        // AMBIL IDENTTIAS MENUNNYA
        $menu = $this->m_master->read('menus', 'weight', 'ASC', '*', 'id', $id);

        $parent_id  = $menu[0]->parent_id;
        $weight     = $menu[0]->weight;
        $weight_up  = $weight - 1;        

        $this->services = $this->load->database('default', TRUE);

        // AMBIL IDENTITAS MENU DIATASNYA
        $sql = 'SELECT id 
                FROM menus 
                WHERE parent_id = "'.$parent_id.'" AND weight = "'.$weight_up.'"';
        $row = $this->services->query($sql)->row();

        // UPDATE MENU DIATASNYA
        $id_top = $row->id;
        $data['weight'] = $weight;
        $this->m_master->update('menus', $data, 'id', $id_top);

        // UPDATE MENU TERSEBUT
        $data['weight'] = $weight_up;
        $this->m_master->update('menus', $data, 'id', $id);

        redirect($_SERVER['HTTP_REFERER'], 'location');
    }

    function down($id){
		if($this->arr_Akses['update'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('menu');
		}
		
        // AMBIL IDENTTIAS MENUNNYA
        $menu = $this->m_master->read('menus', 'weight', 'ASC', '*', 'id', $id);

        $parent_id  = $menu[0]->parent_id;
        $weight     = $menu[0]->weight;
        $weight_down= $weight + 1;        

        $this->services = $this->load->database('default', TRUE);

        // AMBIL IDENTITAS MENU DIBAWAHNYA
        $sql = 'SELECT id 
                FROM menus 
                WHERE parent_id = "'.$parent_id.'" AND weight = "'.$weight_down.'"';
        $row = $this->services->query($sql)->row();

        // UPDATE MENU DIBAWAHNYA
        $id_top = $row->id;
        $data['weight'] = $weight;
        $this->m_master->update('menus', $data, 'id', $id_top);

        // UPDATE MENU TERSEBUT
        $data['weight'] = $weight_down;
        $this->m_master->update('menus', $data, 'id', $id);

        redirect($_SERVER['HTTP_REFERER'], 'location');
    }

    function add($parent_id = 0){
        if($this->input->post()){
            $parent_id = $this->input->post('parent_id');
			if(empty($parent_id) || $parent_id=='')$parent_id	= '0';
	
            // AMBIL WEIGHT BY PARENT ID
			$weight		= 1;
			
			$this->services = $this->load->database('default', TRUE);
			$sql = 'SELECT MAX(weight) as max_weight
					FROM menus 
					WHERE parent_id = "'.$parent_id.'"';
			$row = $this->services->query($sql)->row();
			if($row){
				$weight = $row->max_weight + 1;
			}
			
            

            $data['name']           = $this->input->post('name');
            $data['path']           = $this->input->post('path');
            $data['parent_id']      = $parent_id;
            $data['weight']         = $weight;
            $data['active']    		= 1;
			$data['icon']           = $this->input->post('icon');
            $data['created_by']     = $this->session->userdata('battindo_ses_userid');
            $data['created_date']   = date('Y-m-d H:i:s');

			$this->db->trans_begin();
			$this->db->insert('menus',$data);
			
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$Arr_Return		= array(
					'status'		=> 2
				);
				
			}else{
				$this->db->trans_commit();
				$Arr_Return		= array(
					'status'		=> 1
				);
				$this->session->set_userdata('notif_sukses', 'Proses tambah menu sukses...');
				history('Add Menu '.$this->input->post('name'));
			}
			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('menu');
			}
			$this->data['parent_menu']  = $this->m_master->read('menus', 'weight', 'ASC', 'id, name', 'parent_id', $parent_id);
			$this->data['title']        = 'TAMBAH MENU';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}

        
    }
	
	 function detail($menu_id = ''){
		$this->data['parent_menu']  = $this->m_master->getArray('menus',array('parent_id'=>'0'),'id','name');
        $this->data['rows_menu']  	= $this->m_master->read('menus', 'weight', 'ASC', '*', 'id', $menu_id);
        $this->data['title']        = 'DETAIL MENU';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }
	
	 function edit($menu_id = ''){
		if($this->input->post()){
            $parent_id 	= $this->input->post('parent_id');
			if(empty($parent_id))$parent_id	= '0';
			$sts_Act	= '0';
			if($this->input->post('active')){
				$sts_Act	= '1';
			}
            $Kode_Menu				= $this->input->post('id');
			$data					= array();
            $data['name']           = $this->input->post('name');
            $data['path']           = $this->input->post('path');
            $data['parent_id']      = $parent_id;
            $data['active']    		= $sts_Act;
			$data['icon']           = $this->input->post('icon');
            $data['modified_by']     = $this->session->userdata('battindo_ses_userid');
            $data['modified_date']   = date('Y-m-d H:i:s');

            //$this->m_master->create($data, 'menus');
            //redirect('menu');
			
			$this->db->trans_begin();
			$this->db->update('menus',$data,array('id'=>$Kode_Menu));
			
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$Arr_Return		= array(
					'status'		=> 2
				);
				
			}else{
				$this->db->trans_commit();
				$Arr_Return		= array(
					'status'		=> 1
				);
				$this->session->set_userdata('notif_sukses', 'Proses edit menu sukses...');
				history('Update Menu '.$Kode_Menu);
			}
			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('menu');
			}
			$this->data['parent_menu']  = $this->m_master->getArray('menus',array('parent_id'=>'0'),'id','name');
			$this->data['rows_menu']  	= $this->m_master->read('menus', 'weight', 'ASC', '*', 'id', $menu_id);
			$this->data['title']        = 'EDIT MENU';
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);
		}
    }
}