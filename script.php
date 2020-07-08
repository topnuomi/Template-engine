<?php

require 'engine/Engine.php';
require 'engine/TagLib.php';
require 'engine/Tags.php';
require 'engine/Article.php';

$t1 = microtime(true);

// 获取模板引擎实例
$config = [
    'ext' => 'html',
    'dir' => './view/',
    'left' => '<',
    'right' => '>',
];
$engine = Engine::instance($config);
// 加载自定义标签库
$engine->loadTaglib('article', Article::class);
// 读取模板内容
$template = file_get_contents('./view/index.html');
// 编译并写入
$content = $engine->compile($template);
file_put_contents('compile.php', $content);

$t2 = microtime(true);

echo '运行时间：' . ($t2 - $t1);
