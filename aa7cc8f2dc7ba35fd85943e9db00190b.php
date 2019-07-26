<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

    <?php  foreach ($array as $vo):   echo $vo;  endforeach; ?>
    将上面的内容放入raw标签：
    
        <volist name="array" id="vo">
            {$vo}
        </volist>
    
    <?php if ($var == 1):  echo time();  endif; ?>
    扩展的标签：
    <?php echo 'Hello'; ?>


    默认内容，没有定义就不会覆盖我


    FOOTER

</body>
</html>