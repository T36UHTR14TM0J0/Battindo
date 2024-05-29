<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	function __construct(){
        parent::__construct();
        $this->load->model('m_master');       

        $this->template         = 'layouts/v_backoffice';
        $this->contents         = 'dashboard/';
        $this->ajax_contents    = 'contents/' . $this->contents . '/';
        $this->data             = array();
        $this->folder			= 'dashboard';
    }

	function index(){       
        ## AMBIL DATA KATEGORI ##
		$Qry_Category = "SELECT
								det_cat.*
							FROM
								kategori det_cat
							INNER JOIN barang det_prod ON det_cat.kode = det_prod.kode_kategori
							WHERE
								det_prod.flag_active = 'Y'
							GROUP BY
								det_cat.kode
							ORDER BY
								det_cat.kategori ASC";
		$rows_Cat		= $this->db->query($Qry_Category)->result();
		$this->data['rows_data']    = $rows_Cat; 
        $this->data['title']        = 'Dashboard';
        $this->data['contents']     = $this->folder.'/v_dashboard';

		$this->load->view($this->template, $this->data);
	}
	/*
    | -------------------------------------------------------------------
    | UNTUK DETAIL PRODUCT KATEGORI
    | -------------------------------------------------------------------
    */
	function product_list(){
		(!$this->input->get() ? show_404() : '');
		$id_category    = Dekripsi($this->input->get('cat'));
		
        $data           = array();

        $Query_Product	= "SELECT
								det_stok.*, det_prod.image_name,det_prod.flag_bundle
							FROM
								barang_stok det_stok
							INNER JOIN barang det_prod ON det_stok.kode_barang = det_prod.kode_barang
							WHERE
								det_stok.qty > 0
							AND det_stok.qty_bagus > 0
							AND det_prod.kode_kategori = '".$id_category."'
							GROUP BY
								det_stok.kode_barang
							ORDER BY 
								det_prod.nama_barang ASC"; 

        
        $query 	= $this->db->query($Query_Product);
        $barang = $query->result_array();
		$category	= $this->db->get_where('kategori',array('kode'=>$id_category))->result();
        $this->data['rows_product']   	= $barang;
		$this->data['rows_category']   	= $category;
		$this->data['contents'] 		= $this->contents . 'v_detail';
        $this->load->view($this->template, $this->data);
    } 
	
	/*
    | -------------------------------------------------------------------
    | INSERT CART ITEM KE SESSION
    | -------------------------------------------------------------------
    */
	function add_product_cart_item(){
		
		if($this->input->post()){
			//echo"<pre>";print_r($this->input->post());exit;
			$Kode_Barang 	= $this->input->post('kode_barang');
			$Qty_Barang 	= $this->input->post('qty');
			
			if(!empty($this->session->userdata('battindo_cart_item'))){
				$Data_Chart	= $this->session->userdata('battindo_cart_item');
				
				if(!empty($Data_Chart[$Kode_Barang])){
					## REMOVE SESSION ##
					unset($Data_Chart[$Kode_Barang]);
					
					
					if($Qty_Barang > 0){						
						$Data_Chart[$Kode_Barang]	= array('qty'=>$Qty_Barang);
					}
				}else{
					$Data_Chart		= $this->session->userdata('battindo_cart_item');
					$Data_Chart[$Kode_Barang]	= array('qty'=>$Qty_Barang);
				}
				$this->session->set_userdata('battindo_cart_item',$Data_Chart);
			}else{
				if($Qty_Barang > 0){
					
					$Arr_Cart	= array(
						'battindo_cart_item'	=> array(
							$Kode_Barang	=> array(
								'qty'	=> $Qty_Barang
							)
						)
					);
					$this->session->set_userdata($Arr_Cart);
				}
			}
			
		}
		//echo"<pre>";print_r($this->session->userdata('battindo_cart_item'));
		## AMBIL JUMLAH ITEM ##
		$Jumlah_Cart	= count_cust_cart();
		$Arr_Return		= array('jumlah'=>$Jumlah_Cart);
		echo json_encode($Arr_Return);  
		
		
    }
    
    //--------------------------------------------------//
    /*            LOAD MORE SEARCH KATEGORI             */
    //--------------------------------------------------//
    function load_more(){
        $response   = array();
        $search     = $this->input->post('search');
    
        if($search){ // SAVE HISTORY
            history('Search kategori By Keyword "' . $search . '"');
        }
        
        $query = "SELECT
                        det_cat.*
                    FROM
                        kategori det_cat
                    INNER JOIN barang det_prod ON det_cat.kode = det_prod.kode_kategori
                    WHERE
                        det_prod.flag_active = 'Y' AND det_cat.kategori LIKE '%$search%'
                    GROUP BY
                        det_cat.kode
                    ORDER BY
                        det_cat.kategori ASC";

            // $query = "SELECT * FROM kategori WHERE kategori LIKE '%$search%'";

        $data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
			$this->data['rows_list']  	= $data;
            $response['content']        = $this->load->view('contents/dashboard/v_more',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }

     //--------------------------------------------------//
    /*            LOAD MORE SEARCH KATEGORI             */
    //--------------------------------------------------//
    function load_more_detail(){
        $response   = array();
        $search     = $this->input->post('search');
    
        if($search){ // SAVE HISTORY
            history('Search kategori By Keyword "' . $search . '"');
        }
        
        $query = "SELECT
                        det_cat.*
                    FROM
                        kategori det_cat
                    INNER JOIN barang det_prod ON det_cat.kode = det_prod.kode_kategori
                    WHERE
                        det_prod.flag_active = 'Y' AND det_cat.kategori LIKE '%$search%'
                    GROUP BY
                        det_cat.kode
                    ORDER BY
                        det_cat.kategori ASC";

            // $query = "SELECT * FROM kategori WHERE kategori LIKE '%$search%'";

        $data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
			$this->data['rows_list']  	= $data;
            $response['content']        = $this->load->view('contents/dashboard/v_more',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }

      //--------------------------------------------------//
    /*            LOAD MORE SEARCH KATEGORI             */
    //--------------------------------------------------//
    function load_more_product(){
        // echo"<pre>";print_r($this->input->get('cat'));exit;
        $id_category    = $this->input->post('id_kategori');
        $response   = array();
        $search     = $this->input->post('search');
    
        if($search){ // SAVE HISTORY
            history('Search kategori By Keyword "' . $search . '"');
        }
        
        $query = "SELECT
                        det_stok.*, det_prod.image_name,det_prod.flag_bundle
                    FROM
                        barang_stok det_stok
                    INNER JOIN barang det_prod ON det_stok.kode_barang = det_prod.kode_barang
                    WHERE
                        det_stok.qty > 0
                    AND det_stok.qty_bagus > 0
                    AND det_prod.kode_kategori = '".$id_category."'
                    AND det_prod.nama_barang LIKE '%$search%'
                    GROUP BY
                        det_stok.kode_barang
                    ORDER BY 
                        det_prod.nama_barang ASC";

            // $query = "SELECT * FROM barang WHERE nama_barang LIKE '%$search%'";

        $data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
			$this->data['rows_list']  	= $data;
            $response['content']        = $this->load->view('contents/dashboard/v_more_detail',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }

    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL KONTAK DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_contact_list(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $this->data['tahun']        = $this->input->post('tahun');
        $this->data['bulan']        = $this->input->post('bulan');
        $this->data['marketing']    = $this->input->post('marketing');

        $this->load->view($this->ajax_contents . 'v_ajax_contact_list', $this->data);
    }

    function ajax_contact_list_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response       = array();
        $tahun          = $this->input->post('tahun');
        $bulan          = $this->input->post('bulan');
        $marketing      = $this->input->post('marketing');
        $exp_marketing  = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_uid      = $exp_marketing[2];
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_uid      = '';
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $limit      = 10; // LIMIT PER HALAMAN
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
        $data       = $this->m_contact->get_all_contact_detail_dashboard($limit, $offset, $search, $tahun, $bulan, $marketing_uid);

        if($search){ // SAVE HISTORY
            history('Search Contact By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']   = $offset + 1;
            $this->data['contact']  = $data;
            $this->data['alfabet']  = $alfabet;

            $response['content']    = $this->load->view($this->ajax_contents . 'v_ajax_contact_list_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);        
    }

	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL LEADS DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_leads_list(){
        $this->data['tahun']        = $this->input->post('tahun');
        $this->data['bulan']        = $this->input->post('bulan');
        $this->data['marketing']    = $this->input->post('marketing');

        $this->load->view($this->ajax_contents . 'v_ajax_leads_list', $this->data);
    }

    function ajax_leads_list_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response       = array();
        $tahun          = $this->input->post('tahun');
        $bulan          = $this->input->post('bulan');
        $marketing      = $this->input->post('marketing');
        $exp_marketing  = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $limit      = 10; // LIMIT PER HALAMAN
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $search     = $this->input->post('search');
        $data       = $this->m_leads->get_all_leads_detail_dashboard($limit, $offset, $search, $tahun, $bulan, $marketing_fullname);

        if($search){ // SAVE HISTORY
            history('Search Leads By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']   = $offset + 1;
            $this->data['leads']    = $data;

            $response['content']    = $this->load->view($this->ajax_contents . 'v_ajax_leads_list_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);        
    }
	*/

    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL KEBUTUHAN KENDARAAN PADA DETAIL LEADS DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    
	*/
    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL QUOTATION PADA DETAIL NEGOTIATION DAN PROSPECT DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_detail_quotation(){
        $tran_header_id = $this->input->post('tran_header_id');

        $db_price = $this->load->database('price_calculation', TRUE);
        $sql_header = "SELECT t1.*
                        , t2.project_name
                        FROM tran_pricelist_header as t1
                        INNER JOIN pros_schedule as t2 ON t1.id_schedule = t2.id_schedule
                        WHERE t1.tran_header_id = '".$tran_header_id."'
                    ";
        $query = $db_price->query($sql_header);
        $header= $query->row_array();

        $this->data['header']   = $header;
        $this->data['detail']   = $this->m_price_calculation->read('tran_pricelist_detail', 'model', 'ASC', '*', 'tran_header_id', $tran_header_id);

        $this->load->view($this->ajax_contents . 'v_ajax_detail_quotation', $this->data);
    }
	*/

    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL PROSPECT DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_prospect_list(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $this->data['tahun']        = $this->input->post('tahun');
        $this->data['bulan']        = $this->input->post('bulan');
        $this->data['marketing']    = $this->input->post('marketing');

        $this->load->view($this->ajax_contents . 'v_ajax_prospect_list', $this->data);
    }

    function ajax_prospect_list_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $tahun          = $this->input->post('tahun');
        $bulan          = $this->input->post('bulan');
        $marketing      = $this->input->post('marketing');
        $exp_marketing  = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $limit      = 10;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $search     = $this->input->post('search');
        $data       = $this->m_prospect->get_all_prospect_detail_dashboard($limit, $offset, $search, $tahun, $bulan, $marketing_fullname);

        if($search){ // SAVE HISTORY
            history('Search Prospect By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']   = $offset + 1;
            $this->data['prospect'] = $data;

            $response['content']    = $this->load->view($this->ajax_contents . 'v_ajax_prospect_list_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);        
    } 
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL NEGOTIATION DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_negotiation_list(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $this->data['tahun']        = $this->input->post('tahun');
        $this->data['bulan']        = $this->input->post('bulan');
        $this->data['marketing']    = $this->input->post('marketing');

        $this->load->view($this->ajax_contents . 'v_ajax_negotiation_list', $this->data);
    }

    function ajax_negotiation_list_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $tahun          = $this->input->post('tahun');
        $bulan          = $this->input->post('bulan');
        $marketing      = $this->input->post('marketing');
        $exp_marketing  = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $limit      = 10;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $search     = $this->input->post('search');
        $data       = $this->m_negotiation->get_all_negotiation_detail_dashboard($limit, $offset, $search, $tahun, $bulan, $marketing_fullname);

        if($search){ // SAVE HISTORY
            history('Search Negotiation By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']   = $offset + 1;
            $this->data['negotiation']  = $data;

            $response['content']    = $this->load->view($this->ajax_contents . 'v_ajax_negotiation_list_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);        
    } 
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL WINLOSS DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_winloss_list(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $this->data['tahun']        = $this->input->post('tahun');
        $this->data['bulan']        = $this->input->post('bulan');
        $this->data['marketing']    = $this->input->post('marketing');

        $this->load->view($this->ajax_contents . 'v_ajax_winloss_list', $this->data);
    }

    function ajax_winloss_list_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $tahun          = $this->input->post('tahun');
        $bulan          = $this->input->post('bulan');
        $marketing      = $this->input->post('marketing');
        $exp_marketing  = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $limit      = 10;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $search     = $this->input->post('search');
        $lost_opportunities     = $this->input->post('lost_opportunities');
        $data       = $this->m_winloss->get_all_winloss_detail_dashboard($limit, $offset, $search, $tahun, $bulan, $marketing_fullname, $lost_opportunities);

        if($search){ // SAVE HISTORY
            history('Search Winloss By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']   = $offset + 1;
            $this->data['winloss']  = $data;
            $this->data['lost_opportunities']  = $lost_opportunities;

            $response['content']    = $this->load->view($this->ajax_contents . 'v_ajax_winloss_list_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);        
    } 
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK DETAIL DELIVERY ON BOARD DI HALAMAN DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_onboard_list(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $this->data['tahun']        = $this->input->post('tahun');
        $this->data['bulan']        = $this->input->post('bulan');
        $this->data['marketing']    = $this->input->post('marketing');

        $this->load->view($this->ajax_contents . 'v_ajax_onboard_list', $this->data);
    }

    function ajax_onboard_list_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $tahun          = $this->input->post('tahun');
        $bulan          = $this->input->post('bulan');
        $marketing      = $this->input->post('marketing');
        $exp_marketing  = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $limit      = 10;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $search     = $this->input->post('search');
        $data       = $this->m_onboard->get_all_onboard_detail_dashboard($limit, $offset, $search, $tahun, $bulan, $marketing_fullname);

        if($search){ // SAVE HISTORY
            history('Search On Board By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']   = $offset + 1;
            $this->data['onboard']  = $data;

            $response['content']    = $this->load->view($this->ajax_contents . 'v_ajax_onboard_list_more',  $this->data, TRUE);
            $response['status']     = TRUE;
        } else {
            $response['status']     = FALSE;
        }  
        echo json_encode($response);        
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK PIPELINE DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_pipeline(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
            $marketing_id       = $exp_marketing[2];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
            $marketing_id       = '';
        }

        $leads          = $this->m_leads->get_funnel_leads_by($tahun, $bulan, $marketing_fullname);
        $prospect       = $this->m_prospect->get_funnel_prospect_by($tahun, $bulan, $marketing_fullname);
        $negotiation    = $this->m_negotiation->get_funnel_negotiate_by($tahun, $bulan, $marketing_fullname);

        $data['contact']            = $this->m_contact->get_funnel_contact_by($tahun, $bulan, $marketing_id);
        $data['leads']              = $leads['total_project'];
        $data['leads_unit']         = $leads['total_unit'];

        $data['prospect']           = $prospect['total_project'];
        $data['prospect_unit']      = $prospect['total_unit'];

        $data['negotiation']        = $negotiation['total_project'];
        $data['negotiation_unit']   = $negotiation['total_unit'];

        $data['winloss']            = $this->m_winloss->get_funnel_winloss_by($tahun, $bulan, $marketing_fullname);
        $data['onboard']            = $this->m_onboard->get_funnel_onboard_by($tahun, $bulan, $marketing_fullname);

        // OUTPUT JSON
        $response['data']       = $data;
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK ACTIVITIES DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_activities(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $aktivitas  = array();

        $data_aktivitas = array();
        $data_tanggal   = array();

        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        // JIKA BULAN DAN TAHUN DI FILTER, MAKA AMBIL TANGGAL TERAKHIR DI BULAN TERSEBUT
        if($bulan && $tahun){
            $current_date = date('Y-m-t', strtotime($tahun.'-'.$bulan.'-01'));
        } else { // JIKA TIDAK, AMBIL CURRENT DATE-NYA HARI INI
            $current_date = date('Y-m-d');
        }
        
        $current_date   = date('Y-m-d', strtotime("+1 day", strtotime($current_date)));
        $minus_date     = date('Y-m-d', strtotime("-10 day", strtotime($current_date))); // AMBIL 10 HARI KE  BELAKANG

        $period = new DatePeriod(
                 new DateTime($minus_date),
                 new DateInterval('P1D'),
                 new DateTime($current_date)
            );

        // AMBIL LIST TANGGAL, DARI CURRENT DATE S/D 10 HARI KEBELAKANG
        $array_date = array();
        foreach ($period as $key => $value) {            
            // if(!in_array($value->format('D'), array('Sat', 'Sun'))){
            //     $array_date[]   = $value->format('Y-m-d');
            //     $data_tanggal[] = $value->format('d M Y');
            // }            

            $array_date[]   = $value->format('Y-m-d');
            $data_tanggal[] = $value->format('d M Y');
        }

        // LIST AKTIVITAS
        $array_jenis_aktivitas  = array('ADMINISTRATIVE', 'CALL', 'EMAIL', 'MEETING', 'PRESENTATION', 'QUOTATION', 'OTHER');
        
        foreach($array_jenis_aktivitas as $jenis_aktivitas){

            $data_jenis_aktivitas = array();

            foreach($array_date as $tanggal){
                $data_jenis_aktivitas[] = $this->m_price_calculation->get_grafik_aktivitas_by($tanggal, $jenis_aktivitas, $marketing_username);
            }

            $aktivitas['jenis'] = $jenis_aktivitas;
            $aktivitas['data']  = $data_jenis_aktivitas;

            $data_aktivitas[] = $aktivitas;
        }

        $data['tanggal']    = $data_tanggal;
        $data['aktivitas']  = $data_aktivitas;

        // OUTPUT JSON
        $response['data']       = $data;
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK CLOSED ACTIVITES BY UNIT
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_closed_sales_by_unit(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $aktivitas  = array();

        $data_aktivitas = array();
        $data_tanggal   = array();

        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        // AMBIL PENJUALAN BERDASARKAN UNIT
        $realisasi_unit  = $this->m_dashboard->get_closed_sales_unit_by($tahun, $bulan, $marketing_fullname);

        // AMBIL TARGET UNIT BY SALES
        $target_unit     = $this->m_dashboard->get_sum_target_unit($marketing_username);
        
        $realisasi_unit = (int) $realisasi_unit;
        $merah          = (int) $target_unit;
        $kuning         = $merah * 2; // DALAM 12 BULAN
        $hijau          = $merah * 3; // DALAM 18 BULAN

        // OUTPUT JSON
        $response['realisasi_unit']     = $realisasi_unit;
        $response['realisasi_bulat']    = ($realisasi_unit > $hijau ? $hijau : $realisasi_unit);
        $response['merah']              = $merah;
        $response['kuning']             = $kuning;
        $response['hijau']              = $hijau;
        $response['status']             = 'success';

         echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK CLOSED ACTIVITES BY REVENUE
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_closed_sales_by_revenue(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $aktivitas  = array();

        $data_aktivitas = array();
        $data_tanggal   = array();

        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }

        $realisasi_revenue  = $this->m_dashboard->get_closed_sales_revenue_by($tahun, $bulan, $marketing_fullname);

        // AMBIL TARGET REVENUE BY SALES
        $target_revenue     = $this->m_dashboard->get_sum_target_revenue($marketing_username);

        $realisasi_revenue = (int) $realisasi_revenue;
        $merah              = (int) $target_revenue;
        $kuning             = $merah * 2;
        $hijau              = $merah * 3;

        // OUTPUT JSON
        $response['realisasi_revenue']  = $realisasi_revenue;
        $response['realisasi_bulat']    = ($realisasi_revenue > $hijau ? $hijau : $realisasi_revenue);
        $response['merah']              = $merah;
        $response['kuning']             = $kuning;
        $response['hijau']              = $hijau;
        $response['status']             = 'success';
        echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK OPEN ACTIVITIES DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_open_activities(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }
        // OUTPUT JSON
        $response['data']       = $this->m_price_calculation->get_all_open_activities($tahun, $bulan, $marketing_fullname);
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK OPEN ACTIVITIES SAAT DI KLIK DETAIL
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_open_activities_detail(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $id_schedule    = $this->input->post('id_schedule');
        $db_price       = $this->load->database('price_calculation', TRUE);

        $sql_schedule = "SELECT t1.id_schedule
                                ,t1.project_name
                                , CONCAT(
                                    IF (ISNULL(t2.inisial_pt), '', t2.inisial_pt), t2.nama_relasi
                                ) AS nama_relasi
                                , xa.total_activity
                        FROM pros_schedule as t1
                        INNER JOIN amstr_pros_relasi as t2 ON t1.kode_relasi = t2.kode
                        LEFT JOIN 
                        (
                            SELECT za.id_schedule
                                    , COUNT(za.id_crm_aktivitas) as total_activity
                            FROM crm_aktivitas as za
                            GROUP BY za.id_schedule
                        ) as xa ON t1.id_schedule = xa.id_schedule
                        WHERE t1.id_schedule='".$id_schedule."'
                        ";
        $query = $db_price->query($sql_schedule);
        $this->data['leads'] = $query->row_array();

        $this->data['id_schedule']  = $id_schedule;

        $this->load->view($this->ajax_contents . 'v_ajax_detail_open_activities', $this->data);
    }

    function ajax_open_activities_detail_more(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $id_schedule= $this->input->post('id_schedule');
        $limit      = 10; // LIMIT PER HALAMAN
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $search     = $this->input->post('search');
        $data       = $this->m_dashboard->get_all_open_activities_detail($limit, $offset, $search, $id_schedule);

        if($search){ // SAVE HISTORY
            history('Search Open Activities By Keyword "' . $search . '"');
        }

        if($data){ // IF DATA EXIST
            $this->data['offset']           = $offset + 1;
            $this->data['open_activities']  = $data;

            $response['content']            = $this->load->view($this->ajax_contents . 'v_ajax_detail_open_activities_more',  $this->data, TRUE);
            $response['status']             = TRUE;
        } else {
            $response['status'] = FALSE;
        }  
        echo json_encode($response);        
    }
	*/
    
    /*
    | -------------------------------------------------------------------
    | UNTUK OPEN OPPORTUNITIES BIG DEALS DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_open_big_deals(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }
        
        // OUTPUT JSON
        $response['data']       = $this->m_dashboard->get_all_open_big_deals($tahun, $bulan, $marketing_fullname);
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK OPEN OPPORTUNITIES NON BIG DEALS DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_open_non_big_deals(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }
        
        // OUTPUT JSON
        $response['data']       = $this->m_dashboard->get_all_open_non_big_deals($tahun, $bulan, $marketing_fullname);
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
    /*
    | -------------------------------------------------------------------
    | UNTUK CLOSED OPPORTUNITIES BIG DEALS DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	/*
    function ajax_closed_big_deals(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }
        
        // OUTPUT JSON
        $response['data']       = $this->m_dashboard->get_all_closed_big_deals($tahun, $bulan, $marketing_fullname);
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
    /*
    | -------------------------------------------------------------------
    | UNTUK CLOSED OPPORTUNITIES NON BIG DEALS DI DETAIL DASHBOARD
    | -------------------------------------------------------------------
    */
	
	/*
    function ajax_closed_non_big_deals(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
        }
        
        // OUTPUT JSON
        $response['data']       = $this->m_dashboard->get_all_closed_non_big_deals($tahun, $bulan, $marketing_fullname);
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
	 /*
    | -------------------------------------------------------------------
    | UNTUK CUSTOMER ACQUISTION
    | -------------------------------------------------------------------
    */
	/*
    function ajax_customer_acquistion(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $data       = array();
        $tahun      = $this->input->post('tahun');
        $bulan      = $this->input->post('bulan');
        $marketing  = $this->input->post('marketing');

        $exp_marketing = explode('*_*', $marketing);
        
        if(count($exp_marketing) > 0 && $marketing){
            $marketing_username = $exp_marketing[1];
            $marketing_fullname = $exp_marketing[0];
            $marketing_id       = $exp_marketing[2];
        } else {
            $marketing_username = '';
            $marketing_fullname = '';
            $marketing_id       = '';
        }

        // CUSTOMER DEALING TRANSFER
        $customer_dealing_transfer = $this->m_dashboard->customer_dealing_transfer($tahun, $bulan, $marketing_username);
        
        $data['customer_dealing_transfer_total_customer']   = $customer_dealing_transfer->total_customer . ' / ' . $customer_dealing_transfer->total_project;
        $data['customer_dealing_transfer_total_unit']       = $customer_dealing_transfer->total_unit;
        $data['customer_dealing_transfer_total_revenue']    = number_format($customer_dealing_transfer->total_revenue);

        // CUSTOMER DEALING ORIGINAL
        $customer_dealing_original = $this->m_dashboard->customer_dealing_original($tahun, $bulan, $marketing_username);
        
        $data['customer_dealing_original_total_customer']   = $customer_dealing_original->total_customer . ' / ' . $customer_dealing_original->total_project;
        $data['customer_dealing_original_total_unit']       = $customer_dealing_original->total_unit;
        $data['customer_dealing_original_total_revenue']    = number_format($customer_dealing_original->total_revenue);

        // TOTAL CUSTOMER MAINTENANCE
        $customer_maintenance_total_customer    = $customer_dealing_transfer->total_customer + $customer_dealing_original->total_customer;
        $customer_maintenance_total_project     = $customer_dealing_transfer->total_project + $customer_dealing_original->total_project;
        $customer_maintenance_total_unit        = $customer_dealing_transfer->total_unit + $customer_dealing_original->total_unit;
        $customer_maintenance_total_revenue     = $customer_dealing_transfer->total_revenue + $customer_dealing_original->total_revenue;

        $data['customer_maintenance_total_customer']        = $customer_maintenance_total_customer . ' / ' . $customer_maintenance_total_project;
        $data['customer_maintenance_total_unit']            = $customer_maintenance_total_unit;
        $data['customer_maintenance_total_revenue']         = number_format($customer_maintenance_total_revenue);

        // CUSTOMER DEALING THIS YEAR
        $customer_dealing_this_year = $this->m_dashboard->customer_dealing_this_year($tahun, $bulan, $marketing_fullname);

        $data['customer_dealing_this_year_total_customer']  = $customer_dealing_this_year->total_customer . ' / ' . $customer_dealing_this_year->total_project;
        $data['customer_dealing_this_year_total_unit']      = $customer_dealing_this_year->total_unit;
        $data['customer_dealing_this_year_total_revenue']   = number_format($customer_dealing_this_year->total_revenue);

        // OUTPUT JSON
        $response['data']       = $data;
        $response['status']     = 'success';

        echo json_encode($response);
    }
	*/
	
    
	
    /*
    | -------------------------------------------------------------------
    | UNTUK HALAMAN ERROR
    | -------------------------------------------------------------------
    */
    function error404(){
        $this->load->view('layouts/v_error'); 
    }
}