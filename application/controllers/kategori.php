<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kategori extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents     = 'kategori/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		
		#### CEK SESSION USER ####
		if($this->session->userdata('battindo_ses_isLogin')){			
			$this->arr_Akses	= $this->m_master->check_menu($controller);
		}
		
		
    }

	//--------------------------------------------------//
    /*                       INDEX                      */
    //--------------------------------------------------//
    function index(){

		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}

        $this->data['rows_list']    = $this->m_master->get_all_list('kategori','*','kode DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR KATEGORI';
        $this->data['contents']     = $this->contents . 'v_kategori';
		
        history('Lihat kategori');
        $this->load->view($this->template, $this->data);
    }
    
	//--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('kategori');
		}

		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
       
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari Kategori Dengan Kata Kunci "' . $search . '"');
			$Search_By	="kategori LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('kategori','*','kategori DESC',"",$limit,$offset,$Search_By);

        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;

            $response['content']   		= $this->load->view('contents/kategori/v_more',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     	= FALSE;
        }  
        echo json_encode($response);   
    }
    
	//--------------------------------------------------//
    /*                      ADD                         */
    //--------------------------------------------------//
    function add(){
        if($this->input->post()){
            $Kategori_Name		= $this->input->post('kategori');
			$Kategori_Desc  	= $this->input->post('desc');
			
			## CEK IF EXISTS ##
			$Num_kategori		= $this->m_master->getCount('kategori','LOWER(kategori)',strtolower($Kategori_Name));
			if($Num_kategori > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Kategori sudah ada dalam daftar...'
				);
			}else{
				# AMBIL KODE URUT #
				$Tahun_Now		= date('Y');
				$Qry_Urut		= "SELECT
										MAX(
											CAST(
												SUBSTRING_INDEX(kode, '-', - 1) AS UNSIGNED
											)
										) AS urut
									FROM
										kategori
									LIMIT 1";
				$Urut_kategori		= 1;
				$det_Urut		= $this->db->query($Qry_Urut)->result();
				if($det_Urut){
					$Urut_kategori	= $det_Urut[0]->urut + 1;
				}
				$Code_kategori		= 'CODE-'.sprintf('%05d',$Urut_kategori);
				if($Urut_kategori >= 100000){
					$Code_kategori		= 'CODE-'.$Urut_kategori;
				}

				// | -------------------------------------------------------------
				// |  UPLOAD FILE BASED ON PILIHAN FILE
				// | ---------------------------------------------------------------
				$path           	= './uploads/kategori/';
                $filename			= $ext ='';
				if($_FILES && isset($_FILES['pic_upload']['name']) && $_FILES['pic_upload']['name'] != ''){
					$filename   = $_FILES['pic_upload']['name'];
					$ext        = getExtension($filename);
					$new_file   = sha1(date('YmdHis'));
					$filename   = $Code_kategori.'.'.$ext;
					ImageResizes($_FILES['pic_upload'],'kategori',$Code_kategori);					
				}

				$data			= array(
                    'kode'          => $Code_kategori,
					'kategori'		=> ucwords(strtolower($Kategori_Name)),
					'image_name'	=> $filename,
					'image_type'	=> $ext,
					'desc'			=> ucwords(strtolower($Kategori_Desc)),
					'created_by'	=> $this->session->userdata('battindo_ses_userid'),
					'created_date'	=> date('Y-m-d H:i:s')
				);
				
				$this->db->trans_begin();
				$this->db->insert('kategori',$data);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah kategori gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah kategori sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah kategori sukses...');
					history('Tambah kategori '.$Code_kategori.' '.$Kategori_Name);
				}
			}           
			echo json_encode($Arr_Return);
        }else{

            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('kategori');
			}

			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH KATEGORI';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}

        
    }

	//--------------------------------------------------//
    /*                    DETAIL                        */
    //--------------------------------------------------//
    function detail($kode_kategori=''){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('kategori');
		}

        $this->data['rows_list']  	= $this->m_master->read('kategori', 'kategori', 'ASC', '*', 'kode', $kode_kategori);
        $this->data['title']        = 'DETAIL KATEGORI';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }
    
	//--------------------------------------------------//
    /*                 		UPDATE	                    */
    //--------------------------------------------------//
    function edit($Kode_kategori= ''){
		if($this->input->post()){

			if($this->input->post('active')){
				$sts_Act	= 'Y';
			}

            $Kode_kategori	= $this->input->post('kode');
            $kategori       = $this->input->post('kategori');
            $desc           = $this->input->post('desc');
            $flag_active   	= 'Y';
            $modified_by    = $this->session->userdata('battindo_ses_userid');
			$modified_date  = date('Y-m-d H:i:s');

			##### CEK DATA KATEGORI #####
			$Find_Count	= $this->db->get_where('kategori',array('kode' => $Kode_kategori,'kode !='=>$Kode_kategori))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Kategori sudah ada dalam daftar...'
				);
			}else {
				                   
                    
				$path = './uploads/kategori/';

				if (!file_exists($path)) {
					mkdir($path, 0777, true);
				}
				$Check_Data		= $this->m_master->read_where('kategori', 'kategori','ASC', '*',array('kode'=>$Kode_kategori));				
				$File_Gambar	= $Check_Data[0]->image_name;		
                $filename		= $ext ='';
                
				/* -------------------------------------------------------------
				|  UPLOAD FILE BASED ON PILIHAN FILE
				| ---------------------------------------------------------------
				*/

				if($_FILES && isset($_FILES['pic_upload']['name']) && $_FILES['pic_upload']['name'] != ''){
					## HAPUS GAMBAR EXISTING ##
					if(!empty($File_Gambar) && $File_Gambar !=='-'){
						if (file_exists($path.$File_Gambar)) {
							unlink($path.$File_Gambar);
						}
                    }
                    
					$filename   = $_FILES['pic_upload']['name'];
					$ext        = getExtension($filename);
					
					$new_file   = sha1(date('YmdHis'));
					$filename   = $Kode_kategori.'.'.$ext;
					ImageResizes($_FILES['pic_upload'],'kategori',$Kode_kategori);					
				}
				


				$data_kategori = array(
					'kategori' 		=> $kategori,
					'desc'			=> $desc,
					'flag_active' 	=> $flag_active,
					'modified_by' 	=> $modified_by,
					'modified_date' => $modified_date
				);

				if($filename != ''){
					$data_kategori = array(
						'image_name'	=> $filename,
						'image_type'	=> $ext
					);
				}

				$this->db->trans_begin();
				$this->db->update('kategori',$data_kategori,array('kode'=>$Kode_kategori));

				$barang_kat = array(
							'kode_kategori' => $Kode_kategori,
							'kategori' 		=> $kategori
				);
				$this->db->update('barang',$barang_kat,array('kode_kategori'=>$Kode_kategori));
			
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Proses edit kategori gagal. Silahkan coba kembali...'
				);
				
			}else{
				
				
				$this->db->trans_commit();
				$Arr_Return		= array(
					'status'		=> 1,
					'pesan'			=> 'Proses edit kategori sukses...'
				);
				$this->session->set_userdata('notif_sukses', 'Proses edit kategori sukses...');
				history('Edit Kategori '.$Kode_kategori);
			}
			echo json_encode($Arr_Return);

			}
			
			
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('kategori');
			}
			$this->data['rows_list']  	= $this->m_master->read('kategori', 'kategori', 'ASC', '*', 'kode', $Kode_kategori);
			$this->data['title']        = "EDIT KATEGORI";
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);
		}

	}
	
	//--------------------------------------------------//
    /*                		DELETE                      */
    //--------------------------------------------------//
	function hapus($kode_kategori=''){

		##### CEK USER AKSES #####
		if($this->arr_Akses['delete'] != '1'){
			$this->session->set_flashdata('no_akses', true);
			redirect('kategori');
		}
  

		$Find_Count	= $this->db->get_where('kategori',array('kode' => $kode_kategori,'kode !='=>$kode_kategori))->num_rows();
		if($Find_Count > 0){
			$this->session->set_userdata('notif_gagal', 'Proses Hapus Kategori gagal. Kategori Tidak ada dalam daftar...');
            redirect('kategori');
		}else {
			$this->db->trans_begin();
			$query_kategori = "SELECT * FROM kategori WHERE kode = '$kode_kategori'";
            $kategori = $this->db->query($query_kategori)->row();

            if($kategori){
                $path          = './uploads/kategori/';
                $File_Gambar   = $kategori->image_name;
                unlink($path.$File_Gambar); 
            }
			$this->db->delete('kategori', array('kode' => $kode_kategori));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus kategori gagal. Silahkan coba kembali...');
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus kategori sukses...');
				history('Hapus Kategori '.$kode_kategori);
			}
			redirect('kategori');
		
		}
	}
	
}