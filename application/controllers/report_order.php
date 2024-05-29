<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report_order extends CI_Controller {

    function __construct(){
        parent::__construct();
		
        $this->load->model('m_master'); 
		$this->load->model('m_contact');
		$this->load->library('encrypt');

        $this->contents     = 'report_pesanan/';
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

		$query= "SELECT * FROM
				 trans_order
				ORDER BY
				datet DESC LIMIT 0,$this->limit";

        $this->data['rows_list']    = $this->db->query($query)->result();
        $this->data['akses_menu']	= $this->arr_Akses;
		$this->data['title']        = 'Laporan Pesanan';
        $this->data['contents']     = $this->contents . 'v_report_pesanan';
		
        history('Melihat daftar laporan pesanan');

        $this->load->view($this->template, $this->data);
    }
    
	//--------------------------------------------------//
    /*                 LOAD MORE                        */
    //--------------------------------------------------//
    function load_more($awal = '',$akhir = ''){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('report_order');
		}
		
		(!$this->input->is_ajax_request() ? show_404() : '');

        $response   = array();
        $limit      = $this->limit;
        $offset     = ($this->input->post('offset') ? $this->input->post('offset') : 0 );
        $alfabet    = $this->input->post('alfabet');
		$search     = $this->input->post('search');
		$first_date	= $awal;
		$last_date	= $akhir;
       
        if($search){ // SAVE HISTORY
            history('cari data pesanan berdasarkan customer "' . $search . '"');
		}
		
		$where_tanggal = '';
		if($first_date){
			$where_tanggal = "AND DATE_FORMAT(datet,'%Y-%m-%d') >= '$first_date' 
							  AND DATE_FORMAT(datet,'%Y-%m-%d') <= '$last_date'";
		}
		
		$query= "SELECT * FROM trans_order WHERE customer LIKE '%$search%' $where_tanggal
				GROUP BY
					order_id
				ORDER BY
					datet 
				DESC LIMIT $offset,$limit";


		$data = $this->db->query($query)->result();
        if($data){ // IF DATA EXIST
            $this->data['offset']   	= $offset + 1;
			$this->data['rows_list']  	= $data;
			$this->data['akses_menu']	= $this->arr_Akses;
			$this->data['alfabet']  	= $alfabet;
            $response['content']    	= $this->load->view('contents/report_pesanan/v_more',  $this->data, TRUE);
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
            redirect('report_pesanan');
		}
 
		$rows_Header        		= $this->db->get_where('trans_order', array('order_id' => $order_id))->result();
		$this->data['rows_detail']  = $this->db->get_where('trans_order_detail', array('order_id' => $order_id))->result();
		$this->data['rows_header']  = $rows_Header;
		$this->data['title']        = 'DETAIL PESANAN';
		$this->data['contents']     = $this->contents . 'v_detail';
		$this->data['rows_akses']   = $this->arr_Akses;
		$this->load->view($this->template, $this->data);
	}

	function excel_header_proses($start_date='',$last_date='')
	{
		##### CEK USER AKSES #####
        if($this->arr_Akses['download'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('report_order');
		}
		$query = "SELECT *
					FROM trans_order AS t1 
					INNER JOIN customers AS t2 ON t1.custid = t2.custid
					WHERE DATE_FORMAT(t1.datet,'%Y-%m-%d') >= '$start_date' 
					AND DATE_FORMAT(t1.datet,'%Y-%m-%d') <= '$last_date' 
					ORDER BY t1.datet ASC";

		

		$Find_Count = $this->db->query($query)->num_rows();

		if($Find_Count < 1){
			$this->session->set_userdata('notif_gagal', 'Proses ekspor data pesanan gagal.....');
			redirect('report_pesanan');
		}else {
			$Nama_pt = $this->db->get('setting')->result();
			$header_penjualan = $this->db->query($query)->result();
			$this->load->library('PHPExcel');
			require(APPPATH. 'third_party/PHPExcel/Writer/Excel2007.php');
			$excel = new PHPExcel();
	
			##### SETTINGAN AWAL FILE EXCEL ######
			$excel->getProperties()->setCreator('Battindo')
            	->setLastModifiedBy('Battindo')
                ->setTitle("Data Laporan Pesanan")
                ->setSubject("Data Laporan Pesanan")
                ->setDescription("Data Laporan Pesanan")
                ->setKeywords("Data Laporan Pesanan");
            
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
			$excel->setActiveSheetIndex(0)->setCellValue('A1', $Nama_pt[0]->nama_perusahaan);
			$excel->getActiveSheet()->mergeCells('A1:G1');
			$excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE); 
			$excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
			$excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			##### PENGATURAN KOLOM A2 ######
			$excel->setActiveSheetIndex(0)->setCellValue('A2', "DATA LAPORAN PESANAN");
			$excel->getActiveSheet()->mergeCells('A2:G2');
			$excel->getActiveSheet()->getStyle('A2')->getFont()->setBold(TRUE); 
			$excel->getActiveSheet()->getStyle('A2')->getFont()->setSize(15);
			$excel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


			##### PENGATURAN KOLOM A3 ######
			// $periode = $start_date. ' - ' . $last_date;
			$tanggal_awal = DATE('d M Y',strtotime($start_date));
			$tanggal_akhir = DATE('d M Y',strtotime($last_date));
			$excel->setActiveSheetIndex(0)->setCellValue('A3', "Periode : $tanggal_awal - $tanggal_akhir");
			$excel->getActiveSheet()->mergeCells('A3:C3');
			$excel->getActiveSheet()->getStyle('A3')->getFont()->setBold(TRUE); 
			

			##### PENGATURAN STYLE HEADER DARI BARIS KE 4 TABEL ######
			$excel->setActiveSheetIndex(0)->setCellValue('A4', "NO");
			$excel->setActiveSheetIndex(0)->setCellValue('B4', "NO PESANAN");
			$excel->setActiveSheetIndex(0)->setCellValue('C4', "TANGGAL PESANAN");
			$excel->setActiveSheetIndex(0)->setCellValue('D4', "CUSTOMER");
			$excel->setActiveSheetIndex(0)->setCellValue('E4', "PROGRAM PENJUALAN");
			$excel->setActiveSheetIndex(0)->setCellValue('F4', "DPP");
			$excel->setActiveSheetIndex(0)->setCellValue('G4', "TOTAL");
			


			##### PENGATURAN STYLE HEADER DARI BARIS KE 4 TABEL ######
			$excel->getActiveSheet()->getStyle('A4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('B4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('C4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('D4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('E4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('F4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('G4')->applyFromArray($style_col);
			


			##### MENAMPILKAN SEMUA DATA DARI DATABASE ######
			$no = 1;
			$numrow = 5;
			$Sub_row = count($header_penjualan) + 5;
			$Sub_total ='';
			foreach($header_penjualan as $row){
				
				##### MENANGKAP DATA DARI ROW ######
				$excel->setActiveSheetIndex(0)->setCellValue('A'.$numrow, $no);
				$excel->setActiveSheetIndex(0)->setCellValue('B'.$numrow, $row->order_id);
				$excel->setActiveSheetIndex(0)->setCellValue('C'.$numrow, DATE('d M Y',strtotime($row->datet)));
				$excel->setActiveSheetIndex(0)->setCellValue('D'.$numrow, $row->customer);
				$excel->setActiveSheetIndex(0)->setCellValue('E'.$numrow, $row->program_penjualan);
				$excel->setActiveSheetIndex(0)->setCellValue('F'.$numrow, number_format($row->dpp_after));
				$excel->setActiveSheetIndex(0)->setCellValue('G'.$numrow, number_format($row->total));
				$Sub_total += $row->total;
				
				

				##### APPLY STYLE ROW / ISI ######
				$excel->getActiveSheet()->getStyle('A'.$numrow)->applyFromArray($style_row);
				$excel->getActiveSheet()->getStyle('B'.$numrow)->applyFromArray($style_row);
				$excel->getActiveSheet()->getStyle('C'.$numrow)->applyFromArray($style_row);
				$excel->getActiveSheet()->getStyle('D'.$numrow)->applyFromArray($style_row);
				$excel->getActiveSheet()->getStyle('E'.$numrow)->applyFromArray($style_row);
				$excel->getActiveSheet()->getStyle('F'.$numrow)->applyFromArray($style_row);
				$excel->getActiveSheet()->getStyle('G'.$numrow)->applyFromArray($style_row);

				
				
				$no++;
				$numrow++;
			}


			$excel->setActiveSheetIndex(0)->setCellValue('F'.$Sub_row, 'Sub Total');
			$excel->getActiveSheet()->getStyle('F'.$Sub_row)->getFont()->setBold(TRUE); 
			$excel->getActiveSheet()->getStyle('F'.$Sub_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$excel->setActiveSheetIndex(0)->setCellValue('G'.$Sub_row, number_format($Sub_total));
			$excel->getActiveSheet()->getStyle('G'.$Sub_row)->getFont()->setBold(TRUE); 


			  ##### PENGATURAN WIDTH KOLOM TABEL ######
			  $excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
			  $excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
			  $excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
			  $excel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
			  $excel->getActiveSheet()->getColumnDimension('E')->setWidth(25); 
			  $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
			  $excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
			


			  ##### Set height semua kolom menjadi auto ######
			  $excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
		
			  ##### Set orientasi kertas jadi LANDSCAPE ######
			  $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
  
			  ##### Set judul file excel nya #####
			  $excel->getActiveSheet(0)->setTitle("Laporan Pesanan");
			  $excel->setActiveSheetIndex(0);

			  $file = DATE('d-M-Y',strtotime($start_date));
			  $file .= "_";
			  $file .=  DATE('d-M-Y',strtotime($last_date));
			  $file	.= "_Laporan_pesanan";
			  $file  .= ".xlsx";
			  ##### PROSES FILE EXCEL #####
			  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			  header("Content-Disposition: attachment; filename=$file");
			  header('Cache-Control: max-age=0');
			  $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
			  $write->save('php://output');
			  exit;

		
		}

	}

	function excel_detail_proses($start_date='',$last_date=''){
		##### CEK USER AKSES #####
        if($this->arr_Akses['read'] != '1'){
			$this->session->set_flashdata('no_akses', true);
            redirect('report_order');
		}

		$query = "SELECT * 
				  FROM trans_order AS t1
				  INNER JOIN trans_order_detail AS t2 ON t1.order_id = t2.order_id
				  INNER JOIN customers AS t3 ON t1.custid = t3.custid
				  WHERE DATE_FORMAT(t1.datet,'%Y-%m-%d') >= '$start_date' 
				   AND DATE_FORMAT(t1.datet,'%Y-%m-%d') <= '$last_date'
				   ORDER BY t1.datet ASC";

		$Find_Count = $this->db->query($query)->num_rows();

		if($Find_Count < 1){
			$this->session->set_userdata('notif_gagal', 'Proses ekspor data detail pesanan gagal.....');
			redirect('report_order');
		}else {
			$detail_penjualan = $this->db->query($query)->result();
			$Nama_pt = $this->db->get('setting')->result();
			$this->load->library('PHPExcel');
			require(APPPATH. 'third_party/PHPExcel/Writer/Excel2007.php');
			$excel = new PHPExcel();

			##### SETTINGAN AWAL FILE EXCEL ######
			$excel->getProperties()->setCreator('Battindo')
               ->setLastModifiedBy('Battindo')
               ->setTitle("Data Laporan Detail Pesanan")
               ->setSubject("Data LaporanDetail Pesanan")
               ->setDescription("Data Laporan Detail Pesanan")
               ->setKeywords("Data Laporan Detail Pesanan");
            
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
			$excel->setActiveSheetIndex(0)->setCellValue('A1', $Nama_pt[0]->nama_perusahaan);
			$excel->getActiveSheet()->mergeCells('A1:K1');
			$excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE); 
			$excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15);
			$excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			##### PENGATURAN KOLOM A2 ######
			$excel->setActiveSheetIndex(0)->setCellValue('A2', "DATA LAPORAN DETAIL PESANAN");
			$excel->getActiveSheet()->mergeCells('A2:K2');
			$excel->getActiveSheet()->getStyle('A2')->getFont()->setBold(TRUE); 
			$excel->getActiveSheet()->getStyle('A2')->getFont()->setSize(15);
			$excel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			// $periode = $start_date. ' - ' . $last_date;
			$tanggal_awal = DATE('d M Y',strtotime($start_date));
			$tanggal_akhir = DATE('d M Y',strtotime($last_date));
			$excel->setActiveSheetIndex(0)->setCellValue('A3', "Periode : $tanggal_awal - $tanggal_akhir");
			$excel->getActiveSheet()->mergeCells('A3:C3');
			$excel->getActiveSheet()->getStyle('A3')->getFont()->setBold(TRUE); 
	
			##### PENGATURAN STYLE HEADER DARI BARIS KE 4 TABEL ######
			$excel->setActiveSheetIndex(0)->setCellValue('A4', "NO");
			$excel->setActiveSheetIndex(0)->setCellValue('B4', "NO PESANAN");
			$excel->setActiveSheetIndex(0)->setCellValue('C4', "TANGGAL PESANAN");
			$excel->setActiveSheetIndex(0)->setCellValue('D4', "CUSTOMER");
			$excel->setActiveSheetIndex(0)->setCellValue('E4', "PROGRAM PENJUALAN");
			$excel->setActiveSheetIndex(0)->setCellValue('F4', "KODE BARANG");
			$excel->setActiveSheetIndex(0)->setCellValue('G4', "NAMA BARANG");
			$excel->setActiveSheetIndex(0)->setCellValue('H4', "QTY");
			$excel->setActiveSheetIndex(0)->setCellValue('I4', "HARGA");
			$excel->setActiveSheetIndex(0)->setCellValue('J4', "DISKON");
			$excel->setActiveSheetIndex(0)->setCellValue('K4', "TOTAL");

			##### PENGATURAN STYLE HEADER DARI BARIS KE 4 TABEL ######
			$excel->getActiveSheet()->getStyle('A4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('B4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('C4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('D4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('E4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('F4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('G4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('H4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('I4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('J4')->applyFromArray($style_col);
			$excel->getActiveSheet()->getStyle('K4')->applyFromArray($style_col);

			##### MENAMPILKAN SEMUA DATA DARI DATABASE ######
			$no = 1;
			$numrow = 5;
			$Sub_row = count($detail_penjualan) + 5;
			$Sub_total ='';
	  		foreach($detail_penjualan as $row){
				$total = ($row->harga_jual - $row->discount) * $row->qty;
			  
				##### MENANGKAP DATA DARI ROW ######
				$excel->setActiveSheetIndex(0)->setCellValue('A'.$numrow, $no);
				$excel->setActiveSheetIndex(0)->setCellValue('B'.$numrow, $row->order_id);
				$excel->setActiveSheetIndex(0)->setCellValue('C'.$numrow, DATE('d M Y',strtotime($row->datet)));
				$excel->setActiveSheetIndex(0)->setCellValue('D'.$numrow, $row->customer);
				$excel->setActiveSheetIndex(0)->setCellValue('E'.$numrow, $row->program_penjualan);
				$excel->setActiveSheetIndex(0)->setCellValue('F'.$numrow, $row->kode_barang);
				$excel->setActiveSheetIndex(0)->setCellValue('G'.$numrow, $row->nama_barang);
				$excel->setActiveSheetIndex(0)->setCellValue('H'.$numrow, $row->qty);
				$excel->setActiveSheetIndex(0)->setCellValue('I'.$numrow, number_format($row->harga_jual));
				$excel->setActiveSheetIndex(0)->setCellValue('J'.$numrow, number_format($row->discount));
				$excel->setActiveSheetIndex(0)->setCellValue('K'.$numrow, $total);
				$Sub_total += $total;
	  
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
				$no++;
				$numrow++;
			}

			$excel->setActiveSheetIndex(0)->setCellValue('J'.$Sub_row, 'Sub Total');
			$excel->getActiveSheet()->getStyle('J'.$Sub_row)->getFont()->setBold(TRUE); 
			$excel->getActiveSheet()->getStyle('J'.$Sub_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$excel->setActiveSheetIndex(0)->setCellValue('K'.$Sub_row, number_format($Sub_total));
			$excel->getActiveSheet()->getStyle('K'.$Sub_row)->getFont()->setBold(TRUE); 

			##### PENGATURAN WIDTH KOLOM TABEL ######
			$excel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
			$excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
			$excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
			$excel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
			$excel->getActiveSheet()->getColumnDimension('E')->setWidth(25); 
			$excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
			$excel->getActiveSheet()->getColumnDimension('G')->setWidth(50);
			$excel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
			$excel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
			$excel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
			$excel->getActiveSheet()->getColumnDimension('K')->setWidth(15);



		
			##### Set height semua kolom menjadi auto ######
			$excel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
		
			##### Set orientasi kertas jadi LANDSCAPE ######
			$excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		
			##### Set judul file excel nya #####
			$excel->getActiveSheet(0)->setTitle("Laporan Detail Pesanan");
			$excel->setActiveSheetIndex(0);
			
			##### PROSES FILE EXCEL #####

			$file = DATE('d-M-Y',strtotime($start_date));
			  $file .= "_";
			  $file .=  DATE('d-M-Y',strtotime($last_date));
			  $file	.= "_Laporan_Detail_pesanan";
			  $file  .= ".xlsx";


			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=$file");
			header('Cache-Control: max-age=0');
			$write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
			$write->save('php://output');
			exit;

		}
	}

}