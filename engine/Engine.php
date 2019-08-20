<?php

namespace engine;

/**
 * 模板标签库（支持模板继承）
 * Class Engine
 * @package template
 */
class Engine
{

    /**
     * @var null 单一实例
     */
    private static $instance = null;

    /**
     * @var string 左定界符
     */
    private $left = '<';

    /**
     * @var string 右定界符
     */
    private $right = '>';

    /**
     * @var array 标签定义
     */
    protected $tags = [];

    /**
     * @var null 模板配置
     */
    protected $config = null;

    /**
     * @var null 扩展标签库
     */
    private $extend = [];

    /**
     * @var array 扩展标签库类实例
     */
    private $extendInstance = [];

    /**
     * @var array 默认标签定义
     */
    private $defaultTags = [
        'php' => ['attr' => null, 'close' => 1],
        'if' => ['attr' => 'condition', 'close' => 1],
        'else' => ['attr' => 'condition', 'close' => 0],
        'volist' => ['attr' => 'name,index,id,key', 'close' => 1],
    ];

    /**
     * 构造方法
     * Engine constructor.
     * @throws \Exception
     */
    private function __construct()
    {
        // $this->config = Register::get('Config')->get('view');
        $this->config['dir'] = './demo/';
        if (isset($this->config['left']) && $this->config['left']) {
            $this->left = $this->config['left'];
        }
        if (isset($this->config['right']) && $this->config['right']) {
            $this->right = $this->config['right'];
        }
    }

    /**
     * 获取类单一实例
     * @return null|Engine
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 处理模板继承
     * @param $template
     * @return mixed
     */
    private function parseExtend($template)
    {
        $pattern = '/' . $this->left . 'extend.*?file=[\'"](.*?)[\'"].*?\/' . $this->right . '/is';
        preg_match($pattern, $template, $matches);
        if (!empty($matches)) {
            $blockPattern = '/' . $this->left . 'block.*?name=[\'"](.*?)[\'"]' . $this->right;
            $blockPattern .= '([\s\S]*?)' . $this->left . '\/block' . $this->right . '/is';
            // 获得被继承的模板内容
            $file = $this->config['dir'] . $matches[1] . '.html';
            $extendFileContent = null;
            if (file_exists($file)) {
                $extendFileContent = file_get_contents($file);
            }
            // 处理继承中的include标签
            $tempContent = $this->parseInclude($extendFileContent);
            $extendFileContent = $tempContent !== false ? $tempContent : $extendFileContent;
            // 被继承模板中的块
            preg_match_all($blockPattern, $extendFileContent, $extendResult);
            // 继承模板中的块
            preg_match_all($blockPattern, $template, $templateResult);
            // 组合搜索的块数组
            $search = [];
            $defaultContent = [];
            for ($i = 0; $i < count($extendResult[0]); $i++) {
                $search[$extendResult[1][$i]] = $extendResult[0][$i];
                $defaultContent[$extendResult[1][$i]] = $extendResult[2][$i];
            }
            // 组合替换的块数组
            $replace = [];
            for ($j = 0; $j < count($templateResult[0]); $j++) {
                $replace[$templateResult[1][$j]] = $templateResult[2][$j];
            }
            // 块是否在继承模板中存在
            $searchArray = [];
            $replaceArray = [];
            foreach ($search as $key => $value) {
                $searchArray[] = $value;
                if (isset($replace[$key])) {
                    $replaceArray[] = $replace[$key];
                } else {
                    $replaceArray[] = $defaultContent[$key];
                }
            }
            $template = str_replace($searchArray, $replaceArray, $extendFileContent);
        }
        return $template;
    }

    /**
     * 处理include标签
     * @param $template
     * @return null|string|string[]
     */
    private function parseInclude($template)
    {
        $pattern = '/' . $this->left . 'include.*?file=[\'"](.*?)[\'"].*?\/' . $this->right . '/is';
        $template = preg_replace_callback($pattern, function ($result) {
            $string = null;
            $file = $this->config['dir'] . $result[1] . '.html';
            if (file_exists($file)) {
                $string = file_get_contents($file);
            }
            return $string;
        }, $template);
        // 处理多层include
        if ($this->hasInclude($template)) {
            $template = $this->parseInclude($template);
        }
        return $template;
    }

