<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Orders extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('m_master');
		$this->load->model('m_contact');
		$this->load->library('encrypt');
		$this->contents      = 'orders/';
		$this->ajax_contents = 'contents/' . $this->contents;
		$this->template      = 'layouts/v_backoffice';
		$this->data          = array();
		$this->limit         = 25;
		$controller			 = ucfirst(strtolower($this->uri->segment(1)));
		$this->arr_Akses	= array();
		if ($this->session->userdata('battindo_ses_isLogin')) {
			$this->arr_Akses	= $this->m_master->check_menu($controller);
		}

		
		header("Access-Control-Allow-Origin: *");

        header("Access-Control-Allow-Methods: PUT, GET, POST");

        header("Access-Control-Allow-Headers: Origin, X-Request-With, Content-Type, Accept");

		

		$params = array('server_key' => config_item('midtrans_server_key'), 'production' => false);

        $this->load->library('midtrans');

        $this->midtrans->config($params);

        $this->load->helper('url');
	}

	// -------------------------------------------- //
	/*					  INDEX 				    */
	// -------------------------------------------- //
	function index(){
		$rows_data	= array();
		if ($this->session->userdata('battindo_ses_isLogin')) {
			if ($this->session->userdata('battindo_ses_groupid') == 1){
				$rows_data	= $this->m_master->get_all_list('trans_order','*','created_date DESC',"",$this->limit);
			}else{
				$Nocust 	= $this->session->userdata('battindo_ses_nocust');
				$rows_data	= $this->m_master->get_all_list('trans_order','*','created_date DESC',"custid='".$Nocust."'",$this->limit);				
			}
		}
		$this->data['title']        	= 'PESANAN';
		$this->data['contents']     	= $this->contents . 'v_order';
		$this->data['rows_data']        = $rows_data;
		$this->data['rows_akses']       = $this->arr_Akses;
		$this->data['offset']        	= 0;
		history('Melihat pesanan');
		$this->load->view($this->template, $this->data);
	} 

	// -------------------------------------------- //
	/*  				LOAD MORE				    */
	// -------------------------------------------- //
	function load_more(){
		(!$this->input->is_ajax_request() ? show_404() : '');

		
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
		$response   = array();
		$limit      = $this->limit;
		$offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );	
		$rows_data	= array();

		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari customer berdasarkan  "' . $search . '"');
			$Search_By	="customer LIKE '%".$search."%'";
		}
		
		if ($this->session->userdata('battindo_ses_isLogin')) {
			if ($this->session->userdata('battindo_ses_groupid') == 1){
				$rows_data	= $this->m_master->get_all_list('trans_order','*','created_date DESC',"",$limit,$offset,$Search_By);
			}else{
				$Nocust 	= $this->session->userdata('battindo_ses_nocust');
				$rows_data	= $this->m_master->get_all_list('trans_order','*','created_date DESC',"custid='".$Nocust."'",$limit,$offset,$Search_By);				
			}
		}
		if($rows_data){ // IF DATA EXIST
			$this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $rows_data;
			$this->data['akses_menu']	= $this->arr_Akses;
			$response['content']        = $this->load->view('contents/orders/v_more',  $this->data, TRUE);
			$response['status']         = TRUE;
		} else {
			$response['status']         = FALSE;
		}  
		echo json_encode($response);    
	}

	//--------------------------------------------------//
	/*                 		DETAIL                      */
	//--------------------------------------------------//
	function detail($kode_order=''){
		$rows_Header        		= $this->db->get_where('trans_order', array('order_id' => $kode_order))->result();
		$this->data['rows_detail']  = $this->db->get_where('trans_order_detail', array('order_id' => $kode_order))->result();
		$this->data['rows_header']  = $rows_Header;
		$this->data['title']        = 'DETAIL PESANAN';
		$this->data['contents']     = $this->contents . 'v_detail';
		$this->data['rows_akses']   = $this->arr_Akses;
		$this->load->view($this->template, $this->data);
	}
	
	// -------------------------------------------- //
	/*  		CANCEL BOOKING SERVICE  		    */
	// -------------------------------------------- //
	function cancel_order(){
		$Arr_Return	= array();
		if($this->input->post()){
			$Cancel_By		= $this->session->userdata('battindo_ses_userid');
			$Cancel_Date	= date('Y-m-d H:i:s');
			$Code_Book		= $this->input->post('kode_booking');
			$Reason			= ucwords(strtolower($this->input->post('reason')));
			$Find_Status	= $this->db->get_where('trans_order',array('order_id'=>$Code_Book))->result();
			$OK_Cancel		= 0;
			
			if($Find_Status[0]->sts_order == 'OPN'){
				$OK_Cancel		= 1;
			}else if($Find_Status[0]->sts_order == 'PAID'){
				if($Find_Status[0]->merchant_type == 'MIDTRANS'){
					$OK_Cancel		= 1;
				}						
			}else if($Find_Status[0]->sts_order == 'PEND'){
				$OK_Cancel		= 1;
			}
			
			if($OK_Cancel !== 1){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Data telah dimodifikasi oleh proses lain ...'
				);
			}else{
				$Upd_Proses		= array(
					'cancel_by'		=> $Cancel_By,
					'cancel_date'	=> $Cancel_Date,
					'cancel_reason'	=> $Reason,
					'sts_order'		=> 'CNC',
					'flag_cancel'	=> 'Y'
				);
				
				$this->db->trans_begin();
				$this->db->update('trans_order',$Upd_Proses,array('order_id'=>$Code_Book));
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Pembatalan pesanan gagal, Silahkan coba kembali ...'
					);
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Pembatalan pesanan sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Pembatalan pesanan sukses...');
					history('Pembatalan Pesanan '.$Code_Book);
				}
			}
			
		}else{
			$Arr_Return		= array(
				'status'		=> 2,
				'pesan'			=> 'Tidak ada Rekaman yang ditemukan untuk diproses...'
			);
		}
		
		echo json_encode($Arr_Return);
	}


	function pilih_cust(){
			if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('dashboard');
            }

			$this->data['rows_cust']	= $this->m_master->get_all_list('customers','*','custid ASC',"",$this->limit);
			$this->data['rows_cat']		= $this->m_master->get_all_list('kategori','*','kode ASC',"",$this->limit);
			$this->data['title']        = 'PILIH CUSTOMER';
			$this->data['contents']     = $this->contents . 'v_pilih_cust';
			$this->load->view($this->template, $this->data);
	}


	function load_more_product(){
		// echo"<pre>";print_r($this->input->post());exit;
		
		$response   = array();
		$offset		= 0;
        $limit      = $this->limit;
        $id_cat     = $this->input->post('id_cat');
		
		
		$query = "SELECT *
					FROM barang INNER JOIN barang_stok
					ON barang.kode_barang = barang_stok.kode_barang WHERE barang.kode_kategori = '$id_cat'";
		$data	= $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_product'] = $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $response['content']    	= $this->load->view('contents/orders/v_more_product',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);   
	}




	/*
    | -------------------------------------------------------------------
    | INSERT CART ITEM KE SESSION
    | -------------------------------------------------------------------
    */
	function add_product_cart_item(){
		
		if($this->input->post()){
			// echo"<pre>";print_r($this->input->post());exit;
			$Kode_Barang 	= $this->input->post('kode_barang');
			$Qty_Barang 	= $this->input->post('qty');
			
			if(!empty($this->session->userdata('battindo_add_order_item'))){
				$Data_Chart	= $this->session->userdata('battindo_add_order_item');
				
				if(!empty($Data_Chart[$Kode_Barang])){
					## REMOVE SESSION ##
					unset($Data_Chart[$Kode_Barang]);
					
					
					if($Qty_Barang > 0){						
						$Data_Chart[$Kode_Barang]	= array('qty'=>$Qty_Barang);
					}
				}else{
					$Data_Chart		= $this->session->userdata('battindo_add_order_item');
					$Data_Chart[$Kode_Barang]	= array('qty'=>$Qty_Barang);
				}
				$this->session->set_userdata('battindo_add_order_item',$Data_Chart);
			}else{
				if($Qty_Barang > 0){
					
					$Arr_Cart	= array(
						'battindo_add_order_item'	=> array(
							$Kode_Barang	=> array(
								'qty'	=> $Qty_Barang
							)
						)
					);
					$this->session->set_userdata($Arr_Cart);
				}
			}
			
		}
		//echo"<pre>";print_r($this->session->userdata('battindo_add_order_item'));
		## AMBIL JUMLAH ITEM ##
		$Jumlah_Cart	= count_cust_cart();
		$Arr_Return		= array('jumlah'=>$Jumlah_Cart);
		echo json_encode($Arr_Return);  
		
		
	}
	
	function v_add_order($custid){
		if($this->arr_Akses['create'] != '1'){
			$this->session->set_flashdata('no_akses', true);
			redirect('dashboard');
		}

			$Custid						 = $custid;
			$rows_Cust					 = $this->db->get_where('customers',array('custid'=>$Custid))->result();
			$this->data['rows_barang']   = $this->m_master->getArray('barang_stok','','kode_barang','nama_barang');
			$rows_Cust_Addr				 = $this->db->get_where('customer_address_deliveries',array('custid'=>$Custid))->result();
			$this->data['title']         = 'TAMBAH PESANAN';
			$this->data['contents']      = $this->contents . 'v_add';	
			$this->data['rows_cust']     = $rows_Cust;
			$this->data['rows_address']  = $rows_Cust_Addr;
			$this->load->view($this->template, $this->data);
	}

	function proses_add_order(){
		

		if($this->input->post()){
			// echo"<pre>";print_r($this->input->post());exit;

			## CHECK APAKAH CART MASIH ADA ATAU KOSONG ##
			if($this->session->userdata('battindo_add_order_item')){
				$Customer		= $this->input->post('customer');
				$Nocust			= $this->input->post('custid');
				$Delivery_Add	= $this->input->post('delivery_address');
				$Latitude		= $this->input->post('latitude');
				$Longitude		= $this->input->post('longitude');
				$Notes			= $this->input->post('notes');
				$Code_Voucher	= $this->input->post('kode_voucher');
				$Payment_Type	= strtoupper($this->input->post('payment_methode'));
				$detail_Cart	= $this->input->post('detCheckout');
				$DPP			= $this->input->post('dpp');
				$Discount		= $this->input->post('discount');
				$Voucher_Val	= $this->input->post('voucher_nil');				
				$DPP_After		= $DPP - $Discount - $Voucher_Val;

				## NEED CONFIRMATION ##
				$PPN			= 0;
				$Exc_PPN		= 1;
				$Grand_Tot		= $this->input->post('grandtot');
				$rows_Cust		= $this->db->get_where('customers',array('custid'=>$Nocust))->result();
				$Datet			= date('Y-m-d');
				$Order_Code		= 'ORD-'.date('HismdY').sprintf('%03d',rand(1,100));
				$Created_By		= $this->session->userdata('battindo_ses_userid');
				$Created_Date	= date('Y-m-d H:i:s'); 

				$Ins_Header		= array(
					'order_id'			=> $Order_Code,
					'datet'				=> $Datet,
					'custid'			=> $Nocust,
					'customer'			=> $Customer,
					'dpp'				=> $DPP_After,
					'discount'			=> $Discount,
					'voucher'			=> $Voucher_Val,
					'dpp_after'			=> $DPP_After,
					'ppn'				=> $PPN,
					'ongkir'			=> 0,
					'total'				=> $Grand_Tot,
					'kode_voucher'		=> $Code_Voucher,
					'sts_order'			=> 'OPN',
					'exc_ppn'			=> $Exc_PPN,
					'delivery_address'	=> $Delivery_Add,
					'latitude'			=> $Latitude,
					'longitude'			=> $Longitude,
					'descr'				=> $Notes,
					'merchant_type'		=> $Payment_Type,
					'created_by'		=> $Created_By,
					'created_date'		=> $Created_Date
				);

				$Ins_Detail		= array();
				
				if($detail_Cart){
					$intP		= 0;
					foreach($detail_Cart as $keyP=>$valP){
						$intP++;
						$Item_Loop		= $Order_Code.'-'.$intP;
						$Item_Code		= $valP['kode_barang'];
						$Item_Price		= $valP['harga'];
						$Item_Qty		= $valP['qty'];
						$Item_Total		= $valP['total'];
						$Item_type		= $valP['type_discount_item'];
						$Item_disc_tot	= $valP['Discount_total'];
						$Item_Disc		= preg_replace('/[Rp. ]/','',$valP['Discount_item']);
						$Item_Name		= '';

						
						## AMBIL DATA BARANG ##

						$rows_Barang	= $this->db->get_where('barang',array('kode_barang'=>$Item_Code))->result();
						if($rows_Barang){
							$Item_Name	= $rows_Barang[0]->nama_barang;
						}


						$Ins_Detail[$intP]	= array(
							'id'			=> $Item_Loop,
							'order_id'		=> $Order_Code,
							'kode_barang'	=> $Item_Code,
							'nama_barang'	=> $Item_Name,
							'qty'			=> $Item_Qty,
							'qty_supply'	=> 0,
							'qty_sisa'		=> $Item_Qty,
							'harga_jual'	=> $Item_Price,
							'disc_type'		=> $Item_type,
							'discount'		=> $Item_Disc,
							'total_discount'=> $Item_disc_tot,
							'total'			=> $Item_Total - $Item_disc_tot
						);
					}

					
					unset($detail_Cart);

				}

				
				$Ins_Progress	= array(
					'order_id'		=> $Order_Code,
					'sts_process'	=> 'OPN',
					'process_date'	=> $Created_Date,
					'process_by'	=> $Created_By
				); 

				
				## PROSES SAVE ##

				$this->db->trans_begin();
				$this->db->insert('trans_order',$Ins_Header);
				$this->db->insert_batch('trans_order_detail',$Ins_Detail);
				$this->db->insert('log_order_progress',$Ins_Progress);

				if(!empty($Code_Voucher) && strtolower($Payment_Type) == 'cod'){
					$Query_Update	= "UPDATE vouchers SET jumlah_use = jumlah_use + 1 WHERE kode_voucher = '".$Code_Voucher."'";
					$this->db->query($Query_Update);

					## INSERT KE LOG VOUCHER ##
					$Ins_Log		= array(
						'kode_voucher'	=> $Code_Voucher,
						'custid'		=> $Nocust,
						'datet'			=> $Created_Date,
						'order_id'		=> $Order_Code
					);

					$this->db->insert('log_voucher_used',$Ins_Log);
				}

				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'			=> 2,
						'pesan'			=> 'Proses pesanan gagal. silahkan coba lagi...'
					);

				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'			=> 1,
						'pesan'			=> 'Proses pesanan berhasil...',
						'tipe'				=> strtolower($Payment_Type),
						'order_id'			=> Enkripsi($Order_Code)
					);

					$this->session->unset_userdata('battindo_add_order_item');
					if(strtolower($Payment_Type) =='cod'){

						## KIRIM WA ## 
						$Pesan_WhatsApp		= "\n *Dear our beloved customer*\n\nThank you for ordering in our shop. your order number is *_".$Order_Code."_*. We will contact you immediately to confirm the order.\n\n Have a nice day.\n\n*The ".$this->config->item('crm_mode')."*\n _This WA message automatically generated from system_";
						$Link_Config	= base_url()."whatsapp/kirim_text.php/?no_hp=".$rows_Cust[0]->phone."&pass=B4tt1nd0zxcvbnm&pesan=".urlencode($Pesan_WhatsApp)."&kategori=".urlencode('order');
						$Kirim_Pesan 	= file_get_contents($Link_Config);

					 }					 
					 history('Tambah pesanan '.$Order_Code);
				}

			} else{
				$Arr_Return		= array(

					'status'			=> 2,
					'pesan'			=> 'Keranjang belanja telah dihapus, pilih produk terlebih dahulu....'

				);

				
			}
			
		}else{
			
			$Arr_Return		= array(
				'status'			=> 2,
				'pesan'			=> 'Tidak ada catatan yang ditemukan..'
			);
			
		}

		echo json_encode($Arr_Return);
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
					 'pesan'			=> 'Proses tambah alamat pengiriman gagal. silahkan coba lagi...'
				 );

			 }else{

				 $this->db->trans_commit();
				 $Arr_Return		= array(
					 'status'		=> 1,
					 'pesan'			=> 'Proses tambah alamat pengiriman sukses...'
				 );

				 $this->session->set_userdata('notif_sukses', 'Proses tambah alamat pengiriman sukses...');
				 history(' Tambah alamat pengiriman '.$custid);

			 }

		}else{
			 $Arr_Return		= array(
				 'status'			=> 2,
				 'pesan'			=> 'Tidak ada data yang tersimpan...'
			 );
		}

		echo json_encode($Arr_Return);



	} 

	
	
	/*

    | -------------------------------------------------------------------

    | VALIDASI VOUCHER

    | -------------------------------------------------------------------

    */

	function validasi_voucher(){
		if($this->input->post())
		{

			$Kode_Voucher	= strtoupper($this->input->post('voucher'));
			$Jumlah_DPP		= floatval($this->input->post('dpp'));
			$Custid			= $this->input->post('nocust');
			$Datet			= date('Y-m-d');
			$Qry_Voucher	= "SELECT * FROM vouchers WHERE kode_voucher = '".$Kode_Voucher."' AND flag_active ='Y'";
			$det_Voucher	= $this->db->query($Qry_Voucher)->result();

			if($det_Voucher){
			
				$Tgl_Valid		= $det_Voucher[0]->valid_until;
				$Tipe_Voucher	= $det_Voucher[0]->type_voucher;
				$Nilai_Voucher	= $det_Voucher[0]->nilai_voucher;
				$Kuota_Voucher	= $det_Voucher[0]->jumlah_voucher;
				$Use_Voucher	= $det_Voucher[0]->jumlah_use;
				$Min_Voucher	= $det_Voucher[0]->min_nilai;
			
				if($Tgl_Valid < $Datet){
			
					$Arr_Return		= array(
						 'status'			=> 2,
						 'pesan'			=> 'Voucher telah kedaluwarsa...'
						);
				
				}else{

					$OK_Voucher	= 1;
					$Pesan_Vaucher	= '';

					if($Kuota_Voucher > 0){

						$Jumlah_Use		= $Use_Voucher + 1;

						if($Kuota_Voucher <= $Jumlah_Use){
							$OK_Voucher		= 0;
							$Pesan_Vaucher	= 'Penggunaan voucher melebihi kuota....';
						}

					}
					
					if($Min_Voucher > 0){

						if($Jumlah_DPP < $Min_Voucher){

							$OK_Voucher		= 0;
							$Pesan_Vaucher	= 'Nilai total kurang dari batas penggunaan voucher minimum';

						}
					}
					
					if($OK_Voucher == 1){

						 $Arr_Return		= array(
							 'status'			=> 1,
							 'tipe'				=> $Tipe_Voucher,
							 'nilai'			=> $Nilai_Voucher
						 );

					}else{

						 $Arr_Return		= array(
							 'status'			=> 2,
							 'pesan'			=> $Pesan_Vaucher
						 );

					}
				}

			}else{
				
				 $Arr_Return		= array(
					 'status'			=> 2,
					 'pesan'			=> 'Kode voucher tidak valid...'
				 );

			}

		}else{

			 $Arr_Return		= array(
				 'status'			=> 2,
				 'pesan'			=> 'Tidak ada data yang tersimpan..'
			 );

		}

		echo json_encode($Arr_Return);

	}

	/* 
	| ----------------------------------
	|	JSON DETAIL BARANG ~ TEGUH 2020-10-26
	| ----------------------------------
	*/
	function ajax_detail_bundle(){
		$Kode_Barang	= $this->input->post('kode_barang');
		$Query_Product	= "SELECT * FROM barang_stok WHERE kode_barang = '".$Kode_Barang."' LIMIT 1";
		$result_product	= $this->db->query($Query_Product)->result();
		$rows_return	= array();
		if($result_product){
			$Product_Name	= $result_product[0]->nama_barang;			
			$harga			= $result_product[0]->harga_jual;
			$rows_return	= array(
				'nama' => $Product_Name,
				'harga'	=> $harga
			);
		}
		echo json_encode($rows_return);
	}



	function checkout_midtrans(){

		if($this->input->get('processid')){
			$Kode_Order		= Dekripsi($this->input->get('processid'));
			$rows_Order		= $this->db->get_where('trans_order',array('order_id' => $Kode_Order))->result();
			$rows_Detail	= $this->db->get_where('trans_order_detail',array('order_id' => $Kode_Order))->result();
			$rows_Cust		= $this->db->get_where('customers',array('custid'=>$rows_Order[0]->custid))->result();

			if($rows_Order[0]->sts_order == 'OPN'){		
				$Trans_Header	= $Trans_Item	= $Trans_User = array();

				if($rows_Detail){
					
					$intP =0;
					foreach($rows_Detail as $key=>$valD){

						$intP++;
						$Harga_Nett		= $valD->harga_jual;
						$row_Item    = array(
							'id'        => $valD->id,
							'price'     => floatval($Harga_Nett),
							'quantity'  => floatval($valD->qty),
							'name'      => substr($valD->nama_barang,0,40) 
						);

						$Trans_Item[]	= $row_Item;
					}

					if($rows_Order[0]->discount > 0){

						$intP++;
						$row_Item    = array(
							'id'        => $Kode_Order.'-'.$intP,
							'price'     => floatval($rows_Order[0]->discount * -1),
							'quantity'  => 1,
							'name'      => 'DISCOUNT'
						);

						$Trans_Item[]	= $row_Item;
					}

					

					if($rows_Order[0]->voucher > 0){

						$intP++;
						$row_Item    = array(
							'id'        => $Kode_Order.'-'.$intP,
							'price'     => floatval($rows_Order[0]->voucher * -1),
							'quantity'  => 1,
							'name'      => 'DISC VOUCHER'

						);

						$Trans_Item[]	= $row_Item;
					}

				}

				//echo"<pre>";print_r($Trans_Item);exit;

				$Trans_Header    = array(
					'order_id'            => $Kode_Order,
					'gross_amount'        => floatval($rows_Order[0]->total)

				);

				$Trans_User        = array(
					'first_name'    => $rows_Cust[0]->customer,
					'last_name'     => '-',
					'email'         => $rows_Cust[0]->email,
					'phone'         => $rows_Cust[0]->phone

				); 

				

				$rows_Token            = $this->midtrans_token($Trans_Header, $Trans_User, $Trans_Item);

				if($rows_Token){
					$res_Update			= $this->db->update('trans_order',array('merchant_code'=>$rows_Token),array('order_id'=>$Kode_Order));
				}

				$this->data['title']        	= 'PEMBAYARAN';
				$this->data['rows_header']      = $rows_Order;
				$this->data['rows_detail']      = $rows_Detail;
				$this->data['rows_token']      	= $rows_Token; 
				$this->data['contents']     	= $this->contents . 'v_cart_payment';	 

				//echo"<pre>";print_r($this->data);exit;

				$this->load->view($this->template, $this->data);

			}else{

				$this->session->set_userdata('notif_gagal', 'Kode proses tidak ditemukan.....');
				redirect('orders');
			}

		}else{

			$this->session->set_userdata('notif_gagal', 'Kode proses tidak ditemukan.....');
			redirect('orders');

		}

	}


	public function midtrans_token($rows_Transaction = array(), $rows_User = array(), $rows_Items = array()){

        ## ALLOW  PAYMENT PROSES ~~ CUSTOM ##
        $enable_payments = array(
            "credit_card",
            "mandiri_clickpay",
            "cimb_clicks",
            "bca_klikbca",
            "bca_klikpay",
            "bri_epay",
            "echannel",
            "permata_va",
            "bca_va",
            "bni_va",
            "other_va",
            "gopay",
            "indomaret",
            "alfamart",
            "danamon_online",
            "akulaku"
        );



        ## EXPIRED TOKEN ~~ CUSTOM ~~ DEFAULT 24 JAM ##
        $custom_expiry    = array();

        /*

		$time = time();

        $custom_expiry = array(

            'start_time' => date("Y-m-d H:i:s O", $time),

            'unit' => 'minute',

            'duration'  => 99999

        );

        */



        $transaction_data = array(
            'transaction_details'     => $rows_Transaction
        );

        if ($rows_User) {
            $transaction_data['customer_details']    = $rows_User;
        }



        if ($rows_Items) {
            $transaction_data['item_details']    = $rows_Items;
        }

        if ($enable_payments) {
            $transaction_data['enabled_payments']    = $enable_payments;
        }

        if ($custom_expiry) {
            $transaction_data['expiry']    = $custom_expiry;
        }

		//echo"<pre>";print_r($transaction_data);exit;

        //error_log(json_encode($transaction_data));

        $snapToken = $this->midtrans->getSnapToken($transaction_data);

        // error_log($snapToken);

        return $snapToken;

	}
	


	

    function midtrans_status($kode_order = ''){ 

        $rows_Header        		= $this->db->get_where('trans_order', array('order_id' => $kode_order))->result();
        $data['rows_detail']       	= $this->db->get_where('trans_order_detail', array('order_id' => $kode_order))->result();
        $data['title']    			= "Status Pembayaran";
        $data['rows_header']    	= $rows_Header;
        // echo "<pre> masuk bro";
        // print_r($rows_Header);
        // unset($rows_Header);
        $data['contents']     	= $this->contents . 'v_status_payment';	
		$this->load->view($this->template, $data);

	}
	
	

    function payment_notif(){
        $Response_proses    = $this->input->post('response');
        $Result_Pros        = json_decode($Response_proses);
		$Kode_Order         = $this->midtrans_update_status($Result_Pros);
        redirect('orders/midtrans_status/' . $Kode_Order, 'refresh');

        /*

		echo"<pre>";print_r($_POST['response']);

		echo"<pre>";print_r($Result_Pros);

		exit;

        */

	}
	



	function midtrans_update_status($Result_Pros){
        $Kode_Order        	= $Result_Pros->order_id;
        $Kode_Token        	= $Result_Pros->transaction_id;
        $trans_status    	= $Result_Pros->transaction_status;
        $trans_code        	= $Result_Pros->status_code;
        $trans_date        	= $Result_Pros->transaction_time;
        $fraud_status    	= $Result_Pros->fraud_status;
        $payment_type    	= $Result_Pros->payment_type;
        //$payment_date		= $Result_Pros->settlement_time;
        $sts_message    	= $Result_Pros->status_message;
        $sts_Bayar        	= $this->payment_category($trans_status);
        $payment_bank    	= $payment_va = $fraud_status = '-';

        $ins_Update        = array(
            'sts_order'        => $sts_Bayar,
            'merchant_code'    => $Kode_Token,
            'trans_status'     => $trans_status,
            'trans_date'       => $trans_date,
            'trans_code'       => $trans_code,
            'trans_message'    => json_encode($Result_Pros),
            'payment_type'     => $payment_type
        );
		
        if ($payment_type == 'credit_card') {
            $payment_bank                = $Result_Pros->bank;
            $fraud_status                = $Result_Pros->fraud_status;

            if (strtolower($trans_status) == 'capture' && strtolower($fraud_status) == 'accept') {
                $sts_Bayar        			= 'PAID';
				$ins_Update['flag_paid']    = 'Y';
            }

            $ins_Update['sts_order']    = $sts_Bayar;

        } else if ($payment_type == 'bank_transfer') {

			$fraud_status			= $Result_Pros->fraud_status;
			
            if (isset($Result_Pros->permata_va_number) && !empty($Result_Pros->permata_va_number)) {

                $payment_va			= $Result_Pros->permata_va_number;
				$payment_bank		= 'Permata';
				
            } else if (isset($Result_Pros->bca_va_number) && !empty($Result_Pros->bca_va_number)) {

                $payment_va			= $Result_Pros->bca_va_number;
				$payment_bank		= 'BCA';
				
            } else if (isset($Result_Pros->va_numbers) && !empty($Result_Pros->va_numbers)) {

                foreach ($Result_Pros->va_numbers as $key => $vals) {

                    $payment_va			= $vals->va_number;
                    $payment_bank		= $vals->bank;

                }

            }

        } else if ($payment_type == 'echannel') {

            $payment_va				= $Result_Pros->biller_code . '-' . $Result_Pros->bill_key;
            $payment_bank			= 'Mandiri Bill';

        } else if ($payment_type == 'bca_klikpay') {

            $payment_va           	= '-';
            $payment_bank        	= 'BCA';
            $fraud_status       	= $Result_Pros->fraud_status;

        } else if ($payment_type == 'bca_klikbca') {

            $payment_va          	= '-';
            $payment_bank        	= 'BCA';

        } else if ($payment_type == 'mandiri_clickpay') {

            $payment_va           	= '-';
            $payment_bank        	= 'Mandiri';
            $fraud_status        	= $Result_Pros->fraud_status;

        } else if ($payment_type == 'cimb_clicks') {

            $payment_va            	= '-';
            $payment_bank        	= 'CIMB';

        } else if ($payment_type == 'danamon_online') {

            $payment_va            	= '-';
            $payment_bank        	= 'Danamon';

        } else if ($payment_type == 'cstore') {

            $payment_va            	= '-';
            $payment_bank        	= $Result_Pros->store;

        } else if ($payment_type == 'akulaku') {

            $payment_va            	= '-';
            $payment_bank        	= 'Aku Laku';

        } else if ($payment_type == 'bri_epay') {

            $payment_va            	= '-';
            $payment_bank        	= 'BRI';

        }

        $ins_Update['payment_bank']    = $payment_bank;
        $ins_Update['payment_va']    = $payment_va;
        $ins_Update['fraud_status']    = $fraud_status;

        if ($sts_Bayar == 'PAID') {
            $data_order = $this->db->query("SELECT * FROM trans_order WHERE order_id = '$Kode_Order'")->row();
			
			// $nonaktifkan_voucher = [

            //     'aktif' => "N"

			// ];
			
            // $this->db->where('kode_voucher', $data_order->kode_voucher);
            // $this->db->update('voucher', $nonaktifkan_voucher);
			$ins_Update['flag_paid']    	= 'Y';
            $ins_Update['payment_date']     = $trans_date;
            $ins_Update['payment_total']    = (int)$Result_Pros->gross_amount;

        } else if ($sts_Bayar == 'CNC' || $sts_Bayar == 'DEN') {

            $ins_Update['cancel_date']      = $trans_date;
            $ins_Update['cancel_reason']    = $sts_message;
			$ins_Update['flag_cancel']    	= 'Y';

        } else if ($sts_Bayar == 'EXP') {

            $ins_Update['expired_date']        = $trans_date;

        }

		

		$Ins_Progress	= array(

			'order_id'		=> $Kode_Order,
			'sts_process'	=> $sts_Bayar,
			'process_date'	=> $trans_date,
			'process_by'	=> 'MIDTRANS RESPONSE'

		);

		

        $this->db->trans_begin();
        $this->db->update('trans_order', $ins_Update, array('order_id' => $Kode_Order));
		$this->db->insert('log_order_progress',$Ins_Progress);

        if ($this->db->trans_status() !== TRUE) {

            $this->db->trans_rollback();

        } else {

            $this->db->trans_commit();

        }

        return $Kode_Order;

	}
	

	function payment_category($pay_status = '')

    {

        $sts_Bayar			= 'OPN';
        $Ket_Bayar    		= strtolower($pay_status);

        if ($Ket_Bayar    == 'authorize') {

            ## CREDIT CARD ##
            $sts_Bayar    	= 'PEND';

        } else if ($Ket_Bayar    == 'capture') {

            $sts_Bayar    	= 'PEND';

        } else if ($Ket_Bayar    == 'settlement') {

            $sts_Bayar    	= 'PAID';

        } else if ($Ket_Bayar    == 'deny') {

            $sts_Bayar    	= 'DEN';

        } else if ($Ket_Bayar    == 'pending') {

            $sts_Bayar    	= 'PEND';

        } else if ($Ket_Bayar    == 'cancel') {

            $sts_Bayar    	= 'CNC';

        } else if ($Ket_Bayar    == 'refund' || $Ket_Bayar    == 'partial_refund') {

            $sts_Bayar    	= 'REF';

        } else if ($Ket_Bayar    == 'chargeback' || $Ket_Bayar    == 'partial_chargeback') {

            $sts_Bayar    	= 'PEND';

        } else if ($Ket_Bayar    == 'expire') {

            $sts_Bayar    	= 'EXP';

        } else if ($Ket_Bayar    == 'failure') {

            $sts_Bayar    	= 'OPN';

        }

        return $sts_Bayar;

	}
	



	
    function payment_finish()
    {

        $Kode_Midtrans    = $this->input->get('id');
        $Rows_Payment    = $this->midtrans->status($Kode_Midtrans);
        $Kode_Order        = $this->midtrans_update_status($Rows_Payment);
        redirect('orders/midtrans_status/' . $Kode_Order, 'refresh');

	}
	

	/* -------------------------------------------------------

	|	SNAP PROSES ~~ 

	|  -------------------------------------------------------

	*/

    function snap_process_error()

    {

        $Kode_Order       = $this->input->get('order_id');
        $rows_Order       = $this->db->get_where('trans_order', array('order_id' => $Kode_Order))->result();
        $rows_Detail      = $this->db->get_where('trans_order_detail', array('order_id' => $Kode_Order))->result_array();

        ## REDIRECT KE PROSES BAYAR KEMBALI##

		$this->data['title']        	= 'Payment';
		$this->data['rows_header']      = $rows_Order;
		$this->data['rows_detail']      = $rows_Detail;
		$this->data['rows_token']      	= $rows_Order[0]->merchant_code;
		$this->data['contents']     	= $this->contents . 'v_cart_payment';	
		$this->load->view($this->template, $this->data);

		

	}
	


	function snap_process_unfinish()

    {

        ## UNTUK SEMENTARA BUANG KE STATUS PAYMENT ~~ SILAHKAN DIUBAH JIKA INGIN DI PROSES BAYAR KEMBALI ##
        $Kode_Order        = $this->input->get('order_id');
        $Rows_Payment    = $this->midtrans->status($Kode_Order);
        $Kode_Proses    = $this->midtrans_update_status($Rows_Payment);

        ## REDIRECT KE STATUS PAYMENT ##
        redirect('orders/midtrans_status/' . $Kode_Proses, 'refresh');

    }



    function snap_process_finish()

    {

        $Kode_Order     = $this->input->get('order_id');
        $Rows_Payment   = $this->midtrans->status($Kode_Order);
        $Kode_Proses    = $this->midtrans_update_status($Rows_Payment);
        ## REDIRECT KE STATUS PAYMENT ##
        redirect('orders/midtrans_status/' . $Kode_Proses, 'refresh');
    }



    function payment_pending_snap()

    {



        if ($this->input->post()) {

            //echo"<pre>";print_r($this->input->post());
            $Kode_Order        	= $this->input->post('order_id');
            $Kode_Token        	= $this->input->post('transaction_id');
            $trans_status		= $this->input->post('transaction_status');
            $trans_code        	= $this->input->post('status_code');
            $trans_date        	= $this->input->post('transaction_time');
            $fraud_status    	= $this->input->post('fraud_status');
            $payment_type    	= $this->input->post('payment_type');
            $payment_bank    	= $payment_va = '-';
            $sts_Bayar        	= $this->payment_category($trans_status);

            if ($this->input->post('va_numbers')) {

                $rows_VA        = $this->input->post('va_numbers');
                $payment_bank   = $rows_VA[0]['bank'];
				$payment_va 	= $rows_VA[0]['va_number'];
				
            }



            $ins_Update        = array(
                'sts_order'        => $sts_Bayar,
                'merchant_code'    => $Kode_Token,
                'fraud_status'     => $fraud_status,
                'trans_status'     => $trans_status,
                'trans_date'       => $trans_date,
                'trans_code'       => $trans_code,
                'trans_message'    => json_encode($this->input->post()),
                'payment_type'     => $payment_type,
                'payment_bank'     => $payment_bank,
                'payment_va'       => $payment_va

            );

            $this->db->trans_begin();

            $this->db->update('trans_order', $ins_Update, array('order_id' => $Kode_Order));



            if ($this->db->trans_status() !== TRUE) {

                $this->db->trans_rollback();

                $Arr_Return        = array(

                    'status'        => false

                );

            } else {

                $this->db->trans_commit();

                $Arr_Return        = array(

                    'status'        => true

                );

            }

        } else {

            $Arr_Return        = array(

                'status'        => false

            );

        }

        echo json_encode($Arr_Return);

    }



    /* -------------------------------------------------------------------------

	|	END MIDTRANS PROSES ~~ ALI 2020-11-13

	|  -------------------------------------------------------------------------

	*/




}
