<?php

namespace template;

class Extend extends Engine
{
    protected $tags = [
        'say' => ['attr' => 'what', 'close' => 0]
    ];
    
    protected function _say_start($tag)
    {
        return '<?php echo \''. $tag['what'] .'\'; ?>';
    }
    
}
