<?php
/**
 * 店铺ITEM接口
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_rpc_request_shop_items {

   public function __construct($app)
    {
        //$identity = $app->getConf('inventorydepth.system.identity');
        $identity = app::get('inventorydepth')->runtask('getIdentity');

        $this->object = kernel::single("inventorydepth_{$identity}_rpc_request_shop_items");
    }

    public function __call($method,$arguments)
    {
        return call_user_func_array(array($this->object,$method), $arguments);
    }
}
