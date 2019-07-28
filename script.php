<?php
require 'template/Engine.php';
require 'template/Extend.php';

$content = file_get_contents('./demo/index.html');
$t1 = microtime(true);
$template = \template\Engine::instance();
// 加载自定义标签库
$template->loadTaglib(\template\Extend::class);
// 编译
$result = $template->compile($content);
// 最后还原raw标签
$result = $template->returnRaw($result);
$t2 = microtime(true);
echo '执行时间' . ($t2 - $t1) . "\r\n";

file_put_contents(md5(time()) . '.php', $result);