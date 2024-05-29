<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class M_master extends CI_Model {

    function __construct(){
        parent::__construct();
		$this->services = $this->load->database('default', TRUE);
    }

    function create($data, $table, $return_id = ''){
        
        $insert_data = $this->services->insert($table, $data);
        
        if($return_id != ''){
            $insert_data = $this->services->insert_id();
        }
        return  $insert_data;
    }

    function update($table, $data, $where_field = '', $where_value = ''){
        if($where_field !== '' && $where_value !== '')
        {
            $this->services->where($where_field, $where_value);
        }
        return $this->services->update($table, $data);
    }

    function delete_data($table, $field, $field_value){
        $del_data   = $this->services->delete($table, array($field => $field_value));
        return  $del_data;
    }

    function read_distinct($table, $ordered_field = '', $order_by = '', $selected_field = '', $where_field = ''){
        $this->services->distinct();
        $this->services->select($selected_field);
        $this->services->order_by($ordered_field, $order_by);

        if($where_field != ''){
            return  $this->services->get_where($table, $where_field)->result();
        } else{
            return  $this->services->get($table)->result();
        }
    }

    function read($table, $ordered_field = '', $order_by = '', $selected_field = '', $where_field = '', $where_value = '') {
        $this->services->select($selected_field);

        if($ordered_field){
            $this->services->order_by($ordered_field, $order_by);
        }       
        if($where_field != '' && $where_value != ''){
            return  $this->services->get_where($table, array($where_field => $where_value))->result();
        } else{
            return  $this->services->get($table)->result();
        }
    }
	
	 function read_where($table, $ordered_field = '', $order_by = '', $selected_field = '', $where_field = array()) {
        $this->services->select($selected_field);

        if($ordered_field){
            $this->services->order_by($ordered_field, $order_by);
        }       
        if(!empty($where_field)){
            return  $this->services->get_where($table, $where_field)->result();
        } else{
            return  $this->services->get($table)->result();
        }
    }
	function getCount($table,$where_field='',$where_value=''){
		if($where_field !='' && $where_value!=''){
			$query = $this->services->get_where($table, array($where_field=>$where_value));
		}else{
			$query = $this->services->get($table);
		}
		return $query->num_rows();
	}
	
	function getArray($table,$WHERE=array(),$keyArr='',$valArr=''){
		if($WHERE){
			$query = $this->services->get_where($table, $WHERE);
		}else{
			$query = $this->services->get($table);
		}
		$dataArr	= $query->result_array();
		
		if(!empty($keyArr)){
			$Arr_Data	= array();
			foreach($dataArr as $key=>$val){
				$nilai_id					= $val[$keyArr];
				if(!empty($valArr)){
					$nilai_val				= $val[$valArr];
					$Arr_Data[$nilai_id]	= $nilai_val;
				}else{
					$Arr_Data[$nilai_id]	= $val;
				}				
			}			
			return $Arr_Data;
		}else{
			return $dataArr;
		}
		
	}
	function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }
	
	function read_join(
        $table1, 
        $table2, 
        $match_field, 
        $ordered_field, 
        $order_by, 
        $selected_field, 
        $where_field = '', 
        $join_status = '', 
        $table3 = '', 
        $match_field2 = '', 
        $table4 = '', 
        $match_field3 = '', 
        $table5 = '', 
        $match_field4 = '')
    {
		$this->services->select($selected_field);
		$this->services->from($table1);
        
		if($join_status == ''){
			$join_status = "INNER";
		}

		$this->services->join($table2, $match_field, $join_status);

		if($match_field2 !== '' && $table3 != ''){
		   $this->services->join($table3, $match_field2, $join_status);
		}

		if($match_field3 !== '' && $table4 != ''){
		   $this->services->join($table4, $match_field3, $join_status);
		}

		if($match_field4 !== '' && $table5 != ''){
		   $this->services->join($table5, $match_field4, $join_status);
		}

		if($where_field !== ''){
		   $this->services->where($where_field);
		}

		$this->services->order_by($ordered_field, $order_by);     
        return $this->services->get()->result();
    }
	
	function check_menu($menu_id=''){
		$data		= array();
        $CI 		=&get_instance();
		
        $groupID 	= $this->session->userdata('battindo_ses_groupid');
		
		if($groupID == 1){
			$data['create']         = '1';
            $data['read']           = '1';
            $data['update']         = '1';
            $data['delete']         = '1';
            $data['approve']  		= '1';
            $data['download']  		= '1';
		}else{
			$query  = "SELECT
                        menus.*
                    FROM
                        menus
                    INNER JOIN group_menus ON menus.id = group_menus.menu_id
                    WHERE
                        group_menus.group_id = ".$this->services->escape($groupID)."
					AND menus.active = '1'
                    AND LOWER(menus.path) = ".$this->services->escape(strtolower($menu_id));
			if($this->services->query($query)->num_rows == 0){
				$data['create']         = '0';
				$data['read']           = '0';
				$data['update']         = '0';
				$data['delete']         = '0';
				$data['approve']  		= '0';
				$data['download']  		= '0';
			}
			else if($this->services->query($query)->num_rows > 0){
				$row                    = $this->services->query($query)->result_array();
				$data['create']         = $row[0]['create'];
				$data['read']           = $row[0]['read'];
				$data['update']         = $row[0]['update'];
				$data['delete']         = $row[0]['delete'];
				$data['approve']  		= $row[0]['approve'];
				$data['download']  		= $row[0]['download'];
			}
		}
		//echo"<pre>";print_r($data);exit;
        return $data;
		
    }
	
	function get_all_list($table,$field_find='*',$order_by='id ASC',$where="",$limit, $offset = 0, $search = null){

        $WHERE		="1=1";
		
		if(!empty($where)){
			if(!empty($WHERE))$WHERE	.=" AND ";
			$WHERE	.= $where;
		}

        if($search){
			if(!empty($WHERE))$WHERE	.=" AND ";
            $WHERE .= $search;
        }
        $sql = 'SELECT '.$field_find.'
                FROM '.$table.'
				WHERE 
                    '.$WHERE.'
                ORDER BY
                    '.$order_by.'
                LIMIT '.$offset.', '.$limit.'
                ';
        $query = $this->services->query($sql);
        return $query->result_array();
    }



    
	
	
	
	
	
}