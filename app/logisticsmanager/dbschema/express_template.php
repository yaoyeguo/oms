<?php
$db['express_template']=array (
  'columns' =>
  array (
    'template_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 75,
      'editable' => false,
    ),
    'template_name' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
      'label' => '模板名称',
      'width' => 290,
      'unique' => true,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 10,
    ),
    'template_type' =>
    array (
      'type' => array(
        'normal' => '普通面单',
        'electron' => '电子面单',
        'delivery' => '发货面单',
        'stock' => '备货面单',
        'cainiao' => '菜鸟面单',

      ),
      'required' => true,
      'default' => 'normal',
      'label' => '面单类型',
      'width' => 110,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 20,
    ),
    'page_type' =>
    array (
      'type' => 'tinyint unsigned',
      'required' => true,
      'default' => '1',
      'label' => '纸张类型',
      'width' => 110,
    ),
    'status' =>
    array (
      'type' => 'bool',
      'default' => 'true',
      'label' => '是否启用',
      'width' => 80,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 30,
    ),
    'template_width' =>
    array (
      'type' => 'number',
      'default' => 100,
      'label' => '宽度',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 40,
    ),
    'template_height' =>
    array (
      'type' => 'number',
      'default' => 100,
      'label' => '高度',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 50,
    ),
    'file_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'editable' => false,
      'default' => 0,
      'label' => '背景文件ID',
      'width' => 75,
    ),


     'is_logo' =>
    array (
      'type' => 'bool',
      'default' => 'true',
      'label' => '打印LOGO',
      'editable' => false,
    ),
    'template_select' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'template_data' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'comment' => '模板内容',
    ),
    'is_default' => array(
        'type'            => 'bool',
        'label'           => app::get('ome')->_('默认'),
        'required'        => true,
        'default'         => 'false',
        //'default_in_list' => true,
        //'in_list'         => true,
        'width'           => 'auto',
        'order' => 90,
    ),
    'aloneBtn' => array(
            'type'            => 'bool',
            'label'           => app::get('ome')->_('独立按钮'),
            'required'        => true,
            'default'         => 'false',
            'default_in_list' => false,
            'in_list'         => true,
            'width'           => 'auto',
            'order' => 60,
        ),
        'btnName' => array(
            'type'            => 'varchar(20)',
            'label'           => app::get('ome')->_('独立按钮名称'),
            'default'         => '',
            'default_in_list' => false,
            'in_list'         => true,
            'width'           => 'auto',
            'searchtype'      => 'has',
            'filtertype'      => 'yes',
            'filterdefault'   => true,
            'order' => 70,
        ),
  ),
  'comment' => '面单模板表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);