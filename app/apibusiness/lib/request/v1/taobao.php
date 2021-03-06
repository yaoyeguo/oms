<?php
/**
* taobao(淘宝平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: taobao.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_taobao extends apibusiness_request_partyabstract
{
    /**
     * 取得发货接口名
     *
     * @return void
     * @author 
     **/
    protected function delivery_api($delivery = '')
    {
        if ('on' == app::get(self::_APP_NAME)->getConf('ome.delivery.method') && $delivery['order']['sync'] == 'none') {
            $api_name = LOGISTICS_ONLINE_RPC;
        } else {
            $api_name = $delivery['is_cod'] == 'true' ? LOGISTICS_ONLINE_RPC : LOGISTICS_OFFLINE_RPC;
        }

        return $api_name;
    }// TODO TEST

    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        $param = array(
            'tid'          => $delivery['order']['order_bn'],
            'company_code' => trim($delivery['dly_corp']['type']),
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
        );
        
        /*------------------------------------------------------ */
        //-- [是否拆单]增加参数回写  ExBOY
        /*------------------------------------------------------ */
        if($delivery['is_split'] == 1 && !empty($delivery['oid_list']))
        {
            #[部分回写]捆绑商品去重oid_list
            $oid_list    = explode(',', $delivery['oid_list']);
            $oid_list    = array_unique($oid_list);
            $oid_list    = implode(',', $oid_list);
            
            $param['is_split']  = $delivery['is_split'];
            $param['oid_list']  = $oid_list;
        }

        #判断是否开启唯一码回写
        if (app::get('wms')->getConf('wms.product.serial.delivery')=='true') {
            $deliveryObj = app::get('ome')->model('delivery');
            $serial = $deliveryObj->getProductserial($delivery['delivery_id']);


            if ($serial) {
                $param['feature'] = $serial;
            }
        }
        
        return $param;
    }// TODO TEST

    /**
     * 取得库存回写接口名
     *
     * @return void
     * @author 
     **/
    protected function stock_api($stocks){

        switch($this->_shop['business_type']){
            case 'fx':
                $api_name = UPDATE_FENXIAO_ITEMS_QUANTITY_LIST_RPC;
            break;
            default:
                $api_name = UPDATE_ITEMS_QUANTITY_LIST_RPC;
            break;            
        }

        return $api_name;
    }
    
   
    /**
     * 取得退款申请对应状态接口名     *
     * @return void
     * @author 
     **/
    protected function refund_apply_api($status)
    {
        $api_method = '';
        switch($status){
            case '3':
                $api_method = REFUSE_REFUND;
            break;
        }

        return $api_method;
    }//

    protected function format_refund_applyParams($refund,$status){
        $oRefund_taobao = app::get(self::_APP_NAME)->model('refund_apply_taobao');
        $refund_taobao = $oRefund_taobao->dump(array('refund_apply_bn'=>$refund['refund_apply_bn']));
        
        $batchList = kernel::single('ome_refund_apply')->return_batch('accept_refund');
        $params = array(
            'refund_id'  =>$refund['refund_apply_bn'],
            'refuse_proof'=>$refund['refuse_proof'],
            'refuse_message'=>$refund['refuse_message'],
        );
        if ($status == '3') {#退款单拒绝
            $params['oid'] = $refund_taobao['oid'];
        }

        return $params;
    }

    protected function format_aftersale_params($aftersale,$status){
        $shop_id = $this->_shop['shop_id'];
        $oReturn_tb = app::get(self::_APP_NAME)->model('return_product_taobao');
        $return_tb = $oReturn_tb->dump(array('shop_id'=>$shop_id,'return_id'=>$aftersale['return_id']));
        
        $params = array(
            'refund_id'=>$aftersale['return_bn'],    
        );
        $params['oid'] = $return_tb['oid'];
        $oReturn_address = app::get(self::_APP_NAME)->model('return_address');
        $address = $oReturn_address->getDefaultAddress($shop_id);
        switch ($status) {
            case '3':
                $batchList = kernel::single('ome_refund_apply')->return_batch('accept_return');
                $return_batch = $batchList[$shop_id];
                $params['receiver_name'] = $return_tb['reship_name'] ? $return_tb['reship_name'] : $address['contact_name'];
                $params['receiver_address'] = $return_tb['reship_addr']? $return_tb['reship_addr'] :$address['address'];
                $params['receiver_zip'] = $return_tb['reship_zip'] ? $return_tb['reship_zip'] : $address['zip_code'];
                $params['receiver_phone'] = $return_tb['reship_phone'] ? $return_tb['reship_phone']: $address['tel'];
                $params['receiver_mobile'] = $return_tb['reship_mobile'] ? $return_tb['reship_mobile']:$address['mobile_phone'];
                $params['memo'] = $return_batch['memo'];
                
            break;
            case '5':
                $params['refuse_message'] = $aftersale['refuse_message'];
                $params['refuse_proof']   = $aftersale['refuse_proof'];
               
      
            break;
        }
        return $params;
    }
    protected function aftersale_api($status){
        $api_method = '';
        switch( $status ){
            case '3':
                $api_method = AGREE_RETURN_GOOD;
            break;
            case '5':
                $api_method = REFUSE_REFUND;
            break;
        }
        return $api_method;
    }
    /**
     * 退款留言凭证回写
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    public function add_refundmemo($refundinfo)
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($refundinfo['order_id'], 'order_bn');
        $params['refund_id']        = $refundinfo['refund_apply_bn'];
        $params['content'] = $refundinfo['content'];
        $params['image'] = $refundinfo['imagebinary'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'add_refundmemo_callback',
        );
       
        $title = '店铺('.$this->_shop['name'].')添加[退款留言]'.'(订单号:'.$order['order_bn'].'退款单号:'.$refundinfo['refund_apply_bn'].')';;

        $shop_id = $this->_shop['shop_id'];

        $addon['bn'] = '';
        
        $this->_caller->request(ADD_REFUND_MESSAGE,$params,$callback,$title,$shop_id,10,false,$addon);
    }

    public function add_refundmemo_callback($result)
    {

        return $this->_caller->callback($result);
    }

    
    
   
    
    
    /**
     * 物流单号回填
     * @param    
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function update_return_logistics($reshipinfo)
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($reshipinfo['order_id'], 'order_bn');
        $confirm_result = '1';
        if ($reshipinfo['is_check'] == '9') {
            $confirm_result = '2';
        }
        $reship_bn = $reshipinfo['reship_bn'];
        #取退货单上的

        $oReturn_tmall = app::get(self::_APP_NAME)->model('return_product_tmall');
        $return_tmall = $oReturn_tmall->dump(array('return_bn'=>$reshipinfo));

        $params['refund_id']        = $reshipinfo['reship_bn'];
        $params['refund_phase '] = $return_tmall['refund_phase'];
        $params['confirm_result '] = $confirm_result;
        $params['company_code']=$reshipinfo['return_logi_name'];
        $params['sid'] = $reshipinfo['return_logi_no'];
        $params['operator']=kernel::single('desktop_user')->get_name();;
        $params['confirm_time']=date('Y-m-d H:i:s',$reshipinfo['t_end']);
        $callback = array(
            'class' => get_class($this),
            'method' => 'update_return_logistics_callback',
        );
       
        $title = '店铺('.$this->_shop['name'].')回填退回物流单号物流公司'.'(订单号:'.$order['order_bn'].'退货单号:'.$reshipinfo['reship_bn'].')';;

        $shop_id = $this->_shop['shop_id'];

        $addon['bn'] = '';
        
        $this->_caller->request(REFUND_GOOD_RETURN_CHECK,$params,$callback,$title,$shop_id,10,false,$addon);
    }

    
    
    public function update_return_logistics_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 获取电子面单
     *
     * @param Array $data
     * @return void
     * @author shshuai
     **/
    public function get_waybill_number($data){
        $param = array(
            'user_id' => $this->_shop['addon']['tb_user_id'],
            'num' => $data['num'],
            'service_code' => $data['service_code'],
            'out_biz_code' => $data['out_biz_code'],
            'pool_type' => $data['pool_type'],
        );
        $callback = array(
           'class' => get_class($this),
           'method' => 'get_waybill_number_callback',
        );
        $shop_id = $this->_shop['shop_id'];
        $title = '店铺('.$this->_shop['name'].')获取电子面单';

        $log_id = $this->_caller->request(GET_WAYBILL_NUMBER,$param,$callback,$title,$shop_id,10,false,$addon);

        return true;
    }

    public function get_waybill_number_callback($result) {
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();
        $data = $result->get_data();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];

        $ret = $this->_caller->callback($result);

        $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
        if ($status == 'succ' && $data['tms_waybill_list']['string']){
            $waybillObj = app::get('logisticsmanager')->model('waybill');
            $channelObj = app::get('logisticsmanager')->model('channel');
            $wlbObj = kernel::single('logisticsmanager_waybill_wlb');
            $logistics_code = $wlbObj->logistics_code($request_params['service_code'],$request_params['pool_type']);
            //获取单号来源信息
            $cFilter = array(
                'shop_id' => $shop_id,
                'logistics_code' => $logistics_code,
                'status'=>'true',
            );
            $channel = $channelObj->dump($cFilter);

            //保存数据
            if($channel['channel_id'] && $logistics_code) {
                foreach($data['tms_waybill_list']['string'] as $val){
                    $waybill = array();
                    $waybill = $waybillObj->dump(array('waybill_number'=>$val),'id');
                    if(!$waybill['id'] && $val) {
                        $logisticsNo = array(
                            'waybill_number' => $val,
                            'channel_id' => $channel['channel_id'],
                            'logistics_code' => $logistics_code,
                            'status' => 0,
                        );
                        $waybillObj->insert($logisticsNo);
                    }
                    unset($val,$logisticsNo,$waybill);
                }
                $waybillLogObj->update(array('status'=>'success'),array('log_id'=>$request_params['out_biz_code']));
            }
        } else {
            $waybillLogObj->update(array('status'=>'fail'),array('log_id'=>$request_params['out_biz_code']));
        }

        return $ret;
    }

    /**
     +----------------------------------------------------------
     * [拆单]淘宝回写参数格式化 ExBOY
     +----------------------------------------------------------
     * return   array   $delivery
     +----------------------------------------------------------
     */
    protected function format_delivery($delivery)
    {
        $delivery   = parent::format_delivery($delivery);
        $order_id   = $delivery['order']['order_id'];
        $order_bn   = $delivery['order']['order_bn'];
        
        #售后发货单直接回写$delivery
        if($delivery['type'] == 'reject')
        {
            return $delivery;
        }
        
        #[发货配置]拆单发货回写方式
        $orderSplitLib    = kernel::single('ome_order_split');
        
        $split_seting     = $orderSplitLib->get_delivery_seting();
        $split_model      = $split_seting['split_model'];
        if($split_model == 0)
        {
            return $delivery;//未开启拆单,直接回写
        }
        
        #判断订单是否已拆单
        $chk_split  = $orderSplitLib->check_order_is_split($delivery, true);
        if($chk_split == false)
        {
            return $delivery;//未进行拆单操作,直接回写
        }
        
        #判断"拆单方式"配置是否变更
        $is_change_set  = false;
        $change_split   = $orderSplitLib->get_split_setup_change($order_id);
        
        if(!empty($change_split))
        {
            if($change_split['old_split_model'] == 2)
            {
                return $delivery;//直接回写全部[按SKU拆单]
            }
            elseif($change_split['old_split_model'] == 1)
            {
                $split_model    = 1;//延续上次的[按子订单方式]回写
                $is_change_set  = true;//与"余单撤消"处理过程一致
            }
        }
        
        #拆单回写方式
        if($split_model == 1)
        {
            //获取淘宝订单进入ERP的原始数据
            $mdl_orddly = app::get('ome')->model('order_delivery');
            $getData    = $mdl_orddly->getList('id, bn, oid', array('order_bn'=>$order_bn), 0, 1);
            $getData    = $getData[0];

            if(empty($getData['oid']))
            {
                return $delivery;//没有淘宝原数据对比,直接回写
            }
            $bn_data   = unserialize($getData['bn']);
            $oid_data  = explode(',', $getData['oid']);
            
            //过滤财务退款确认中申请的商品明细
            if($delivery['order']['pay_status'] == '4' || $delivery['order']['pay_status'] == '5')
            {
                $refund_list    = $orderSplitLib->getRefundBnList($order_id, $delivery['delivery_id'], $bn_data, $oid_data);
                if($refund_list)
                {
                    $bn_data    = $refund_list['bn_data'];
                    $oid_data   = $refund_list['oid_data'];
                }
            }
            
            /*------------------------------------------------------ */
            //-- “余单撤消”回写操作
            /*------------------------------------------------------ */
            $remain_cancel  = $orderSplitLib->order_remain_cancel($order_id);
            if($remain_cancel || $is_change_set)
            {
                if($is_change_set)
                {
                    $delivery_data  = $orderSplitLib->get_delivery_succ($order_id, $delivery['delivery_id']);//配置变更_排除本次的delivery_id
                }
                else 
                {
                    $delivery_data  = $orderSplitLib->get_delivery_succ($order_id);//获取订单关联的所有成功发货的"发货单"
                }
                
                if(empty($delivery_data))
                {
                    return $delivery;
                }
                
                $delivery_ids   = array();
                foreach ($delivery_data as $key => $val)
                {
                    $delivery_ids[] = $val['delivery_id'];
                }
                
                //发货单明细
                $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
                $develiy_items = $deliItemModel->getList('product_id, bn, number', array('delivery_id'=>$delivery_ids));
                
                //获取购买商品的bn
                $goods_bn     = array();
                foreach($develiy_items as $key => $item)
                {
                    $val_bn            = trim($item['bn']);
                    $goods_bn[$val_bn] = $val_bn;
                }
                
                //获取订单原始数据[货号对应的oid]
                $buy_oid   = array();
                foreach($bn_data as $key => $val)
                {
                    $val          = trim($val);
                    if(!empty($goods_bn[$val]))
                    {
                        $buy_oid[]   = $oid_data[$key];
                    }
                }
                $buy_oid    = array_diff($oid_data, $buy_oid);
                
                if(!empty($buy_oid))
                {
                    $delivery['oid_list'] = implode(',', $buy_oid);
                    $delivery['is_split'] = 1;
                }
                
                return $delivery;
            }
            
            /*------------------------------------------------------ */
            //-- 判断订单中购买商品如果被"删除或调换"则直接回写
            /*------------------------------------------------------ */
            
            //获取现系统中的订单数据
            $in_oids        = '';
            foreach ($oid_data as $key => $val)
            {
                $in_oids   .= ",'".$val."'";
            }
            $in_oids    = substr($in_oids, 1);

            $sql        = "SELECT oi.item_id, oi.delete, ob.obj_id, ob.oid FROM sdb_ome_order_items AS oi LEFT JOIN sdb_ome_order_objects AS ob ON oi.obj_id=ob.obj_id 
                            WHERE oi.order_id='".$order_id."' AND ob.oid in(".$in_oids.")";
            $result     = kernel::database()->select($sql);
            
            $erp_oids   = array();
            foreach ($oid_data as $key_j => $val_j)
            {
                foreach ($result as $key => $item)
                {
                    $val_oid    = $item['oid'];
                    if($val_oid == $val_j && $item['delete']=='false')
                    {
                        $erp_oids[$val_oid] = $val_oid;
                    }
                }
            }
            $chk_data   = array_diff($oid_data, $erp_oids);
            
            //有变更的订单商品，直接回写
            if(!empty($chk_data))
            {
                return $delivery;
            }
            
            /*------------------------------------------------------ */
            //-- 按淘宝子订单方式进行拆单回写 [回写多次]          
            /*------------------------------------------------------ */
            
            //发货单明细
            $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
            $develiy_items = $deliItemModel->getList('product_id, bn, number', array('delivery_id'=>$delivery['delivery_id']));
            
            //获取购买商品的bn
            $goods_bn     = array();
            foreach($develiy_items as $key => $item)
            {
                $val_bn            = trim($item['bn']);
                $goods_bn[$val_bn] = $val_bn;
            }
            
            //获取订单原始数据[货号对应的oid]
            $buy_oid   = array();
            foreach($bn_data as $key => $val)
            {
                $val          = trim($val);
                if(!empty($goods_bn[$val]))
                {
                    $buy_oid[]   = $oid_data[$key];
                }
            }
            
            if(!empty($buy_oid))
            {
                $delivery['oid_list'] = implode(',', $buy_oid);
                $delivery['is_split'] = 1;
            }
        }
        else 
        {
            //按sku进行拆单,只回写一次[无需处理，直接回写]
        }
        
        return $delivery;
    }
}
