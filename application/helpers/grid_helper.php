<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    
	/*
	| -----------------------------------------------------------------
	| ENCRYPT PASSWORD
	| -----------------------------------------------------------------	
	*/
	function security_hash($text=''){
		$CI 			= get_instance();
		$Text_Salt		= $CI->config->item("login_key");
		$text_encrypt	= sha1($Text_Salt.$text);
		return $text_encrypt;
	}
	
    /*
    | -------------------------------------------------------------------
    | UNTUK UPLOAD FILE KE SERVER TUJUAN
    | -------------------------------------------------------------------
    */
    function upload_file_remote_server($upload_file, $upload_url, $upload_filename){
        $postfields = array();
        $postfields['nama_file'] = $upload_filename;

        if (isset($upload_file['name'])) {
            
            if (function_exists('curl_file_create')) { // For PHP 5.5+
                $postfields["upload_file"] = curl_file_create(
                    $upload_file['tmp_name'],
                    $upload_file['type'],
                    $upload_file['name']
                );
                
            } else {
                $postfields["upload_file"] = '@' . $upload_file['tmp_name']
                                                      . ';filename=' . $upload_file['name']
                                                      . ';type='     . $upload_file['type'];
            }
        }
        
        $ch         = curl_init();
        $headers    = array("Content-Type:multipart/form-data");

        curl_setopt_array($ch, array(
            CURLOPT_POST            => 1,
            CURLOPT_URL             => $upload_url,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLINFO_HEADER_OUT     => 1,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_POSTFIELDS      => $postfields
        ));
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            //echo $error_msg;
        }

        curl_close ($ch);

        // DEBUG 
        // echo '<hr />';        
        // echo $result;
    }

   function count_cust_cart(){
	   $CI 		=& get_instance();
	   $Jumlah	= 0;
	  
	   if($CI->session->userdata('battindo_cart_item')){
		   $Jumlah	= count($CI->session->userdata('battindo_cart_item'));
	   }
	   return $Jumlah;
   } 
    
    function history($desc=NULL){
        $CI =& get_instance();
        $CI->load->database('default', TRUE);
        
        $path   = $CI->uri->segment(1);
        $userID = $CI->session->userdata('battindo_ses_userid');
        $Date   = date('Y-m-d H:i:s');
		if ( !empty($_SERVER['HTTP_CLIENT_IP']) ) {		 
		  $ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		  // Check IP is passed from proxy.
		  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		  // Get IP address from remote address.
		  $ip = $_SERVER['REMOTE_ADDR'];
		 }
        
        $DataHistory=array();
        $DataHistory['user_id']     	= $userID;
        $DataHistory['path']        	= $path;
        $DataHistory['description'] 	= $desc;
        $DataHistory['created']    		= $Date;  
		$DataHistory['ip_address']    	= $ip;		
        
        $CI->db->insert('histories',$DataHistory);
    }

    function error_redirect_permission(){
        $CI =& get_instance();
        $CI->session->set_userdata('error_permission', TRUE);

        redirect('dashboard');
    }
	
	function getRomawi($bulan){
		$month	= intval($bulan);
		switch($month){
			case "1":
				$romawi	='I';	
				break;
			case "2":
				$romawi	='II';	
				break;
			case "3":
				$romawi	='III';	
				break;
			case "4":
				$romawi	='IV';	
				break;
			case "5":
				$romawi	='V';	
				break;
			case "6":
				$romawi	='VI';	
				break;
			case "7":
				$romawi	='VII';	
				break;
			case "8":
				$romawi	='VIII';	
				break;
			case "9":
				$romawi	='IX';	
				break;
			case "10":
				$romawi	='X';	
				break;
			case "11":
				$romawi	='XI';	
				break;
			case "12":
				$romawi	='XII';	
				break;
		}
		return $romawi;
	}
	
	function getColsChar($colums)
	{
		// Palleng by jester
		
		if($colums>26)
		{
			$modCols = floor($colums/26);
			$ExCols = $modCols*26;
			$totCols = $colums-$ExCols;
			
			if($totCols==0)
			{
				$modCols=$modCols-1;
				$totCols+=26;
			}
			
			$lets1 = getLetColsLetter($modCols);
			$lets2 = getLetColsLetter($totCols);
			return $letsi = $lets1.$lets2;
		}
		else
		{
			$lets = getLetColsLetter($colums);
			return $letsi = $lets;
		}
	}

	function getLetColsLetter($numbs){
	// Palleng by jester
		switch($numbs){
			case 1:
			$Chars = 'A';
			break;
			case 2:
			$Chars = 'B';
			break;
			case 3:
			$Chars = 'C';
			break;
			case 4:
			$Chars = 'D';
			break;
			case 5:
			$Chars = 'E';
			break;
			case 6:
			$Chars = 'F';
			break;
			case 7:
			$Chars = 'G';
			break;
			case 8:
			$Chars = 'H';
			break;
			case 9:
			$Chars = 'I';
			break;
			case 10:
			$Chars = 'J';
			break;
			case 11:
			$Chars = 'K';
			break;
			case 12:
			$Chars = 'L';
			break;
			case 13:
			$Chars = 'M';
			break;
			case 14:
			$Chars = 'N';
			break;
			case 15:
			$Chars = 'O';
			break;
			case 16:
			$Chars = 'P';
			break;
			case 17:
			$Chars = 'Q';
			break;
			case 18:
			$Chars = 'R';
			break;
			case 19:
			$Chars = 'S';
			break;
			case 20:
			$Chars = 'T';
			break;
			case 21:
			$Chars = 'U';
			break;
			case 22:
			$Chars = 'V';
			break;
			case 23:
			$Chars = 'W';
			break;
			case 24:
			$Chars = 'X';
			break;
			case 25:
			$Chars = 'Y';
			break;
			case 26:
			$Chars = 'Z';
			break;
		}

		return $Chars;
	}

	function getColsLetter($char){
	//	Palleng by jester
		$len = strlen($char);
		if($len==1)
		{
			$numb = getLetColsNumber($char);
		}
		elseif($len==2)
		{
			$i=1;
			$j=0;
			$jm=1;
			while($i<$len)
			{
				$let_fst = substr($char, $j,1);
				$dv = getLetColsNumber($let_fst);
				$jm = $dv * 26;
				
				$i++;
				$j++;
			}
			$let_last = substr($char, $j,1);
			$numb = $jm + getLetColsNumber($let_last);
		}
		
		return $numb;
	}
	
	function getLetColsNumber($char)
	{
		// Palleng by jester
		
		switch($char){
			case 'A':$numb = 1;break;
			case 'B':$numb = 2;break;
			case 'C':$numb = 3;break;
			case 'D':$numb = 4;break;
			case 'E':$numb = 5;break;
			case 'F':$numb = 6;break;
			case 'G':$numb = 7;break;
			case 'H':$numb = 8;break;
			case 'I':$numb = 9;break;
			case 'J':$numb = 10;break;
			case 'K':$numb = 11;break;
			case 'L':$numb = 12;break;
			case 'M':$numb = 13;break;
			case 'N':$numb = 14;break;
			case 'O':$numb = 15;break;
			case 'P':$numb = 16;break;
			case 'Q':$numb = 17;break;
			case 'R':$numb = 18;break;
			case 'S':$numb = 19;break;
			case 'T':$numb = 20;break;
			case 'U':$numb = 21;break;
			case 'V':$numb = 22;break;
			case 'W':$numb = 23;break;
			case 'X':$numb = 24;break;
			case 'Y':$numb = 25;break;
			case 'Z':$numb = 26;break;
		}
		
		return $numb;
	}

	function access_menu_group($groupID){
		$CI 			=& get_instance();
		
		$MenusAccess	= array();
		$Query	= "SELECT menus.*,group_menus.id AS kode_group,group_menus.`read`,group_menus.`create`,group_menus.`update`,group_menus.`delete`,group_menus.`approve`,group_menus.`download` FROM menus LEFT JOIN group_menus ON menus.id=group_menus.menu_id AND group_menus.group_id='$groupID' AND menus.parent_id='0' WHERE menus.active='1' ORDER BY menus.parent_id,menus.weight,menus.id ASC";
		
		$jumlah		= $CI->db->query($Query);
		//echo"ono bro ".$jumlah;exit;
		if($jumlah->num_rows() > 0){
			$hasil		= $jumlah->result_array();
			
			foreach($hasil as $key=>$val){
				if($groupID=='1'){
					$MenusAccess[$val['id']]['read']=1;	
					$MenusAccess[$val['id']]['create']=1;
					$MenusAccess[$val['id']]['update']=1;
					$MenusAccess[$val['id']]['delete']=1;
					$MenusAccess[$val['id']]['approve']=1;
					$MenusAccess[$val['id']]['download']=1;
				}else{
					if(isset($val['kode_group']) && $val['kode_group']){
						$MenusAccess[$val['id']]['read']=$val['read'];	
						$MenusAccess[$val['id']]['create']=$val['create'];
						$MenusAccess[$val['id']]['update']=$val['update'];
						$MenusAccess[$val['id']]['delete']=$val['delete'];
						$MenusAccess[$val['id']]['approve']=$val['approve'];
						$MenusAccess[$val['id']]['download']=$val['download'];
					}
				}
							
			}
		}
		
		return $MenusAccess;
	}
	function reconstruction_tree($parent_id=0,$data=array()){
		$menus=array();
		foreach($data as $key=>$value){
			$index=count($menus);
			if($value['parent_id']==$parent_id){
				$menus[$index]=$value;
				if(count($value) >1){
					$menus[$index]['detail']=$value;	
				}
				//unset print
				unset($data[$key]);
				if($child=reconstruction_tree($value['id'],$data)){
					$menus[$index]['child']=$child;	
				}
			}
		}
		return $menus;
	}
	function generate_tree($data=array(),$depth=0,$nilai=array()){
		
		if(isset($data) && $data){
			foreach($data as $key=>$value){
				echo create_datas($value,$nilai);
				if(array_key_exists('child',$value)){
					generate_tree($value['child'],$nilai);	
				}
			}
		}
	}
	
	function create_datas($value=array(),$data=array()){				
			$template='<div class="form-group alert alert-secondary">';
			$state['read']		= (isset($data[$value['id']]['read']) && $data[$value['id']]['read'] == 1) ? ' checked="checked"' : '';
			$state['create']	= (isset($data[$value['id']]['create']) && $data[$value['id']]['create'] == 1) ? ' checked="checked"' : '';
			$state['update']	= (isset($data[$value['id']]['update']) && $data[$value['id']]['update'] == 1) ? ' checked="checked"' : '';
			$state['delete']	= (isset($data[$value['id']]['delete']) && $data[$value['id']]['delete'] == 1) ? ' checked="checked"' : '';
			$state['download']	= (isset($data[$value['id']]['download']) && $data[$value['id']]['download'] == 1) ? ' checked="checked"' : '';
			$state['approve']	= (isset($data[$value['id']]['approve']) && $data[$value['id']]['approve'] == 1) ? ' checked="checked"' : '';
			
			$template.=		'<label for="'.$value['id'].'" class="text-bold">
								<strong># <span class="text-info">'.$value['name'].'</span></strong>
							</label>
							<input type="hidden" name="tree['.$value['id'].'][menu_id]" value="'.$value['id'].'">
						<div class="row">';
			$template.=		'<div class="col-4">
								<input type="checkbox" class="read_chk form-check-input" name="tree['.$value['id'].'][read]" id="read'.$value['id'].'" value="1" '.$state['read'].'/>
								<label class="label-checkbox" for="read'.$value['id'].'">READ</label>								
							</div>';
			$template.=		'<div class="col-4">
								<input type="checkbox" class="form-check-input create_chk" name="tree['.$value['id'].'][create]" id="create'.$value['id'].'" value="1" '.$state['create'].'>
								<label class="label-checkbox" for="create'.$value['id'].'">ADD</label>								
							</div>';
			$template.=		'<div class="col-4">
								<input type="checkbox" class="form-check-input update_chk" name="tree['.$value['id'].'][update]" id="update'.$value['id'].'" value="1" '.$state['update'].'>
								<label class="label-checkbox" for="update'.$value['id'].'">UPD</label>
							</div>
						</div>
						<div class="row">';				
			$template.=		'<div class="col-4">
								<input type="checkbox" class="form-check-input delete_chk" name="tree['.$value['id'].'][delete]" id="delete'.$value['id'].'" value="1" '.$state['delete'].'>
								<label class="label-checkbox" for="delete'.$value['id'].'">DEL</label>
							</div>';
			$template.=		'<div class="col-4">
								<input type="checkbox" class="form-check-input approve_chk" name="tree['.$value['id'].'][approve]" id="approve'.$value['id'].'" value="1" '.$state['approve'].'>
								<label class="label-checkbox" for="approve'.$value['id'].'">APP</label>
							</div>';
			$template.=		'<div class="col-4">
								<input type="checkbox" class="form-check-input download_chk" name="tree['.$value['id'].'][download]" id="download'.$value['id'].'" value="1" '.$state['download'].'>
								<label class="label-checkbox" for="download'.$value['id'].'">DOWN</label>
							</div>';
			
			
			$template.='	</div>
						</div>';
		//echo $template;
		return $template;
	}
	
	function getExtension($str) {

		 $i = strrpos($str,".");
		 if (!$i) { return ""; } 

		 $l = strlen($str) - $i;
		 $ext = substr($str,$i+1,$l);
		 return $ext;
	}
	
	function ImageResizes($data,$location,$NewName=NULL){
		 $CI 			=& get_instance();
		 $image 		= $data["name"];
		 $uploadedfile 	= $data['tmp_name'];
		 $Arr_Return	= array();
		 if ($image){
			$filename 	= stripslashes($data['name']);
			$extension 	= getExtension($filename);
			$extension 	= strtolower($extension);
			if (($extension != "jpg") && ($extension != "jpeg") && ($extension != "png") && ($extension != "gif")) {
					$Arr_Return	= array(
						'status'	=> 2,
						'pesan'		=> 'File ekstension tidak valid.....'
					);
										
					
			}else{
				$size	= filesize($data['tmp_name']);
				// cek image size		 
				if ($size > (3840*3840))	{
					$Arr_Return	= array(
						'status'	=> 2,
						'pesan'		=> 'Ukuran File terlalu besar......'
					);				
					 
				}else{
	 
					if($extension=="jpg" || $extension=="jpeg" ){
						$uploadedfile = $data['tmp_name'];
						$src = imagecreatefromjpeg($uploadedfile);
					}else if($extension=="png"){
						$uploadedfile = $data['tmp_name'];
						$src = imagecreatefrompng($uploadedfile);
					}else {
						$src = imagecreatefromgif($uploadedfile);
					}
		 
					list($width,$height)=getimagesize($uploadedfile);
		
					$newwidth	= 1024;
					$newheight	= ($height/$width)*$newwidth;
					$tmp		= imagecreatetruecolor($newwidth,$newheight);		
					imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);
					$uploaddir 	= './uploads/'.$location.'/';
					if($NewName){
						$filename = $uploaddir.$NewName.'.'.$extension;
					}else{
						$filename = $uploaddir.$data['name'];
					}
					if (!file_exists($uploaddir)) {
						mkdir($uploaddir, 0777, true);
					}
					if (file_exists($filename)) {
						unlink($filename);
					}
					
					imagejpeg($tmp,$filename,100);
		
					imagedestroy($src);
					imagedestroy($tmp);
					
				}
	
			}
		}
		
		return $Arr_Return;
	} 
	
	function Enkripsi($sData){ 
		$sResult = '';
		$CI 			= get_instance();
		$sKey			= $CI->config->item("login_key");
		for($i=0;$i<strlen($sData);$i++){
			$sChar    = substr($sData, $i, 1);
			$sKeyChar = substr($sKey, ($i % strlen($sKey)) - 1, 1);
			$sChar    = chr(ord($sChar) + ord($sKeyChar));
			$sResult .= $sChar;
		}
		return Enkripsi_base64($sResult);
	}
	
	function Dekripsi($sData){
		$CI 		= get_instance();
		$sKey		= $CI->config->item("login_key");
		$sResult 	= '';
		$sData   	= Dekripsi_base64($sData);
		for($i=0;$i<strlen($sData);$i++){
			$sChar    = substr($sData, $i, 1);
			$sKeyChar = substr($sKey, ($i % strlen($sKey)) - 1, 1);
			$sChar    = chr(ord($sChar) - ord($sKeyChar));
			$sResult .= $sChar;
		}
		return $sResult;
	}
	
	function Enkripsi_base64($sData){
		$sBase64 = base64_encode($sData);
		return strtr($sBase64, '+/', '-_');
	}
	
	function Dekripsi_base64($sData){
		$sBase64 = strtr($sData, '-_', '+/');
		return base64_decode($sBase64);
	}
	
	function access_menu_child($Groupid ='', $Parentid =''){
		$CI 			=& get_instance();
		
		$WHERE			= "menus.active = '1' AND NOT(menus.parent_id IS NULL OR menus.parent_id ='0' OR menus.parent_id ='')";
		if($Groupid != '1'){
			if(!empty($WHERE))$WHERE .=" AND ";
			$WHERE	.="group_menus.read = 'Y' AND group_menus.group_id = '".$Groupid."'";
		}
		
		if($Parentid){
			$rows_Parent	= $CI->db->get_where('menus',array('LOWER(path)' => strtolower($Parentid)))->result();
			if($rows_Parent){
				if(!empty($WHERE))$WHERE .=" AND ";
				$WHERE	.="menus.parent_id = '".$rows_Parent[0]->id."'";
			}
		}
		
		$Qry_Select		= "SELECT menus.* FROM menus WHERE ".$WHERE." ORDER BY menus.name ASC";
		if($Groupid != '1'){
			$Qry_Select		= "SELECT menus.* FROM menus INNER JOIN group_menus ON menus.id=group_menus.menu_id WHERE ".$WHERE." ORDER BY menus.name ASC";
		}
		$rows_Menus		= $CI->db->query($Qry_Select)->result();
		
		return $rows_Menus;
		
		
	}
	
	function get_menu_parent($menu_path =''){
		$CI 			=& get_instance();
		$Menu_Parent	= '';
		$rows_Menu 		= $CI->db->get_where('menus',array('LOWER(path)'=>strtolower($menu_path)))->result();
		if($rows_Menu){
			$Parent_Code	= $rows_Menu[0]->parent_id;
			$rows_Parent	= $CI->db->get_where('menus',array('id'=>$Parent_Code))->result();
			if($rows_Parent){
				$Menu_Parent	= $rows_Parent[0]->menu_path;
			}
		}
		return $Menu_Parent;
		
		
		
	}
	
?>