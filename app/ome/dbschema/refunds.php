<?php
$db['refunds']=array (
  'columns' =>
  array (
    'refund_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'refund_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => '',
      'label' => '退款单号',
      'width' => 160,
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'label' => '订单号',
      'width' => 200,
      'editable' => false,
//      'in_list' => true,
//      'default_in_list' => true,
    ),
    'account' =>
    array (
      'type' => 'varchar(50)',
      'label' => '退款账号',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'bank' =>
    array (
      'type' => 'varchar(50)',
      'label' => '退款银行',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'pay_account' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收款账户',
      'width' => 110,
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'currency' =>
    array (
      'type' => 'varchar(10)',
      'label' => '货币',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'money' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'label' => '支付金额',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'paycost' =>
    array (
      'type' => 'money',
      'label' => '支付网关费用',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'cur_money' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
      'comment' => '实际支付货币金额',
    ),
    'pay_type' =>
    array (
      'type' =>
      array (
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      ),
      'default' => 'online',
      'required' => true,
      'label' => '支付类型',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'payment' =>
    array (
      'type' => 'number',
      'editable' => false,
      'comment' => '支付方式id'
    ),
    'paymethod' =>
    array (
      'type' => 'varchar(100)',
      'label' => '支付方式',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'op_id' =>
    array (
      'type' => 'table:account@pam',
      'label' => '操作员',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    't_ready' =>
    array (
      'type' => 'time',
      'label' => '支付开始时间',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'download_time' =>
    array (
      'type' => 'time',
      'label' => '单据下载时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    't_sent' =>
    array (
      'type' => 'time',
      'label' => '发款时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    't_received' =>
    array (
      'type' => 'time',
      'label' => '用户确认收款时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    'status' =>
    array (
      'type' =>
      array (
        'succ' => '支付成功',
        'failed' => '支付失败',
        'cancel' => '未支付',
        'error' => '处理异常',
        'invalid' => '非法参数',
        'progress' => '处理中',
        'timeout' => '超时',
        'ready' => '准备中',
      ),
      'default' => 'ready',
      'required' => true,
      'label' => '支付状态',
      'width' => 75,
      'editable' => false,
      'filtertype' => 'yes',
      'hidden' => true,
      'filterdefault' => true,
      'in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'comment' => '备注',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
    ),
    'trade_no' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'comment' => '支付网关的内部交易单号',
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'return_id' =>
    array (
      'type' => 'table:return_product@ome',
      'label' => '售后ID',
    ),
    'refund_refer' =>
    array(
      'type' =>
      array(
         0=>'normal',
         1=>'aftersale',
         2=>'archive',
      ),
      'default' => '0',
      'label' => '退款来源',            
    ),
    'archive' =>
    array (
      'type' => 'tinyint unsigned',
      'label' => '是否归档',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '支付记录',
  'index' =>
  array (
    'ind_refund_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'refund_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_refund_bn' =>
    array (
        'columns' =>
        array (
          0 => 'refund_bn',
        ),
    ),
    'ind_t_sent' =>
    array (
        'columns' =>
        array (
          0 => 't_sent',
        ),
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev: 41103 $',
);