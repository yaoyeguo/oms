<?php
/**
 * 分片导出任务类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_exportsplit extends taskmgr_task_abstract {

    protected $_process_id = 'task_id';

    protected $_gctime = 3600;

    protected $_timeout = 180;

    protected $_retry = true;
}