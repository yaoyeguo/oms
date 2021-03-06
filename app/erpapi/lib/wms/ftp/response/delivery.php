<?php
/**
 * WMS 发货单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_ftp_response_delivery  extends erpapi_wms_response_delivery 
{
    public function status_update($params)
    {
        $params = parent::status_update($params);

        if ($params['items']){
            foreach ((array) $params['items'] as $key=>$item){
                $barcode = $item['bn'] ? $item['bn'] : $item['product_bn'];

                // 条码转货号
                if ($barcode) {
                    $product = app::get('ome')->model('products')->dump(array('barcode'=>$barcode),'bn');
                    $params['items'][$key]['bn'] = $params['items'][$key]['product_bn'] = $product['bn'] ? $product['bn'] : $barcode;    
                }
            }
        }

        return $params;
    }
}
