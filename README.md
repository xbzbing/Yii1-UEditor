Yii1-UEditor
===================
项目更名为Yii1-UEditor，采用2推荐的方式重新命名，方便管理。

Yii v1.x 的UEditor扩展，支持的UEditor版本为1.4.3。

测试使用的PHP版本为5.5.9，测试使用的Yii版本是1.1.15，采用alias的方式部署在Ubuntu apache 2.4.7。

扩展特性：

支持自动的缩略图管理（默认开启，可以关闭）。

支持水印（默认关闭，可以开启）。

使用TP框架的tpImage来生成和处理图片。

注意：2015.2.2 更新版本与之前并不兼容，本次修改更贴近InputWidget的设计意图，扩展使用时将不再需要原有的输入框。

update：

移除登录权限限制，目的时希望开发者能通过继承UeditorController来完善各个action的权限。

Yii2版本的扩展参考 [Yii2-UEditor](https://github.com/xbzbing/Yii2-UEditor "Yii2版本的UEditor扩展") 。

使用说明
---------------------

1、将ueditor放在项目的/protected/extensions/目录下。

2、配置UEditor的后台控制器

方法有两种

1）、自己写controller （推荐）

在/protected/controllers目录新建一个controller，并继承UeditorController，如下：

```php
Yii::import('ext.ueditor.UeditorController');
class EditorController extends UeditorController{
//自定义修改
}
```

这时候serverUrl为刚才新建的UeditorController，在下面配置widget时候要特别注意，widget默认的serverUrl是`ueditor/index`。

这样做的好处是，可以自定义多种使用场景，并且能够自定义各个action的权限控制，非常灵活。

2）配置controllerMap

在config.php中配置controllerMap，来指定ueditor的后端控制器。

当有多个使用场景时，可以配置多个map，在widget使用时指定serverUrl即可。

```php
    'controllerMap'=>array(
        'ueditor'=>array(
            'class'=>'ext.ueditor.UeditorController',
        ),
    ),
```

可选配置:

```php
    'controllerMap'=>array(
        'ueditor'=>array(
            'class'=>'ext.ueditor.UeditorController',
            'config'=>array(),//参考config.json的配置，此处的配置具备最高优先级
            'thumbnail'=>true,//是否开启缩略图
            'watermark'=>'',//水印图片的地址，使用相对路径
            'locate'=>9,//水印位置，1-9，默认为9在右下角
        ),
    ),
```

这样做的好处是，配置方便快捷，不需要增加额外的controller，适用于简单的项目。

如果thumbnail属性为false，后端将不会生成缩略图。

具体的config配置参考[UEditor后端配置项说明](http://fex.baidu.com/ueditor/#server-config "后端配置项说明.md")。

3、在view中使用widget。

配合CActiveForm和ActiveRecord（model）使用：

```php
    $form->widget('ext.ueditor.UeditorWidget',
            array(
                'model' => $model,
                'attribute' => 'content',
                'htmlOptions' => array('rows'=>6, 'cols'=>50)
    ));
```

当作普通表单使用:

```php
    $this->widget('ext.ueditor.UeditorWidget',
        array(
            'name'=>'excerpt_editor',
            'id'=>'Post_excerpt',
            'value' => '输入值',
            'config'=>array(
                'serverUrl' => Yii::app()->createUrl('editor/'),//指定serverUrl
                'toolbars'=>array(
                    array('source','link','bold','italic','underline','forecolor','superscript','insertimage','spechars','blockquote')
                ),
                'initialFrameHeight'=>'150',
                'initialFrameWidth'=>'95%'
            ),
            'htmlOptions' => array('rows'=>3,'class'=>'span12 controls')
    ));
```

当扩展被当做普通表单使用时，其name为必填项。id可以通过id配置或者htmlOptions配置引入，如果没有设置id，扩展将自动生成。

widget默认的serverUrl为`ueditor/index`，如果自己写了controller或者在controllerMap中配置了多个控制器，可以为不同的widget指定对应的serverUrl的地址，可以适用不同的场景。

如果thumbnail属性为false，前端将不会附加缩略图管理代码。

具体的config配置参考[UEditor前端配置项说明](http://fex.baidu.com/ueditor/#start-config "前端配置项说明.md")。

4、错误排除

- 出现错误首先应该打开浏览器调试工具查看请求返回具体信息。

- 默认上传路径为「应用根目录」，而不是网站根目录，如果上传失败请查看目录权限。

- 不要开启Yii的调试，因为UEditor的返回都是json格式，开启调试会导致返回格式不识别。

- 出现404错误可能是因为widget没有正确配置serverUrl。


其他说明
---------------------
@see https://github.com/fex-team/ueditor

@see https://github.com/xbzbing/Yii2-Ueditor

参考：[［更新］UEditor1.4.3-for-Yii1-扩展](http://www.crazydb.com/archive/更新_UEditor1.4.3-for-Yii1-扩展 "UEditor1.4.3-for-Yii1-扩展")。
