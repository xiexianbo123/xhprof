<?php
//
//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

//
// This file defines the interface iXHProfRuns and also provides a default
// implementation of the interface (class XHProfRuns).
//

/**
 * iXHProfRuns interface for getting/saving a XHProf run.
 *
 * Clients can either use the default implementation,
 * namely XHProfRuns_Default, of this interface or define
 * their own implementation.
 *
 * @author Kannan
 */
interface iXHProfRuns
{

    /**
     * Returns XHProf data given a run id ($run) of a given
     * type ($type).
     *
     * Also, a brief description of the run is returned via the
     * $run_desc out parameter.
     */
    public function get_run($run_id, $type, &$run_desc);

    /**
     * Save XHProf data for a profiler run of specified type
     * ($type).
     *
     * The caller may optionally pass in run_id (which they
     * promise to be unique). If a run_id is not passed in,
     * the implementation of this method must generated a
     * unique run id for this saved XHProf run.
     *
     * Returns the run id for the saved XHProf run.
     *
     */
    public function save_run($xhprof_data, $type, $run_id = null);
}


/**
 * XHProfRuns_Default is the default implementation of the
 * iXHProfRuns interface for saving/fetching XHProf runs.
 *
 * It stores/retrieves runs to/from a filesystem directory
 * specified by the "xhprof.output_dir" ini parameter.
 *
 * @author Kannan
 */
class XHProfRuns_Default implements iXHProfRuns
{

    private $dir = '';
    private $suffix = 'xhprof';

    private function gen_run_id($type)
    {
        return uniqid();
    }

    private function file_name($run_id, $type)
    {

        $file = "$run_id.$type." . $this->suffix;

        if (!empty($this->dir)) {
            $file = $this->dir . "/" . $file;
        }
        return $file;
    }

    public function __construct($dir = null)
    {
        // if user hasn't passed a directory location,
        // we use the xhprof.output_dir ini setting
        // if specified, else we default to the directory
        // in which the error_log file resides.

        if (empty($dir)) {
            $dir = ini_get("xhprof.output_dir");
            if (empty($dir)) {

                // some default that at least works on unix...
                $dir = "/tmp";

                xhprof_error("Warning: Must specify directory location for XHProf runs. " .
                    "Trying {$dir} as default. You can either pass the " .
                    "directory location as an argument to the constructor " .
                    "for XHProfRuns_Default() or set xhprof.output_dir " .
                    "ini param.");
            }
        }
        $this->dir = $dir;
    }

    public function get_run($run_id, $type, &$run_desc){
        $run_desc = "XHProf Run (Namespace=$type)";

        $redis = create_redis();
        $res = $redis->get(X_KEY_PREFIX.':xhprof_log:'.$run_id);
        return unserialize($res);
    }

    //??????????????????
    public function save_run($xhprof_data, $type, $run_id = null)
    {
        //??????????????????????????????????????????
        if (X_TIME_LIMIT > 0 && $xhprof_data['main()']['wt'] < (X_TIME_LIMIT * 1000 * 1000)) return false;

        //????????????????????????????????????????????????
        if (!isIgnore()) return false;

        //??????????????????
        $this->_checkLogNum();

        //???????????????redis
        $run_id = $this->_saveToRedis($xhprof_data);
        return $run_id;
    }


    /**
     * ??????????????????
     * @return bool
     */
    protected function _checkLogNum()
    {
        $redis = create_redis();
        $num = $redis->incr(X_KEY_PREFIX . ":run_id_num");
        if ($num > X_LOG_NUM) {
            $old_run_id = $redis->rpop(X_KEY_PREFIX . ':run_id');
            $redis->delete(X_KEY_PREFIX . ':request_log:' . $old_run_id);
            $redis->delete(X_KEY_PREFIX . ':xhprof_log:' . $old_run_id);
            $redis->decr(X_KEY_PREFIX . ':run_id_num');  //??????-1
        }
        return true;
    }

