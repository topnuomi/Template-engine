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
     * @var null 模板路径
     */
    private $viewDir = null;

    /**
     * @var array 默认标签定义
     */
    private $defaultTags = [
        'if' => ['attr' => 'condition', 'close' => 1],
        'else' => ['attr' => 'condition', 'close' => 0],
        'volist' => ['attr' => 'name,id,key', 'close' => 1],
        'assign' => ['attr' => 'name,value', 'close' => 0],
        'switch' => ['attr' => 'name', 'close' => 1],
        'case' => ['attr' => 'value', 'close' => 1],
        'default' => ['attr' => '', 'close' => 1]
    ];

    /**
     * 构造方法
     * Engine constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        // 仅作示例，请在外部传入模板目录
        $this->viewDir = './demo/';
    }

    /**
     * 处理模板继承
     */
    private function parseExtend($tmpl)
    {
        $pattern = '#' . $this->left . 'extend +file=[\'"](.*?)[\'"] +/' . $this->right . '#';
        preg_match($pattern, $tmpl, $matches);
        if (!empty($matches)) {
            $blockPattern = '#' . $this->left . 'block +name=[\'"](.*?)[\'"]' . $this->right;
            $blockPattern .= '([\s\S]*?)' . $this->left . '\/block' . $this->right . '#';
            // 获得被继承的模板内容
            $file = $this->viewDir . $matches[1] . '.html';
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
            preg_match_all($blockPattern, $tmpl, $templateResult);
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
            $tmpl = str_replace($searchArray, $replaceArray, $extendFileContent);
        }
        return $tmpl;
    }

    /**
     * 处理include标签
     * @return bool
     */
    private function parseInclude($tmpl)
    {
        $pattern = '#' . $this->left . 'include +file=[\'"](.*?)[\'"] +/' . $this->right . '#';
        $tmpl = preg_replace_callback($pattern, function ($result) {
            $str = null;
            $file = $this->viewDir . $result[1] . '.html';
            if (file_exists($file)) {
                $str = file_get_contents($file);
            }
            return $str;
        }, $tmpl);
        // 处理多层include
        if ($this->hasInclude($tmpl)) {
            $tmpl = $this->parseInclude($tmpl);
        }
        return $tmpl;
    }

    /**
     * 检测是否含有include
     * @param $tmpl
     * @return bool
     */
    private function hasInclude($tmpl)
    {
        $pattern = '#' . $this->left . 'include +file=[\'"](.*?)[\'"] +/' . $this->right . '#';
        preg_match($pattern, $tmpl, $matches);
        return !empty($matches);
    }

    /**
     * 分析参数以及函数输出
     */
    private function parseVars($tmpl)
    {
        preg_match_all('#{(.*?)}#', $tmpl, $matches);
        $search = [];
        $replace = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $start = substr($matches[1][$i], 0, 1);
            if ($start == '$') {
                $search[] = $matches[0][$i];
                $replace[] = '<?php echo ' . $matches[1][$i] . '; ?>';
            } elseif ($start == ':') {
                $search[] = $matches[0][$i];
                $replace[] = '<?php echo ' . ltrim($matches[1][$i], ':') . '; ?>';
            }
        }
        $tmpl = str_replace($search, $replace, $tmpl);
        return $tmpl;
    }

    /**
     * 处理用户自定义标签
     * @param $tmpl
     * @return null|string|string[]
     */
    public function parseCustomizeTags($tmpl)
    {
        return $this->parseTags($tmpl, $this->tags);
    }

    /**
     * 处理默认的标签
     * @param $tmpl
     * @return null|string|string[]
     */
    private function parseDefaultTags($tmpl)
    {
        return $this->parseTags($tmpl, $this->defaultTags);
    }

    /**
     * 进行标签处理
     * @param $tmpl
     * @param $tags
     * @return null|string|string[]
     */
    private function parseTags($tmpl, $tags)
    {
        foreach ($tags as $name => $item) {
            $attr = ($item['attr']) ? ' +(.*?)' : null;
            $pattern = '#' . $this->left . $name . $attr . ($item['close'] ? $this->right : '\/' . $this->right . '#');
            if ($item['close']) {
                $pattern .= '([\s\S]*?)' . $this->left . '\/' . $name . $this->right . '#';
            }
            preg_match_all($pattern, $tmpl, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $attrPattern = '#(.*?)=[\'"](.*?)[\'"]#';
                preg_match_all($attrPattern, $matches[1][$i], $result);
                $tag = [];
                if (!empty($result)) {
                    foreach ($result[1] as $key => $value) {
                        $tag[trim($value, ' ')] = $result[2][$key];
                    }
                }
                if ($item['close']) {
                    $tagContent = isset($matches[2][$i]) ? $matches[2][$i] : (($attr) ? null : $matches[1][$i]);
                    $content = $this->{'_' . $name . '_start'}($tag, $tagContent);
                    if ($item['close']) {
                        $content .= $this->{'_' . $name . '_end'}($tag, $tagContent);
                    }
                } else {
                    $content = $this->{'_' . $name . '_start'}($tag);
                }
                $tmpl = str_replace($matches[0][$i], $content, $tmpl);
            }
        }
        return preg_replace('#\?>([\r|\n|\s]*?)<\?php#', '', $tmpl);
    }

    /**
     * 处理raw标签
     * @param $tmpl
     * @return null|string|string[]
     */
    private function parseRaw($tmpl)
    {
        $pattern = '#' . $this->left . 'raw' . $this->right . '([\s\S]*?)';
        $pattern .= $this->left . '\/raw' . $this->right . '#';
        $tmpl = preg_replace_callback($pattern, function ($matches) {
            return str_replace([
                $this->left, $this->right,
                '{', '}'
            ], [
                '<raw!--', '--raw>',
                '{raw!--', '--raw}'
            ], $matches[1]);
        }, $tmpl);
        return $tmpl;
    }

    /**
     * 还原raw
     * @param $tmpl
     * @return null|string|string[]
     */
    public function returnRaw($tmpl)
    {
        $pattern = '#[{|<]raw!--([\s\S]*?)--raw[>|}]#';
        $tmpl = preg_replace_callback($pattern, function ($matches) {
            $left = substr($matches[0], 0, 1);
            $right = substr($matches[0], -1);
            return $left . $matches[1] . $right;
        }, $tmpl);
        return $tmpl;
    }

    /**
     * if标签
     * @param $tag
     * @param $content
     * @return string
     */
    private function _if_start($tag, $content)
    {
        return '<?php if (' . $tag['condition'] . '): ?>' . $content;
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
     * @param $content
     * @return string
     */
    private function _volist_start($tag, $content)
    {
        if (substr($tag['name'], 0, 1) == ':') {
            $name = substr($tag['name'], 1);
        } else {
            $name = '$' . $tag['name'];
        }
        $key = (empty($tag['key'])) ? null : '$' . $tag['key'] . ' = 0;';
        $parse = '<?php ' . $key . ' foreach (' . $name . ' as $' . $tag['id'] . '): ';
        $parse .= ($key ? '$' . $tag['key'] . '++;' : null) . ' ?>' . $content;
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
    private function _assign_start($tag)
    {
        return '<?php $' . $tag['name'] . ' = ' . $tag['value'] . '; ?>';
    }

    /**
     * switch标签
     * @param $tag
     * @param $content
     * @return string
     */
    private function _switch_start($tag, $content)
    {
        $parse = '<?php switch($' . $tag['name'] . '): ?>' . $content;
        return $parse;
    }

    /**
     * switch标签结束
     * @return string
     */
    private function _switch_end()
    {
        $parse = '<?php endswitch; ?>';
        return $parse;
    }

    /**
     * case标签
     * @param $tag
     * @param $content
     * @return string
     */
    private function _case_start($tag, $content)
    {
        if (is_numeric($tag['value'])) {
            $caseVar = $tag['value'];
        } else {
            $caseVar = '\'' . $tag['value'] . '\'';
        }
        $parse = '<?php case ' . $caseVar . ': ?>' . $content;
        return $parse;
    }

    /**
     * case标签结束
     * @return string
     */
    private function _case_end()
    {
        $parse = '<?php break; ?>';
        return $parse;
    }

    /**
     * default标签
     * @param $tag
     * @param $content
     * @return string
     */
    private function _default_start($tag, $content)
    {
        $parse = '<?php default: ?>' . $content;
        return $parse;
    }

    /**
     * default标签结束
     * @return string
     */
    private function _default_end()
    {
        $parse = '<?php break; ?>';
        return $parse;
    }

    /**
     * 获取编译后的内容
     * @param $tmpl
     * @return bool|mixed|null|string|string[]
     */
    public function compile($tmpl)
    {
        $tmpl = $this->parseRaw($tmpl);
        // 处理模板继承标签
        $tmpl = $this->parseExtend($tmpl);
        // 处理include标签
        $tmpl = $this->parseInclude($tmpl);
        // 处理变量以及函数
        $tmpl = $this->parseVars($tmpl);
        // 处理定义的标签
        $tmpl = $this->parseDefaultTags($tmpl);
        return $tmpl;
    }

}
