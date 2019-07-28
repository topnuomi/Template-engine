<?php

namespace template;

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
        'volist' => ['attr' => 'name,id,key', 'close' => 1],
        'assign' => ['attr' => 'name,value', 'close' => 0]
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
        $pattern = '#' . $this->left . 'extend +file=[\'"](.*?)[\'"] +/' . $this->right . '#';
        preg_match($pattern, $template, $matches);
        if (!empty($matches)) {
            $blockPattern = '#' . $this->left . 'block +name=[\'"](.*?)[\'"]' . $this->right;
            $blockPattern .= '([\s\S]*?)' . $this->left . '\/block' . $this->right . '#';
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
        $pattern = '#' . $this->left . 'include +file=[\'"](.*?)[\'"] +/' . $this->right . '#';
        $template = preg_replace_callback($pattern, function ($result) {
            $str = null;
            $file = $this->config['dir'] . $result[1] . '.html';
            if (file_exists($file)) {
                $str = file_get_contents($file);
            }
            return $str;
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
        $pattern = '#' . $this->left . 'include +file=[\'"](.*?)[\'"] +/' . $this->right . '#';
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
        preg_match_all('#{(.*?)}#', $template, $matches);
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
     * @param $name
     * @param $tagContent
     * @return mixed
     */
    private function getTagParseResult($name, $tagContent = [])
    {
        if (method_exists($this, $name)) {
            return $this->{$name}($tagContent);
        } else {
            foreach ($this->extendInstance as $item) {
                if (method_exists($item, $name)) {
                    return $item->{$name}($tagContent);
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
            $pattern = '#' . $this->left . $name . '(.*?)' . ($item['close'] ? null : '\/') . $this->right . '#';
            preg_match_all($pattern, $template, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $tag = [];
                if ($item['attr']) {
                    $attrPattern = '#(.*?)=[\'"](.*?)[\'"]#';
                    preg_match_all($attrPattern, $matches[1][$i], $result);
                    if (isset($result[0]) && !empty($result[0])) {
                        foreach ($result[1] as $key => $value) {
                            $tag[trim($value, ' ')] = $result[2][$key];
                        }
                    }
                }
                $function = ($item['close']) ? '_' . $name . '_start' : '_' . $name;
                $template = str_replace($matches[0][$i], $this->getTagParseResult($function, $tag), $template);
            }
            if ($item['close']) {
                $closePattern = '#' . $this->left . '\/' . $name . $this->right . '#';
                $template = preg_replace_callback($closePattern, function () use ($name) {
                    $function = '_' . $name . '_end';
                    return $this->getTagParseResult($function);
                }, $template);
            }
        }
        return preg_replace('#\?>([\r|\n|\s]*?)<\?php#', '', $template);
    }

    /**
     * 处理raw标签
     * @param $template
     * @return null|string|string[]
     */
    private function parseRaw($template)
    {
        $pattern = '#' . $this->left . 'raw' . $this->right . '([\s\S]*?)';
        $pattern .= $this->left . '\/raw' . $this->right . '#';
        $template = preg_replace_callback($pattern, function ($matches) {
            return str_replace([
                $this->left, $this->right,
                '{', '}'
            ], [
                '<raw!--', '--raw>',
                '<-raw!--', '--raw->'
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
            '<raw!--', '--raw>',
            '<-raw!--', '--raw->'
        ], [
            $this->left, $this->right,
            '{', '}'
        ], $template);
        return $template;
    }

    /**
     * php标签开始
     * @return string
     */
    private function _php_start()
    {
        return '<?php ';
    }

    /**
     * php标签结束
     * @return string
     */
    private function _php_end()
    {
        return ' ?>';
    }

    /**
     * if标签
     * @param $tag
     * @return string
     */
    private function _if_start($tag)
    {
        return '<?php if (' . $tag['condition'] . '): ?>';
    }

    /**
     * if标签结束
     * @return string
     */
    private function _if_end()
    {
        return '<?php endif; ?>';
    }

    /**
     * else标签（支持条件）
     * @param $tag
     * @return string
     */
    private function _else_start($tag)
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
     * @return string
     */
    private function _volist_start($tag)
    {
        if (substr($tag['name'], 0, 1) == ':') {
            $name = substr($tag['name'], 1);
        } else {
            $name = '$' . $tag['name'];
        }
        $key = (empty($tag['key'])) ? null : '$' . $tag['key'] . ' = 0;';
        $parse = '<?php ' . $key . ' foreach (' . $name . ' as $' . $tag['id'] . '): ';
        $parse .= ($key ? '$' . $tag['key'] . '++;' : null) . ' ?>';
        return $parse;
    }

    /**
     * volist标签结束
     * @return string
     */
    private function _volist_end()
    {
        return '<?php endforeach; ?>';
    }

    /**
     * assign标签
     * @param $tag
     * @return string
     */
    private function _assign($tag)
    {
        return '<?php $' . $tag['name'] . ' = ' . $tag['value'] . '; ?>';
    }

    /**
     * 获取编译后的内容
     * @param $template
     * @return bool|mixed|null|string|string[]
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
