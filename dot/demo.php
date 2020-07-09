<?php

class Template
{
    /**
     * 解析变量输出
     * @param string $variable
     * @return string
     */
    public function parseVariable($variable)
    {
        // 处理|函数调用
        if (strstr($variable, '|')) {
            $functions = explode('|', $variable);
            // 赋值初始执行结果
            $executeResult = $functions[0];
            // 只留下函数表达式
            unset($functions[0]);
            // 重置调用函数数组索引以便开始foreach循环
            $functions = array_values($functions);
            foreach ($functions as $function) {
                $expParameters = explode('=', $function);
                $functionName = $expParameters[0];
                // 如果有带上参数，则进行参数处理，没有声明参数则直接将当前值作为函数的第一个参数
                if (isset($expParameters[1])) {
                    $parameters = $expParameters[1];
                    // 如果有参数，则处理，同时将占位符###替换为上次解析结果
                    // 如果存在占位符，则直接替换，没有占位符则将当前执行结果作为函数的第一个参数
                    $invokeParameters = strstr($expParameters[1], '###')
                        ? str_replace('###', $executeResult, $parameters)
                        : $executeResult . ',' . $parameters;
                    $executeResult = $functionName . '(' . $invokeParameters . ')';
                } else {
                    $executeResult = $functionName . '(' . $executeResult . ')';
                }
            }
            $variable = $executeResult;
        }

        return $this->_parseDotSyntax($variable);
    }

    /**
     * 解析点语法
     * @param string $variable
     * @return string
     */
    private function _parseDotSyntax($variable)
    {
        // 处理.语法（仅数组或已实现数组访问接口的对象）
        return preg_replace_callback("/\.([a-zA-Z0-9_-]*)/", function ($match) {
            if (isset($match[1]) && $match[1]) {
                return '[' . (is_numeric($match[1]) ? $match[1] : "'{$match[1]}'") . ']';
            } else {
                return null;
            }
        }, $variable);
    }
}

$template = new Template();

echo $template->parseVariable('$arr.0.id|md5|substr=0,1|json_encode|date="Y-m-d",###|test=1,2,###,3,4');
echo PHP_EOL;
echo $template->parseVariable('$arr.id');
echo PHP_EOL;
echo $template->parseVariable("\$arr['id']");
echo PHP_EOL;
echo $template->parseVariable('$arr.article_id|intval');
echo PHP_EOL;
echo $template->parseVariable("\$arr['article_id']|intval|strtotime");
