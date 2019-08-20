<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

    <?php $i = 0; foreach($array as $key=>$vo): $i++;  echo ($vo);  endforeach; ?>
    将上面的内容放入raw标签：
    
        <volist name="array" id="vo">
            <$vo>
        </volist>
    
    <?php echo ($a);  if ($var == 1):  echo (time());  if ($var1 == 2):  echo (time());  echo $var1 == 2; endif;  echo $var == 1; endif; ?>
    扩展的标签：
    <?php echo 'Hello'; ?>


    默认内容，没有定义就不会覆盖我


    FOOTER

</body>
</html>