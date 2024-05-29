<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Group extends CI_Controller {

	function __construct(){
        parent::__construct();

       (!$this->session->userdata('battindo_ses_isLogin') ? redirect('contact') : '');

        $this->load->model('m_master');

        $this->contents = 'group/';
        $this->template = 'layouts/v_backoffice';
        $this->data     = array();
		
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$this->arr_Akses	= $this->m_master->check_menu($controller);
		$this->limit        = 5;
    }

	function index(){
		
		if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}
		$this->data['rows_group']   = $this->m_master->get_all_list('groups','*','name ASC',"",$this->limit);
        $this->data['title']        = 'DAFTAR GRUP';
        $this->data['contents']     = $this->contents . 'v_group';
		$this->data['akses_menu']	= $this->arr_Akses;
		
		$this->load->view($this->template, $this->data);
	}
	function load_more(){
		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
       
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari Grup Berdasarkan Kata Kunci "' . $search . '"');
			$Search_By	="name LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('groups','*','name ASC',"",$limit,$offset,$Search_By);

        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
            $this->data['rows_group']  	= $data;
            $this->data['alfabet']  	= $alfabet;

            $response['content']    = $this->load->view('contents/group/v_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);   
	}
	
	 function add($parent_id = 0){
        if($this->input->post()){
            $Group_Name		= $this->input->post('name');
			$Group_Descr	= $this->input->post('descr');
			
			## CEK IF EXISTS ##
			$Num_Group		= $this->m_master->getCount('groups','LOWER(name)',strtolower($Group_Name));
			if($Num_Group > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Grup sudah ada dalam daftar....'
				);
			}else{
				$data			= array(
					'name'			=> ucwords(strtolower($Group_Name)),
					'descr'			=> ucwords(strtolower($Group_Descr)),
					'flag_all'		=> 'N',
					'created_by'	=> $this->session->userdata('battindo_ses_userid'),
					'created_date'	=> date('Y-m-d H:i:s')
				);
				
				$this->db->trans_begin();
				$this->db->insert('groups',$data);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah grup gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah grup sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah grup sukses...');
					history('Tambah grup '.$Group_Name);
				}
			}           
			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('group');
			}
			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH GRUP';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}

        
    }
	
	function detail($kode_group=''){
		if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
			redirect('group');
		}
		$this->data['rows_group']  	= $this->m_master->read('groups', 'name', 'ASC', '*', 'id', $kode_group);
		$this->data['action']  		= 'detail';
		$this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DETAIL GRUP';
		$this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
	}
	
	function edit($kode_group=''){
		if($this->input->post()){
            $Group_Name		= $this->input->post('name');
			$Group_Descr	= $this->input->post('descr');
			$Group_Code		= $this->input->post('id');
			## CEK IF EXISTS ##
			$Num_Group		= $this->db->get_where('groups',array('LOWER(name)'=>strtolower($Group_Name),'id !='=>$Group_Code))->num_rows();
			if($Num_Group > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Grup sudah ada dalam daftar....'
				);
			}else{
				$data			= array(
					'name'			=> ucwords(strtolower($Group_Name)),
					'descr'			=> ucwords(strtolower($Group_Descr)),
					'modified_by'	=> $this->session->userdata('battindo_ses_userid'),
					'modified_date'	=> date('Y-m-d H:i:s')
				);
				
				$this->db->trans_begin();
				$this->db->update('groups',$data,array('id'=>$Group_Code));
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit grup gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses edit grup sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses edit grup sukses...');
					history('Edit grup '.ucwords(strtolower($Group_Name)));
				}
			}           
			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('group');
			}
			$this->data['rows_group']  	= $this->m_master->read('groups', 'name', 'ASC', '*', 'id', $kode_group);
			$this->data['action']  		= 'edit';
			$this->data['title']        = 'EDIT GRUP';
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);
		}
	}
	public function access_menu($id=''){
		if($this->input->post()){
			//echo"<pre>";print_r($this->input->post());exit;
			
			$Group_id				= $this->input->post('id');
			$Flag_All				= 'N';
			if($this->input->post('flag_all')){
				$Flag_All				= $this->input->post('flag_all');
			}
			$Cek_Data				= $this->db->get_where('group_menus',array('group_id'=>$Group_id))->num_rows();
			
			$data			= array(
				'flag_all'		=> $Flag_All,
				'modified_by'	=> $this->session->userdata('battindo_ses_userid'),
				'modified_date'	=> date('Y-m-d H:i:s')
			);
			
			$data_session			= $this->session->userdata;
			$Jam					= date('Y-m-d H:i:s');
			$Arr_Detail				= array();
			$Loop					= 0;
			$dataDetail				= $this->input->post('tree');
			foreach($dataDetail as $key=>$value){
				if(isset($value['read']) || isset($value['create']) || isset($value['update']) || isset($value['delete']) || isset($value['approve']) || isset($value['download'])){
					$Loop++;
					$a_read			= (isset($value['read']) && $value['read'])?$value['read']:0;
					$a_create		= (isset($value['create']) && $value['create'])?$value['create']:0;
					$a_update		= (isset($value['update']) && $value['update'])?$value['update']:0;
					$a_delete		= (isset($value['delete']) && $value['delete'])?$value['delete']:0;
					$a_download		= (isset($value['download']) && $value['download'])?$value['download']:0;
					$a_approve		= (isset($value['approve']) && $value['approve'])?$value['approve']:0;
					
					if($a_create =='1' || $a_update=='1' || $a_delete=='' || $a_download=='' || $a_approve == '1'){
						$a_read		= '1';
					}
					
					$det_Detail		= array(
						'menu_id'		=> $value['menu_id'],
						'group_id'		=> $Group_id,
						'read'			=> $a_read,
						'create'		=> $a_create,
						'update'		=> $a_update,
						'delete'		=> $a_delete,
						'approve'		=> $a_approve,
						'download'		=> $a_download,
						'created_date'	=> $Jam,
						'created_by'	=> $this->session->userdata('battindo_ses_userid')
					);
					$Arr_Detail[$Loop]	= $det_Detail;
					
				}
			}
			$this->db->trans_begin();
			if($Cek_Data > 0){
				$Q_Del				= "DELETE FROM `group_menus` WHERE `group_id`='".$Group_id."'";
				$this->db->query($Q_Del);
			}
			$this->db->insert_batch('group_menus',$Arr_Detail);
			$this->db->update('groups',$data,array('id'=>$Group_id));
			
			if ($this->db->trans_status() !== true){
				$this->db->trans_rollback();
				$Arr_Kembali		= array(
					'status'		=> 2,
					'pesan'			=> 'Kelola grup akses gagal. Silakan coba kembali.......'
				);
			}else{
				$this->db->trans_commit();
				$Arr_Kembali		= array(
					'status'		=> 1,
					'pesan'			=> 'Kelola grup akses sukses.......'
				);				
				history('Kelola grup akses '.$this->input->post('name'));
				
			}			
			echo json_encode($Arr_Kembali);
		}else{		
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('group');
			}
			
			$get_Data			= $this->db->get_where('menus',array('active'=>'1'))->result_array();
			$detail				= access_menu_group($id);
			
			$int_data			= $this->db->get_where('groups',array('id'=>$id))->result();
			
			$this->data = array(
				'title'			=> 'KELOLA AKSES',
				'action'		=> 'access_menu',
				'data_menu'		=> $get_Data,
				'row_akses'		=> $detail,
				'rows_group'	=> $int_data
			);
			$this->data['contents']     = $this->contents . 'v_akses_menu';
			$this->load->view($this->template, $this->data);
			
		}
	}
	
}