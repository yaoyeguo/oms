<?php
/**
 * 更新订单状态(所有关于订单状态的更改的公用方法)
 * @author wangyunkai
 * @version 0.1 b
 */
class ome_order {

    /**
    * 订单编辑 iframe
    * @access public
    * @param Number $order_id 订单ID
    * @param Bool $is_request 是否发起请求
    * @param Array $ext 扩展参数
    * @return Array
    */
    function update_iframe($order_id,$is_request=true,$ext=array()){
        $instance = kernel::service('service.order');
        return $instance->update_iframe($order_id,$is_request,$ext);
    }


    /**
   * 更新订单同步状态
   * @access public
   * @param number $order_id 订单ID
   * @param String $sync_status 编辑同步状态
   * @return json
   */
   function set_sync_status($order_id,$sync_status=''){
        if (!empty($order_id) && in_array($sync_status,array('fail','success'))){
           $oOrder_sync = app::get('ome')->model('order_sync_status');
           $sdf = array(
               'order_id' => $order_id,
               'sync_status' => $sync_status == 'success' ? '2' : '1',
           );
           return $oOrder_sync->save($sdf);
       }
       return false;
    }
    
    /**
    * 订单快照 （编辑前 订单存留）
    * @access public
    * @param Number $order_id 订单ID
    * @return Array
    */
    function order_photo($order_id){
        $oOrder = app::get('ome')->model('orders');
        $opObj = app::get('ome')->model('order_pmt');
        $paymentsObj = app::get('ome')->model('payments');
        $memObj = app::get('ome')->model('members');
        $data = $oOrder->dump($order_id,"*",array("order_objects"=>array("*",array("order_items"=>array("*")))));
        $data['pmt'] = $opObj->getList('*',array('order_id'=>$order_id));//订单优惠方案
        $data['payments'] = $paymentsObj->getList('*',array('order_id'=>$order_id));//支付单
        $mem_info = $memObj->getList('*',array('member_id'=>$data['member_id']));//会员信息
        $data['mem_info'] = $mem_info[0];
        return $data;
    }

    /**
    * 获取子订单的订单号
    * @access public 
    * @param String $oid 子订单号
    * @return String 订单号
    */
    function getOrderBnByoid($oid='',$node_id=''){
        if (empty($oid)) return NULL;
        
        if ($this->order_is_exists($oid,$node_id)){
            return $oid;#子订单与订单号相同,直接返回
        }

        $objModel = app::get('ome')->model('order_objects');
        $obj_detail = $objModel->getList('order_id',array('oid'=>$oid),0,1);
        if (isset($obj_detail[0]) && $order_id = $obj_detail[0]['order_id']){
            $oModel = app::get('ome')->model('orders');
            $filter = array('order_id'=>$order_id);
            if ($node_id) {
              $shopObj = kernel::single('ome_shop');
              $shops = $shopObj->getRowByNodeId($node_id);
              $shop_id = $shops['shop_id'];
              $filter['shop_id'] = $shop_id;
            }
            
            $orders = $oModel->getRow($filter,'order_bn');
            return $orders['order_bn'];
        }else{
            return NULL;
        }
    }

    /**
    * 订单号是否存在
    * @access public
    * @param String $order_bn 订单号
    * @param String $node_id 节点ID
    * @return bool
    */
    public function order_is_exists($order_bn='',$node_id=''){
        if (empty($order_bn)) return false;
        
        $filter = array('order_bn'=>$order_bn);

        $oModel = app::get('ome')->model('orders');
        if ($node_id){
            $shopObj = kernel::single('ome_shop');
            $shops = $shopObj->getRowByNodeId($node_id);
            $shop_id = $shops['shop_id'];

            $filter['shop_id'] = $shop_id;
        }
        $orders = $oModel->getRow($filter,'order_id,shop_id');
        if (isset($orders['order_id']) && $orders['order_id']){
            $shop = kernel::single('ome_shop')->getRowByShopId($orders['shop_id']);
            $orders['shop_name'] = $shop['name'];

            return $orders;
        }else{
            return false;
        }
    }

