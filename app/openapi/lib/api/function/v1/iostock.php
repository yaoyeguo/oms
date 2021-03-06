<?php

class openapi_api_function_v1_iostock extends openapi_api_function_abstract implements openapi_api_function_interface{

    public function getList($params,&$code,&$sub_msg){

        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        $iostock_bn = trim($params['iostock_bn']);
        $branch_bn = trim($params['branch_bn']);
        $original_bn = trim($params['original_bn']);
        $bn = trim($params['bn']);
        $type = trim($params['io_type']);

        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }

        $iostock_data = kernel::single('openapi_data_original_iostock')->getList($start_time,$end_time,$iostock_bn,$original_bn,$branch_bn,$bn,$type,$offset,$limit);

        $iostock_arr = array();
        foreach ($iostock_data['lists'] as $k => $iostock){
            $iostock_arr[$k]['iostock_id'] = $iostock['iostock_id'];

            $iostock_arr[$k]['iostock_bn'] = $this->charFilter($iostock['iostock_bn']);
            $iostock_arr[$k]['branch_bn'] = $this->charFilter($iostock['branch_bn']);
            $iostock_arr[$k]['branch_name'] = $this->charFilter($iostock['branch_name']);
            $iostock_arr[$k]['bn'] = $this->charFilter($iostock['bn']);
            $iostock_arr[$k]['name'] = $this->charFilter($iostock['name']);
            $iostock_arr[$k]['barcode'] = $iostock['barcode'];            $iostock_arr[$k]['nums'] = $iostock['nums'];
            $iostock_arr[$k]['type'] = $this->charFilter($iostock['type_name']);
            $iostock_arr[$k]['iostock_time'] = date('Y-m-d H:i:s',$iostock['create_time']);
            $iostock_arr[$k]['memo'] = $this->charFilter($iostock['memo']);
            $iostock_arr[$k]['original_bn'] = $this->charFilter($iostock['original_bn']);
            $iostock_arr[$k]['iostock_price'] = $iostock['iostock_price'];
            $iostock_arr[$k]['unit_cost'] = $iostock['unit_cost'];
            if(isset($iostock['appropriation_no'])){
                $iostock_arr[$k]['appropriation_no'] = $iostock['appropriation_no'];
            }
            $iostock_price_num += $iostock['iostock_price']*$iostock['nums'];
            $unit_cost_num += $iostock['unit_cost']*$iostock['nums'];
        }

        unset($iostock_data['lists']);
        $iostock_data['iostock_price_num'] = sprintf("%.3f", $iostock_price_num);
        $iostock_data['unit_cost_num'] = sprintf("%.3f", $unit_cost_num);
        $iostock_data['lists'] = $iostock_arr;
        return $iostock_data;
    }

    public function add($params,&$code,&$sub_msg){
        
    }
}