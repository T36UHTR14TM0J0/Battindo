<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');
        $this->contents     = 'users/';
        $this->ajax_contents= 'users/' . $this->contents;
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
		// CEK AKSES USER //
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
        }
        
        $this->data['rows_list']    = $this->m_master->get_all_list('users','*','created_date DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR AKUN PENGGUNA';
        $this->data['contents']     = $this->contents . 'v_users';
        history('View List Users');
        $this->load->view($this->template, $this->data);
	}

	 //--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
        ##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('users');
        }
        
		(!$this->input->is_ajax_request() ? show_404() : '');
        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
    
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Search Users By Keyword "' . $search . '"');
			$Search_By	="name LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('users','*','created_by DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']        = $this->load->view('contents/users/v_more',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
	}
	
	//--------------------------------------------------//
    /*                 DETAIL PRODUCT                   */
    //--------------------------------------------------//
    function detail($user_id=''){
        ##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('users');
        }
        
        $this->data['rows_list']  	= $this->m_master->read('users', 'created_by', 'DESC', '*', 'userid', $user_id);
        $this->data['title']        = 'DETAIL AKUN PENGGUNA';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }



    function add(){
        if($this->input->post()){
            // echo"<pre>";print_r($this->input->post());exit;
            $Created_By		= $this->session->userdata('battindo_ses_userid');
			$Created_Date	= date('Y-m-d H:i:s');
			$Name			= ucwords(strtolower($this->input->post('name')));
			$prog_penjualan = $this->input->post('prog_penjualan');
			$Area			= 'JKT';
			$Phone			= str_replace(array('+',' '),'',$this->input->post('phone'));
			$No_KTP			= str_replace(' ','',$this->input->post('no_ktp'));
			$Group_ID		= $this->input->post('group');
			$Email			= $this->input->post('email');
			$Password		= security_hash($this->input->post('password'));
			$Notes_alamat   = $this->input->post('notes_alamat');
			$lat			= $this->input->post('latitude');
			$long			= $this->input->post('longitude');
			$alamat			= $this->input->post('address');
			$cat_alamat		= 'OTHER';

            

            //-------------------------------------------- //
			/*  CEK EXISTING BASED ON NO HANDPHONE OR EMAIL */
			// -------------------------------------------- //
			$Qry_Find	= "SELECT * FROM users WHERE phone = '".$Phone."' OR LOWER(email) = '".strtolower($Email)."'";
			$Find_Count	= $this->db->query($Qry_Find)->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'No Telepon Atau Email sudah digunakan ...'
				);
			}else{

                # AMBIL KODE URUT #
				$Tahun_Now		= date('Y');
				$Qry_Urut		= "SELECT
										MAX(
											CAST(
												SUBSTRING_INDEX(userid, '-', - 1) AS UNSIGNED
											)
										) AS urut
									FROM
										users
									LIMIT 1";
				$Urut_User		= 1;
				$det_Urut		= $this->db->query($Qry_Urut)->result();
				if($det_Urut){
					$Urut_User	= $det_Urut[0]->urut + 1;
				}
				$Code_User		= 'USER-'.sprintf('%05d',$Urut_User);
				if($Urut_User >= 100000){
					$Code_User		= 'USER-'.$Urut_User;
                }
                
                $Qry_Urut_Cust		= "SELECT
 										MAX(
 											CAST(
 												SUBSTRING_INDEX(custid, '-', - 1) AS UNSIGNED
 											)
 										) AS urut
 									FROM
 										customers
 									WHERE created_date LIKE '".$Tahun_Now."%'
 									LIMIT 1";
				$Urut_Cust		= 1;
				$det_Urut_Cust	= $this->db->query($Qry_Urut_Cust)->result();
				if($det_Urut_Cust){
					$Urut_Cust	= $det_Urut_Cust[0]->urut + 1;
				}
				$Nocust			= 'CUST-'.$Tahun_Now.'-'.sprintf('%05d',$Urut_Cust);
				if($Urut_Cust >= 100000){
					$Nocust		='CUST-'.$Tahun_Now.'-'.$Urut_Cust;
				}
				
				// | -------------------------------------------------------------
				// |  UPLOAD FILE BASED ON PILIHAN FILE
				// | ---------------------------------------------------------------
				$path           	= './uploads/user/';
				$path           	= './uploads/identity_card/';
				$filename			= $ext ='';
				$filename_ktp		= $ext_ktp = '';

				if($_FILES && isset($_FILES['pic_upload']['name']) && $_FILES['pic_upload']['name'] != ''){
					$filename   = $_FILES['pic_upload']['name'];
					$ext        = getExtension($filename);
					$new_file   = sha1(date('YmdHis'));
					$filename   = $Code_User.'.'.strtolower($ext);
					ImageResizes($_FILES['pic_upload'],'user',$Code_User);					
				}

				if($_FILES && isset($_FILES['pic_upload_ktp']['name']) && $_FILES['pic_upload_ktp']['name'] != ''){
					$filename_ktp   = $_FILES['pic_upload_ktp']['name'];
					$ext_ktp        = getExtension($filename_ktp);
					$new_file   = sha1(date('YmdHis'));
					$filename_ktp   = $Nocust.'.'.strtolower($ext_ktp);
					ImageResizes($_FILES['pic_upload_ktp'],'identity_card',$Nocust);					
				}
                
                

                
                

                $Ins_Customer		= array(
                    'custid'		=> $Nocust,
                    'customer'		=> $Name,
                    'cust_type'		=> 'PER',
                    'no_ktp'		=> $No_KTP,
                    'email'			=> $Email,
                    'phone'			=> $Phone,
                    'file_ktp'		=> $filename_ktp,
					'file_ktp_ext'	=> strtolower($ext_ktp),
					'program_penjualan' => $prog_penjualan,
                    'flag_active'	=> 'Y',
                    'created_by'	=> $Created_By,
                    'created_date'	=> $Created_Date
                );
                                    
                $Ins_Users			= array(
                	'userid'			=> $Code_User,
                	'phone'				=> $Phone,
                	'password'			=> $Password,
                	'group_id'			=> $Group_ID,
                	'name'				=> $Name,
                	'email'				=> $Email,
                	'kdcab'				=> $Area,
                	'custid'			=> $Nocust,
                	'flag_active'		=> '1',
                	'image_name'		=> $filename,
                	'image_type'		=> strtolower($ext),
                	'created_date'		=> $Created_Date,
                	'created_by'		=> $Created_By
				);
				
				$Ins_alamat	= array(
						'custid'	=> $Nocust,
						'address'	=> $alamat,
						'latitude'	=> $lat,
						'longitude' => $long,
						'category'	=> $cat_alamat,
						'notes'		=> $Notes_alamat,
						'flag_active' => 'Y',
						'created_by' => $Created_By,
						'created_date' => $Created_Date
				);
                

                $this->db->trans_begin();
				$this->db->insert('users',$Ins_Users);
				$this->db->insert('customers',$Ins_Customer);
				$this->db->insert('customer_address_deliveries',$Ins_alamat);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah akun pengguna gagal. Silahkan coba lagi...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah akun pengguna berhasil...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses daftar berhasil...');
					history('Tambah Akun Pengguna '.$Name.' - '.$Phone);
				}
            }

            echo json_encode($Arr_Return);

        }else{
            $this->data['rows_group'] 	= $this->m_master->getArray('groups',array(),'id','name');
			$this->data['title']        = 'Tambah Akun Pengguna';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
        }
	}
	


	function edit($custid = ''){
		if($this->input->post()){
			$sts_Act	= 0;
			if($this->input->post('flag_active')){
				$sts_Act	= 1;
			}

			$parent_id = 'N';
			if($sts_Act == 1){
				$parent_id = 'Y';
			}


			$User_Code = $this->input->post('user_id');
			$Cust_Code = $this->input->post('custid');
			$nama		= $this->input->post('name');
			$prog_penjualan = $this->input->post('prog_penjualan');
			$group		= $this->input->post('group');
			$no_ktp		= str_replace(' ','',$this->input->post('no_ktp'));
			$Email		= $this->input->post('email');
			$phone 		=  str_replace(array('+',' '),'',$this->input->post('phone'));
			$Modified_By	= $this->session->userdata('battindo_ses_userid');
			$Modified_Date	= date('Y-m-d H:i:s');

			$Find_Count	= $this->db->get_where('users',array('phone' => $phone,'userid !='=>$User_Code))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Phone No already exists in list...'
				);
			}else{

				$path_profil = './uploads/user/';
				$path_ktp	 = './uploads/identity_card/';

				if (!file_exists($path_profil)) {
					mkdir($path_profil, 0777, true);
				}
				$Check_Data		= $this->m_master->read_where('users', 'name','ASC', '*',array('userid'=>$User_Code));				
				$File_Gambar_profil	= $Check_Data[0]->image_name;		
				$filename_profil		= $ext_profil ='';

				if (!file_exists($path_ktp)) {
					mkdir($path_ktp, 0777, true);
				}
				$Check_Data		= $this->m_master->read_where('customers', 'customer','ASC', '*',array('custid'=>$Cust_Code));				
				$File_Gambar_ktp	= $Check_Data[0]->file_ktp;		
				$filename_ktp		= $ext_ktp ='';



				/* -------------------------------------------------------------
				|  UPLOAD FILE PROFIL
				| ---------------------------------------------------------------
				*/
				if($_FILES && isset($_FILES['pic_upload_selfi']['name']) && $_FILES['pic_upload_selfi']['name'] != ''){
					## HAPUS GAMBAR EXISTING ##
					if(!empty($File_Gambar_profil) && $File_Gambar_profil !=='-'){
						if (file_exists($path_profil.$File_Gambar_profil)) {
							unlink($path_profil.$File_Gambar_profil);
						}
					}
					$filename_profil   = $_FILES['pic_upload_selfi']['name'];
					$ext_profil        = getExtension($filename_profil);
					
					$new_file   = sha1(date('YmdHis'));
					$filename_profil   = $User_Code.'.'.$ext_profil;
					ImageResizes($_FILES['pic_upload_selfi'],'user',$User_Code);					
				}

				/* -------------------------------------------------------------
				|  UPLOAD FILE KTP
				| ---------------------------------------------------------------
				*/
				if($_FILES && isset($_FILES['pic_upload_ktp']['name']) && $_FILES['pic_upload_ktp']['name'] != ''){
					## HAPUS GAMBAR EXISTING ##
					if(!empty($File_Gambar_ktp) && $File_Gambar_ktp !=='-'){
						if (file_exists($path_ktp.$File_Gambar_ktp)) {
							unlink($path_ktp.$File_Gambar_ktp);
						}
					}
					$filename_ktp   = $_FILES['pic_upload_ktp']['name'];
					$ext_ktp        = getExtension($filename_ktp);
					
					$new_file   = sha1(date('YmdHis'));
					$filename_ktp   = $Cust_Code.'.'.$ext_ktp;
					ImageResizes($_FILES['pic_upload_ktp'],'identity_card',$Cust_Code);					
				}

				$Ins_User = array(
					'phone' 		=> $phone,
					'group_id' 		=> $group,
					'name'			=> $nama,
					'email' 		=> $Email,
					'flag_active' 	=> $sts_Act,
					'modified_by' 	=> $Modified_By,
					'modified_date' => $Modified_Date

				);

				$Ins_Customer = array(
					'customer' 			=> $nama,
					'no_ktp'	 		=> $no_ktp,
					'email' 			=> $Email,
					'phone' 			=> $phone,
					'program_penjualan' => $prog_penjualan,
					'flag_active' 		=> $parent_id,
					'modified_by' 		=> $Modified_By,
					'modified_date' 	=> $Modified_Date

				);

				

				if($filename_profil != ''){
					$Ins_Detail['image_name'] = $filename_profil;
					$Ins_Detail['image_type'] = $ext_profil;
				}

				if($filename_ktp != ''){
					$Ins_Detail['file_ktp'] = $filename_ktp;
					$Ins_Detail['file_ktp_ext'] = $ext_ktp;
				}


				$this->db->trans_begin();
				$this->db->update('users',$Ins_User,array('userid' => $User_Code));
				$this->db->update('customers',$Ins_Customer,array('custid'=> $Cust_Code));
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit akun pengguna gagal. Silahkan coba lagi...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses edit akun pengguna berhasil...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses edit akun pengguna berhasil...');
					history('Edit Akun Pengguna '.$nama.' - '.$phone);
				}

			}

			echo json_encode($Arr_Return);



		}else{

			 $sql_contact = 'SELECT 
							t1.*
							, t2.name AS group_name	
                       	 	FROM users as t1
                        	INNER JOIN groups as t2 ON t1.group_id = t2.id 
                        	WHERE t1.custid = "'.$custid.'"
                    		';
			
			$this->data['rows_edit']    = $this->db->query($sql_contact)->row();
			$this->data['rows_customer']  	= $this->m_master->read('customers', 'customer', 'ASC', '*', 'custid', $custid);
			$this->data['rows_alamat']  	= $this->m_master->read('customer_address_deliveries', 'custid', 'ASC', '*', 'custid', $custid);
			$this->data['rows_address']    = $this->db->get_where('customer_address_deliveries',array('custid'=>$custid))->result();
			$this->data['rows_group'] 	= $this->m_master->getArray('groups',array(),'id','name');
			$this->data['title']    	= 'Edit Akun Pengguna';
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);

		}
	}



	

	/*

    | -------------------------------------------------------------------

    | SAVE CUST ADDRESS

    | -------------------------------------------------------------------

    */

	function save_cust_address(){



		if($this->input->post())
		{
			$custid 		= $this->input->post('custid');
			$address		= $this->input->post('alamat');
			$latitude		= $this->input->post('latitude');
			$longitude		= $this->input->post('longitude');
			$category		= $this->input->post('category');
			$notes			= $this->input->post('notes');
			$Modified_By	= $this->session->userdata('battindo_ses_userid');
			$Modified_Date	= date('Y-m-d H:i:s');
			

			$Ins_Detail			= array(
				 'custid'		=> $custid,
				 'address'		=> $address,
				 'latitude'		=> $latitude,
				 'longitude'	=> $longitude,
				 'category'		=> $category,
				 'notes'		=> $notes,
				 'modified_by'	=> $Modified_By,
				 'modified_date' => $Modified_Date
			 );



		
			$this->db->trans_begin();
			$this->db->insert('customer_address_deliveries',$Ins_Detail);
			 if ($this->db->trans_status() !== TRUE){
				 $this->db->trans_rollback();
				 $Arr_Return		= array(
					 'status'		=> 2,
					 'pesan'			=> 'Add Address Delivery failed. Please try again...'
				 );

			 }else{

				 $this->db->trans_commit();
				 $Arr_Return		= array(
					 'status'		=> 1,
					 'pesan'			=> 'Address Delivery process. Thank you & have a nice day..'
				 );

				 $this->session->set_userdata('notif_sukses', 'Add Address process success. Thank you & have a nice day..');
				 history(' Add Address Delivery '.$custid);

			 }

		}else{
			 $Arr_Return		= array(
				 'status'			=> 2,
				 'pesan'			=> 'No records was found..'
			 );
		}

		echo json_encode($Arr_Return);



	} 

    
    //--------------------------------------------------//
    /*                 DELETE PRODUCT                   */
    //--------------------------------------------------//
    function hapus($custid=''){
        if($this->arr_Akses['delete'] != '1'){
            $this->session->set_flashdata('no_akses', true);
            redirect('users');
        }

		
            $query_user = "SELECT * FROM users WHERE custid = '$custid'";
            $query_cust = "SELECT * FROM customers WHERE custid = '$custid'";
            $user = $this->db->query($query_user)->row();
            $cust = $this->db->query($query_cust)->row();

            if($user){
                $path          = './uploads/user/';
                $File_Gambar   = $user->image_name;
                unlink($path.$File_Gambar);
            }

            if($cust){
                $path_ktp          = './uploads/identity_card/';
                $File_Gambar_ktp   = $cust->file_ktp;
                unlink($path_ktp.$File_Gambar_ktp);
            }
            
			$this->db->trans_begin();
			## HAPUS DI DATA BUNDLE ~ ANTISIPASI ##
			 $this->db->delete('users',array('custid' => $custid));
             $this->db->delete('customers',array('custid' => $custid));
             $this->db->delete('customer_address_deliveries',array('custid' => $custid));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus akun gagal ....');
						
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus akun berhasil ....');
				history('Hapus Akun '.$custid);
			}
			redirect('users');
        
    }

 
}