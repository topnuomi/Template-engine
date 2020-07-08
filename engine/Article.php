<?php
/**
 * Author: TopNuoMi
 * Date: 2020/07/08
 */

class Article extends TagLib
{
    public $tags = [
        'list' => ['attr' => 'category-id', 'close' => 0]
    ];

    public function _list($attr)
    {
        $param = $this->parseDotSyntax($attr['category-id']);
        return "<?php echo {$param}; ?>";
    }
}
