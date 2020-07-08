<?php

abstract class TagLib
{
    public $tags = [];

    /**
     * 处理点语法
     * @param $string
     * @return null|string|string[]
     */
    protected function parseDotSyntax($string)
    {
        // 处理.语法（仅数组或已实现数组访问接口的对象）
        return preg_replace_callback("/\.([a-zA-Z0-9_-]*)/", function ($match) {
            if (isset($match[1])) {
                return '[' . (is_numeric($match[1]) ? $match[1] : '\'' . $match[1] . '\'') . ']';
            } else {
                return null;
            }
        }, $string);
    }
}
