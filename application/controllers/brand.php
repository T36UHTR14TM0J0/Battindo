<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Brand extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents    		= 'brand/';
        $this->ajax_contents	= 'contents/' . $this->contents;
        $this->template     	= 'layouts/v_backoffice';
        $this->data         	= array();
        $this->limit        	= 25;
		$controller				= ucfirst(strtolower($this->uri->segment(1)));
		
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

        $this->data['rows_list']    = $this->m_master->get_all_list('brands_produk','*','id DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR MERK';
        $this->data['contents']     = $this->contents . 'v_brand';
		
        history('Lihat Merk');
        $this->load->view($this->template, $this->data);
    }
    
	// -------------------------------------------- //
	/*  				LOAD MORE				    */
	// -------------------------------------------- //
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('brand');
		}

		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
		$Search_By	= "";

        if($search){ // SAVE HISTORY
            history('Cari Merk Dengan Kata Kunci "' . $search . '"');
			$Search_By	="brand LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('brands_produk','*','brand DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
			$this->data['offset']   	= $offset + 1;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['rows_list']  	= $data;
            $this->data['alfabet']  	= $alfabet;
            $response['content']    	= $this->load->view('contents/brand/v_more',  $this->data, TRUE);
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
            $brand			= $this->input->post('brand');
			$descr			= $this->input->post('descr');
			
			## CEK IF EXISTS ##
			$Num_brand		= $this->m_master->getCount('brands_produk','LOWER(brand)',strtolower($brand));
			if($Num_brand > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Merk sudah ada dalam daftar....'
				);
			}else{

				$data	= array(
					'brand'			=> ucwords(strtolower($brand)),
					'descr'			=> ucwords(strtolower($descr)),
					'created_by'	=> $this->session->userdata('battindo_ses_userid'),
					'created_date'	=> date('Y-m-d H:i:s')
				);
				$this->db->trans_begin();
				$this->db->insert('brands_produk',$data);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah merk gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah merk sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah merk sukses...');
					history('Tambah Merk '.$brand);
				}
			}           
			echo json_encode($Arr_Return);
        }else{
            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('brand');
			}
			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH MERK';
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
            redirect('brand');
		}

        $this->data['rows_list']  	= $this->m_master->read('brands_produk', 'brand', 'DESC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL MERK';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }
    
	// ---------------------------------//
	/*               EDIT              */
	//---------------------------------//
    function edit($id= ''){
		if($this->input->post()){
            $id				= $this->input->post('id');
            $brand        	= $this->input->post('brand');
            $descr      	= $this->input->post('descr');
            $modified_by   	= $this->session->userdata('battindo_ses_userid');
			$modified_date 	= date('Y-m-d H:i:s');
			
			##### CEK DATA BRAND #####
			$Find_Count	= $this->db->get_where('brands_produk',array('id' => $id,'id !='=>$id))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Merk tidak ada dalam daftar...'
				);
			}else {

				$data_brand = array(
					'brand'			=> $brand,
					'descr'			=> $descr,
					'modified_by' 	=> $modified_by,
					'modified_date' => $modified_date
				);

				$data_barang = array(
					'id_brand' 	=> $id,
					'brand'		=> $brand
				);


				$this->db->trans_begin();
				$this->db->update('brands_produk',$data_brand,array('id'=>$id));
				$this->db->update('barang',$data_barang,array('id_brand'=>$id));

				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit merk gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1
					);
					$this->session->set_userdata('notif_sukses', 'Proses edit merk sukses...');
					history('Edit Merk '.$id);
				}
				

			}
			echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('brand');
			}
			$this->data['rows_list']  	= $this->m_master->read('brands_produk', 'brand', 'ASC', '*', 'id', $id);
			$this->data['title']        = "EDIT MERK";
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
			redirect('brand');
		}
		
		$Find_Count	= $this->db->get_where('brands_produk',array('id' => $id,'id !='=>$id))->num_rows();
		if($Find_Count > 0){
			$this->session->set_userdata('notif_gagal', 'Proses hapus merk gagal. Merk tidak ada dalam daftar');
            redirect('brand');
		}else {
			$this->db->trans_begin();
			$query_brand = "SELECT * FROM brands_produk WHERE id = '$id'";
            $brand = $this->db->query($query_brand)->row();

            if($brand){
                $path          = './uploads/brand/';
                $File_Gambar   = $brand->image_name;
                unlink($path.$File_Gambar);
            }
			$this->db->delete('brands_produk', array('id' => $id));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus merk gagal. Silahkan coba kembali...');
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus merk sukses...');
				history('Hapus Merk '.$id);
			}
			redirect('brand');
		}
	}
}