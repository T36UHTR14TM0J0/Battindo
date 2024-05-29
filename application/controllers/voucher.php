<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Voucher extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
        $this->contents     = 'voucher/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$this->load->library('encrypt');
		
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



        $this->data['rows_list']    = $this->m_master->get_all_list('vouchers','*','kode_voucher ASC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR VOUCHER';
        $this->data['contents']     = $this->contents . 'v_voucher';
		
        history('Lihat Voucher');

        $this->load->view($this->template, $this->data);
    }
    

	// -------------------------------------------- //
	/*  				LOAD MORE				    */
	// -------------------------------------------- //
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('voucher');
		}


		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
       
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari Voucher Dengan Kata Kunci "' . $search . '"');
			$Search_By	="kode_voucher LIKE '%".$search."%'" . " OR valid_until LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('vouchers','*','kode_voucher ASC',"",$limit,$offset,$Search_By);

        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;

            $response['content']    = $this->load->view('contents/voucher/v_more',  $this->data, TRUE);
            $response['status']     = TRUE;
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
			$kode_voucher		= htmlspecialchars(strtolower($this->input->post('kode_voucher')));
			$valid_until		= $this->input->post('valid_until');
			$type_voucher		= $this->input->post('type_voucher');
			$nilai_voucher		= preg_replace('/[Rp. ]/','',$this->input->post('nilai_voucher'));
			$jumlah_voucher		= $this->input->post('jumlah_voucher');
			$jumlah_use			= $this->input->post('jumlah_use');
			$descr			  	= htmlspecialchars(strtolower($this->input->post('descr')));
			$min_nilai			= preg_replace('/[Rp. ]/','',$this->input->post('min_nilai'));
			$flag_active		= "Y";
			$create_by			= $this->session->userdata('battindo_ses_userid');
			$create_date		= date('Y-m-d H:i:s');

			
			## CEK IF EXISTS ##
			$Num_voucher		= $this->m_master->getCount('vouchers','LOWER(kode_voucher)',strtolower($kode_voucher));
			if($Num_voucher > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Vaucher sudah ada didalam daftar....'
				);
			}else{
				
				$data			= array(
					'kode_voucher'  => $kode_voucher,
					'valid_until'	=> $valid_until,
					'type_voucher'	=> $type_voucher,
					'nilai_voucher'	=> $nilai_voucher,
					'jumlah_voucher'=> $jumlah_voucher,
					'jumlah_use'	=> $jumlah_use,
					'descr'			=> $descr,
					'min_nilai'		=> $min_nilai,
					'flag_active'	=> $flag_active,
					'created_by'	=> $create_by,
					'created_date'	=> $create_date
				);
				
				$this->db->trans_begin();
				$this->db->insert('vouchers',$data);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah voucher gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah voucher sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah voucher sukses...');
					history('Tambah Voucher '.$kode_voucher.' '.$type_voucher);
				}
			}           
			echo json_encode($Arr_Return);
        }else{
            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('voucher');
			}

			$this->data['action']  		= 'add';
			$this->data['title']        = 'Tambah Voucher';
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
            redirect('voucher');
		}

        $this->data['rows_list']  	= $this->m_master->read('vouchers', 'kode_voucher', 'ASC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL VOUCHER';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
	}
	
	// ---------------------------------//
	/*               EDIT              */
	//---------------------------------//  
    function edit($id= ''){
		if($this->input->post()){			
            $id					= $this->input->post('id');
			$kode_voucher		= htmlspecialchars(strtolower($this->input->post('kode_voucher')));
			$valid_until		= $this->input->post('valid_until');
			$type_voucher		= $this->input->post('type_voucher');
			$nilai_voucher		= preg_replace('/[Rp. ]/','',$this->input->post('nilai_voucher'));
			$jumlah_voucher		= $this->input->post('jumlah_voucher');
			$jumlah_use			= $this->input->post('jumlah_use');
			$descr			  	= htmlspecialchars(strtolower($this->input->post('descr')));
			$min_nilai			= preg_replace('/[Rp. ]/','',$this->input->post('min_nilai'));
			$flag_active		= "Y";
            $modified_by    	= $this->session->userdata('battindo_ses_userid');
			$modified_date 		= date('Y-m-d H:i:s');

			$Find_Count	= $this->db->get_where('vouchers',array('id' => $id,'id !='=>$id))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Voucher tidak ada didalama daftar...'
				);
			}else {
				$voucher = array(
                    'kode_voucher' => $kode_voucher,
                    'valid_until' => $valid_until,
                    'type_voucher'       => $type_voucher,
					'nilai_voucher'       => $nilai_voucher,
					'jumlah_voucher'	=> $jumlah_voucher,
					'jumlah_use'	=> $jumlah_use,
					'descr'		=> $descr,
					'min_nilai'	=> $min_nilai,
					'flag_active' => $flag_active, 
                    'modified_by'  => $modified_by,
                    'modified_date'=> $modified_date
                );
				
				$this->db->trans_begin();
				$this->db->update('vouchers',$voucher,array('id'=>$id));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'		=> 'Proses edit voucher gagal. Silahkan coba kembali...'
				);
				
			}else{
				
				
				$this->db->trans_commit();
				$Arr_Return		= array(
					'status'		=> 1,
					'pesan'		=> 'Proses edit voucher sukses...'
				);
				$this->session->set_userdata('notif_sukses', 'Proses edit voucher sukses...');
				history('Edit Voucher '.$kode_voucher);
			}
			echo json_encode($Arr_Return);

			}
			
			
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('voucher');
			}

			$this->data['rows_list']  	= $this->m_master->read('vouchers', 'kode_voucher', 'ASC', '*', 'id', $id);
			$this->data['title']        = "EDIT VOUCHER";
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
			redirect('voucher');
		}

		$Find_Count	= $this->db->get_where('vouchers',array('id' => $id,'id !='=>$id))->num_rows();
		if($Find_Count > 0){
			$this->session->set_userdata('notif_gagal', 'Proses hapus voucher gagal. Voucher tidak ada didalam daftar...');
            redirect('voucher');
		}else {
			$this->db->trans_begin();
			$this->db->delete('vouchers', array('id' => $id));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus voucher gagal. Silahkan coba kembali...');
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus voucher sukse...');
				history('Hapus Voucher '.$id);
			}
			redirect('voucher');
		
		}
	}	
}