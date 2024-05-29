<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class M_contact extends CI_Model {

    function __construct(){
        parent::__construct();
    }

    function get_login($phone, $password){			
        $this->services = $this->load->database('default', TRUE);
		

        $select = "
                    t1.*
                    ,t2.name AS group_name ,t2.flag_all
                ";
        $this->services->select($select);
        $this->services->where('t1.phone', $phone);
		$this->services->where('t1.flag_active', '1');
		
		$New_Pass	= security_hash($password);
		//echo"<pre>";print_r($query);
		$this->services->where('t1.password', $New_Pass);
        
		$this->services->join('groups as t2', 't1.group_id = t2.id');
		$this->services->from('users as t1');

        $query = $this->services->get();
		
        return $query->row_array();
    }

    // function get_where_email($email)
    // {
    //     $query = "SELECT * FROM users WHERE email = '$email'";
    //     $query  = $this->db->query($query);
    //     if ($query->num_rows() > 0) {
    //         return $query->result();
    //     } else {
    //         return 0;
    //     }
    // }
}