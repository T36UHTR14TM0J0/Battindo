<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stockproduct extends CI_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
        $this->contents     = 'stock_barang/';
        $this->ajax_contents= 'contents/' . $this->contents;
        $this->template     = 'layouts/v_backoffice';
        $this->data         = array();
        $this->limit        = 25;
		$controller			= ucfirst(strtolower($this->uri->segment(1)));
        $this->load->library('encrypt');
        
        #### CEK SESSION USER ####
		if($this->session->userdata('battindo_ses_isLogin')){			
			$this->arr_Akses	= $this->m_master->check_menu($controller);
		}
		
		
    }

    //--------------------------------------------------//
    /*                       INDEX                      */
    //--------------------------------------------------//
    function index(){
        #### CEK AKSES USER ####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('dashboard');
        }
        
        $limit = $this->limit;
        $Query_Product	= "SELECT
								det_stok.*, det_prod.image_name
							FROM
								barang_stok det_stok
                            INNER JOIN barang det_prod ON det_stok.kode_barang = det_prod.kode_barang
                            GROUP BY kode_barang
                            ORDER BY kode_barang DESC
                            LIMIT 0,$limit
                            ";
        
        $query 	= $this->db->query($Query_Product);
        $barang = $query->result_array();
        $this->data['rows_product'] = $barang;
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'DAFTAR STOK PRODUK';
        $this->data['contents']     = $this->contents . 'v_stock';
        history('Lihat Stok Produk');
        $this->load->view($this->template, $this->data);
    }
    

    //--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more(){
        #### CEK AKSES USER ####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('stockproduct');
        }


		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
        if($search){ // SAVE HISTORY
            history('Cari Stok Produk Dengan Kata Kunci "' . $search . '"');;
        }
		
        $Query_Product	= "SELECT
                                det_stok.*, det_prod.image_name
                            FROM
                                barang_stok det_stok
                            INNER JOIN barang det_prod ON det_stok.kode_barang = det_prod.kode_barang
                            WHERE det_stok.nama_barang LIKE  '%$search%'
                            GROUP BY kode_barang
                            ORDER BY kode_barang DESC
                            LIMIT $offset,$limit";

        $query 	= $this->db->query($Query_Product);
        $data = $query->result_array();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_product'] = $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']        = $this->load->view('contents/stock_barang/v_more',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }
    
    //--------------------------------------------------//
    /*                 ADD PRODUCT STOCK                */
    //--------------------------------------------------//
    function add(){
        if($this->input->post()){
            $kode_barang        = $this->input->post('kode_barang');
            $qty                = htmlspecialchars($this->input->post('qty'));
            $qty_bagus          = htmlspecialchars($this->input->post('qty_bagus'));
            $qty_rusak          = htmlspecialchars($this->input->post('qty_rusak'));
            $harga_landed       = htmlspecialchars(preg_replace('/[Rp. ]/','',$this->input->post('harga_landed')));
            $harga_jual         = htmlspecialchars(preg_replace('/[Rp. ]/','',$this->input->post('harga_jual')));
            $mip                = htmlspecialchars($this->input->post('mip'));
            $descr              = htmlspecialchars($this->input->post('descr'));
            $created_by         = $this->session->userdata('battindo_ses_userid');
            $created_date       = date('Y-m-d H:i:s');

			## CEK IF EXISTS ##
			$Num_product		= $this->m_master->getCount('barang_stok','kode_barang',$kode_barang);
			if($Num_product > 0){
				$Arr_Return		= array(
					'status'	=> 2,
					'pesan'		=> 'Stok produk sudah ada didalam daftar....'
				);
			}else{
                $sql_barang  = $this->db->get_where('barang', array('kode_barang' => $kode_barang))->row();
                $nama_barang = $sql_barang->nama_barang;
                $Ins_Detail			    = array(
					'kode_barang'		=> $kode_barang,
                    'nama_barang'		=> $nama_barang,
                    'kdcab'             => "JKT",
                    'qty'               => $qty,
                    'qty_bagus'         => $qty_bagus,
                    'qty_rusak'         => $qty_rusak,
                    'harga_landed'      => $harga_landed,
                    'harga_jual'        => $harga_jual,
                    'mip'               => $mip,
					'descr'		        => $descr,
                    'created_by'	    => $created_by,
                    'created_date'	    => $created_date
                );
                $histori_barang_stock = array(
                    'id'          => '',
                    'kode_barang' => $kode_barang,
                    'nama_barang' => $nama_barang,
                    'kdcab'       => 'JKT',
                    'trans_date'  => date('Y-m-d H:i:s'),
                    'category'    => 'ADJ',
                    'sts_type'    => 'IN',
                    'qty_awal'    => 0,
                    'qty_update'  => $qty,
                    'qty_akhir'   => 0 + $qty,
                    'no_reff'     => '-',
                    'descr'       => $nama_barang,
                    'created_by'  => $created_by,
                    'created_date'=> $created_date
                );
                $histori_jual = array(
                    'kode_barang' => $kode_barang,
                    'nama_barang' => $nama_barang,
                    'kdcab'       => 'JKT',
                    'harga'       => $harga_jual,
                    'created_by'  => $created_by,
                    'created_date'=> $created_date
                ); 
                    $this->db->trans_begin();
                    $this->db->insert('barang_stok',$Ins_Detail);
                    $this->db->insert('histori_barang_stok',$histori_barang_stock);
                    $this->db->insert('histori_harga_jual',$histori_jual);
				if ($this->db->trans_status() !== TRUE){
					$this->db->trans_rollback();
					$Arr_Return		= array(
						'status'    => 2,
						'pesan'		=> 'Proses tambah stok produk gagal. Silahkan coba kembali...'
					);
				}else{
					$this->db->trans_commit();
					$Arr_Return		= array(
						'status'	=> 1,
						'pesan'		=> 'Proses tambah stok produk sukses...'
					);
					$this->session->set_userdata('notif_sukses', 'Proses tambah stok produk sukses...');
					history('Tambah Stok Produk '.$kode_barang.' '.$nama_barang);
				}
			}           
			echo json_encode($Arr_Return);
        }else{
            if($this->arr_Akses['create'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('stockproduct');
            }

            $query_barang               = "SELECT * FROM barang";
            $this->data['barang']       = $this->db->query($query_barang)->result();
			$this->data['action']  		= 'add';
			$this->data['title']        = 'TAMBAH STOK PRODUK';
			$this->data['contents']     = $this->contents . 'v_add';
			$this->load->view($this->template, $this->data);
		}        
    }

//--------------------------------------------------//
/*                 DETAIL PRODUCT  STOCK            */
//--------------------------------------------------//
    function detail($kode_barang=''){
        #### CEK AKSES USER ####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('stockproduct');
        }
       
        $Query_Product	= "SELECT
                                det_stok.*, det_prod.image_name
                            FROM
                                barang_stok det_stok
                            INNER JOIN barang det_prod ON det_stok.kode_barang = det_prod.kode_barang
                            WHERE det_stok.kode_barang = '$kode_barang'
                            ORDER BY kode_barang DESC";

        $query 	= $this->db->query($Query_Product);
        $barang = $query->result();
        $this->data['rows_list']    = $barang;
        $this->data['title']        = 'DETAIL STOK PRODUK';
        $this->data['contents']     = $this->contents . 'v_detail';
		$this->load->view($this->template, $this->data);
    }

