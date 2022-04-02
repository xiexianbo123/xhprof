<?php
//ini_set("display_errors", "On");
//error_reporting(-1);

require "../vendor/autoload.php";


function bar($x) {
    if ($x > 0) {
        bar($x - 1);
    }
}

function foo() {
    for ($idx = 0; $idx < 101; $idx++) {
        bar($idx);
        $x = strlen("abc");
    }
}


$obj = new \Xhprof\Xhprof();
//echo $obj->xhprofStart();
//foo();


//输出页面
$obj->index();
