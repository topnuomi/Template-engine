<?php

/**
 * 自带标签库
 */
class Tags extends TagLib
{

    /**
     * 标签描述
     * @var array
     */
    public $tags = [
        'php' => ['attr' => '', 'close' => 1],
        'if' => ['attr' => 'condition', 'close' => 1],
        'else' => ['attr' => 'condition', 'close' => 0],
        'loop' => ['attr' => 'from,to,index,key', 'close' => 1],
        'assign' => ['attr' => 'name,value', 'close' => 0],
        'switch' => ['attr' => 'name', 'close' => 1],
        'case' => ['attr' => 'value', 'close' => 1],
    ];


    /**
     * php标签
     * @param $attr
     * @param $content
     * @return string
     */
    public function _php($attr, $content)
    {
        return '<?php ' . $content . ' ?>';
    }

    /**
     * if标签
     * @param $attr
     * @param $content
     * @return string
     */
    public function _if($attr, $content)
    {
        $attr['condition'] = $this->_parseCondition($attr['condition']);
        $parse = '<?php if (' . $attr['condition'] . '): ?>';
        $parse .= $content;
        $parse .= '<?php endif; ?>';
        return $parse;
    }

    /**
     * else标签
     * @param $attr
     * @return string
     */
    public function _else($attr)
    {
        if (isset($attr['condition'])) {
            $attr['condition'] = $this->_parseCondition($attr['condition']);
            $parse = '<?php elseif (' . $attr['condition'] . '): ?>';
        } else {
            $parse = '<?php else: ?>';
        }
        return $parse;
    }

    /**
     * loop标签
     * @param $attr
     * @param $content
     * @return string
     */
    public function _loop($attr, $content)
    {
        $parse = '<?php ' . (isset($attr['key']) ? '$' . $attr['key'] . ' = 0; ' : '');
        $parse .= 'foreach($' . $attr['from'] . ' as ' . (isset($attr['index']) ? '$' . $attr['index'] . '=>' : '') . '$' . $attr['to'] . '): ';
        $parse .= (isset($attr['key']) ? '$' . $attr['key'] . '++;' : '') . ' ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /**
     * 模版变量赋值
     * @param $attr
     * @return string
     */
    public function _assign($attr)
    {
        if (isset($attr['name']) && isset($attr['value'])) {
            $quot = (strstr($attr['value'], '\'')) ? '"' : '\'';
            $parse = '<?php $' . $attr['name'] . ' = ' . (is_numeric($attr['value']) ? $attr['value'] : $quot . $attr['value'] . $quot) . '; ?>';
            return $parse;
        }
        throw new \Exception('assign标签必须拥有属性：' . $this->defaultTags['assign']['attr']);
    }

    /**
     * switch标签
     * @param $attr
     * @param $content
     * @return string
     */
    public function _switch($attr, $content)
    {
        if (isset($attr['name'])) {
            $parse = '<?php switch ($' . $attr['name'] . '): ?>';
            $parse .= $content;
            $parse .= '<?php endswitch; ?>';
            return $parse;
        }
        throw new \Exception('switch标签必须拥有属性：' . $this->defaultTags['switch']['attr']);
    }

    /**
     * switch标签case处理
     * @param $attr
     * @param $content
     * @return string
     */
    public function _case($attr, $content)
    {
        if (isset($attr['value'])) {
            $quot = (strstr($attr['value'], '\'')) ? '"' : '\'';
            $parse = '<?php case ' . $quot . $attr['value'] . $quot . ': ?>';
            $parse .= $content;
            $parse .= '<?php break; ?>';
        } else {
            $parse = '<?php default: ?>';
            $parse .= $content;
        }
        return $parse;
    }

    /**
     * 处理if/else标签的条件比较符
     * @param $condition
     * @return mixed
     */
    private function _parseCondition($condition)
    {
        return str_ireplace([
            ' eq ',
            ' neq ',
            ' lt ',
            ' elt ',
            ' gt ',
            ' egt ',
            ' heq ',
            ' nheq '
        ], [
            ' == ',
            ' != ',
            ' < ',
            ' <= ',
            ' > ',
            ' >= ',
            ' === ',
            ' !== '
        ], $condition);
    }
}
