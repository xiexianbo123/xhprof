<?php

namespace Xhprof;

class Xhprof
{
    protected $config = [];

    public function __construct($config=[])
    {
        $this->_defineConfig([]);
    }

    //页面输出
    public function index(){
        $this->_init();

        $GLOBALS['XHPROF_LIB_ROOT'] = dirname(__FILE__) . '/xhprof_lib';
        require_once $GLOBALS['XHPROF_LIB_ROOT'].'/display/xhprof.php';

//        echo "<pre>";

        $params = array('run'        => array(XHPROF_STRING_PARAM, ''),
                        'wts'        => array(XHPROF_STRING_PARAM, ''),
                        'symbol'     => array(XHPROF_STRING_PARAM, ''),
                        'sort'       => array(XHPROF_STRING_PARAM, 'wt'), // wall time
                        'run1'       => array(XHPROF_STRING_PARAM, ''),
                        'run2'       => array(XHPROF_STRING_PARAM, ''),
                        'source'     => array(XHPROF_STRING_PARAM, 'xhprof'),
                        'all'        => array(XHPROF_UINT_PARAM, 0),
        );
        xhprof_param_init($params);

        $run = $GLOBALS['run'];
        $wts = $GLOBALS['wts'];
        $symbol = $GLOBALS['symbol'];
        $sort = $GLOBALS['sort'];
        $run1 = $GLOBALS['run1'];
        $run2 = $GLOBALS['run2'];
        $source = $GLOBALS['source'];
        $all = $GLOBALS['all'];

        foreach ($params as $k => $v) {
            $params[$k] = $$k;
            if ($params[$k] == $v[1]) {
                unset($params[$k]);
            }
        }

        echo "<html>";

        echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
        $ui_dir_url_path = '/xhprof/src/xhprof/xhprof_html';
//        $ui_dir_url_path = '/xhprof/xhprof_html';
        xhprof_include_js_css($ui_dir_url_path);
        echo "</head>";

        echo "<body>";

        $vbar  = ' class="vbar"';
        $vwbar = ' class="vwbar"';
        $vwlbar = ' class="vwlbar"';
        $vbbar = ' class="vbbar"';
        $vrbar = ' class="vrbar"';
        $vgbar = ' class="vgbar"';

        $xhprof_runs_impl = new \XHProfRuns_Default();

        displayXHProfReport($xhprof_runs_impl, $params, $source, $run, $wts,
            $symbol, $sort, $run1, $run2);


        echo "</body>";
        echo "</html>";
    }

    //监听入口
    public function xhprofStart(){
        $this->_init();

        if(preg_match('/cli/i', php_sapi_name())) return;

        xhprof_enable();

        register_shutdown_function([$this, 'xhprofStop']);
    }

    public function xhprofStop(){
        $xhprof_data = xhprof_disable();

        $XHPROF_ROOT = realpath(dirname(__FILE__));
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

        $xhprof_runs = new \XHProfRuns_Default();
        $xhprof_runs->save_run($xhprof_data, "xhprof_foo");
    }

    protected function _init(){
        date_default_timezone_set('PRC');
        extension_loaded("xhprof") || trigger_error('请检查「xhprof」扩展是否安装!', E_USER_ERROR);
        extension_loaded("redis") || trigger_error('请检查「redis」扩展是否安装!', E_USER_ERROR);
    }

    // [TODO] 设计为可配置
    protected function _defineConfig($config){
        /**************  redis **************/
        define('X_REDIS_HOST', 'localhost');
        define('X_REDIS_PORT', 6379);
        define('X_REDIS_PWD', '');
        define('X_REDIS_DB', 0);
        define('X_KEY_PREFIX', 'xhprof');

        /************* 新增日志 *************/
        define('X_TIME_LIMIT', 0);      //仅记录响应超过多少秒的请求  默认0记录所有
        define('X_LOG_NUM', 1000);      //仅记录最近的多少次请求(最大值有待观察，看日志、查看响应时间) 默认1000

        /********* 日志列表页面展现 *********/
        define('X_VIEW_WTRED', 3);      //列表耗时超过多少秒标红 默认3s

        /********* 忽略URL配置 *********/
        define('X_IGNORE_URL_ARR', [
            'samples'
        ]);
    }
}
