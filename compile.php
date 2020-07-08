<?php if (!defined('APP_PATH')) exit; ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

    <?php $i = 0; foreach($array as $key=>$vo): $i++;  echo htmlentities($vo);  endforeach; ?>
    将上面的内容放入raw标签：
    
        <loop from="array" to="vo">
            {$vo}
        </loop>
    
    <?php $a = '张三'; ?>
    {123456|md5}
    <?php echo htmlentities(mb_substr($a,0,1));  if ($var == 1):  echo (time());  if ($var1 == 2):  echo (time());  endif;  endif; ?>
    扩展的标签：
    <?php echo $cate['id']; ?>


    默认内容，没有定义就不会覆盖我


    FOOTER

</body>
</html>