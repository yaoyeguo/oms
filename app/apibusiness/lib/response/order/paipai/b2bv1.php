<?php
/**
* paipai(拍拍平台)分销订单处理 版本一
*
* @category apibusiness
* @package apibusiness/response/order/paipai
* @author chenping<chenping@shopex.cn>
* @version $Id: b2bv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_paipai_b2bv1 extends apibusiness_response_order_paipai_abstract
{

    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function canAccept()
    {

        $this->_apiLog['info']['msg'] = '拍拍分销订单暂时不接收';

        return false;
    }
    
    /**
     * 插件
     *
     * @return void
     * @author 
     **/
    public function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'sellingagent';

        return $plugins;
    }
}