    /**
     * 发送全链路参数
     * @param Int $status 全路径状态
     * @param Int $order_id 订单序号
     * @param String $order_bn 订单号
     * @param String $remark 备注信息
     */
    public function sendMessageProduce($status, $order_id = '', $order_bn = '', $remark = '') {
        //全链路开关
        if (!(defined('MESSAGE_PRODUCE') && MESSAGE_PRODUCE)) {
            return '';
        }
        $statusList = array(
            'taobao' => array(
                0 => array('key' => 'X_TO_SYSTEM', 'value' => ''),
                1 => array('key' => 'X_SERVICE_AUDITED', 'value' => ''),
                2 => array('key' => 'X_FINANCE_AUDITED', 'value' => ''),
                3 => array('key' => 'X_ALLOCATION_NOTIFIED', 'value' => ''),
                4 => array('key' => 'X_WAIT_ALLOCATION', 'value' => ''),
                5 => array('key' => 'X_SORT_PRINTED', 'value' => ''),
                6 => array('key' => 'X_SEND_PRINTED', 'value' => ''),
                7 => array('key' => 'X_LOGISTICS_PRINTED', 'value' => ''),
                8 => array('key' => 'X_SORTED', 'value' => ''),
                9 => array('key' => 'X_EXAMINED', 'value' => ''),
                10 => array('key' => 'X_PACKAGED', 'value' => ''),
                11 => array('key' => 'X_WEIGHED', 'value' => ''),
                12 => array('key' => 'X_OUT_WAREHOUSE', 'value' => ''),
            ),
        );
        if (empty($order_id) && empty($order_bn)) {
            return false;
        }
        $ordersModel = app::get('ome')->model('orders');
        $filter = array();
        if ($order_id) {
            $filter['order_id'] = $order_id;
        }
        if ($order_bn) {
            $filter['order_bn'] = $order_bn;
        }

        $result = '';
        $orderInfo = $ordersModel->getList('order_id,order_bn,shop_id,createway', $filter);
        if ($orderInfo) {
            //线上订单
            if ($orderInfo[0]['createway'] == 'matrix') {
                //检查店铺类型是否合法
                $shopModel = app::get('ome')->model('shop');
                $shopInfo = $shopModel->getList('node_type', array('shop_id' => $orderInfo[0]['shop_id']));
                if (in_array($shopInfo[0]['node_type'], array_keys($statusList))) {
                    $tid = $orderInfo[0]['order_bn'];
                    //获得自订单
                    $orderObjectsModel = app::get('ome')->model('order_objects');
                    $orderObjectsInfo = $orderObjectsModel->getList('oid', array('order_id' => $orderInfo[0]['order_id']));
                    $orderIds = '';
                    if ($orderObjectsInfo) {
                        foreach ($orderObjectsInfo as $v) {
                            $orderIds .= strval($v['oid']) . ',';
                        }
                        $orderIds = trim(strval($orderIds), ',');
                    }
                    else {
                        $orderIds = strval($tid);
                    }
                    $statusTitle = $statusList[$shopInfo[0]['node_type']][$status]['key'];
                    $remarkTitle = empty($remark) ? $statusList[$shopInfo[0]['node_type']][$status]['value'] : $remark;
                    $dateTime = date("Y-m-d H:i:s");
                    $params = array(
                        'topic'=>'taobao_jds_TradeTrace', 
                        'tid' => $tid,
                        'order_ids' => $orderIds,
                        'status' => $statusTitle,
                        'action_time' => $dateTime,
                        'remark' => $remarkTitle,
                    );
                    $shop_id = $orderInfo[0]['shop_id'];
                    if ($shop_id) {
                        $result = kernel::single('apibusiness_router_request')->setShopId($shop_id)->add_tmc_message_produce($params);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 根据传入参数重置订单分派及审核状态，主要用于发货单叫回以后的后续处理
     *
     * @param Array $param 传入参数
     * @return void
     * @author xiayuanjun
     **/
    public function resumeOrdStatus($param){

        $orderObj = app::get('ome')->model('orders');
        $combineObj = new omeauto_auto_combine();
        $dispatchObj = app::get('omeauto')->model('autodispatch');
        $dlyObj = app::get('ome')->model("delivery");

        //检查传入参数
        if(!empty($param['order_id']) && !empty($param['order_combine_idx']) && !empty($param['order_combine_hash'])){
            $orderInfo = $param;
        }elseif(!empty($param['order_id'])){
            $tmpInfo = $orderObj->getList('order_id,order_combine_idx,order_combine_hash',array('order_id'=>$param['order_id']), 0, 1);
            $orderInfo = $tmpInfo[0];
        }else{
            return;
        }

        $need_dispatch = true;
        //修改订单确认状态
        $opData['confirm'] = 'N';
        $opData['process_status'] = 'unconfirmed';
        $opData['pause'] = 'true';
        
        #[拆单]判断有部分拆分的有效发货单存在(确认状态为splitting) ExBOY
        if ($dlyObj->validDeiveryByOrderId($orderInfo['order_id']))
        {
            $opData['confirm'] = 'Y';
            $opData['process_status']   = 'splitting';
            $opData['pause']            = 'false';//因部分拆分后订单"基本信息"Tab没有操作按扭
            $need_dispatch = false;
        }
        
        $orderObj->update($opData,array('order_id'=>$orderInfo['order_id']));

        //订单状态重置后判断重新分派，放后面是因为分派的时候只认未拆分的订单，不然取规则的时候初始化订单信息会是空
        if($need_dispatch){
            $dispatchParams[] = array(
                'idx' => $orderInfo['order_combine_idx'],
                'hash' => $orderInfo['order_combine_hash'],
                'orders' => array (
                    0 => $orderInfo['order_id'],
                ),
            );
            
            //开始处理订单分派
            $result = $combineObj->dispatch($dispatchParams);
            if($result['did'] && $result['did']>0){
                $opData = $dispatchObj->dump($result['did'],'group_id,op_id');
            }else{
                $dispatchData = $dispatchObj->getList('group_id,op_id',array('defaulted'=>'true'));
                if($dispatchData && is_array($dispatchData[0])){
                    $opData = $dispatchData[0];
                }else{
                    $opData = array('group_id'=>'0','op_id'=>'0');
                }
            }

            $orderObj->update($opData,array('order_id'=>$orderInfo['order_id']));
        }
    }

}
