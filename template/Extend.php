<?php

namespace template;

class Extend
{
    public $tags = [
        'say' => ['attr' => 'what', 'close' => 0]
    ];

    public function _say($tag)
    {
        return '<?php echo \''. $tag['what'] .'\'; ?>';
    }
    
}