    /**
     * ???????????????redis
     * @return string
     */
    protected function _saveToRedis($xhprof_data)
    {
        $redis = create_redis();

        $run_id = uniqid();
        $redis->lPush(X_KEY_PREFIX . ":run_id", $run_id);
        $wt = 0;   //???????????????
        $mu = 0;   //???????????????
        if (!empty($xhprof_data['main()']['wt']) && $xhprof_data['main()']['wt'] > 0) {
            $wt = round($xhprof_data['main()']['wt'] / 1000000, 4);        //1???=1000??????=1000*1000??????
            $mu = round($xhprof_data['main()']['mu'] / 1024 / 1024, 4);      //???????????? ??????mb   1mb=1024kb=1024*1024b(??????)
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "";
        $row = array(
            'request_uri' => $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
            'method'      => $method,
            'wt'          => $wt,
            'mu'          => $mu,
            'ip'          => xhprof_get_ip(),
            'create_time' => time(),  //????????????
        );

        $key = X_KEY_PREFIX . ':request_log:' . $run_id;  //????????????log
        $redis->set($key, json_encode($row));



        $key = X_KEY_PREFIX . ':xhprof_log:' . $run_id;   //????????????log
        $xhprof_data_str = serialize($xhprof_data);
        $redis->set($key, $xhprof_data_str);




        return $run_id;
    }



    public function list_runs2() {
        echo "<meta charset='utf-8'>";
        echo "<hr/>Existing runs:\n<ul>\n";
        echo '<li><small class="small_filemtime">????????????</small><small class="small_wt">??????(s)</small><small class="small_wt">??????(MB)</small><small class="small_log">xhprof??????</small><small class="small_method">Method</small><small>??????url</small></li>';

        //?????????????????????
        $redis = create_redis();
        $run_id_lists = $redis->lrange(X_KEY_PREFIX.':run_id', 0, X_LOG_NUM);

        foreach ($run_id_lists as $run_id) {
            $res = $redis->get(X_KEY_PREFIX.":request_log:".$run_id);
            if(!$res) continue;

            $request_arr = json_decode($res, true);
            if(!is_array($request_arr)) continue;

            //????????????????????????
            $wtClass = $request_arr['wt'] > X_VIEW_WTRED ? "red" : "";

            echo '<li><small class="small_filemtime">'
                . date("Y-m-d H:i:s", $request_arr['create_time'])
                . '</small><small class="small_wt '.$wtClass.'">'.$request_arr['wt'].'</small></small><small class="small_wt">'.$request_arr['mu'].'</small><small class="small_log"><a href="' . htmlentities($_SERVER['SCRIPT_NAME'])
                . '?run=' . $run_id . '&source=xhprof_foo&requrl='.urlencode($request_arr['request_uri']).'">'
                . $run_id . "</a></small>"
                . '<small class="small_method">'.$request_arr['method'].'</small>'
                . "<small>".$request_arr['request_uri']."</small></li>\n";
        }
        echo "</ul>\n";
    }

    public function list_runs() {
        //?????????????????????
        $redis = create_redis();
        $run_id_lists = $redis->lrange(X_KEY_PREFIX.':run_id', 0, X_LOG_NUM);

        $table_html = "";
        foreach ($run_id_lists as $run_id) {
            $res = $redis->get(X_KEY_PREFIX.":request_log:".$run_id);
            if(!$res) continue;

            $request_arr = json_decode($res, true);
            if(!is_array($request_arr)) continue;

            //????????????????????????
            $wtClass = $request_arr['wt'] > X_VIEW_WTRED ? "red" : "";

            $arr = parse_url($_SERVER['REQUEST_URI']);
            $tr = '<tr>'
                . '<td>'.$request_arr['method'].'</td>'
                . '<td><a href="' . htmlentities($arr['path']). '?all=1&run=' . $run_id . '&source=xhprof_foo&requrl='.urlencode($request_arr['request_uri']).'">'. $request_arr['request_uri'] . "</a></td>"
                .'<td>'. date("Y-m-d H:i:s", $request_arr['create_time']). '</td>'
                .'<td class="'.$wtClass.'">'.$request_arr['wt'].'</small></small>'
                . '<td>'.$request_arr['mu'].'</td>'
                . '<td>'.$request_arr['ip'].'</td>'
                . '</tr>';
            $table_html .= $tr;
        }

        $str_html=<<<HTML
<div class="container-fluid" style="width: 90%">
<div class="row">
<div class="col-xs-12">
<!--???????????????????????? HTML ??????-->
<table id="table_id_example" class="table table-bordered table-hover">
    <thead>
        <tr>
            <th width="40">??????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th width="90">????????????(s)</th>
            <th width="100">????????????(Mb)</th>
            <th width="100">IP??????</th>
        </tr>
    </thead>
    <tbody>
        {$table_html}
    </tbody>
</table>
</div>
</div>
</div>


HTML;

        echo $str_html;
    }
}
