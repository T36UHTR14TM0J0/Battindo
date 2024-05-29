<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ConfirmOrder extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents     = 'outstanding_confirm/';
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
			     WHERE t1.sts_order = 'OPN' AND t1.merchant_type = 'COD'
		         OR t1.sts_order = 'PAID' AND t1.merchant_type != 'COD'
			     GROUP BY
				t1.order_id
				ORDER BY
				t1.datet DESC LIMIT 0,$this->limit";

        $this->data['rows_list']    = $this->db->query($query)->result();
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'KONFIRMASI PESANAN';
        $this->data['contents']     = $this->contents . 'v_outstanding';
		
        history('Lihat Konfirmasi Pesanan');

        $this->load->view($this->template, $this->data);
    }
    
	//--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('confirmOrder');
		}

		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
       
        if($search){ // SAVE HISTORY
            history('Cari Pesanan dengan kata kunci "' . $search . '"');
        }
		
		$query= "SELECT
					t1.*
					FROM
					trans_order t1
					INNER JOIN trans_order_detail t2 ON t1.order_id = t2.order_id
					WHERE t1.sts_order = 'OPN' AND t1.merchant_type = 'COD'
					OR t1.sts_order = 'PAID' AND t1.merchant_type != 'COD'
					AND t1.customer LIKE '%$search%'
					GROUP BY
					t1.order_id
					ORDER BY
					t1.datet DESC LIMIT $offset,$limit";


		$data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;

            $response['content']    	= $this->load->view('contents/outstanding_confirm/v_more',  $this->data, TRUE);
            $response['status']     	= TRUE;
        } else {
            $response['status']     	= FALSE;
        }  
        echo json_encode($response);   
	}
	
	//--------------------------------------------------//
    /*                      CONFIRM                     */
    //--------------------------------------------------//

	function confirm($kode_order='')
	{
			##### CEK USER AKSES #####
			if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('confirmOrder');
			}

			$rows_Header        		= $this->db->get_where('trans_order', array('order_id' => $kode_order))->result();
			$this->data['rows_detail']  = $this->db->get_where('trans_order_detail', array('order_id' => $kode_order))->result();
			$this->data['rows_header']  = $rows_Header;
			$this->data['title']        = 'DETAIL KONFIRMASI PESANAN';
			$this->data['contents']     = $this->contents . 'v_detailconfirm';
			$this->data['rows_akses']   = $this->arr_Akses;
			$this->load->view($this->template, $this->data);

	}

	function proses_confirm()
	{
		if($this->input->post())
		{
			$Order_id 		= $this->input->post('order_id');
			$address		= $this->input->post('address');
			$latitude		= $this->input->post('latitude');
			$longitude		= $this->input->post('longitude');
			$notes			= $this->input->post('descr');
			// $Modified_By	= $this->session->userdata('battindo_ses_userid');
			// $Modified_Date	= date('Y-m-d H:i:s');
			$Num_order		= $this->db->get_where('trans_order',array('order_id' => $Order_id,'order_id !='=>$Order_id))->num_rows();
			if($Num_order > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Data tidak ada didalam daftar...'
				);
			}else{
				$Ins_Detail			= array(
					'delivery_address'	=> $address,
					'latitude'		 	=> $latitude,
					'longitude'	 		=> $longitude,
					'descr'		 		=> $notes,
					'sts_order'	 		=> 'CONF'
				);
   
				$Ins_log_order = array(
				   'order_id' 		=> $Order_id,
				   'sts_process' 	=> 'CONF',
				   'process_date' 	=> date('Y-m-d H:i:s'),
				   'process_by'		=> $this->session->userdata('battindo_ses_userid')
				);
   
				$this->db->trans_begin();
				$this->db->update('trans_order',$Ins_Detail,array('order_id'=>$Order_id));
				$this->db->insert('log_order_progress',$Ins_log_order);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'		=> 2,
						'pesan'		=> 'Konfirmasi gagal. Silahkan coba kembali...'
					);
					
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'		=> 1,
						'pesan'		=> 'Konfirmasi sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Konfirmasi sukses...');
					history('Konfirmasi Pesanan '.$Order_id);
				}
			}
			 echo json_encode($Arr_Return);
		}
	}
    

}