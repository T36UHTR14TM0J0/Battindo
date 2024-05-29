<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Satuan extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');
        $this->contents     = 'satuan/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		
		##### CEK SESSION LOGIN #####
		if($this->session->userdata('battindo_ses_isLogin')){			
			$this->arr_Akses	= $this->m_master->check_menu($controller);
		}
    }

	// -------------------------------------------- //
	/*					  INDEX 				    */
	// -------------------------------------------- //
    function index(){

		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}
        $this->data['rows_list']    = $this->m_master->get_all_list('satuan','*','id DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR SATUAN';
        $this->data['contents']     = $this->contents . 'v_satuan';
        history('Lihat satuan');
        $this->load->view($this->template, $this->data);
    }
    
	// -------------------------------------------- //
	/*  				LOAD MORE				    */
	// -------------------------------------------- //
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('satuan');
		}

		(!$this->input->is_ajax_request() ? show_404() : '');
        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
		$Search_By	= "";
		
		##### SAVE HISTORY #####
        if($search){
            history('Cari Satuan Dengan Kata Kunci "' . $search . '"');
			$Search_By	="satuan LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('satuan','*','satuan DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']    	= $this->load->view('contents/satuan/v_more',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);   
    }
	
	// ---------------------------------//
	/*                ADD              */
	//---------------------------------//
    function add(){
        if($this->input->post()){
            $Satuan			= $this->input->post('satuan');
			$Satuan_Desc  	= $this->input->post('desc');
			
			## CEK IF EXISTS ##
			$Num_satuan		= $this->m_master->getCount('satuan','LOWER(satuan)',strtolower($Satuan));
			if($Num_satuan > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Satuan sudah ada dalam daftar...'
				);
			}else{
				$data			= array(
					'satuan'		=> ucwords(strtolower($Satuan)),
					'descr'			=> ucwords(strtolower($Satuan_Desc)),
					'created_by'	=> $this->session->userdata('battindo_ses_userid'),
					'created_date'	=> date('Y-m-d H:i:s')
				);
				
				$this->db->trans_begin();
				$this->db->insert('satuan',$data);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah satuan gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah satuan sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah satuan sukses...');
					history('Tambah Satuan '.$Satuan);
				}
			}           
			echo json_encode($Arr_Return);
        }else{

            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('satuan');
			}

			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH SATUAN';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		} 
    }

	// ---------------------------------//
	/*		        DETAIL             */
	//---------------------------------//
    function detail($id=''){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('satuan');
		}

        $this->data['rows_list']  	= $this->m_master->read('satuan', 'satuan', 'ASC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL SATUAN';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }
    
	// ---------------------------------//
	/*               EDIT              */
	//---------------------------------//
    function edit($id= ''){
		if($this->input->post()){
            $id						= $this->input->post('id');
			$data					= array();
            $data['satuan']       	= $this->input->post('satuan');
            $data['descr']           = $this->input->post('desc');
            $data['modified_by']     = $this->session->userdata('battindo_ses_userid');
			$data['modified_date']   = date('Y-m-d H:i:s');
			
			##### CEK DATA SATUAN #####
			$Find_Count	= $this->db->get_where('satuan',array('id' => $id,'id !='=>$id))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Satuan tidak ada dalam daftar...'
				);
			}else {
				$data_satuan = array(
					'id_satuan' => $id,
					'satuan' => $this->input->post('satuan')
				);

				$this->db->trans_begin();
				$this->db->update('satuan',$data,array('id'=>$id));
				$this->db->update('barang',$data_satuan,array('id_satuan'=>$id));
				
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit satuan gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses edit satuan sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses edit satuan sukses...');
					history('Edit Satuan '.$id);
				}
			}
			

			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('satuan');
			}

			$this->data['rows_list']  	= $this->m_master->read('satuan', 'satuan', 'ASC', '*', 'id', $id);
			$this->data['title']        = "EDIT SATUAN";
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);
		}
	}
	
	// ---------------------------------//
	/*			     DELETE        	   */
	//---------------------------------//
	function hapus($id=''){
		if($this->arr_Akses['delete'] != '1'){
			$this->session->set_flashdata('no_akses', true);
			redirect('satuan');
		}

		$Find_Count	= $this->db->get_where('satuan',array('id' => $id,'id !='=>$id))->num_rows();
		if($Find_Count > 0){
			$this->session->set_userdata('notif_gagal', 'Proses hapus satuan gagal. Satuan tidak ada dalam daftar...');
            redirect('satuan');
		}else {
			$this->db->trans_begin();
			$this->db->delete('satuan', array('id' => $id));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus satuan gagal. Silahkan coba kembali...');
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus satuan sukses...');
				history('Hapus Satuan '.$id);
			}
			redirect('satuan');
		}		
	}
}