//--------------------------------------------------//
/*                 UPDATE PRODUCT STOCK             */
//--------------------------------------------------//  
    function edit($id= ''){
		if($this->input->post()){
            $id                 = $this->input->post('id');
            $kode_barang        = $this->input->post('kode_barang');
            $nama_barang        = $this->input->post('nama_barang');
            $harga_jual         = htmlspecialchars(preg_replace('/[Rp. ]/','',$this->input->post('harga_jual')));
            $modified_by        = $this->session->userdata('battindo_ses_userid');
            $modified_date      = date('Y-m-d H:i:s');

            $Find_Count	= $this->db->get_where('barang_stok',array('id' => $id,'nama_barang !='=>$nama_barang))->num_rows();
            if($Find_Count > 0){
                $Arr_Return		= array(
					'status'		=> 2,
					'pesan'			=> 'Produk tidak ada didalam daftar stok...'
				);
            } else {
                $histori_jual = array(
                    'kode_barang' => $kode_barang,
                    'nama_barang' => $nama_barang,
                    'kdcab'       => 'JKT',
                    'harga'       => $harga_jual,
                    'created_by'  => $modified_by,
                    'created_date'=> $modified_date
                );
                $this->db->trans_begin();
                $this->db->set('harga_jual',$harga_jual);
                $this->db->where('id', $id);
                $this->db->update('barang_stok');
                $this->db->insert('histori_harga_jual',$histori_jual,array('kode_barang'=>$kode_barang));
                if ($this->db->trans_status() !== TRUE){
                    $this->db->trans_rollback();
                    $Arr_Return		= array(
                        'status'	=> 2,
                        'pesan'		=> 'Proses edit harga stok produk gagal. Silahkan coba kembali...'
                    );
                }else{
                    $this->db->trans_commit();            
                    $Arr_Return		= array(
                        'status'	=> 1,
                        'pesan'		=> 'Proses edit harga stok produk sukses...'
                    );
                    $this->session->set_userdata('notif_sukses', 'Proses edit harga stok produk sukses...');
                    history('Edit Harga Stok Produk '.$kode_barang.' - '.$nama_barang);
                }
            }
            echo json_encode($Arr_Return);
        }else{
			if($this->arr_Akses['update'] != '1'){
				$this->session->set_flashdata('no_akses', true);
				redirect('stockproduct');
            }

            $Query_Product	= "SELECT
                                det_stok.*, det_prod.image_name
                            FROM
                                barang_stok det_stok
                            INNER JOIN barang det_prod ON det_stok.kode_barang = det_prod.kode_barang
                            WHERE det_stok.id = '$id'
                            ORDER BY kode_barang DESC";

            $query 	= $this->db->query($Query_Product);
            $barang = $query->result();
			$this->data['rows_list']  	= $barang;
			$this->data['title']        = "EDIT HARGA STOK PRODUK";
			$this->data['contents']     = $this->contents . 'v_edit';
			$this->load->view($this->template, $this->data);
		}
    }


