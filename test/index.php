<?php
//ini_set("display_errors", "On");
//error_reporting(-1);

require "../vendor/autoload.php";

$obj = new \Xhprof\Xhprof();
//echo $obj->xhprofStart();

//输出页面
$obj->index();
