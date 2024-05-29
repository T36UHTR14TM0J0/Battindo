<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AdjustmentStock extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
        $this->contents     = 'adjustmentStock/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
		$this->load->library('encrypt');
		
		if($this->session->userdata('battindo_ses_isLogin')){			
			$this->arr_Akses	= $this->m_master->check_menu($controller);
		}
    }

    //--------------------------------------------------//
    /*                     INDEX                        */
    //--------------------------------------------------//
    function index(){
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
        }
        
        $this->data['rows_list']    = $this->m_master->get_all_list('adjustment_stok','*','created_date DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'Daftar Penyesuaian Stok';
        $this->data['contents']     = $this->contents . 'v_adjustmentstock';
        history('Lihat Penyesuaian Stok Produk');
        $this->load->view($this->template, $this->data);
    }
    

    //--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('adjustmentStock');
        }

		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
    
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari Penyesuaian Stok Dengan Kata Kunci "' . $search . '"');
			$Search_By	="nama_barang LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('adjustment_stok','*','created_date DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']        = $this->load->view('contents/adjustmentStock/v_more',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }

    //--------------------------------------------------//
    /*                      ADD                         */
    //--------------------------------------------------//
    function add(){
        if($this->input->post()){
            $kode_barang        = $this->input->post('kode_barang');
            $sts_type           = $this->input->post('sts_type');
            $qty_awal           = $this->input->post('qty_awal');
            $qty_bagus          = $this->input->post('qty_bagus');
            $qty_rusak          = $this->input->post('qty_rusak');
            $qty_hasil          = $this->input->post('qty_hasil');
            $category           = $this->input->post('category');
            $descr              = $this->input->post('descr');
            $created_by         = $this->session->userdata('battindo_ses_userid');
            $created_date       = date('Y-m-d H:i:s');

            $sql_barang   = $this->db->get_where('barang', array('kode_barang' => $kode_barang))->row();
            $nama_barang  = $sql_barang->nama_barang;

            $Ins_Detail			    = array(
				'kode_barang'		=> $kode_barang,
                'nama_barang'		=> strtoupper($nama_barang),
                'kdcab'             => 'JKT',
                'sts_type'		    => $sts_type,
                'qty_bagus'         => $qty_bagus,
                'qty_rusak'         => $qty_rusak,
                'qty'               => $qty_bagus + $qty_rusak,
                'no_reff'           => '-',
                'category'          => $category,
				'descr'		        => $descr, 
                'created_by'	    => $created_by,
                'created_date'	    => $created_date
            );

            ##### CEK STS TYPE IN ATAU OUT #####
            $sql_stock   = $this->db->get_where('barang_stok', array('kode_barang' => $kode_barang))->row();
            if($sts_type == 'IN'){
                $bagus = $sql_stock->qty_bagus + $qty_bagus;
                $rusak = $sql_stock->qty_rusak + $qty_rusak;
            }else{
                $bagus = $sql_stock->qty_bagus - $qty_bagus;
                $rusak = $sql_stock->qty_rusak - $qty_rusak;
            }

            $Ins_Barang_Stock = array(
               'qty'       => $bagus + $rusak,
               'qty_bagus' => $bagus,
               'qty_rusak' => $rusak
            );

            $Ins_histori = array(
                'kode_barang'   => $kode_barang,
                'nama_barang'   => $nama_barang,
                'kdcab'         => 'JKT',
                'trans_date'    => date('Y-m-d H:i:s'),
                'category'      => 'ADJ',
                'sts_type'      => $sts_type,
                'qty_awal'      => $qty_awal,
                'qty_update'    => $qty_bagus + $qty_rusak,
                'qty_akhir'     => $qty_hasil,
                'no_reff'       => '-',
                'descr'         => $nama_barang,
                'created_by'    => $created_by,
                'created_date'  => $created_date
            );

            $this->db->trans_begin(); 
            ##### INSERT DATA KE TABLE ADJUSTMENT_STOK #####
            $this->db->insert('adjustment_stok',$Ins_Detail);
            ##### UPDATE DATA KE TABLE BARANG_STOK #####
            $this->db->update('barang_stok',$Ins_Barang_Stock,array('kode_barang'=>$kode_barang));
            ##### INSERT DATA KE TABLE HISTORI_BARANG_STOK #####
            $this->db->insert('histori_barang_stok',$Ins_histori);
			if ($this->db->trans_status() !== TRUE){
                $this->db->trans_rollback();
                if($sts_type == 'IN'){
                    $Arr_Return		= array(
                       'status'     => 2,
                       'pesan'		=> 'Proses tambah produk in gagal. Silahkan coba kembali!...'
                    );
                } else {
                    $Arr_Return		= array(
                       'status'     => 2,
                       'pesan'		=> 'Proses tambah produk out gagal. Silahkan coba kembali...'
                    );
                }
				
			}else{
                $this->db->trans_commit();
                if($sts_type == 'IN'){
                    $Arr_Return		= array(
                        'status'    => 1,
                        'pesan'		=> 'Proses tambah produk in sukses...'
                    );
                    $this->session->set_userdata('notif_sukses', 'Add product incoming process success. Thank you & have a nice day..');
                    history('Tambah Produk In '.$kode_barang.' '.$nama_barang);  
                } else {
                    $Arr_Return		= array(
                       'status'    => 1,
                       'pesan'		=> 'Proses tambah produk in sukses...'
                    );
                    $this->session->set_userdata('notif_sukses', 'Add product out process success. Thank you & have a nice day..');
                    history('Tambah Produk Out '.$kode_barang.' '.$nama_barang);
                }	
            }
			echo json_encode($Arr_Return);
        }else{

            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('adjustmentStock');
            }

            $query_kategori             = "SELECT * FROM barang_stok";      
            $this->data['rows_barang_stock']     = $this->db->query($query_kategori)->result();
			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH PENYESUAIAN STOK';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}
    }
	
    //--------------------------------------------------//
    /*       JSON DETAIL BARANG ~ ALI 2020-10-26        */
    //--------------------------------------------------//
	function ajax_detail_product_stock(){
		$Kode_Barang	= $this->input->post('kode_barang');
		$Query_Product	= "SELECT * FROM barang_stok WHERE kode_barang = '".$Kode_Barang."' LIMIT 1";
		$result_product	= $this->db->query($Query_Product)->result();
		$rows_return	= array();
		if($result_product){
			$qty_stock	= $result_product[0]->qty;			
			$rows_return	= array(
				'qty_stock' => $qty_stock
			);
		}
		echo json_encode($rows_return);
	}

    //--------------------------------------------------//
    /*                     DETAIL                       */
    //--------------------------------------------------//
    function detail($id=''){
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('adjustmentStock');
        }

        $this->data['rows_list']  	= $this->m_master->read('adjustment_stok', 'nama_barang', 'ASC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL PENYESUAIAN';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }


    //--------------------------------------------------//
    /*                     HISTORI                      */
    //--------------------------------------------------//
    function viewhistori(){
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('adjustmentStock');
        }

        $this->data['rows_list']    = $this->m_master->get_all_list('histori_barang_stok','*','trans_date DESC',"",10);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR HISTORI STOK PRODUK';
        $this->data['contents']     = $this->contents . 'v_histori_stock';
        history('View List History Stock Product');
        $this->load->view($this->template, $this->data);
    }

    //--------------------------------------------------//
    /*                LOAD MORE HISTORI                 */
    //--------------------------------------------------//
    function load_more_histori(){
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('adjustmentStock');
        }

        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = 10;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
        $Search_By	= "";
        
        if($search){ // SAVE HISTORY
            history('Cari Histori Stok Dengan Kata Kunci "' . $search . '"');
			$Search_By	="nama_barang LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('histori_barang_stok','*','trans_date DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']        = $this->load->view('contents/adjustmentStock/v_more_histori',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }

    //--------------------------------------------------//
    /*                  DETAIL HISTORI                  */
    //--------------------------------------------------//
    function detailHistori($id=''){
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('adjustmentStock');
        }
        
        $this->data['rows_list']  	= $this->m_master->read('histori_barang_stok', 'nama_barang', 'DESC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL HISTORI STOK';
        $this->data['contents']     = $this->contents . 'v_detail_histori';
		$this->load->view($this->template, $this->data);
    }
}