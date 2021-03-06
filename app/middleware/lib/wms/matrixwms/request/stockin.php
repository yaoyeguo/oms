<?php
/**
* 入库单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_matrixwms_request_stockin extends middleware_wms_matrixwms_request_common{

    public $__stockin_type = array(
        'PURCHASE' => 'IN_PURCHASE',// 采购入库
        'ALLCOATE' => 'IN_ALLCOATE',// 调拨入库
        'DEFECTIVE' => 'IN_DEFECTIVE',// 残损入库
        'ADJUSTMENT' => 'IN_ADJUSTMENT',// 调帐入库
        'EXCHANGE' => 'IN_EXCHANGE',// 换货入库
        'OTHER' => 'IN_OTHER',// 其他入库
    );

    /**
    * 入库通知
    * @access public
    * @param Array $sdf 入库单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockin_create(&$sdf,$sync=false,$cur_page='1',$log_id=''){

        $stockin_bn = $sdf['io_bn'];

        // 状态判断,入库单状态为取消，则不发起同步
        if ( $this->iscancel($stockin_bn,'stockin') ){
            return $this->msgOutput('succ','入库单已取消,终止同步');
        }
        $stockin_bn = $sdf['io_bn'];
        $items_count = count($sdf['items']);
        $total_page = ceil($items_count/$this->limit);
        $sdf['batch_id'] = substr(self::uniqid(),0,25);
        for ($page=1;$page<=$total_page;$page++)
        {
            
            $params = $this->_getStockin_create_params($sdf,$page);
            
            $adapter_callback = array(
                'class' => __CLASS__,
                'method' => 'stockin_create_callback',
            );
            $writelog = array(
                'log_id' => $log_id,
                'log_title' => '入库单添加',
                'log_type' => 'store.trade.stockin',
                'original_bn' => $stockin_bn,
                'original_params' => serialize($sdf),
            );
            $method = 'store.wms.inorder.create';
    
            $this->request($method,$params,$writelog,$sync,$adapter_callback);

        }
        
    }

    /**
    * 入库通知callback接收
    * @access public
    * @param Array $callback_result 入库单callback结果(标准的msgOutput:middleware_message)
    * @param Array $callback_params adapter_callback参数
    * @return Array 标准输出格式
    */
    public function stockin_create_callback($callback_result,$callback_params){
        $ext_params = array('class'=>__CLASS__,'method'=>'stockin_create');

        return $this->batch_callback($callback_result,$callback_params,$ext_params);
    }
    
    
    /**
     * 入库创建通知单参数
     * @param   array sdf
     * @return  sdf
     * @access  protected
     * @author cyyr24@sina.cn
     */
    protected  function _getStockin_create_params( $sdf ,$cur_page)
    {
        $stockin_bn = $sdf['io_bn'];
        $items_count = count($sdf['items']);
        $total_page = ceil($items_count/$this->limit);
        $sdf['item_total_num'] = $sdf['line_total_count'] = $items_count;

        $sdf_items = self::getItems($sdf['items'],$cur_page,$this->limit);
        
        if ($sdf_items){
            $offset = ($cur_page-1) * $this->limit + 1;
            $items = array();
            foreach ($sdf_items as $v){
                // 获取外部商品sku
                $items[] = array(
                    'item_code' => $v['bn'],
                    'item_name' => $v['name'],
                    'item_quantity' => $v['num'],
                    'item_price' => $v['price'] ? $v['price'] : '0',// TODO: 商品价格
                    'item_line_num' => $offset,// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code' => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id' => $v['bn'],// 商品ID
                    'is_gift' => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark' => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
                $offset++;
            }
        }
        $wms_order_code = $stockin_bn;
        $order_type = $this->__stockin_type[$sdf['io_type']] ? $this->__stockin_type[$sdf['io_type']] : 'IN_OTHER';
        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);

        $params = array(
            'uniqid' => self::uniqid(),
            'out_order_code' => $stockin_bn,
            'order_type' => $order_type,
            'created' => $create_time,
            'wms_order_code' => $wms_order_code,
            'is_finished' => $cur_page >= $total_page ? 'true' : 'false',
            'current_page' => $cur_page,// 当前批次,用于分批同步
            'total_page' => $total_page,// 总批次,用于分批同步
            'logistics_code' => '',// TODO: 快递公司（如果是汇购传递快递公司，则该项目不能为空，否则可以为空处理）
            'logistics_no' => '',// TODO: 运输公司运单号
            'remark' => $sdf['memo'],
            'shipper_name' => $sdf['shipper_name'] ? $sdf['shipper_name'] : '未知',
            'shipper_zip' => $sdf['shipper_zip'] ? $sdf['shipper_zip'] : '未知',// TODO: 收货人邮政编码
            'shipper_state' => $sdf['shipper_state'] ? $sdf['shipper_state'] : '未知',// TODO: 退货人所在省
            'shipper_city' => $sdf['shipper_city'] ? $sdf['shipper_city'] : '未知',// TODO: 退货人所在市
            'shipper_district' => $sdf['shipper_district'] ? $sdf['shipper_district'] : '未知',// TODO: 退货人所在县（区），注意有些市下面是没有区的

            'shipper_address' => $sdf['shipper_address']  ? $sdf['shipper_address'] : '未知',// TODO: 收货地址（出库时非空）
            'shipper_phone' => $sdf['shipper_phone']  ? $sdf['shipper_phone'] : '未知',// TODO: 收货人电话号码（如有分机号用“-”分隔）(电话和手机必选一项) 
            'shipper_mobile' => $sdf['shipper_mobile']  ? $sdf['shipper_mobile'] : '未知',// TODO: 收货人手机号码(电话和手机必选一项) 
            'shipper_email ' => $sdf['shipper_email']  ? $sdf['shipper_email'] : '未知',// TODO: 收货人手机号码(电话和手机必选一项) 
            'total_goods_fee' => $sdf['total_goods_fee'],// 订单商品总价（精确到小数点后2位）

            'line_total_count' => $sdf['line_total_count'],// TODO: 订单行项目数量
            'item_total_num' => $sdf['item_total_num'],
            'storage_code' => $sdf['storage_code'],// 库内存放点编号
            'items' => json_encode(array('item'=>$items)),
             
        );
       return $params;
    }
    /**
    * 入库单取消
    * @access public
    * @param Array $sdf 入库单数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function stockin_cancel(&$sdf,$sync=false){

        $stockin_bn = $sdf['io_bn'];
        $order_type = $this->__stockin_type[$sdf['io_type']] ? $this->__stockin_type[$sdf['io_type']] : 'IN_OTHER';

        $params =$this->_getStockin_cancel_params($sdf);

        $writelog = array(
            'log_title' => '入库单取消',
            'log_type' => 'store.trade.stockin',
            'original_bn' => $stockin_bn,
        );
        $method = 'store.wms.inorder.cancel';

        return $this->request($method,$params,$writelog,$sync);
    }

    
    /**
     * 入库单查询
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function stockin_search(&$sdf,$sync=false)
    {
        
        $writelog = array(
            'log_title' => '入库单查询',
            'log_type' => 'store.trade.stockin',
            'original_bn' => $sdf['stockin_bn'],
        );
        $method = 'store.wms.inorder.get';
        $params = $this->_getStockin_search_params($sdf);
        return $this->request($method,$params,$writelog,$sync);
    }
    

    /**
     * 入库单查询参数
     * @param  
     * @return  
     * @access  protected
     * @author sunjing@shopex.cn
     */
    protected function _getStockin_search_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['out_order_code'],    
        );
        return $params;
    }
    /**
     * 入库单取消参数
     * @param   array sdf
     * @return  array
     * @access  protected
     * @author sunjing@shopex.cn
     */
    protected function _getStockin_cancel_params($sdf)
    {
        $stockin_bn = $sdf['io_bn'];
        $order_type = $this->__stockin_type[$sdf['io_type']] ? $this->__stockin_type[$sdf['io_type']] : 'IN_OTHER';
        $params = array(
            'out_order_code' => $stockin_bn,
            'order_type' => $order_type,
        );
        return $params;
    }
}