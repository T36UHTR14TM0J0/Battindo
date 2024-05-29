<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_master'); 
        $this->load->model('m_contact');
        $this->load->library('encrypt');

        $this->contents     = 'product/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
        
        // CEK APAKAH USER SUDAH LOGIN //
		if($this->session->userdata('battindo_ses_isLogin')){			
			$this->arr_Akses	= $this->m_master->check_menu($controller);
		}
    }

    //--------------------------------------------------//
    /*                     INDEX                        */
    //--------------------------------------------------//
    function index(){
        // CEK AKSES USER //
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
        }
        
        $this->data['rows_list']    = $this->m_master->get_all_list('barang','*','kode_barang DESC',"",$this->limit);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR PRODUK';
        $this->data['contents']     = $this->contents . 'v_product';
        history('Lihat Produk');
        $this->load->view($this->template, $this->data);
    }
    

    //--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
        ##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('product');
        }
        
		(!$this->input->is_ajax_request() ? show_404() : '');
        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
    
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari Produk Dengan Kata Kunci "' . $search . '"');
			$Search_By	="nama_barang LIKE '%".$search."%' OR brand LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('barang','*','kode_barang DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']        = $this->load->view('contents/product/v_more',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }


   
    
    //--------------------------------------------------//
    /*                 ADD PRODUCT                      */
    //--------------------------------------------------//
    function add(){
        if($this->input->post()){
            $detail             = $this->input->post('detDetail');
            $product_name       = htmlspecialchars($this->input->post('nama_barang'));
            $kode_kategori      = $this->input->post('kode_kategori');
            $id_brand           = $this->input->post('brand');
            $id_satuan          = $this->input->post('id_satuan');
            $id_kemasan         = $this->input->post('id_kemasan');
            $ukuran             = htmlspecialchars($this->input->post('ukuran'));
            $descr              = htmlspecialchars($this->input->post('descr'));
            $flag_bundle        = htmlspecialchars($this->input->post('barang_bundle'));
            $created_by         = $this->session->userdata('battindo_ses_userid');
            $created_date       = date('Y-m-d H:i:s');


			
			## CEK IF EXISTS ##
			$Num_product		= $this->m_master->getCount('barang','LOWER(nama_barang)',strtolower($product_name));
			if($Num_product > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Produk sudah ada dalam daftar...'
				);
			}else{
				# AMBIL KODE URUT #
				$Tahun_Now		= date('Y');
				$Qry_Urut		= "SELECT
										MAX(
											CAST(
												SUBSTRING_INDEX(kode_barang, '-', - 1) AS UNSIGNED
											)
										) AS urut
									FROM
										barang
									LIMIT 1";
				$Urut_product		= 1;
				$det_Urut		    = $this->db->query($Qry_Urut)->result();
				if($det_Urut){
					$Urut_product	= $det_Urut[0]->urut + 1;
				}
				$Code_product		= 'PRO-'.sprintf('%05d',$Urut_product);
				if($Urut_product >= 100000){
					$Code_product	= 'PRO-'.$Urut_product;
                }


                $path           	= './uploads/product/';
                $filename			= $ext ='';
				// | -------------------------------------------------------------
				// |  UPLOAD FILE BASED ON PILIHAN FILE
				// | ---------------------------------------------------------------
				
				if($_FILES && isset($_FILES['pic_upload']['name']) && $_FILES['pic_upload']['name'] != ''){
					$filename   = $_FILES['pic_upload']['name'];
					$ext        = getExtension($filename);
					$new_file   = sha1(date('YmdHis'));
					$filename   = $Code_product.'.'.$ext;
					ImageResizes($_FILES['pic_upload'],'product',$Code_product);					
				}
               
                $sql_kategori   = $this->db->get_where('kategori', array('kode' => $kode_kategori))->row();
                $sql_satuan     = $this->db->get_where('satuan', array('id' => $id_satuan))->row();
                $sql_kemasan    = $this->db->get_where('kemasan', array('id' => $id_kemasan))->row();
                $sql_brand      = $this->db->get_where('brands_produk', array('id' => $id_brand))->row();
                
                $nama_kategori  = $sql_kategori->kategori;
                $satuan         = $sql_satuan->satuan;
                $kemasan        = $sql_kemasan->kemasan;
                $brand          = $sql_brand->brand;

                $Ins_Detail			    = array(
					'kode_barang'		=> $Code_product,
                    'nama_barang'		=> strtoupper($product_name),
                    'kode_kategori'     => $kode_kategori,
                    'kategori'		    => $nama_kategori,
                    'id_brand'          => $id_brand,
                    'brand'             => $brand,
                    'id_satuan'         => $id_satuan,
                    'satuan'			=> $satuan,
                    'id_kemasan'        => $id_kemasan,
					'kemasan'			=> $kemasan,
					'ukuran'			=> $ukuran,
					'image_name'		=> $filename,
					'image_type'       	=> $ext,
					'descr'		        => $descr,
                    'flag_bundle'	    => $flag_bundle,
                    'flag_active'	    => 'Y',
                    'created_by'	    => $created_by,
                    'created_date'	    => $created_date
                );

                $Detail_Bundle = array();
                if($flag_bundle == "Y"){
                    $intL	= 0;
                    foreach ($detail as $key => $vals) {
                        $intL++;
                        $Code_Detail	    = $Code_product.'-'.sprintf('%03d',$intL);
                        if($intL >= 1000){
                            $Code_Detail	= $Code_product.'-'.$intL;
                        }
                       
                        $Kode_barang_bundle			= $vals['kode_barang'];
                        $Nama_barang                = strtoupper($vals['nama_barang']);
                        $qty                        = $vals['qty'];
                        $descr_bundle               = $vals['descr'];
                        $Detail_Bundle[$intL]	= array(
                            'kode_header'	        => $Code_product,
                            'kode_detail'  		    => $Code_Detail,
                            'kode_barang'    		=> $Kode_barang_bundle,
                            'nama_barang'  		    => $Nama_barang,
                            'qty'                   => $qty,
                            'descr'                 => $descr_bundle,
                            'created_by'            => $created_by,
                            'created_date'          => $created_date
                        );
                    }
                }

                $this->db->trans_begin();
                $this->db->insert('barang',$Ins_Detail);
				if($Detail_Bundle){
					$this->db->insert_batch('barang_bundle',$Detail_Bundle);   
				}
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'    => 2,
						'pesan'		=> 'Proses tambah produk gagal. Silahkan coba kembali...'
					);
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'	=> 1,
						'pesan'		=> 'Proses tambah produk sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah produk sukses...');
					history('Tambah Produk '.$Code_product.' '.$product_name);
				}
			}           
			echo json_encode($Arr_Return);
        }else{

            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('product');
            }

            $query_kategori             = "SELECT * FROM kategori";
            $query_satuan               = "SELECT * FROM satuan";
            $query_kemasan              = "SELECT * FROM kemasan";
            $query_brand                = "SELECT * FROM brands_produk";

            $this->data['rows_barang']  = $this->m_master->getArray('barang',array('flag_bundle'=>'N'),'kode_barang','nama_barang');
            $this->data['kategori']     = $this->db->query($query_kategori)->result();
            $this->data['satuan']       = $this->db->query($query_satuan)->result();
            $this->data['brand']        = $this->db->query($query_brand)->result();
            $this->data['kemasan']      = $this->db->query($query_kemasan)->result();
			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH PRODUK';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}

        
    }
	
	/* 
	| ----------------------------------
	|	JSON DETAIL BARANG ~ ALI 2020-10-26
	| ----------------------------------
	*/
	function ajax_detail_bundle(){
		$Kode_Barang	= $this->input->post('kode_barang');
		$Query_Product	= "SELECT * FROM barang WHERE kode_barang = '".$Kode_Barang."' LIMIT 1";
		$result_product	= $this->db->query($Query_Product)->result();
		$rows_return	= array();
		if($result_product){
			$Product_Name	= $result_product[0]->nama_barang;			
			$rows_return	= array(
				'nama' => $Product_Name
			);
		}
		echo json_encode($rows_return);
	}

    //--------------------------------------------------//
    /*                 DETAIL PRODUCT                   */
    //--------------------------------------------------//
    function detail($kode_barang=''){
        ##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('product');
        }
        
        $this->data['rows_bundle']  = $this->db->get_where('barang_bundle',array('kode_header' => $kode_barang))->result();
        $this->data['rows_list']  	= $this->m_master->read('barang', 'nama_barang', 'ASC', '*', 'kode_barang', $kode_barang);
        $this->data['title']        = 'DETAIL PRODUK';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }

    //--------------------------------------------------//
    /*                 UPDATE PRODUCT                   */
    //--------------------------------------------------//  
    function edit($kode_barang= ''){
		if($this->input->post()){
            // echo"<pre>";print_r($this->input->post());exit;
            $detail                 = $this->input->post('detDetail');
            $kode_barang			= $this->input->post('kode_barang');
            $nama_barang            = $this->input->post('nama_barang');
            $kode_kategori          = $this->input->post('kode_kategori');
            $id_brand               = $this->input->post('id_brand');
            $id_satuan              = $this->input->post('id_satuan');
            $id_kemasan             = $this->input->post('id_kemasan');
            $ukuran                 = $this->input->post('ukuran');
            $descr                  = $this->input->post('descr');
            $flag_bundle            = $this->input->post('flag_bundle');
            $modified_by            = $this->session->userdata('battindo_ses_userid');
            $modified_date          = date('Y-m-d H:i:s');

            $Flag_Active	='0';
            if($this->input->post('active')){
                $sts_Act	= 'Y';
            }

            $Num_stock		= $this->m_master->getCount('barang_stok','kode_barang',$kode_barang);
            if($Num_stock > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Tidak dapat edit produk. produk telah ada di daftar stok....'
				);
            } else {
                $Find_Count	= $this->db->get_where('barang',array('LOWER(nama_barang)' => strtolower($nama_barang),'kode_barang !='=>$kode_barang))->num_rows();
                if($Find_Count > 0){
                    $Arr_Return		= array(
                        'status'	=> 2,
                        'pesan'		=> 'Produk sudah ada didalam daftar...'
                    );
                }else{
                    $path = './uploads/product/';

                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }
                    $Check_Data		= $this->m_master->read_where('barang', 'nama_barang','ASC', '*',array('kode_barang'=>$kode_barang));				
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
                        $filename   = $kode_barang.'.'.$ext;
                        ImageResizes($_FILES['pic_upload'],'product',$kode_barang);					
                    }

                    $sql_kategori   = $this->db->get_where('kategori', array('kode' => $kode_kategori))->row();
                    $sql_satuan     = $this->db->get_where('satuan', array('id' => $id_satuan))->row();
                    $sql_kemasan    = $this->db->get_where('kemasan', array('id' => $id_kemasan))->row();
                    $sql_brand      = $this->db->get_where('brands_produk', array('id' => $id_brand))->row();
                    
                    $nama_kategori  = $sql_kategori->kategori;
                    $satuan         = $sql_satuan->satuan;
                    $kemasan        = $sql_kemasan->kemasan;
                    $brand          = $sql_brand->brand;

                    
                    $Ins_Detail			= array(
                        'nama_barang'	=> $nama_barang,
                        'kode_kategori' => $kode_kategori,
                        'kategori'		=> $nama_kategori,
                        'id_brand'      => $id_brand,
                        'brand'         => $brand,
                        'id_satuan'     => $id_satuan,
                        'satuan'		=> $satuan,
                        'id_kemasan'    => $id_kemasan,
                        'kemasan'		=> $kemasan,
                        'ukuran'		=> $ukuran,
                        'descr'		    => $descr,
                        'flag_bundle'	=> $flag_bundle,
                        'flag_active'	=> 'Y',
                        'modified_by'	=> $modified_by,
                        'modified_date'	=> $modified_date
                    );

                    
                    if($filename != ''){
                        $Ins_Detail['image_name'] = $filename;
                        $Ins_Detail['image_type'] = $ext;
                    }

                    if($flag_bundle == 'Y'){
                        $Detail_Bundle = array();
                        $intL	= 0;
                        foreach ($detail as $key => $vals) {
                            $intL++;
                            $Code_Detail	= $kode_barang.'-'.sprintf('%03d',$intL);
                            if($intL >= 1000){
                                $Code_Detail	= $kode_barang.'-'.$intL;
                            }
                            $Kode_barang_bundle			= $vals['kode_barang'];
                            $Nama_barang                = $vals['nama_barang'];
                            $qty                        = $vals['qty'];
                            $descr_bundle               = $vals['descr'];
                            
                                
                                
                            $Detail_Bundle[$intL]	= array(
                                'kode_header'           => $kode_barang,
                                'kode_detail'  		    => $Code_Detail,
                                'kode_barang'    		=> $Kode_barang_bundle,
                                'nama_barang'  		    => $Nama_barang,
                                'qty'                   => $qty,
                                'descr'                 => $descr_bundle,
                                'created_by'            => $this->session->userdata('battindo_ses_userid'),
                                'created_date'          => date('Y-m-d H:i:s')
                            );
                        }
                    }
					$this->db->trans_begin();
                    ## HAPUS DI DATA BUNDLE ~ ANTISIPASI ##
                    $this->db->where("kode_header", $kode_barang);
                    $this->db->delete('barang_bundle');
                    if($flag_bundle == "Y") {
                         $this->db->insert_batch('barang_bundle',$Detail_Bundle);
                    }
                    $this->db->update('barang',$Ins_Detail,array('kode_barang'=>$kode_barang));
                    if ($this->db->trans_status() !== TRUE){
                        $this->db->trans_rollback();
                        $Arr_Return		= array(
                            'status'	=> 2,
                            'pesan'		=> 'Proses edit produk gagal. Silahkan coba kembali...'
                        );
                                
                    }else{
                        $this->db->trans_commit();
                                            
                        $Arr_Return		= array(
                            'status'	=> 1,
                            'pesan'		=> 'Proses edit produk sukses...'
                        );
                            
                
						$this->session->set_userdata('notif_sukses', 'Proses edit produk sukses...');
                        history('Edit Produk '.$kode_barang.' - '.$nama_barang);
                    }
                   
                }
            }
             echo json_encode($Arr_Return);

             
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('product');
            }


            $query_kategori             = "SELECT * FROM kategori";
            $query_satuan               = "SELECT * FROM satuan";
            $query_kemasan              = "SELECT * FROM kemasan";
            $query_brand                = "SELECT * FROM brands_produk";
            $this->data['kategori']     = $this->db->query($query_kategori)->result();
            $this->data['satuan']       = $this->db->query($query_satuan)->result();
            $this->data['kemasan']      = $this->db->query($query_kemasan)->result();
            $this->data['brand']        = $this->db->query($query_brand)->result();
            $this->data['rows_barang']  = $this->m_master->getArray('barang',array('flag_bundle'=>'N'),'kode_barang','nama_barang');
            $this->data['rows_list']  	= $this->m_master->read('barang', 'nama_barang', 'ASC', '*', 'kode_barang', $kode_barang);
            $query                      = "SELECT * FROM barang_bundle WHERE kode_header = '$kode_barang'";
            $this->data['data_bundle']  = $this->db->query($query)->result();
            $data_bundle                = $this->db->query($query)->result();
            if ($data_bundle) {
                foreach ($data_bundle as $row_bundle) {
                    $data['kode_detail']	= $row_bundle->kode_detail;
                }
            }
			$this->data['title']        = "EDIT PRODUK";
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);
		}
    }


    //--------------------------------------------------//
    /*                 DELETE PRODUCT                   */
    //--------------------------------------------------//
    function hapus($kode_barang=''){
        if($this->arr_Akses['delete'] != '1'){
            $this->session->set_flashdata('no_akses', true);
            redirect('product');
        }

		$Num_stock		= $this->m_master->getCount('barang_stok','kode_barang',$kode_barang);
		if($Num_stock > 0){
			$this->session->set_userdata('notif_gagal', 'Produk tidak boleh dihapus. Produk sudah didalam daftar stok...');
			redirect('product');
		} else {
			$query_produk = "SELECT * FROM barang WHERE kode_barang = '$kode_barang'";
            $produk = $this->db->query($query_produk)->row();

            if($produk){
                $path          = './uploads/product/';
                $File_Gambar   = $produk->image_name;
                unlink($path.$File_Gambar);
            }
            
			$this->db->trans_begin();
			## HAPUS DI DATA BUNDLE ~ ANTISIPASI ##
			 $this->db->delete('barang',array('kode_barang' => $kode_barang));
			 $this->db->delete('barang_bundle',array('kode_header' => $kode_barang));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus produk gagal. Silahkan coba kembali...');
						
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus produk sukses...');
				history('Hapus Produk '.$kode_barang);
			}
			redirect('product');
		}
        
    }

    function excel(){
        $this->load->view($this->ajax_contents. 'v_export');
    }



    //--------------------------------------------------//
    /*                 EXPORT EXCEL                     */
    //--------------------------------------------------//
    function exportexcel($start_date,$last_date){
            // echo"<pre>";print_r($this->input->post());exit;
        $query = "SELECT * FROM barang WHERE DATE_FORMAT(created_date,'%Y-%m-%d') >= '$start_date' AND DATE_FORMAT(created_date,'%Y-%m-%d') <= '$last_date'";
        $Find_Count = $this->db->query($query)->num_rows();
        if($Find_Count < 1){
            $this->session->set_userdata('notif_gagal', 'Proses ekspor excel gagal.....');
            redirect('product');
        }else{
            $produk = $this->db->query($query)->result();
            $this->load->library('PHPExcel');
            require(APPPATH. 'third_party/PHPExcel/Writer/Excel2007.php');
            $excel = new PHPExcel();

            ##### SETTINGAN AWAL FILE EXCEL ######
            $excel->getProperties()->setCreator('Battindo')
                    ->setLastModifiedBy('Battindo')
                    ->setTitle("Data Produk")
                    ->setSubject("Produk")
                    ->setDescription("Laporan Semua Produk")
                    ->setKeywords("Data Produk");

            ##### PENGATURAN STYLE DARI HEADER TABEL ######
            $style_col = array(
                'font'          => array('bold' => true),
                'alignment'     => array(
                    'horizontal'    => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical'      => PHPExcel_Style_Alignment::VERTICAL_CENTER
                ),
                'borders' => array(
                    'top'       => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'right'     => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'bottom'    => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'left'      => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
                )
            );

            ##### PENGATURAN STYLE DARI ISI TABEL ######
            $style_row = array(
                'alignment' => array(
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
                ),
                'borders' => array(
                    'top'      => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'right'    => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'bottom'   => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'left'     => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
                )
            );

            ##### PENGATURAN KOLOM A1 ######
            $excel->setActiveSheetIndex(0)->setCellValue('A1', "LAPORAN DAFTAR DATA PRODUK");
            $excel->getActiveSheet()->mergeCells('A1:K1');
            $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE);
            $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
            $excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            ##### PENGATURAN STYLE HEADER DARI BARIS KE 3 TABEL ######
            $excel->setActiveSheetIndex(0)->setCellValue('A3', "NO");
            $excel->setActiveSheetIndex(0)->setCellValue('B3', "TANGGAL");
            $excel->setActiveSheetIndex(0)->setCellValue('C3', "KODE PRODUK");
            $excel->setActiveSheetIndex(0)->setCellValue('D3', "NAMA PRODUK");
            $excel->setActiveSheetIndex(0)->setCellValue('E3', "KATEGORI");
            $excel->setActiveSheetIndex(0)->setCellValue('F3', "MERK");
            $excel->setActiveSheetIndex(0)->setCellValue('G3', "SATUAN");
            $excel->setActiveSheetIndex(0)->setCellValue('H3', "KEMASAN");
            $excel->setActiveSheetIndex(0)->setCellValue('I3', "UKURAN");
            $excel->setActiveSheetIndex(0)->setCellValue('J3', "GAMBAR");
            $excel->setActiveSheetIndex(0)->setCellValue('K3', "TIPE");
            $excel->setActiveSheetIndex(0)->setCellValue('L3', "DESKRIPSI");

            ##### PENGATURAN STYLE HEADER DARI BARIS KE 3 TABEL ######
            $excel->getActiveSheet()->getStyle('A3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('B3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('C3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('D3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('E3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('F3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('G3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('H3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('I3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('J3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('K3')->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle('L3')->applyFromArray($style_col);

            ##### MENAMPILKAN SEMUA DATA DARI DATABASE ######
            $no = 1; 
            $numrow = 4;
            foreach($produk as $row){

                ##### MENAMPILKAN DATA ROW ######
                $excel->setActiveSheetIndex(0)->setCellValue('A'.$numrow, $no);
                $excel->setActiveSheetIndex(0)->setCellValue('B'.$numrow, date('d M Y',strtotime($row->created_date)));
                $excel->setActiveSheetIndex(0)->setCellValue('C'.$numrow, $row->kode_barang);
                $excel->setActiveSheetIndex(0)->setCellValue('D'.$numrow, $row->nama_barang);
                $excel->setActiveSheetIndex(0)->setCellValue('E'.$numrow, $row->kategori);
                $excel->setActiveSheetIndex(0)->setCellValue('F'.$numrow, $row->brand);
                $excel->setActiveSheetIndex(0)->setCellValue('G'.$numrow, $row->satuan);
                $excel->setActiveSheetIndex(0)->setCellValue('H'.$numrow, $row->kemasan);
                $excel->setActiveSheetIndex(0)->setCellValue('I'.$numrow, $row->ukuran);
                $excel->setActiveSheetIndex(0)->setCellValue('J'.$numrow, $row->image_name);
                $excel->setActiveSheetIndex(0)->setCellValue('K'.$numrow, $row->image_type);
                $excel->setActiveSheetIndex(0)->setCellValue('L'.$numrow, $row->descr);

                ##### APPLY STYLE ROW / ISI ######
                $excel->getActiveSheet()->getStyle('A'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('B'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('C'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('D'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('E'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('F'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('G'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('H'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('I'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('J'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('K'.$numrow)->applyFromArray($style_row);
                $excel->getActiveSheet()->getStyle('L'.$numrow)->applyFromArray($style_row);
                $no++;
                $numrow++;
            }

            ##### PENGATURAN WIDTH KOLOM TABEL ######
            $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
            $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
            $excel->getActiveSheet()->getColumnDimension('C')->setWidth(50); 
            $excel->getActiveSheet()->getColumnDimension('D')->setWidth(50); 
            $excel->getActiveSheet()->getColumnDimension('E')->setWidth(30); 
            $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15); 
            $excel->getActiveSheet()->getColumnDimension('G')->setWidth(15); 
            $excel->getActiveSheet()->getColumnDimension('H')->setWidth(25); 
            $excel->getActiveSheet()->getColumnDimension('I')->setWidth(20); 
            $excel->getActiveSheet()->getColumnDimension('J')->setWidth(30); 
            $excel->getActiveSheet()->getColumnDimension('K')->setWidth(30); 
            $excel->getActiveSheet()->getColumnDimension('L')->setWidth(30); 
 
            
            ##### Set height semua kolom menjadi auto ######
            $excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);

            ##### Set orientasi kertas jadi LANDSCAPE ######
            $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
            
            ##### Set judul file excel nya #####
            $excel->getActiveSheet(0)->setTitle("Laporan Data Product");
            $excel->setActiveSheetIndex(0);


            ##### PROSES FILE EXCEL #####
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Data Product.xlsx"');
            header('Cache-Control: max-age=0');
            $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $write->save('php://output');
            exit;
        }

    }

}