    /**
     * 检测是否含有include
     * @param $template
     * @return bool
     */
    private function hasInclude($template)
    {
        $pattern = '/' . $this->left . 'include.*?file=[\'"](.*?)[\'"].*?\/' . $this->right . '/is';
        preg_match($pattern, $template, $matches);
        return !empty($matches);
    }

    /**
     * 分析参数以及函数输出
     * @param $template
     * @return mixed
     */
    private function parseVars($template)
    {
        preg_match_all('/{(.*?)}/', $template, $matches);
        $search = [];
        $replace = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $start = substr($matches[1][$i], 0, 1);
            $search[] = $matches[0][$i];
            if ($start == '$') {
                $replace[] = '<?php echo (' . $matches[1][$i] . '); ?>';
            } elseif ($start == ':') {
                $replace[] = '<?php echo (' . ltrim($matches[1][$i], ':') . '); ?>';
            } else {
                $replace[] = $matches[0][$i];
            }
        }
        $template = str_replace($search, $replace, $template);
        return $template;
    }

    /**
     * 外部加载扩展标签
     * @param $lib
     */
    public function loadTaglib($lib)
    {
        $this->extend[] = $lib;
    }

    /**
     * 标签处理
     * @param $template
     * @return null|string|string[]
     */
    private function parseTags($template)
    {
        foreach ($this->extend as $lib) {
            $this->extendInstance[$lib] = $object = new $lib;
            foreach ($object->tags as $name => $tag) {
                if (!isset($this->tags[$name]) && !isset($this->defaultTags[$name])) {
                    $this->tags[$name] = $tag;
                }
            }
        }
        $tags = array_merge($this->defaultTags, $this->tags);
        return $this->_parseTags($template, $tags);
    }

    /**
     * 获取标签处理结果
     * @param $method
     * @param $tag
     * @param string $content
     * @return null
     */
    private function getTagParseResult($method, $tag, $content = '')
    {
        if (method_exists($this, $method)) {
            return $this->{$method}($tag, $content);
        } else {
            foreach ($this->extendInstance as $item) {
                if (method_exists($item, $method)) {
                    return $item->{$method}($tag, $content);
                }
            }
            return null;
        }
    }

    /**
     * 进行标签处理
     * @param $template
     * @param $tags
     * @return null|string|string[]
     */
    private function _parseTags($template, $tags)
    {
        foreach ($tags as $name => $item) {
            $pattern = '/' . $this->left . '(?:(' . $name . ')\b(?>[^' . $this->right . ']*)|\/(' . $name . '))';
            $pattern .= $this->right . '/is';
            if ($item['close']) {
                preg_match_all($pattern, $template, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
                $nodes = [];
                if (!empty($matches)) {
                    // 将匹配结果组合为成对数组
                    $start = [];
                    foreach ($matches as $match) {
                        // 为空则为结束标签
                        if ($match[1][0] == '') {
                            $tag = array_pop($start);
                            $nodes[$match[0][1]] = [
                                'name' => $name,
                                'start' => $tag[1],
                                'end' => $match[0][1],
                                'start_str' => $tag[0],
                                'end_str' => $match[0][0],
                            ];
                        } else {
                            $start[] = $match[0];
                        }
                    }
                    unset($matches, $start);
                    krsort($nodes);

                    if (!empty($nodes)) {
                        $nodes = array_merge($nodes, []);
                        $cut = '<!--CONTENT-->';
                        $method = '_' . $name;
                        $startArray = [];
                        foreach ($nodes as $pos => $node) {
                            $attr = $item['attr'] ? $this->getAttr($node['start_str'], explode(',', $item['attr'])) : [];
                            // 得到准备替换的值
                            $replace = explode($cut, $this->getTagParseResult($method, $attr, $cut));
                            while ($startArray) {
                                $begin = end($startArray);
                                // 如果当前结束位置大于最后一个开始标签的位置，则跳过，直接去替换这个结束标签
                                if ($node['end'] > $begin['start']) {
                                    break;
                                } else {
                                    // 否则先替换掉这个标签后面的所有开始标签
                                    $begin = array_pop($startArray);
                                    $template = substr_replace($template, $begin['string'], $begin['start'], $begin['length']);
                                }
                            }
                            $template = substr_replace($template, $replace[1], $node['end'], strlen($node['end_str']));
                            $startArray[] = [
                                'start' => $node['start'],
                                'length' => strlen($node['start_str']),
                                'string' => $replace[0]
                            ];
                        }
                        // 替换掉最后入栈，未进入while循环的开始标签
                        while ($startArray) {
                            $begin = array_pop($startArray);
                            $template = substr_replace($template, $begin['string'], $begin['start'], $begin['length']);
                        }
                    }
                }
            } else { // 自闭合标签处理
                $template = preg_replace_callback($pattern, function ($matches) use ($item) {
                    $method = '_' . $matches[1];
                    $attr = $item['attr'] ? $this->getAttr($matches[0], explode(',', $item['attr'])) : [];
                    return $this->getTagParseResult($method, $attr);
                }, $template);
            }
        }
        return preg_replace('/\?>([\r|\n|\s]*?)<\?php/is', '', $template);
    }

    /**
     * 获取属性
     * @param $string
     * @param array $tags
     * @return array
     */
    private function getAttr($string, $tags = [])
    {
        $attr = [];
        $attrPattern = '/[ +](.*?)=[\'"](.*?)[\'"]/is';
        preg_match_all($attrPattern, $string, $result);
        if (isset($result[0]) && !empty($result[0])) {
            foreach ($result[1] as $key => $value) {
                $name = trim($value, ' ');
                if (in_array($name, $tags)) {
                    $attr[$name] = $result[2][$key];
                }
            }
        }
        return $attr;
    }

    /**
     * 处理raw标签
     * @param $template
     * @return null|string|string[]
     */
    private function parseRaw($template)
    {
        $pattern = '/' . $this->left . 'raw' . $this->right . '([\s\S]*?)';
        $pattern .= $this->left . '\/raw' . $this->right . '/is';
        $template = preg_replace_callback($pattern, function ($matches) {
            return str_replace([
                $this->left, $this->right,
                '{', '}'
            ], [
                '<RAW!--', '--RAW>',
                '<RAW!--', '--RAW>'
            ], $matches[1]);
        }, $template);
        return $template;
    }

    /**
     * 还原raw
     * @param $template
     * @return mixed
     */
    public function returnRaw($template)
    {
        $template = str_replace([
            '<RAW!--', '--RAW>',
            '<RAW!--', '--RAW>'
        ], [
            $this->left, $this->right,
            '{', '}'
        ], $template);
        return $template;
    }

    /**
     * php标签开始
     * @param $tag
     * @param $content
     * @return string
     */
    private function _php($tag, $content)
    {
        return '<?php ' . $content . ' ?>';
    }

    /**
     * if标签
     * @param $tag
     * @param $content
     * @return string
     */
    private function _if($tag, $content)
    {
        $parse = '<?php if (' . $tag['condition'] . '): ?>';
        $parse .= $content;
        $parse .= '<?php echo ' . $tag['condition'] . '; endif; ?>';
        return $parse;
    }

    /**
     * else标签
     * @param $tag
     * @return string
     */
    private function _else($tag)
    {
        if (isset($tag['condition'])) {
            $parse = '<?php elseif (' . $tag['condition'] . '): ?>';
        } else {
            $parse = '<?php else: ?>';
        }
        return $parse;
    }

    /**
     * volist标签
     * @param $tag
     * @param $content
     * @return string
     */
    private function _volist($tag, $content)
    {
        $parse = '<?php ' . (isset($tag['key']) ? '$' . $tag['key'] . ' = 0; ' : '');
        $parse .= 'foreach($' . $tag['name'] . ' as ' . (isset($tag['index']) ? '$' . $tag['index'] . '=>' : '') . '$' . $tag['id'] . '): ';
        $parse .= (isset($tag['key']) ? '$' . $tag['key'] . '++;' : '') . ' ?>';
        $parse .= $content;
        $parse .= '<?php endforeach; ?>';
        return $parse;
    }

    /**
     * 获取编译后的内容
     * @param $template
     * @return mixed|null|string|string[]
     */
    public function compile($template)
    {
        // 处理raw标签
        $template = $this->parseRaw($template);
        // 处理模板继承标签
        $template = $this->parseExtend($template);
        // 处理include标签
        $template = $this->parseInclude($template);
        // 处理变量以及函数
        $template = $this->parseVars($template);
        // 处理定义的标签
        $template = $this->parseTags($template);

        return $template;
    }

}
