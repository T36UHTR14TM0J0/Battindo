<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Contact extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');
        $this->contents     = 'contact/';
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
		$this->data['title']        = 'Akun Pengguna';
        $this->data['contents']     = $this->contents . 'v_contact';
        history('lihat Akun Pengguna');
        $this->load->view($this->template, $this->data);
	}

	// -------------------------------------------- //
	/*				REGISTRASI ACCOUNT			    */
	// -------------------------------------------- //
	function registrasi_account(){
        if($this->input->post()){
			$Created_By		= $this->session->userdata('battindo_ses_userid');
			$Created_Date	= date('Y-m-d H:i:s');
			$Name			= ucwords(strtolower($this->input->post('name')));
			$Area			= 'JKT';
			$Phone			= str_replace(array('+',' '),'',$this->input->post('phone'));
			$No_KTP			= str_replace(' ','',$this->input->post('no_ktp'));
			$Group_ID		= '2';
			$Email			= $this->input->post('email');
			$Password		= security_hash($this->input->post('password'));

			// -------------------------------------------- //
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
				
				$path           	= './uploads/user/';
				$path_ktp           = './uploads/identity_card/';
				$filename			= $ext ='';
				$filename_ktp		= $ext_ktp ='';
				/* -------------------------------------------------------------
				|  UPLOAD FILE BASED ON PILIHAN FILE
				| ---------------------------------------------------------------
				
				if($_FILES && isset($_FILES['pic_upload']['name']) && $_FILES['pic_upload']['name'] != ''){
					$filename   = $_FILES['pic_upload']['name'];
					$ext        = getExtension($filename);
					//$ext        = end($ext);  
					$new_file   = sha1(date('YmdHis'));
					$filename   = $Code_User.'.'.$ext;
					ImageResizes($_FILES['pic_upload'],'user',$Code_User);					
				}
				*/
				
				/* -------------------------------------------------------------
				|  UPLOAD FILE BASED ON CAMERA
				| ---------------------------------------------------------------
				*/
				if($this->input->post('pic_webcam')){					
					$img = $this->input->post('pic_webcam');
					$image_parts    = explode(";base64,", $img);
					$image_type_aux = explode("image/", $image_parts[0]);
					$image_type     = $image_type_aux[1];              
					$image_base64   = base64_decode($image_parts[1]);

					$ext       		= 'png';
					$filename       = $Code_User.'.'.$ext;				  
					file_put_contents($path . $filename, $image_base64);

					
				}
				
				/* -------------------------------------------------------------
				|  UPLOAD FILE BASED ON CAMERA
				| ---------------------------------------------------------------
				*/
				if($this->input->post('pic_webcam_ktp')){					
					$img_ktp 			= $this->input->post('pic_webcam_ktp');
					$image_parts_ktp 	= explode(";base64,", $img_ktp);
					$image_type_aux_ktp = explode("image/", $image_parts_ktp[0]);
					$image_type_ktp    	= $image_type_aux_ktp[1];              
					$image_base64_ktp   = base64_decode($image_parts_ktp[1]);

					$ext_ktp       	= 'png';
					$filename_ktp   = $Nocust.'.'.$ext_ktp;				  
					file_put_contents($path_ktp . $filename_ktp, $image_base64_ktp);

					
				}
				
				$Ins_Customer		= array(
					'custid'		=> $Nocust,
					'customer'		=> $Name,
					'cust_type'		=> 'PER',
					'no_ktp'		=> $No_KTP,
					'email'			=> $Email,
					'phone'			=> $Phone,
					'file_ktp'		=> $filename_ktp,
					'file_ktp_ext'	=> $ext_ktp,
					'flag_active'	=> 'Y',
					'created_by'	=> $Created_By,
					'created_date'	=> $Created_Date
				);
				
				$Ins_Detail			= array(
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
					'image_type'		=> $ext,
					'created_date'		=> $Created_Date,
					'created_by'		=> $Created_By
				);
				
				$this->db->trans_begin();
				$this->db->insert('users',$Ins_Detail);
				$this->db->insert('customers',$Ins_Customer);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses daftar gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses daftar berhasil...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses daftar berhasil...');
					history('Daftar Akun '.$Name.' - '.$Phone);
				}
			}
			echo json_encode($Arr_Return);
			 
            
        }else{			 
			$this->data['rows_group'] 	= $this->m_master->getArray('groups',array(),'id','name');
			$this->data['title']        = 'Daftar Akun';
			$this->data['contents']     = $this->contents . 'v_add';
			
			$this->load->view($this->template, $this->data);
		} // END POST
    }
	
	/* -------------------------------------
	|  LOGIN
	| --------------------------------------
	*/
	function login(){
		$this->data['title']        = 'Login Akun';
        $this->data['contents']     = $this->contents . 'v_login';
        $this->load->view($this->template, $this->data);
	}

	
	/* -------------------------------------
	|  PROSES LOGIN
	| --------------------------------------
	*/
	function check_username(){
    	// PROTECT SQL INJECTION
		$Arr_Return		= array();
		if($this->input->post()){
			$phone 		= $this->security->xss_clean($this->input->post('phone'));
			$password 	= $this->security->xss_clean($this->input->post('password'));
			$phone		= str_replace(array('+',' '),'',$phone);
			$row 		= $this->m_contact->get_login($phone, $password);
			$login		= array();

			if($row){
				$login = array(
					'battindo_ses_userid'        => $row['userid'],
					'battindo_ses_username'      => $row['name'],
					'battindo_ses_cabang'        => $row['kdcab'], // ses_ukdcab
					'battindo_ses_isLogin'       => 1,
					'battindo_ses_fullname'      => $row['name'],
					'battindo_ses_phone'         => $row['phone'],
					'battindo_ses_groupid'       => $row['group_id'],
					'battindo_ses_groupname'     => $row['group_name'],
					'battindo_ses_pic'           => $row['image_name'], 
					'battindo_ses_allbranch'     => $row['flag_all'],
					'battindo_ses_email'	     => $row['email'],
					'battindo_ses_nocust'	     => $row['custid']
				); 
				
					
				$this->session->set_userdata($login);

				// ENCRYPT 
				$password = security_hash($password);
				history('Login Success With Username = "' .$phone. '" and Password = "' .$password. '"');

				$Arr_Return		= array(
					'hasil'			=> 1,
					'pesan'			=> 'Login berhasil...'
				);    		
			} else {
				$Arr_Return		= array(
					'hasil'			=> 2,
					'pesan'			=> 'No Hp atau Kata Sandi salah!.....'
				);
				
			}
		}else{
			$Arr_Return		= array(
				'hasil'			=> 2,
				'pesan'			=> 'Tidak ada rekaman yang diproses.....'
			);
		}
		
		echo json_encode($Arr_Return);
    }

    /* -------------------------------------
	|  DETAIL ACCOUNT
	| --------------------------------------
	*/
    function profile($id_contact=''){        	
		$Kode_User		= '';
		if($id_contact){
			$Kode_User	= $id_contact;
		}else if($this->session->userdata('battindo_ses_isLogin')){
			$Kode_User	= $this->session->userdata('battindo_ses_userid');
		}
		if($Kode_User){
			 $sql_contact = 'SELECT 
								t1.*
								, t2.name AS group_name
								
                        FROM users as t1
                        INNER JOIN groups as t2 ON t1.group_id = t2.id
                        WHERE t1.userid = "'.$Kode_User.'"
                    ';
		
			$contact = $this->db->query($sql_contact)->row();
			history('Lihat Detail Kontak' . $contact->name);
			$this->data['contact']      = $contact;
			$this->data['title']        = 'Akun Detail';
			$this->data['contents']     = $this->contents . 'v_detail';
			$this->load->view($this->template, $this->data); 
		}else{
			$this->session->set_flashdata('no_akses', true);
			redirect('contact');
		}
       
		
	}
	
	/* -------------------------------------------------------------
	|  UPDATE PROFILE
	| ---------------------------------------------------------------
	*/
	function edit_profile(){ 
		if($this->input->post()){
			$id_contact	= Dekripsi($this->input->post('kode_user'));
			// GET CONTACT BY ID
			 $sql_contact = 'SELECT 
								t1.*
								, t2.name AS group_name
								
                        FROM users as t1
                        INNER JOIN groups as t2 ON t1.group_id = t2.id
                        WHERE t1.userid = "'.$id_contact.'"
                    ';

			$this->data['rows_edit']    = $this->db->query($sql_contact)->row();
			
			$this->data['title']    	= 'Edit Akun';
			
			$this->load->view($this->ajax_contents. 'v_edit', $this->data);
		}
	}
	
	

   /* -------------------------------------------------------------
	|  PROSES UPDATE PROFILE
	| ---------------------------------------------------------------
	*/
    function update_data(){
        if($this->input->post()){
            $Modified_By	= $this->session->userdata('battindo_ses_userid');
			$Modified_Date	= date('Y-m-d H:i:s');
			$Name			= ucwords(strtolower($this->input->post('name')));
			$Phone			= str_replace(array('+',' '),'',$this->input->post('phone'));
			$Nocust 		= $this->input->post('custid');
			$Kode_User		= $this->input->post('userid');
			$Email			= $this->input->post('email');
			
			$Flag_Active	='0';
			if($this->input->post('flag_active')){
				$Flag_Active	='1';
			}
			
			$Find_Count	= $this->db->get_where('users',array('phone' => $Phone,'userid !='=>$Kode_User))->num_rows();
			if($Find_Count > 0){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'No hp belum terdaftar...'
				);
			}else{
				$path = './uploads/user/';

				if (!file_exists($path)) {
					mkdir($path, 0777, true);
				}
				$Check_Data		= $this->m_master->read_where('users', 'name','ASC', '*',array('userid'=>$Kode_User));				
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
					$filename   = $Kode_User.'.'.$ext;
					ImageResizes($_FILES['pic_upload'],'user',$Kode_User);					
				}

				/* -------------------------------------------------------------
				|  UPLOAD FILE BASED ON CAMERA
				| ---------------------------------------------------------------
				*/
				if($this->input->post('pic_webcam')){
					## HAPUS GAMBAR EXISTING ##
					if(!empty($File_Gambar) && $File_Gambar !=='-'){
						if (file_exists($path.$File_Gambar)) {
							unlink($path.$File_Gambar);
						}
					}
					
					$img 			= $this->input->post('pic_webcam');
					$image_parts    = explode(";base64,", $img);
					$image_type_aux = explode("image/", $image_parts[0]);
					$image_type     = $image_type_aux[1];              
					$image_base64   = base64_decode($image_parts[1]);

					$ext       		= 'png';
					$filename       = $Kode_User.'.'.$ext;				  
					file_put_contents($path . $filename, $image_base64);

					
				}

				$get_customer = "SELECT * FROM users WHERE userid = '$Kode_User'";
				$result_data = $this->db->query($get_customer)->row();

				$is_customer = array();
				if($result_data->group_id == '2'){
					$is_customer		= array(
						'customer'		=> $Name,
						'email'			=> $Email,
						'phone'			=> $Phone,
						'flag_active'	=> 'Y',
						'modified_date'	=> $Modified_Date,
						'modified_by'	=> $Modified_By
					);
				}

				$Ins_Detail			= array(
					'phone'				=> $Phone,
					'name'				=> $Name,
					'email'				=> $Email,
					'modified_date'		=> $Modified_Date,
					'modified_by'		=> $Modified_By
				);

				if($filename != ''){
					$Ins_Detail['image_name'] = $filename;
					$Ins_Detail['image_type'] = $ext;
				}
	
				
				$this->db->trans_begin();
				$this->db->update('users',$Ins_Detail,array('userid'=>$Kode_User));
				if($is_customer){
					$this->db->update('customers',$is_customer,array('custid'=>$Nocust));
				}
				
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit gagal. Silakan coba kembali ...'
					);
					
				}else{
					$this->db->trans_commit();
								
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses edit sukses ...'
					);
					$update = array(
						'battindo_ses_userid'        => $Kode_User,
						'battindo_ses_nocust'        => $Nocust,
						'battindo_ses_username'      => $Name,
						'battindo_ses_fullname'      => $Name,
						'battindo_ses_phone'         => $Phone,
						'battindo_ses_pic'           => $filename,
						'battindo_ses_email'	     => $Email
					); 
					
						
					$this->session->set_userdata($update);
					$this->session->set_userdata('notif_sukses', 'Akun berhasil di ubah ...');
					history('Edit Akun '.$Kode_User.' - '.$Nocust.' - '.$Phone);
				}
			}
			
			
            
        } else{
			$Arr_Return		= array(
				'status'		=> 2,
				'pesan'			=> 'Tidak ada rekaman yang diproses ...'
			); 
		}
		echo json_encode($Arr_Return);
    }


    
	/* -------------------------------------------------------------
	|  CHANGE PASSWORD
	| ---------------------------------------------------------------
	*/
	function upd_password(){
		if($this->input->post()){
			 $Modified_By	= $this->session->userdata('battindo_ses_userid');
			 $Modified_Date	= date('Y-m-d H:i:s');
			 $password		= security_hash($this->input->post('password'));
			 $Kode_User		= $this->input->post('userid');
				 
			 $Ins_Detail			= array(
				 'password'			=> $password,
				 'modified_date'	=> $Modified_Date,
				 'modified_by'		=> $Modified_By
			 );
			 
			 $this->db->trans_begin();
			 $this->db->update('users',$Ins_Detail,array('userid'=>$Kode_User));
			 if ($this->db->trans_status() !== TRUE){
				 $this->db->trans_rollback();
				 $Arr_Return		= array(
					 'status'		=> 2,
					 'pesan'		=> 'Proses edit Kata sandi gagal. Silahkan coba kembali ...'
				 );
				 
			 }else{
				 $this->db->trans_commit();
				 $Arr_Return		= array(
					 'status'		=> 1,
					 'pesan'		=> 'Proses edit kata sandi sukses...'
				 );
				 $this->session->set_userdata('notif_sukses', 'Proses edit kata sandi sukses...');
				 history('Ubah Kata Sandi Pengguna '.$Kode_User);
			 }
			 
			 echo json_encode($Arr_Return);
		}else{
			$user_id = $this->session->userdata('battindo_ses_userid');
			## PROSES TAMPILKAN FORM  ~~ SAAT LINK EMAIL DI KLIK  ##
			$this->data['title']        = 'EDIT KATA SANDI';
			$this->data['contents']     = $this->contents . 'v_change_password';

			$sql = "SELECT * FROM users WHERE userid = '$user_id'";

			$this->data['user'] = $this->db->query($sql)->row();
				
			history('Lihat Edit Kata Sandi');

			$this->load->view($this->template, $this->data);
 
		}
		

	}



	

	// --------------------------------------------------------//
						// ADD ADRESS USER //
	// -------------------------------------------------------//
	function add_address(){ 
		
		$id_contact	= Dekripsi($this->input->get('page'));
		// GET CONTACT BY ID
		 $sql_contact = "SELECT * FROM users 
							LEFT JOIN customers ON users.custid = customers.custid 
							WHERE userid = '$id_contact'
				";
		$sql_address = "SELECT * FROM customer_address_deliveries";

		$this->data['rows_add']		= $this->db->query($sql_contact)->row();

		
		$this->data['title']		= 'TAMBAH ALAMAT';
		$this->data['contents']     = $this->contents . 'v_add_delivery';
		
		$this->load->view($this->template, $this->data);
		
	}

	// --------------------------------------------------------//
					  // PROSESS ADD ADDRESS//
	// -------------------------------------------------------//
	function proses_add_address()
	{

		if($this->input->post())
		{
			$Kode_User 		= $this->input->post('userid');
			$custid 		= $this->input->post('custid');
			$address		= $this->input->post('address');
			$latitude		= $this->input->post('latitude');
			$longitude		= $this->input->post('longitude');
			$category		= $this->input->post('category');
			$notes			= $this->input->post('notes');
			$Modified_By	= $this->session->userdata('battindo_ses_userid');
			$Modified_Date	= date('Y-m-d H:i:s');
			
			$Ins_Detail			= array(
				'custid'		 => $custid,
				 'address'		 => $address,
				 'latitude'		 => $latitude,
				 'longitude'	 => $longitude,
				 'category'		 => $category,
				 'notes'		 => $notes,
				 'modified_by'	 => $Modified_By,
				 'modified_date' => $Modified_Date
			 );

			//  $get_customer = "SELECT * FROM customer_address_deliveries";
			// $result_data = $this->db->query($get_customer)->result();
			 $this->db->trans_begin();
			//  $this->db->update('customer_address_deliveries',$Ins_Detail,array('custid'=>$custid));
			$this->db->insert('customer_address_deliveries',$Ins_Detail);
			 if ($this->db->trans_status() !== TRUE){
				 $this->db->trans_rollback();
				 $Arr_Return		= array(
					 'status'		=> 2,
					 'pesan'			=> 'Proses tambah alamat pengiriman gagal. Silahkan coba kembali...'
				 );
				 
			 }else{
				 $this->db->trans_commit();
				 $Arr_Return		= array(
					 'status'		=> 1,
					 'pesan'			=> 'Proses tambah alamat pengiriman sukses...'
				 );
				 $this->session->set_userdata('notif_sukses', 'Proses tambah alamat pengiriman sukses...');
				 history(' Tambag Alamat Pengiriman Pengguna '.$custid);
			 }
			 
			 echo json_encode($Arr_Return);
		}

	}
	
	// --------------------------------------------------------//
						// GET AJAX KAMERA //
	// -------------------------------------------------------//
    function ajax_ambil_kamera(){
        (!$this->input->is_ajax_request() ? show_404() : '');
		$kategori		= $this->input->get('kategori');
		$this->data['kategori']	= $kategori;

        $this->load->view($this->ajax_contents . 'v_ajax_ambil_kamera', $this->data);
    }

    // SAVE HISTORY
    function save_histories(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $description = $this->input->post('description');

        // SAVE TO HISTORY
        history($description);

        echo json_encode(array('status' => TRUE));
    }

   
	
	/* -------------------------------------
	|  PROSES LOGOUT
	| --------------------------------------
	*/
	 function do_logout(){
		$WHR_Log = array(
                'battindo_ses_userid',
                'battindo_ses_username',
                'battindo_ses_cabang',
                'battindo_ses_isLogin',
                'battindo_ses_fullname',
                'battindo_ses_phone',
                'battindo_ses_groupid',
                'battindo_ses_groupname',
                'battindo_ses_pic', 
				'battindo_ses_email',
				'battindo_ses_nocust'
            ); 
		$this->session->unset_userdata($WHR_Log);
		$Cart_item	= array();
		if($this->session->userdata('battindo_cart_item')){
			$Cart_item	= $this->session->userdata('battindo_cart_item');
		}
		/*
		| -------------------------------------------------------------
		| CLOSE DULU SESSION DESTROY ~ AGAR SESSION CART TIDAK TERHAPUS
		| -------------------------------------------------------------
		*/
		$this->session->sess_destroy();
		if($Cart_item){
			$Ses_Cart		= array('battindo_cart_item'=>$Cart_item);
			$this->session->set_userdata($Ses_Cart);
			unset($Cart_item);
			unset($Ses_Cart);
		}
		redirect(base_url('index.php/dashboard'));
	}
	



    // --------------------------------------------------------//
						// TERM & CONDITION //
	// -------------------------------------------------------//
    function term()
    {
    	$this->data['title']        = 'Syarat & Ketentuan';
		$this->data['contents']     = $this->contents . 'v_term';		
        history('Melihat syarat dan ketentuan');
        $this->load->view($this->template, $this->data);
	}
	
	function modal_term()
	{
		$this->data['title']        = 'Syarat & Ketentuan';
		$this->data['contents']     = $this->contents . 'v_detail_term';
		history('Melihat syarat dan ketentuan');
		$this->load->view($this->template, $this->data);
	}

	/* -------------------------------------------------------------
	|  FORGOT PASSWORD KIRIM EMAIL| 
	---------------------------------------------------------------
	*/

    function forgot_account_email(){
    		## JIKA POST DATA SAAT SUBMIT##
    		if($this->input->post()){
    			$email = $this->input->post('email');

    			## CEK JIKA EMAIL EXIXTING DI DATABASE ##
    			$find_Email	= $this->db->get_where('users', array('email' => $email))->num_rows();
    			if($find_Email > 0){

    				$Date_send	= date('Y-m-d H:i:s');

    				$Expired_Date	= date('Y-m-d H:i:s',strtotime('+1 days',strtotime($Date_send)));
    				## PROSES KIRIM EMAIL FORGOT PASSWORD ##
    					
    				$Encrypt_Params	= Enkripsi($email).'/'.Enkripsi($Date_send).'/Aoihvcdefbnmnhy/KYb2bhugf0088treed1173'.$this->config->item('login_key');
					$url = base_url('index.php/contact/recovery_password_email?page=').$Encrypt_Params;

					//Load email library
					$this->load->library('email');
					//SMTP & mail configuration
					$config = array(
						'protocol'  => $this->config->item('protocol'),
						'smtp_host' => $this->config->item('host'),
						'smtp_port' => $this->config->item('port'),
						'smtp_user' => $this->config->item('user'),
						'smtp_pass' => $this->config->item('pass'),
						'mailtype'  => 'html',
						'charset'   => 'iso-8859-1',
						// 'mailpath'   => '/usr/sbin/sendmail',
						'wordwrap' => TRUE
						// 'charset'   => 'utf-8'
					);
					$this->email->initialize($config);
					// $this->email->set_mailtype("html");
					$this->email->set_newline("\r\n");

				

					$sub = $this->config->item('crm_mode');
					$msg = '<p>Hello User</p><p>There was recently a request to change the password on your account.</p>
						<p>If you requested this password change,please click the link below to set a new password within 24 hours:</p><p><a href="'.$url.'">Click here to change your password</a></p><p>If the link above does not work,paste this into your browser:</p>'.$url."<br><br><p>If you dont't want to change your password,just ignore this message.</p><br>
						<p>Thank you.</p><br>
						<p>The</p>".$this->config->item('crm_mode');
					$from = $this->config->item('from');

					$this->email->to($email);
					$this->email->from($from, $this->config->item('crm_mode'));
					$this->email->subject($sub);
					$this->email->message($msg);

					//Send email
					$Hasil_kirim = $this->email->send();
						if($Hasil_kirim == FALSE){
							$rows_Return	= array(
		    					'result'		=> 2,
		    					'pesan'			=>'Kirim email gagal, Silahkan coba kembali...'
		    				);
						}else{
							## INSERT KE DATABASE ##
							$data = array(
								'email' => $email,
								'link_email' => $url,
								'send_date' => $Date_send,
								'expired_date' => $Expired_Date,
								'email_type' => 'LUPA KATA SANDI'
							);

							$Rest_Insert = $this->db->insert('log_emails',$data);
							

							$rows_Return	= array(
		    					'result'		=> 1,
		    					'pesan'			=>'Email telah terkirim. Silakan periksa email Anda untuk memperbarui kata sandi...'
		    				);
		    				$this->session->set_userdata('notif_sukses', 'Email telah terkirim. Silakan periksa email Anda untuk memperbarui kata sandi');
		    				history('Kirim Email Lupa Kata Sandi '.$email);
						}


    			}else{
    				## EMAIL TIDAK ADA DI SISTEM ##
    				$rows_Return	= array(
    					'result'		=> 2,
    					'pesan'			=>'Email tidak valid. Silahkan coba kembali...'
    				);
    			}
    			echo json_encode($rows_Return);
    		}else{

    			## LOADING PAGE SAAT DI KLIK FORGOT PASSWORD ##
    			$this->data['title']        = 'LUPA KATA SANDI';
		        $this->data['contents']     = $this->contents . 'v_forgot_account_email';
				
		        history('lihat Form Lupa Kata Sandi');

		        $this->load->view($this->template, $this->data);
    		}
 
    }

	
	/* -------------------------------------------------------------
	|  PROSES UPDATE PASSWORD BARU DARI LINK EMAIL					| 
	   -------------------------------------------------------------
	*/
    function recovery_password_email()
    {
		if($this->input->post()){
			$Modified_By	= $this->session->userdata('battindo_ses_userid');
			 $Modified_Date	= date('Y-m-d H:i:s');
			 $Password		= security_hash($this->input->post('new_password'));
			 $Kode_User		= $this->input->post('userid');
				 
			 $Ins_Detail			= array(
				 'password'			=> $Password,
				 'modified_date'		=> $Modified_Date,
				 'modified_by'		=> $Modified_By
			 );
			 
			 $this->db->trans_begin();
			 $this->db->update('users',$Ins_Detail,array('userid'=>$Kode_User));
			 if ($this->db->trans_status() !== TRUE){
				 $this->db->trans_rollback();
				 $Arr_Return		= array(
					 'status'		=> 2,
					 'pesan'			=> 'Proses edit kata sandi gagal. Silahkan coba kembali...'
				 );
				 
			 }else{
				 $this->db->trans_commit();
				 $Arr_Return		= array(
					 'status'		=> 1,
					 'pesan'			=> 'Proses edit kata sandi sukses...'
				 );
				 $this->session->set_userdata('notif_sukses', 'Proses edit kata sandi sukses...');
				 history('Edir Kata Sandi Pengguna '.$Kode_User);
			 }
			 
			 echo json_encode($Arr_Return);
		}else{
			## PROSES TAMPILKAN FORM  ~~ SAAT LINK EMAIL DI KLIK  ##
			$page_email 	= $_GET['page'];
			$Pecah_Kode		= explode('/',$page_email);
			$email 			= Dekripsi($Pecah_Kode[0]);
			$send_date 		= Dekripsi($Pecah_Kode[1]);
			$sql_contact 	= "SELECT * FROM log_emails WHERE send_date='$send_date'";
			$rows_email   	= $this->db->query($sql_contact)->row();
			$date_new 		= date('Y-m-d H:i:s');
			if($date_new >= $rows_email->expired_date){
			
				$this->session->set_userdata('notif_gagal','Link telah kadaluarsa. Silahkan input konfirmasi lupa kata sandi...');
				redirect('/contact/forgot_account_email');
			}else{
				$this->data['title']        = 'PEMULIHAN KATA SANDI';
				$this->data['contents']     = $this->contents . 'v_upd_password_email';

				$sql = "SELECT * FROM users WHERE email = '$email'";

				$this->data['user'] = $this->db->query($sql)->row();
				
				history('Lihat Pemulihan Kata Sandi');

				$this->load->view($this->template, $this->data);

			}
 
		}
	}

	/* -------------------------------------------------------------
	|  FORGOT PASSWORD SEND SMS PHONE					| 
	   -------------------------------------------------------------
	*/
	public function forgot_account_phone()
	{

		if($this->input->post()){
			$phone 	= $this->input->post('phone');
			$phone	= str_replace(array('+',' '),'',$phone);
			## CEK JIKA PHONE EXIXTING DI DATABASE ##
			$find_Phone	= $this->db->get_where('users', array('phone' => $phone))->num_rows();
			
			if($find_Phone > 0){
				
				$Date_send		= date('Y-m-d H:i:s');
				$Date_Encrypt	= date('HidmYs');

				$Expired_Date	= date('Y-m-d H:i:s',strtotime('+1 days',strtotime($Date_send)));
				
					
				$Encrypt_Params	= Enkripsi($phone).'/'.Enkripsi($Date_send).'/'.Enkripsi($Expired_Date).'/Aoihvcdefbnmnhy/KYb2bhugf0088treed1173'.$this->config->item('login_key').Enkripsi($Date_Encrypt);
				$Link_URL 		= base_url('index.php/contact/recovery_password_phone?page=').$Encrypt_Params;
				
				
				$Pesan_WhatsApp		= "\n *Hello User*\n\nThere was recently a request to change the password on your account.\nIf you requested this password change,please click the link below to set a new password within 24 hours:\n ".$Link_URL."\n\n If you dont want to change your password,just ignore this message.\n\nThank you.\n\n*The ".$this->config->item('crm_mode')."*\n _This WA message automatically generated from system_";
				$Link_Config	= base_url()."whatsapp/kirim_text.php/?no_hp=".$phone."&pass=B4tt1nd0zxcvbnm&pesan=".urlencode($Pesan_WhatsApp)."&kategori=".urlencode('forgot password');
				$Kirim_Pesan 	= file_get_contents($Link_Config);
				$Result_Masking	= json_decode($Kirim_Pesan);
				
				$hasil_WA		= $Result_Masking->result;
				$status_WA		= $Result_Masking->message;
				
				if($hasil_WA == '1'){
					$phone_Return	= array(
						'result'		=> 1,
						'pesan'			=>'Pesan telah dikirim ke whatsapp Anda, silakan periksa whatsapp ...'
					);
					$this->session->set_userdata('notif_sukses', 'Pesan telah dikirim ke whatsapp Anda, silakan periksa whatsapp ...');
		    		history('Kirim Whatsapp Lupa Kata Sandi  '.$phone);
				}else{
					$phone_Return	= array(
						'result'		=> 2,
						'pesan'			=>'Send Pesan gagal. Silahkan coba kembali...'
					);
				}
				
			} else{
				## PHONE TIDAK ADA DI SISTEM ##
				$phone_Return	= array(
					'result'		=> 2,
					'pesan'			=>'Invalid phone...'
				);
			}
			echo json_encode($phone_Return);

		}else{
			## LOADING PAGE SAAT DI KLIK FORGOT PASSWORD ##
			$this->data['title']        = 'LUPA KATA SANDI';
			$this->data['contents']     = $this->contents . 'v_forgot_account_phone';
			
			history('Liat Lupa Kata Sandi');

			$this->load->view($this->template, $this->data);
		}
	}

	
	// --------------------------------------------------------//
				   // RECOVERY PASSWORD PHONE //
	// -------------------------------------------------------//
	function recovery_password_phone(){

		if($this->input->post()){
			$Modified_By	= $this->session->userdata('battindo_ses_userid');
			 $Modified_Date	= date('Y-m-d H:i:s');
			 $Password		= security_hash($this->input->post('new_password'));
			 $Kode_User		= $this->input->post('userid');
				 
			 $Ins_Detail			= array(
				 'password'			=> $Password,
				 'modified_date'		=> $Modified_Date,
				 'modified_by'		=> $Modified_By
			 );
			 
			 $this->db->trans_begin();
			 $this->db->update('users',$Ins_Detail,array('userid'=>$Kode_User));
			 if ($this->db->trans_status() !== TRUE){
				 $this->db->trans_rollback();
				 $Arr_Return		= array(
					 'status'		=> 2,
					 'pesan'			=> 'Proses Edit kata sandi gagal. Silahkan coba kembali...'
				 );
				 
			 }else{
				 $this->db->trans_commit();
				 $Arr_Return		= array(
					 'status'		=> 1,
					 'pesan'		=> 'Proses Edit kata sandi sukses...'
				 );
				 $this->session->set_userdata('notif_sukses', 'Proses Edit kata sandi sukses...');
				 history('Edit Kata Sandi Pengguna '.$Kode_User);
			 }
			 
			 echo json_encode($Arr_Return);
		}else{
			## PROSES TAMPILKAN FORM  ~~ SAAT LINK EMAIL DI KLIK  ##
			$page_phone 	= $_GET['page'];

			
			$Pecah_Kode		= explode('/',$page_phone);
			$phone 			= Dekripsi($Pecah_Kode[0]);			
			$send_date		= Dekripsi($Pecah_Kode[1]);
			$Tgl_Cari		= date('Y-m-d H:i',strtotime($send_date));
			$Expired_date	= Dekripsi($Pecah_Kode[2]);
			$sql_contact 	= "SELECT * FROM log_sms WHERE hp='$phone' AND DATE_FORMAT(waktukirim,'%Y-%m-%d %H:%i') = '".$Tgl_Cari."' AND jenis_sms= 'forgot password'";
			$rows_phone   	= $this->db->query($sql_contact)->num_rows();
			$date_new 		= date('Y-m-d H:i:s');
			if ($rows_phone == '' && $rows_phone == null){
				$this->session->set_userdata('notif_gagal','Link sudah tidak aktif. Silahkan input konfirmasi lupa kata sandi...');
				redirect('/contact/forgot_account_phone');
			}else{
				
				if($date_new >= $Expired_date){
			
					$this->session->set_userdata('notif_gagal','Link sudah kadaluarsa. Silahkan input konfirmasi lupa kata sandi...');
					redirect('/contact/forgot_account_phone');
				}else{
					$this->data['title']        = 'PEMULIHAN KATA SANDI';
					$this->data['contents']     = $this->contents . 'v_upd_password_phone';
	
					$sql = "SELECT * FROM users WHERE phone = '$phone'";
	
					$this->data['user'] = $this->db->query($sql)->row();
					
					history('Lihat Pemulihan Kata Sandi');
	
					$this->load->view($this->template, $this->data);
				}	


			}
			
 
		}
	}
}