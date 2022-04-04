<?php
// ini_set("display_errors", "On");
// error_reporting(-1);

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
    sleep(3);
}

$config = [
    'view_wtred' => 4,
    'ui_dir_url_path' => '/xhprof/src/xhprof/xhprof_html'
];
$obj = new \Xhprof\Xhprof($config);
// echo $obj->xhprofStart();
// foo();


// 输出页面
$obj->index();
