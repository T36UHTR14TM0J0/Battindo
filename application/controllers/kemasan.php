<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kemasan extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents     = 'kemasan/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		
		##### CEK SESSION LOGIN USER #####
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

        $this->data['rows_list']    = $this->m_master->get_all_list('kemasan','*','id DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'Daftar Kemasan';
        $this->data['contents']     = $this->contents . 'v_kemasan';
		
        history('Lihat Kemasan Produk');

        $this->load->view($this->template, $this->data);
    }
    
	// -------------------------------------------- //
	/*  				LOAD MORE				    */
	// -------------------------------------------- //
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('kemasan');
		}

		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
		$Search_By	= "";

        if($search){ // SAVE HISTORY
            history('Cari Kemasan Dengan Kata Kunci "' . $search . '"');
			$Search_By	="kemasan LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('kemasan','*','kemasan DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
			$this->data['offset']   	= $offset + 1;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['rows_list']  	= $data;
            $this->data['alfabet']  	= $alfabet;
            $response['content']    	= $this->load->view('contents/kemasan/v_more',  $this->data, TRUE);
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
            $kemasan			= $this->input->post('kemasan');
			$Desc  				= $this->input->post('desc');
			
			## CEK IF EXISTS ##
			$Num_kemasan		= $this->m_master->getCount('kemasan','LOWER(kemasan)',strtolower($kemasan));
			if($Num_kemasan > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Kemasan sudah ada dalam daftar....'
				);
			}else{
				$data			= array(
					'kemasan'		=> ucwords(strtolower($kemasan)),
					'descr'			=> ucwords(strtolower($Desc)),
					'created_by'	=> $this->session->userdata('battindo_ses_userid'),
					'created_date'	=> date('Y-m-d H:i:s')
				);
				$this->db->trans_begin();
				$this->db->insert('kemasan',$data);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah kemasan gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah kemasan sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah kemasan sukses...');
					history('Tambah Kemasan '.$kemasan);
				}
			}           
			echo json_encode($Arr_Return);
        }else{
            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('kemasan');
			}
			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH KEMASAN';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}
    }

	// ---------------------------------//
	/*		        DETAIL             */
	//---------------------------------//
    function detail($id=''){
		if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
			redirect('kemasan');
		}

        $this->data['rows_list']  	= $this->m_master->read('kemasan', 'kemasan', 'ASC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL KEMASAN';
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
            $data['kemasan']        = $this->input->post('kemasan');
            $data['descr']          = $this->input->post('desc');
            $data['modified_by']    = $this->session->userdata('battindo_ses_userid');
			$data['modified_date']  = date('Y-m-d H:i:s');
			
			##### CEK DATA KEMASAN #####
			$Find_Count	= $this->db->get_where('kemasan',array('id' => $id,'id !='=>$id))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Kemasan tidak ada dalam daftar...'
				);
			}else {

				$data_kemas = array(
					'id_kemasan' => $id,
					'kemasan' => $this->input->post('kemasan')
				);

				$this->db->trans_begin();
				$this->db->update('kemasan',$data,array('id'=>$id));
				$this->db->update('barang',$data_kemas,array('id_kemasan'=>$id));

				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit kemasan gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1
					);
					$this->session->set_userdata('notif_sukses', 'Proses edit kemasan sukses...');
					history('Edit Kemasan '.$id);
				}
				

			}
			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('kemasan');
			}
			$this->data['rows_list']  	= $this->m_master->read('kemasan', 'kemasan', 'DESC', '*', 'id', $id);
			$this->data['title']        = "EDIT KEMASAN";
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
			redirect('kemasan');
		}

		$Find_Count	= $this->db->get_where('kemasan',array('id' => $id,'id !='=>$id))->num_rows();
		if($Find_Count > 0){
			$this->session->set_userdata('notif_gagal', 'Hapus kemasan gagal. Kemasan tidak ada dalam daftar...');
            redirect('kemasan');
		}else {
			$this->db->trans_begin();
			$this->db->delete('kemasan', array('id' => $id));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus kemasan gagal. Silahkan coba kembali...');
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus kemasan sukses...');
				history('Delete kemasan '.$id);
			}
			redirect('kemasan');
		}
	}
}