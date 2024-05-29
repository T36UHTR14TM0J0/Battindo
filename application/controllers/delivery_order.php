<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Delivery_order extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');
		
        $this->contents     = 'delivery_order/';
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
		
		$limit = $this->limit;
		$query = "SELECT t1.* FROM
				  trans_delivery t1
				  WHERE t1.sts_delivery = 'OPN'
				  ORDER BY t1.datet ASC LIMIT 0,$limit";

		$this->data['rows_list']	= $this->db->query($query)->result();
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'Pengiriman Pesanan';
        $this->data['contents']     = $this->contents . 'v_delivery';
		
        history('Lihat Pengiriman Pesanan');

        $this->load->view($this->template, $this->data);
    }
    
	// -------------------------------------------- //
	/*  				LOAD MORE 1				    */
	// -------------------------------------------- //
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}


		(!$this->input->is_ajax_request() ? show_404() : '');
		
        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');

        if($search){ // SAVE HISTORY
            history('Cari Pengiriman pesanan dengan kata kunci "' . $search . '"');
        }
		
		$query = "SELECT t1.* FROM trans_delivery t1				  
				  WHERE t1.sts_delivery = 'OPN' 
				  AND t1.customer LIKE '%$search%' 
				  ORDER BY t1.datet ASC LIMIT $offset,$limit";

		$data       = $this->db->query($query)->result();

        if($data){ // IF DATA EXIST
			$this->data['offset']   	= $offset + 1;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['rows_list']  	= $data;
            $this->data['alfabet']  	= $alfabet;
            $response['content']    	= $this->load->view('contents/delivery_order/v_more',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);   
	}

	
	
	// ---------------------------------//
	/*		        DETAIL             */
	//---------------------------------//
	function detail($Delivery_id=''){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}


		$Delivery_id = Dekripsi($Delivery_id);
		$rows_Header        		= $this->db->get_where('trans_delivery', array('delivery_id' => $Delivery_id))->result();
		$this->data['rows_detail']  = $this->db->get_where('trans_delivery_detail', array('delivery_id' => $Delivery_id))->result();
		$this->data['rows_header']  = $rows_Header;
		$this->data['title']        = 'DETAIL PENGIRIMAN';
		$this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
	}
	
	function add_delivery()
	{
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}
		
		$limit = $this->limit;
		$query = "SELECT t1.* FROM
				  trans_order t1 INNER JOIN trans_order_detail t2
				  ON t1.order_id = t2.order_id
				  WHERE t1.sts_order NOT IN('EXP','CNC','OPN','PEND','PAID') AND t1.flag_cancel !='Y' AND t2.qty_sisa > 0
				  GROUP BY t1.order_id
				  ORDER BY t1.datet ASC LIMIT 0,$limit";

		$this->data['rows_list']	= $this->db->query($query)->result();
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DATA PESANAN KONFIRMASI';
        $this->data['contents']     = $this->contents . 'v_outstanding_delivery';
		
        history('Lihat Data Pesanan Konfirmasi');
		
        $this->load->view($this->template, $this->data);	
	}

	// -------------------------------------------- //
	/*  				LOAD MORE 2				    */
	// -------------------------------------------- //
    function load_more_outstanding(){
		##### CEK USER AKSES #####
		if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
			redirect('dashboard');
		}
	
			(!$this->input->is_ajax_request() ? show_404() : '');
			
			$response   = array();
			$limit      = $this->limit;
			$offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
			$alfabet    = $this->input->post('alfabet');
			$search     = $this->input->post('search');
	
			if($search){ // SAVE HISTORY
				history('Cari Pesanan Konfirmasi atau Sisa Qty Dengan Kata Kunci "' . $search . '"');
			}
			
			$query = "SELECT t1.* FROM trans_order t1
					  INNER JOIN trans_order_detail t2
					  ON t1.order_id = t2.order_id 
					  WHERE t1.sts_order NOT IN('EXP','CNC','OPN') AND t1.flag_cancel !='Y'
					  AND t2.qty_sisa > 0 
					  AND t1.customer LIKE '%$search%'
					  GROUP BY t1.order_id 
					  ORDER BY t1.datet ASC LIMIT $offset,$limit";
	
			$data       = $this->db->query($query)->result();
	
			if($data){ // IF DATA EXIST
				$this->data['offset']   	= $offset + 1;
				$this->data['akses_menu']	= $this->arr_Akses;
				$this->data['rows_list']  	= $data;
				$this->data['alfabet']  	= $alfabet;
				$response['content']    	= $this->load->view('contents/delivery_order/v_more_outstanding',  $this->data, TRUE);
				$response['status']     	= TRUE;
			} else {
				$response['status']     = FALSE;
			}  
			echo json_encode($response);   
		}
	

	
	
	function add_delivery_order($kode_order=''){
		$kode_order = Dekripsi($kode_order);
		if($this->input->post()){
				// echo"<pre>";print_r($this->input->post());exit; 
			$orderid 	  = $this->input->post('order_id');
			$driver  	  = $this->input->post('driver');
			$nopol	 	  = $this->input->post('nopol');
			$descr	 	  = $this->input->post('descr');
			$detail   	  = $this->input->post('detbarang');
			$plan_date	  = $this->input->post('plan_date');
			$sts_delivery = 'OPN';
			$datet  	  = date('Y-m-d');

			
			## CEK IF EXISTS ##
			$Query_Count	= "SELECT t1.* FROM
				  trans_order t1 INNER JOIN trans_order_detail t2
				  ON t1.order_id = t2.order_id
				  WHERE t1.sts_order NOT IN('EXP','CNC','OPN') AND t1.flag_cancel !='Y' AND t2.qty_sisa > 0 AND t1.order_id = '".$orderid."'
				  GROUP BY t1.order_id";
			
			$Num_order		= $this->db->query($Query_Count)->num_rows();
			if($Num_order <= 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Proses tambah gagal, data telah di proses...'
				);
			}else{
				// Delivery id
				$Delivery_id	= 'DEL-'.date('HismdY').sprintf('%03d',rand(1,100));
				// no resi
				$nomor			= 'BATTINDO-'.date('HismdY').sprintf('%03d',rand(1,100));
				// trans order
				$order_header 	= $this->db->get_where('trans_order', array('order_id' => $orderid))->row();
				$custid 		= $order_header->custid;
				$customer 		= $order_header->customer;
				$address		= $order_header->delivery_address;
				$latitude		= $order_header->latitude;
				$longitude		= $order_header->longitude;

				// array trans_order
				$Ins_order 		= array();
				$Query_Order	="SELECT * FROM trans_delivery WHERE order_id = '".$orderid."' AND sts_delivery NOT IN('CNC')";
				$Jum_Order		= $this->db->query($Query_Order)->num_rows();
				if($Jum_Order < 1){
					$Ins_order = array('sts_order' => 'DEL');
				}
				
					
				// array log_progress
				$Ins_log = array(
					'order_id' => $orderid,
					'sts_process' => 'DEL',
					'process_date' => date('Y-m-d H:i:s'),
					'process_by'	=> $this->session->userdata('battindo_ses_userid')
					
				);

				// array delivery
				$Ins_delivery = array(
					'delivery_id' => $Delivery_id,
					'plan_date'	  => $plan_date,
					'datet'		  => $datet,
					'nomor'		  => $nomor,
					'order_id'	  => $orderid,
					'custid'	  => $custid,
					'customer'	  => $customer,
					'address'	  => $address,
					'latitude'	  => $latitude,
					'longitude'	  => $longitude,
					'sts_delivery'=> $sts_delivery,
					'send_by'	  => $this->session->userdata('battindo_ses_userid'),
					'driver'	  => $driver,
					'nopol'		  => $nopol,
					'descr'		  => $descr,
					'created_by'  => $this->session->userdata('battindo_ses_userid'),
					'created_date'=> date('Y-m-d H:i:s')
				);

				$Ins_delivery_detail		= array();
				$Ins_Detail					= array();
				if($detail){
					$intP		= 0;
					foreach($detail as $keyP=>$valP){
						$intP++;
						$Code_id		= $Delivery_id.'-'.$intP;
						$Item_Id		= $orderid.'-'.$intP;
						$Item_Code		= $valP['kode_barang'];
						$Item_Name		= $valP['nama_barang'];
						$Item_Qty		= $valP['qty'];
						$Item_Qty_Send	= $valP['qty_send'];
						$Item_Discount  = $valP['discount'];
						$Item_Price		= $valP['harga'];

						##### AMBIL DATA STOCK BARANG #####
						$rows_barang_stock	= $this->db->get_where('barang_stok',array('kode_barang'=>$Item_Code))->result();
						if($rows_barang_stock){
							$Item_Product_Stock_Landed	= $rows_barang_stock[0]->harga_landed;
						}
						
						// array delivery detail
						$Ins_delivery_detail[$intP] = array(
							'id'				=> $Code_id,
							'delivery_id' 		=> $Delivery_id,
							'order_detail_id' 	=> $Item_Id,
							'kode_barang'		=> $Item_Code,
							'nama_barang'		=> $Item_Name,
							'qty'				=> $Item_Qty_Send,
							'harga_landed'		=> $Item_Product_Stock_Landed,
							'harga_jual'		=> $Item_Price,
							'discount' 			=> $Item_Discount,
							'total_harga'		=> $Item_Price * $Item_Qty_Send - $Item_Discount,
							'total_landed'		=> $Item_Qty_Send * $Item_Product_Stock_Landed
						);
						
						$Ins_Detail[$intP]	= array(
							'id'			=> $Item_Id,
							'qty_supply'	=> $Item_Qty_Send,
							'qty_sisa'		=> $Item_Qty - $Item_Qty_Send,
						);
					}
				}

				$this->db->trans_begin();
				## UPDATE DI PROSES EXPENDITURE SAJA UNTUK PROSES DELIVERY ~ ALI 2020-12-11 ##
				/*
				if($Ins_order){
					$this->db->update('trans_order',$Ins_order,array('order_id'=>$orderid));
				}
				*/
				
				if($Ins_Detail){
					foreach($Ins_Detail as $keyD=>$valD){
						$Upd_Detail	= "UPDATE trans_order_detail SET qty_supply = qty_supply + ".$valD['qty_supply'].", qty_sisa = qty_sisa - ".$valD['qty_supply']." WHERE id='".$valD['id']."'";
						$this->db->query($Upd_Detail);
					}
				}
				
				$this->db->insert('log_order_progress',$Ins_log);
				$this->db->insert('trans_delivery',$Ins_delivery);
				$this->db->insert_batch('trans_delivery_detail',$Ins_delivery_detail);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah pengiriman gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah pengiriman sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah pengiriman sukses...');
					history('Tambah Pengiriman Pesanan '.$Delivery_id);
				}
			}
			echo json_encode($Arr_Return);
			
		} else {
			##### CEK USER AKSES #####
			if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('delivery_order');
			}


			$rows_Header        		= $this->db->get_where('trans_order', array('order_id' => $kode_order))->result();
			$this->data['rows_detail']  = $this->db->get_where('trans_order_detail', array('order_id' => $kode_order))->result();
			$this->data['rows_header']  = $rows_Header;
			$this->data['title']        = 'TAMBAH PENGIRIMAN';
			$this->data['contents']     = $this->contents . 'v_add_delivery_order';
			$this->data['rows_akses']   = $this->arr_Akses;
			$this->load->view($this->template, $this->data);
		} 
	}


	function cancel(){
		if($this->input->post()){
			$id_order	= $this->input->post('order_id');
			// GET CONTACT BY ID
			$sql_cancel = "SELECT * FROM trans_delivery WHERE delivery_id = '$id_order'";
			$this->data['rows_list']    = $this->db->query($sql_cancel)->row();			
			$this->load->view($this->ajax_contents. 'v_cancel', $this->data);
		}
	}
	
	
	function cancel_delivery(){
		if($this->input->post()){
			//echo"<pre>";print_r($this->input->post());exit; 
			$delivery_id		=$this->input->post('delivery_id');
			
			
			
			$sts			= 'CNC';			
			$cancel_by		= $this->session->userdata('battindo_ses_userid');
			$cancel_date 	= date('Y-m-d H:i:s');
			$cancel_reason  = $this->input->post('cancel_reason');
			$Find_Count		= $this->db->get_where('trans_delivery',array('delivery_id' => $delivery_id))->result();	
			if($Find_Count[0]->sts_delivery !== 'OPN'){
				$Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Proses Pembatalan gagal, data telah diubah...'
				);
				
			}else{
				$Ins_delivery = array(
					'sts_delivery' => $sts,
					'cancel_by'	=> $cancel_by,
					'cancel_date' => $cancel_date,
					'cancel_reason' => $cancel_reason
				);
				
				$Ins_Order 	= array();
				
				## CEK JIKA ADA LEBIH DARI SATU ORDER ##
				$Query_Order	="SELECT * FROM trans_delivery WHERE order_id = '".$Find_Count[0]->order_id."' AND delivery_id != '".$delivery_id."' AND sts_delivery NOT IN('CNC')";
				$Jum_Order		= $this->db->query($Query_Order)->num_rows();
				if($Jum_Order < 1){
					$Ins_Order 	= array(
						'sts_order'	=> 'CONF'
					);
				}
				
				$Ins_Log_Progress = array(
					'order_id' => $Find_Count[0]->order_id,
					'sts_process' => 'CNC',
					'process_date' => date('Y-m-d H:i:s'),
					'process_by'	=> $this->session->userdata('battindo_ses_userid')

				);
				
				
				$this->db->trans_begin();
				## UPDATE DI PROSES EXPENDITURE SAJA UNTUK PROSES DELIVERY ~ ALI 2020-12-11 ##
				/*
				if($Ins_Order){
					$this->db->update('trans_order',$Ins_Order,array('order_id'=>$Find_Count[0]->order_id));
				}
				*/				
				$this->db->update('trans_delivery',$Ins_delivery,array('delivery_id'=>$delivery_id));
				$this->db->insert('log_order_progress',$Ins_Log_Progress);
				
				$rows_delivery_det	= $this->db->get_where('trans_delivery_detail',array('delivery_id'=>$delivery_id))->result();
				if($rows_delivery_det){
					foreach($rows_delivery_det as $keyD=>$valD){
						$Upd_Detail	= "UPDATE trans_order_detail SET qty_supply = qty_supply - ".$valD->qty.", qty_sisa = qty_sisa + ".$valD->qty." WHERE id='".$valD->order_detail_id."'";
						$this->db->query($Upd_Detail);
					}
				}
				
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses pembatalan gagal. Silahkan coba kembali...'
					);
					$this->session->set_userdata('notif_gagal', 'Proses pembatalan gagal. Silahkan coba kembali...');
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses pembatalan sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses pembatalan sukses....');
					history('Pembatalan Pengiriman Pesanan '.$delivery_id);
				}	
				
			}
			echo json_encode($Arr_Return);
		}
		
	}

	
	function pdf($Delivery_id){
		$rows_Header        		= $this->db->get_where('trans_delivery', array('delivery_id' => Dekripsi($Delivery_id)))->result();
		$this->data['rows_detail']  = $this->db->get_where('trans_delivery_detail', array('delivery_id' => Dekripsi($Delivery_id)))->result();
		$this->data['rows_header']	= $rows_Header; 
		$this->data['title']        = 'PRINTPENGIRIMAN PESANAN';
		$this->data['logo']			= base_url('assets/images/crm-rint2.png');
		$contents   				= $this->ajax_contents . 'v_delivery_print';
				// $this->load->view('contents/delivery_order/v_delivery_print',$this->data);
		if($rows_Header){
			foreach($rows_Header as $row){
				$filename = $row->custid;
			}
		}
			
		$this->load->library('m_pdf');
		$mpdf = $this->m_pdf->load();
		$html = $this->load->view($contents, $this->data,TRUE);
		$mpdf->WriteHTML($html);
		$mpdf->Output("".$filename."-delivery_order.pdf" ,'D');
	}
   
}