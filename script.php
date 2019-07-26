<?php
require 'template/Engine.php';
require 'template/Extend.php';

$content = file_get_contents('./demo/index.html');

echo "\r\n\r\n原始内容\r\n\r\n";
print_r($content);

$template = new \template\Engine();
// 默认编译结果
$result = $template->compile($content);

echo "\r\n\r\n第一次结果\r\n\r\n";
print_r($result);

// 开始处理用户定义标签库
$extend = new \template\Extend();
$result = $extend->parseCustomizeTags($result);
// 用户定义标签库处理结束

// 最后还原raw标签
$result = $template->returnRaw($result);

echo "\r\n\r\n第二次结果\r\n\r\n";
print_r($result);
