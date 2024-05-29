<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Expenditure_product extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents     = 'expenditure_product/';
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

		$query= "SELECT
				  t1.*
				 FROM
				 trans_delivery t1
				 INNER JOIN trans_delivery_detail t2 ON t1.delivery_id = t2.delivery_id
			     WHERE t1.sts_delivery NOT IN ('OPN')
			     GROUP BY
				t1.delivery_id
				ORDER BY
				t1.sts_delivery DESC LIMIT 0,$this->limit";

        $this->data['rows_list']    = $this->db->query($query)->result();
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DATA PENGELUARAN PRODUK';
        $this->data['contents']     = $this->contents . 'v_list_expenditure';
		
        history('Lihat Pengeluaran Produk');

        $this->load->view($this->template, $this->data);
    }
    
	//--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('expenditure_product');
		}


		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
       
        if($search){ // SAVE HISTORY
            history('Cari Data Pengeluaran Dengan Kata Kunci "' . $search . '"');
        }
		
		$query= "SELECT
					t1.*
					FROM
					trans_delivery t1
					INNER JOIN trans_delivery_detail t2 ON t1.delivery_id = t2.delivery_id
			     	WHERE t1.sts_delivery NOT IN ('OPN')
					AND t1.customer LIKE '%$search%'
					GROUP BY
					t1.delivery_id
					ORDER BY
					t1.datet AND t1.sts_delivery DESC LIMIT $offset,$limit";


		$data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;

            $response['content']    	= $this->load->view('contents/expenditure_product/v_more_expenditure',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     	= FALSE;
        }  
        echo json_encode($response);   
	}



	function list_delivery(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}


		$query= "SELECT
					t1.*
				FROM
				trans_delivery t1
				INNER JOIN trans_delivery_detail t2 ON t1.delivery_id = t2.delivery_id
				WHERE t1.sts_delivery = 'OPN'
				GROUP BY
				t1.delivery_id
				ORDER BY
				t1.datet DESC LIMIT 0,$this->limit";
			$this->data['rows_list']  	= $this->db->query($query)->result();
			$this->data['title']        = 'DATA PENGIRIMAN';
			$this->data['contents']     = $this->contents . 'v_list_delivery';
			$this->data['akses_menu']   = $this->arr_Akses;
			$this->load->view($this->template, $this->data);
	}


	//--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more_delivery(){
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
            history('Search Delivery By Keyword "' . $search . '"');
        }
		
		$query= "SELECT
					t1.*
					FROM
					trans_delivery t1
					INNER JOIN trans_delivery_detail t2 ON t1.delivery_id = t2.delivery_id
			     	WHERE t1.sts_delivery = 'OPN'
					AND t1.customer LIKE '%$search%'
					GROUP BY
					t1.delivery_id
					ORDER BY
					t1.datet DESC LIMIT $offset,$limit";


		$data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;

            $response['content']    	= $this->load->view('contents/expenditure_product/v_more_delivery',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     	= FALSE;
        }  
        echo json_encode($response);   
	}

	function add_expenditure($kode_delivery){
			##### CEK USER AKSES #####
			if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('expenditure_product');
			}


			$kode_delivery = Dekripsi($kode_delivery);
			$rows_Header        		= $this->db->get_where('trans_delivery', array('delivery_id' => $kode_delivery))->result();
			$this->data['rows_detail']  = $this->db->get_where('trans_delivery_detail', array('delivery_id' => $kode_delivery))->result();
			$this->data['rows_header']  = $rows_Header;
			$this->data['title']        = 'DETAIL PENGELUARAN';
			$this->data['contents']     = $this->contents . 'v_add_expenditure';
			$this->data['rows_akses']   = $this->arr_Akses;
			$this->load->view($this->template, $this->data);	
	}

	function proses_add_expenditure(){
		if($this->input->post()){
			//echo"<pre>";print_r($this->input->post());exit; 
			$order_id	= $this->input->post('order_id');
			$delivery_id= $this->input->post('delivery_id');
			$detail		= $this->input->post('detail');
			$Sess_User	= $this->session->userdata('battindo_ses_userid');
			$date		= date('Y-m-d H:i:s');

			## CEK IF EXISTS ##
			$Num_delivery		= $this->db->get_where('trans_delivery',array('delivery_id' => $delivery_id))->result();
			if($Num_delivery[0]->sts_delivery !='OPN'){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Data sudah diubah...'
				);
			}else{

				##### TRANS DELIVERY #####
				$Ins_Trans_Delivery = array(
					'sts_delivery' 	=> 'PRO',
					'proses_by'	   	=> $Sess_User,
					'proses_date'  	=> $date,
					'modified_by'  	=> $Sess_User,
					'modified_date'	=> $date
				);

				##### LOG PROGRESS ORDER ######
				$Ins_log = array(
					'order_id'		=> $order_id,
					'sts_process'	=> 'DEL',
					'process_date' 	=> $date,
					'process_by'	=> $Sess_User
				);
				
				$Ins_Order 		= array();
				$Query_Order	="SELECT * FROM trans_delivery WHERE order_id = '".$order_id."' AND sts_delivery IN('CLS')";
				$Jum_Order		= $this->db->query($Query_Order)->num_rows();
				if($Jum_Order < 1){
					$Ins_Order = array('sts_order' => 'DEL');
				}
				
				
				
				$Upd_Barang_Stock	= $Upd_Order_Detail = $Upd_Delivery_Detail	= array();
				if($detail){
					$intP = 0;
					foreach($detail as $keyD=>$valD){
						$det_Delivery_Code		= $valD['id'];
						$det_Qty_Send			= $valD['qty_send'];
						$det_Nama_Barang		= $valD['nama_barang'];
						$det_Code_Barang 		= $valD['kode_barang'];
						
						## AMBIL DATA DETAIL DELIVERY ##
						$row_det_Delivery	= $this->db->get_where('trans_delivery_detail',array('id'=>$det_Delivery_Code,'delivery_id'=>$delivery_id))->result();
						if($row_det_Delivery){
							$det_Order_Code		= $row_det_Delivery[0]->order_detail_id;
							$det_Barang_Code	= $row_det_Delivery[0]->kode_barang;
							$det_Qty_Plan		= $row_det_Delivery[0]->qty;
							
							$Sisa_Plan			= $det_Qty_Plan - $det_Qty_Send;
							$rows_Barang		= $this->db->get_where('barang_stok',array('kode_barang'=>$det_Barang_Code))->result();
							$Harga_Landed		= $row_det_Delivery[0]->harga_landed;
							if($rows_Barang){
								$Harga_Landed		= $row_det_Delivery[0]->harga_landed;
							}
							$total_Landed		= $Harga_Landed * $det_Qty_Send;
							## UPDATE DELIVERY DETAIL ~ JIKA QTY SEND > 0##
							if($det_Qty_Send > 0){
								$intP++;
								$Upd_Delivery_Detail[$intP]	= array(
									'id'			=> $det_Delivery_Code,
									'qty_send'		=> $det_Qty_Send,
									'harga_landed'	=> $Harga_Landed,
									'total_landed'	=> $total_Landed
								);
								
								if(empty($Upd_Barang_Stock[$det_Barang_Code])){
									$Upd_Barang_Stock[$det_Barang_Code]	= 0;
								}
								$Upd_Barang_Stock[$det_Barang_Code]	+=$det_Qty_Send;
							}
							## JIKA QTY SEND KURANG QTY PLAN ##
							if($Sisa_Plan > 0){
								$Upd_Order_Detail[$det_Order_Code]	= $Sisa_Plan;
							}
						}

						$Ins_Adjust[$intP] = array(
							'kode_barang'		=> $det_Code_Barang,
							'nama_barang'		=> strtoupper($det_Nama_Barang),
							'kdcab'             => 'JKT',
							'sts_type'		    => 'OUT',
							'qty_bagus'         => $det_Qty_Send,
							'qty_rusak'         => 0,
							'qty'               => $det_Qty_Send + 0,
							'no_reff'           => '-',
							'category'          => 'penjualan',
							'descr'		        => $det_Nama_Barang, 
							'created_by'	    => $this->session->userdata('battindo_ses_userid'),
							'created_date'	    => date('Y-m-d H:i:s')

						);

						$sql_barang_stock   = $this->db->get_where('barang_stok', array('kode_barang' => $det_Code_Barang))->row();
						$qty_awal  = $sql_barang_stock->qty;

						$Ins_histori[$intP] = array(
							'kode_barang'   => $det_Code_Barang,
							'nama_barang'   => $det_Nama_Barang,
							'kdcab'         => 'JKT',
							'trans_date'    => date('Y-m-d H:i:s'),
							'category'      => 'ADJ',
							'sts_type'      => 'OUT',
							'qty_awal'      => $qty_awal,
							'qty_update'    => $det_Qty_Send + 0,
							'qty_akhir'     => $qty_awal - $det_Qty_Send + 0,
							'no_reff'       => '-',
							'descr'         => $det_Nama_Barang,
							'created_by'    => $this->session->userdata('battindo_ses_userid'),
							'created_date'  => date('Y-m-d H:i:s')
						);
					}
				}
				
	
				$this->db->trans_begin();
				## UPDATE DELIVERY HEADER ##
				$this->db->update('trans_delivery',$Ins_Trans_Delivery,array('delivery_id'=>$delivery_id));
				
				## UPDATE ORDER HEADER ##
				if($Ins_Order){
					$this->db->update('trans_order',$Ins_Order,array('order_id'=>$order_id));
				}
				## INSERT LOG PROGRESS ##
				$this->db->insert('log_order_progress',$Ins_log);
				
				## UPDATE DELIVERY DETAIL ##
				if($Upd_Delivery_Detail){
					$this->db->update_batch('trans_delivery_detail',$Upd_Delivery_Detail,'id');
				}
				
				## UPDATE ORDER DETAIL ##
				if($Upd_Order_Detail){
					foreach($Upd_Order_Detail as $keyO=>$valO){
						$Upd_Detail	= "UPDATE trans_order_detail SET qty_supply = qty_supply - ".$valO.", qty_sisa = qty_sisa + ".$valO." WHERE id='".$keyO."'";
						$this->db->query($Upd_Detail);
					}
				}
				
				## UPDATE BARANG STOK ##
				if($Upd_Barang_Stock){
					foreach($Upd_Barang_Stock as $keyB=>$valB){
						$Upd_Detail	= "UPDATE barang_stok SET qty = qty - ".$valB.", qty_bagus = qty_bagus - ".$valB." WHERE kode_barang='".$keyB."'";
						$this->db->query($Upd_Detail);
					}
				}

				if($Ins_Adjust){
					  ##### INSERT DATA KE TABLE ADJUSTMENT_STOK #####
					  $this->db->insert_batch('adjustment_stok',$Ins_Adjust);
				}
				
				if($Ins_histori){
					$this->db->insert_batch('histori_barang_stok',$Ins_histori);
				}
				
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses tambah pengeluaran gagal. Silahkan coba kembali...'
					);
						
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses tambah pengeluaran sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah pengeluaran sukses...');
					history('Tambah Pengeluaran '.$delivery_id);
				}
			}			
		}else{
			$Arr_Return		= array(
				'status'		=> 2,
				'pesan'			=> 'Tidak ada data yang diproses...'
			);
		}
		echo json_encode($Arr_Return);
	}



	function update($kode_delivery){
		##### CEK USER AKSES #####
        if($this->arr_Akses['update'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('expenditure_product');
		}


		$kode_delivery = Dekripsi($kode_delivery);
		$rows_Header        		= $this->db->get_where('trans_delivery', array('delivery_id' => $kode_delivery))->result();
		$this->data['rows_detail']  = $this->db->get_where('trans_delivery_detail', array('delivery_id' => $kode_delivery))->result();
		$this->data['rows_header']  = $rows_Header;
		$this->data['title']        = 'EDIT PENGELUARAN';
		$this->data['contents']     = $this->contents . 'v_update';
		$this->data['rows_akses']   = $this->arr_Akses;
		$this->load->view($this->template, $this->data);

	}
 

	function proses_update_expenditure(){
		if($this->input->post()){
			// echo"<pre>";print_r($this->input->post());exit; 
			$Delivery_Code 	= $this->input->post('delivery_id');
			$order_id		= $this->input->post('order_id');
			$detUpdate		= $this->input->post('detUpdate');
			$reason			= $this->input->post('reason');
			$receive_by		= $this->input->post('receive_by');
			$Sess_User		= $this->session->userdata('battindo_ses_userid');
			$Date			= date('Y-m-d H:i:s');

			$Num_delivery		= $this->db->get_where('trans_delivery',array('delivery_id' => $Delivery_Code))->result();
			if($Num_delivery[0]->sts_delivery !='PRO'){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Data telah diubah.....'
				);
			}else{
							
				$Upd_Barang_Stock	= $Upd_Order_Detail = $Upd_Delivery_Detail	= array();
				$Ins_histori = '';
				$Ins_Adjust = '';
				$Jum_Receive		= 0;
				if($detUpdate){
					$intP = 0;
					foreach($detUpdate as $keyD=>$valD){
						$det_Delivery_Code		= $valD['id'];
						$det_Qty_Rec			= $valD['qty_rec'];
						$det_Nama_Barang		= $valD['nama_barang'];
						$det_Code_Barang 		= $valD['kode_barang'];
						
						
						## AMBIL DATA DETAIL DELIVERY ##
						$row_det_Delivery	= $this->db->get_where('trans_delivery_detail',array('id'=>$det_Delivery_Code,'delivery_id'=>$Delivery_Code))->result();
						if($row_det_Delivery){
							$det_Order_Code		= $row_det_Delivery[0]->order_detail_id;
							$det_Barang_Code	= $row_det_Delivery[0]->kode_barang;
							$det_Qty_Send		= $row_det_Delivery[0]->qty_send;
							
							$Sisa_Kirim			= $det_Qty_Send - $det_Qty_Rec;
							$rows_Barang		= $this->db->get_where('barang_stok',array('kode_barang'=>$det_Barang_Code))->result();
							$Harga_Landed		= $row_det_Delivery[0]->harga_landed;
							
							$total_Landed		= $Harga_Landed * $det_Qty_Rec;
							## UPDATE DELIVERY DETAIL ##
							$Jum_Receive		+=$det_Qty_Rec;
							$intP++;
							$Upd_Delivery_Detail[$intP]	= array(
								'id'			=> $det_Delivery_Code,
								'qty_rec'		=> $det_Qty_Rec,
								'harga_landed'	=> $Harga_Landed,
								'total_landed'	=> $total_Landed
							);
							
							if($Sisa_Kirim > 0){
								
								
								if(empty($Upd_Barang_Stock[$det_Barang_Code])){
									$Upd_Barang_Stock[$det_Barang_Code]	= array(
										'qty_balik'		=>0,
										'landed_balik'	=>0
									);
								}
								$Landed_Balik	= $Harga_Landed * $Sisa_Kirim;
								$Upd_Barang_Stock[$det_Barang_Code]['qty_balik']	+=$Sisa_Kirim;
								$Upd_Barang_Stock[$det_Barang_Code]['landed_balik']	+=$Landed_Balik;
								
								$Upd_Order_Detail[$det_Order_Code]	= $Sisa_Kirim;

								$Ins_Adjust[$intP] = array(
									'kode_barang'		=> $det_Code_Barang,
									'nama_barang'		=> strtoupper($det_Nama_Barang),
									'kdcab'             => 'JKT',
									'sts_type'		    => 'IN',
									'qty_bagus'         => $Sisa_Kirim,
									'qty_rusak'         => 0,
									'qty'               => $Sisa_Kirim + 0,
									'no_reff'           => '-',
									'category'          => 'penjualan',
									'descr'		        => $det_Nama_Barang, 
									'created_by'	    => $this->session->userdata('battindo_ses_userid'),
									'created_date'	    => date('Y-m-d H:i:s')
		
								);
		
								$sql_barang_stock   = $this->db->get_where('barang_stok', array('kode_barang' => $det_Code_Barang))->row();
								$qty_awal  = $sql_barang_stock->qty;
		
								$Ins_histori[$intP] = array(
									'kode_barang'   => $det_Code_Barang,
									'nama_barang'   => $det_Nama_Barang,
									'kdcab'         => 'JKT',
									'trans_date'    => date('Y-m-d H:i:s'),
									'category'      => 'ADJ',
									'sts_type'      => 'IN',
									'qty_awal'      => $qty_awal,
									'qty_update'    => $Sisa_Kirim + 0,
									'qty_akhir'     => $qty_awal + $Sisa_Kirim + 0,
									'no_reff'       => '-',
									'descr'         => $det_Nama_Barang,
									'created_by'    => $this->session->userdata('battindo_ses_userid'),
									'created_date'  => date('Y-m-d H:i:s')
								);
							}

							
							
						}

						
					}
				}
				$Ins_log		= array();
				
				$Ins_Order 		= array();
				$Query_Order	="SELECT * FROM trans_delivery WHERE order_id = '".$order_id."' AND sts_delivery IN('CLS')";
				$Jum_Order		= $this->db->query($Query_Order)->num_rows();
				if($Jum_Order < 1 && $Jum_Receive > 0){
					$Ins_Order = array('sts_order' => 'REC');
				}
				
				if($Jum_Receive > 0){
					$Ins_Delivery_Header = array(
						'sts_delivery'	=> 'CLS',
						'receive_by'	=> $receive_by,
						'modified_by'	=> $Sess_User,
						'modified_date'	=> $Date
					);
					
					$Ins_log = array(
						'order_id'		=> $order_id,
						'sts_process'	=> 'CLS',
						'process_date' 	=> $Date,
						'process_by'	=> $Sess_User
					);
					
				}else{
					$Ins_Delivery_Header = array(
						'sts_delivery'	=> 'CNC',
						'cancel_reason' => $reason,
						'cancel_by'		=> $Sess_User,
						'cancel_date'	=> $Date,
						'modified_by'	=> $Sess_User,
						'modified_date'	=> $Date
					);
					
					$Ins_log = array(
						'order_id'		=> $order_id,
						'sts_process'	=> 'UNREC',
						'process_date' 	=> $Date,
						'process_by'	=> $Sess_User
					);
				}
				
				
				$this->db->trans_begin();
				## UPDATE DELIVERY HEADER ##
				$this->db->update('trans_delivery',$Ins_Delivery_Header,array('delivery_id'=>$Delivery_Code));
				
				## UPDATE ORDER HEADER ##
				if($Ins_Order){
					$this->db->update('trans_order',$Ins_Order,array('order_id'=>$order_id));
				}
				## INSERT LOG PROGRESS ##
				$this->db->insert('log_order_progress',$Ins_log);
				
				## UPDATE DELIVERY DETAIL ##
				if($Upd_Delivery_Detail){
					$this->db->update_batch('trans_delivery_detail',$Upd_Delivery_Detail,'id');
				}
				
				## UPDATE ORDER DETAIL ##
				if($Upd_Order_Detail){
					foreach($Upd_Order_Detail as $keyO=>$valO){
						$Upd_Detail	= "UPDATE trans_order_detail SET qty_supply = qty_supply - ".$valO.", qty_sisa = qty_sisa + ".$valO." WHERE id='".$keyO."'";
						$this->db->query($Upd_Detail);
					}
				}
				
				## UPDATE BARANG STOK ##
				if($Upd_Barang_Stock){
					foreach($Upd_Barang_Stock as $keyB=>$valB){
						$Upd_Detail	= "UPDATE barang_stok SET qty = qty + ".$valB['qty_balik'].", qty_bagus = qty_bagus + ".$valB['qty_balik']." WHERE kode_barang='".$keyB."'";
						## JIKA MAU MENGHITUNG ULANG HARGA LANDED DI BARANG STOK ##
						//$Upd_Detail	= "UPDATE barang_stok SET qty = qty + ".$valB['qty_balik'].", qty_bagus = qty_bagus + ".$valB['qty_balik'].", harga_landed = ROUND(((harga_landed * qty) + ".$valB['landed_balik'].") / (qty + ".$valB['qty_balik']."),0) WHERE kode_barang='".$keyB."'";
						$this->db->query($Upd_Detail);
					}
				}


				if($Ins_Adjust){
					##### INSERT DATA KE TABLE ADJUSTMENT_STOK #####
					$this->db->insert_batch('adjustment_stok',$Ins_Adjust);
			  }
			  
			  if($Ins_histori){
				  $this->db->insert_batch('histori_barang_stok',$Ins_histori);
			  }
			  
				
				
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses edit pengeluaran gagal. Silahkan coba kembali...'
					);
						
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses edit pengeluaran sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses edit pengeluaran sukses...');
					history('Edit Pengeluaran'.$Delivery_Code);
				}
			}
		}else{
			$Arr_Return		= array(
				'status'		=> 2,
				'pesan'			=> 'Tidak ada data yang diproses....'
			);
		}

		echo json_encode($Arr_Return);
	}


	

}