//--------------------------------------------------//
/*               DELETE PRODUCT STOCK            */
//--------------------------------------------------//

    function hapus($kode_barang=''){
        if($this->arr_Akses['delete'] != '1'){
            $this->session->set_flashdata('no_akses', true);
            redirect('stockproduct');
        }
        
        $Find_Count	= $this->db->get_where('barang_stok',array('kode_barang' => $kode_barang,'kode_barang !='=>$kode_barang))->num_rows();
        if($Find_Count > 0){
            $this->session->set_userdata('notif_gagal', 'Proses hapus gagal. produk tidak ada didalam daftar stok...');
            redirect('stockproduct');
        }else {
            $this->db->trans_begin();
			## HAPUS DI DATA BUNDLE ~ ANTISIPASI ##
            $this->db->delete('barang_stok', array('kode_barang' => $kode_barang));
            $this->db->delete('barang', array('kode_barang' => $kode_barang));
            $this->db->delete('barang_bundle', array('kode_header' => $kode_barang));
			if ($this->db->trans_status() !== TRUE){
				$this->db->trans_rollback();
				$this->session->set_userdata('notif_gagal', 'Proses hapus stok produk gagal. Silahkan coba kembali...');
			}else{
				$this->db->trans_commit();
				$this->session->set_userdata('notif_sukses', 'Proses hapus stok produk sukses...');
				history('Hapus Stok Produk '.$kode_barang);
			}
			redirect('stockproduct');
        }
    }	


    //--------------------------------------------------//
    /*                 EXPORT EXCEL                     */
    //--------------------------------------------------//

    function excel(){
        $this->load->view($this->ajax_contents. 'v_export');
    }

    function exportexcel($start_date,$last_date){
        $query = "SELECT * FROM barang_stok WHERE DATE_FORMAT(created_date,'%Y-%m-%d') >= '$start_date' AND DATE_FORMAT(created_date,'%Y-%m-%d') <= '$last_date' ORDER BY created_date";
        $Find_Count = $this->db->query($query)->num_rows();
        if($Find_Count < 1){
            $this->session->set_userdata('notif_gagal', 'Proses ekspor stok produk gagal. Silahkan coba kembali...');
            redirect('stockproduct');
        }else {
            $produk = $this->db->query($query)->result();
            $this->load->library('PHPExcel');
            require(APPPATH. 'third_party/PHPExcel/Writer/Excel2007.php');
            $excel = new PHPExcel();

            ##### SETTINGAN AWAL FILE EXCEL ######
            $excel->getProperties()->setCreator('Battindo')
                ->setLastModifiedBy('Battindo')
                ->setTitle("Data Stok Produk")
                ->setSubject("Stok Produk")
                ->setDescription("Laporan Data Stok Produk")
                ->setKeywords("Data Stok Produk");
            
            ##### PENGATURAN STYLE DARI HEADER TABEL ######
            $style_col = array(
                'font'       => array('bold' => true),
                'alignment'  => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
                ),
                'borders'    => array(
                    'top'        => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'right'      => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'bottom'     => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'left'       => array('style'  => PHPExcel_Style_Border::BORDER_THIN)
                )
            );

            ##### PENGATURAN STYLE DARI ISI TABEL ######
            $style_row = array(
                'alignment'  => array(
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER 
                ),
                'borders'    => array(
                    'top'        => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'right'      => array('style'  => PHPExcel_Style_Border::BORDER_THIN),
                    'bottom'     => array('style'  => PHPExcel_Style_Border::BORDER_THIN), 
                    'left'       => array('style'  => PHPExcel_Style_Border::BORDER_THIN) 
                )
            );

            ##### PENGATURAN KOLOM A1 ######
            $excel->setActiveSheetIndex(0)->setCellValue('A1', "LAPORAN DATA STOK PRODUK");
            $excel->getActiveSheet()->mergeCells('A1:K1');
            $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE); 
            $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
            $excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            ##### PENGATURAN STYLE HEADER DARI BARIS KE 3 TABEL ######
            $excel->setActiveSheetIndex(0)->setCellValue('A3', "NO");
            $excel->setActiveSheetIndex(0)->setCellValue('B3', "TANGGAL");
            $excel->setActiveSheetIndex(0)->setCellValue('C3', "KODE PRODUK");
            $excel->setActiveSheetIndex(0)->setCellValue('D3', "NAMA PRODUK");
            $excel->setActiveSheetIndex(0)->setCellValue('E3', "KDCAB");
            $excel->setActiveSheetIndex(0)->setCellValue('F3', "KUANTITAS");
            $excel->setActiveSheetIndex(0)->setCellValue('G3', "KUANTITAS BAGUS");
            $excel->setActiveSheetIndex(0)->setCellValue('H3', "KUANTITAS RUSAK");
            $excel->setActiveSheetIndex(0)->setCellValue('I3', "HARGA LANDED");
            $excel->setActiveSheetIndex(0)->setCellValue('J3', "HARGA JUAL");
            $excel->setActiveSheetIndex(0)->setCellValue('K3', "MIP");
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

                ##### MENANGKAP DATA DARI ROW ######
                $excel->setActiveSheetIndex(0)->setCellValue('A'.$numrow, $no);
                $excel->setActiveSheetIndex(0)->setCellValue('B'.$numrow, DATE('d M Y',strtotime($row->created_date)));
                $excel->setActiveSheetIndex(0)->setCellValue('C'.$numrow, $row->kode_barang);
                $excel->setActiveSheetIndex(0)->setCellValue('D'.$numrow, $row->nama_barang);
                $excel->setActiveSheetIndex(0)->setCellValue('E'.$numrow, $row->kdcab);
                $excel->setActiveSheetIndex(0)->setCellValue('F'.$numrow, $row->qty);
                $excel->setActiveSheetIndex(0)->setCellValue('G'.$numrow, $row->qty_bagus);
                $excel->setActiveSheetIndex(0)->setCellValue('H'.$numrow, $row->qty_rusak);
                $excel->setActiveSheetIndex(0)->setCellValue('I'.$numrow, $row->harga_landed);
                $excel->setActiveSheetIndex(0)->setCellValue('J'.$numrow, $row->harga_jual);
                $excel->setActiveSheetIndex(0)->setCellValue('K'.$numrow, $row->mip);
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
            $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $excel->getActiveSheet()->getColumnDimension('E')->setWidth(10); 
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
            $excel->getActiveSheet(0)->setTitle("Laporan Data Stok Produk");
            $excel->setActiveSheetIndex(0);

            ##### PROSES FILE EXCEL #####
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Data Stock Product.xlsx"');
            header('Cache-Control: max-age=0');
            $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $write->save('php://output');
            exit;
        }
  

    }
    function load_more_histori(){
        (!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = 10;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
        $search     = $this->input->post('search');
    
		$Search_By	= "";
        if($search){ // SAVE HISTORY
            history('Cari Histori Stok Produk Dengan Kata Kunci "' . $search . '"');
			$Search_By	="nama_barang LIKE '%".$search."%'";
        }
		
		$data       = $this->m_master->get_all_list('histori_barang_stok','*','trans_date DESC',"",$limit,$offset,$Search_By);
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
            $this->data['alfabet']  	= $alfabet;
            $response['content']        = $this->load->view('contents/stock_barang/v_more_histori',  $this->data, TRUE);
            $response['status']         = TRUE;
        } else {
            $response['status']         = FALSE;
        }  
        echo json_encode($response);   
    }


    function histori_stock_barang(){
        $this->data['rows_list']    = $this->m_master->get_all_list('histori_barang_stok','*','trans_date DESC',"",10);
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'HISTORI STOK PRODUK';
        $this->data['contents']     = $this->contents . 'v_histori_stock';
        history('Lihat Daftar Histori Stok Produk');
        $this->load->view($this->template, $this->data);
    }

    function detailHistori($id=''){

        $this->data['rows_list']  	= $this->m_master->read('histori_barang_stok', 'nama_barang', 'DESC', '*', 'id', $id);
        $this->data['title']        = 'DETAIL HISTORI STOK PRODUK';
        $this->data['contents']     = $this->contents . 'v_detail_histori';
		$this->load->view($this->template, $this->data);
    }
}