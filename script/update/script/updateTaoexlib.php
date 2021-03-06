<?php
/**
 * 根据传入的域名做初始化工作
 *
 * @author hzjsq@msn.com
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);
//kernel::single('base_shell_webproxy')->exec_command('cacheclean');
//kernel::single('base_shell_webproxy')->exec_command('kvstorerecovery');
//$app_info = array(
//    "exclusion" => "true",
//    "value" => "true",
//    "app_id" => "ome"
//);
//app::get('base')->setConf('system.main_app', $app_info);
kernel::single('base_shell_webproxy')->exec_command("update taoexlib --ignore-download");
