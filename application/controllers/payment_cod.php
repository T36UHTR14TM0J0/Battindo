<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payment_cod extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents     = 'payment_cod/';
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
				 trans_order t1
				 INNER JOIN trans_order_detail t2 ON t1.order_id = t2.order_id
			     WHERE t1.sts_order = 'REC' AND t1.merchant_type = 'COD' 
				 AND t1.flag_paid = 'N'
			     GROUP BY
				t1.order_id
				ORDER BY
				t1.datet ASC LIMIT 0,$this->limit";

        $this->data['rows_list']    = $this->db->query($query)->result();
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'PEMBAYARAN COD';
        $this->data['contents']     = $this->contents . 'v_payment_cod';
		
        history('Lihat Pembayaran COD');

        $this->load->view($this->template, $this->data);
    }
    
	//--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
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
            history('Cari Pembayaran COD Dengan Kata Kunci"' . $search . '"');
        }
		
		$query= "SELECT
					t1.*
					FROM
					trans_order t1
					INNER JOIN trans_order_detail t2 ON t1.order_id = t2.order_id
			     	WHERE t1.sts_order = 'REC' AND t1.merchant_type = 'COD'
					 AND t1.flag_paid = 'N'
					AND t1.customer LIKE '%$search%'
					GROUP BY
					t1.order_id
					ORDER BY
					t1.datet ASC LIMIT $offset,$limit";


		$data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
			$this->data['alfabet']  	= $alfabet;
            $response['content']    	= $this->load->view('contents/payment_cod/v_more_payment_cod',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     	= FALSE;
        }  
        echo json_encode($response);   
	}

	function detail($order_id){
			##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
		}


		$order_id = Dekripsi($order_id);  
		$rows_Header        		= $this->db->get_where('trans_order', array('order_id' => $order_id))->result();
		$this->data['rows_detail']  = $this->db->get_where('trans_order_detail', array('order_id' => $order_id))->result();
		$this->data['rows_header']  = $rows_Header;
		$this->data['title']        = 'PROSES PEMBAYARAN';
		$this->data['contents']     = $this->contents . 'v_detail_payment_cod';
		$this->data['rows_akses']   = $this->arr_Akses;
		$this->load->view($this->template, $this->data);
	}


	function update($order_id){	
			##### CEK USER AKSES #####
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('dashboard');
			}

			$rows_list        			= $this->db->get_where('trans_order', array('order_id' => $order_id))->result();
			$this->data['rows_detail']  = $this->db->get_where('trans_order_detail', array('order_id' => $order_id))->result();	
			$this->data['rows_order']	= $rows_list;
			$this->load->view($this->ajax_contents. 'v_update_payment', $this->data);
	}

	function proses_update_payment(){
		if($this->input->post()){
			// echo"<pre>";print_r($this->input->post());exit;
			$order_id 		= $this->input->post('order_id');
			$payment_type	= $this->input->post('payment_type');
			$payment_date	= $this->input->post('payment_date');
			$no_reff		= $this->input->post('no_reff');
			$payment_total	= str_replace(",","",$this->input->post('payment_total'));
			$flag_paid		= 'Y';

			## CEK IF EXISTS ##
			$Num_order		= $this->db->get_where('trans_order',array('order_id' => $order_id,'order_id !='=>$order_id))->num_rows();
			if($Num_order > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Data tidak ada dalam daftar!...'
				);
			}else{

				$Ins_Order_Header	= array(
					'payment_type'	=> $payment_type,
					'payment_date'	=> $payment_date,
					'payment_bank'	=> $no_reff,
					'payment_total'	=> $payment_total,
					'flag_paid'		=> $flag_paid
				);
				
				$this->db->trans_begin();
				$this->db->update('trans_order',$Ins_Order_Header,array('order_id'=>$order_id));
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'			=> 'Proses pembayaran gagal. Silahkan coba kembali!...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'			=> 'Proses pembayaran sukses. Silahkan coba kembali!...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses pembayaran sukses...');
					history('Proses Pembayaran Cod '.$order_id);
				}
			}
				echo json_encode($Arr_Return);	
		}
	}

}