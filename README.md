Ueditor-yiiv1.x-ext
===================

Yii v1.x 的Ueditor扩展，支持的Ueditor版本为1.3.6。<br>
支持自动的缩略图管理。<br>
使用TP框架的tpImage来生成和处理图片。

###配置说明
--------------
<ul>
<li>
1、将ueditor放在项目的/protected/extensions/目录下。
</li>
<li>
2、在config.php中配置controllerMap，来指定ueditor的访问路径
```php
'controllerMap'=>array(
    'ueditor'=>array(
        'class'=>'ext.ueditor.UeditorController',
    ),
),
```
	可选配置：
```php
'controllerMap'=>array(
    'ueditor'=>array(
        'class'=>'ext.ueditor.UeditorController',
        //文件保存位置
        'savePath'=>'upload/'
        //允许的文件扩展名
        'allowFiles'=>array(
            //上传图片
            'image' => array(".gif", ".png", ".jpg", ".jpeg", ".bmp"),
            //上传附件同时包括 image和file
            'file' => array( ".rar" , ".doc" , ".docx" , ".zip" , ".pdf" , ".txt" , ".swf" , ".wmv" ),
        ),
    ),
),
```
    其中savePath还支持Yii::app()->params['UESavePath']的方式设置。
</li>
<li>
3、在view中使用widget。
    在原有的view中添加即可，注意id填写为原有的textarea的id。
```php
$this->widget('ext.ueditor.UeditorWidget',
        array(
                'id'=>'Post_content'
        )
);
```
</li>
<li>
4、错误排除<br>
出现错误请查看上传目录的权限问题。默认上次到根目录的upload/目录。
不要开启Yii的调试，因为UEditor的返回都是json格式，开启调试会导致返回格式不识别。
</li>
