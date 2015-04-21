<?php

/**
 * @author xbzbing<xbzbing@gmail.com>
 * @link www.crazydb.com
 *
 * UEditor版本v1.4.3
 * Yii版本v1.x
 *
 * 配合AR使用：
 *
 * $this->widget('ext.ueditor.UeditorWidget',
 * array(
 * 'model' => $model,
 * 'attribute' => 'content',
 * 'htmlOptions' => array('rows'=>6, 'cols'=>50)
 * ));
 *
 * 当作普通表单使用:
 *
 * $this->widget('ext.ueditor.UeditorWidget',
 * array(
 * 'id'=>'Post_excerpt',
 * 'name'=>'excerpt_editor',
 * 'value' => '输入值',
 * 'config'=>array(
 * 'serverUrl' => Yii::app()->createUrl('editor/'),//指定serverUrl
 * 'toolbars'=>array(
 * array('source','link','bold','italic','underline','forecolor','superscript','insertimage','spechars','blockquote')
 * ),
 * 'initialFrameHeight'=>'150',
 * 'initialFrameWidth'=>'95%'
 * ),
 * 'htmlOptions' => array('rows'=>3,'class'=>'span12 controls')
 * ));
 *
 */
class UeditorWidget extends CInputWidget
{
    /**
     * 需要引入的JS文件列表，为以后升级添加配置保证兼容。
     * 可以单独引入patch文件
     * @var array js列表
     */
    public $jsFiles = array(
        '/ueditor.all.min.js',
    );

    /**
     * 是否附加缩略图管理，默认为true。
     * @var bool
     */
    public $thumbnail = true;

    /**
     * 前端配置，详见 @see http://fex.baidu.com/ueditor/#start-config
     * @var array
     */
    public $config;

    /**
     * 用于注册javascript脚本
     * @var CClientScript
     */
    protected $clientScript;

    /**
     * 初始化配置，发布资源文件
     */
    public function init()
    {
        parent::init();
        //发布资源文件
        $assetManager = Yii::app()->assetManager;
        $assetManager->excludeFiles = array(
            'action_crawler.php',
            'action_upload.php',
            'action_list.php',
            'controller.php',
            'Uploader.class.php',
            'index.html'
        );
        $_assetUrl = $assetManager->publish(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'resources') . '/';

        //注册资源文件
        $this->clientScript = Yii::app()->clientScript;

        foreach ($this->jsFiles as $jsFile)
            $this->clientScript->registerScriptFile($_assetUrl . $jsFile, CClientScript::POS_END);

        //常用配置项
        if (empty($this->config['UEDITOR_HOME_URL']))
            $this->config['UEDITOR_HOME_URL'] = $_assetUrl;

        if (empty($this->config['serverUrl']))
            $this->config['serverUrl'] = Yii::app()->createUrl('ueditor/index');
        elseif (is_array($this->config['serverUrl']))
            $this->config['serverUrl'] = Yii::app()->createUrl($this->config['serverUrl']);

        if (empty($this->config['lang']))
            $this->config['lang'] = 'zh-cn';

        if (empty($this->config['initialFrameHeight']))
            $this->config['initialFrameHeight'] = 400;

        if (empty($this->config['initialFrameWidth']))
            $this->config['initialFrameWidth'] = '100%';

        //扩展默认不直接引入config.js文件，因此需要自定义配置项.
        if (empty($this->config['toolbars'])) {
            //为了避免每次使用都输入乱七八糟的按钮，这里预先定义一些常用的编辑器按钮。
            //这是一个丑陋的二维数组
            $this->config['toolbars'] = array(
                array(
                    'fullscreen', 'source', 'undo', 'redo', '|',
                    'customstyle', 'paragraph', 'fontfamily', 'fontsize'
                ),
                array(
                    'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat',
                    'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
                    'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', '|',
                    'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
                    'directionalityltr', 'directionalityrtl', 'indent', '|'
                ),
                array(
                    'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
                    'link', 'unlink', '|',
                    'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'insertcode', 'pagebreak', '|',
                    'horizontal', 'inserttable', '|',
                    'print', 'preview', 'searchreplace', 'help'
                )
            );
        }

        if (!is_array($this->htmlOptions))
            $this->htmlOptions = array();

    }

    /**
     * 输出widget页面，注册相关JS代码。
     */
    public function run()
    {
        if ($this->hasModel()) {
            $id = CHtml::getIdByName(CHtml::activeName($this->model, $this->attribute));
            $name = $this->name ? $this->name : $id;
        } else {
            if (empty($this->name))
                throw new CException(Yii::t('yii', '{class} must specify "model" and "attribute" or "name" property values.', array('{class}' => get_class($this))));
            $name = $this->name;
            $id = isset($this->htmlOptions['id']) ? $this->htmlOptions['id'] : $this->id;
        }

        $config = json_encode($this->config);

        $script = "var {$name} = UE.getEditor('{$id}',{$config});\n";

        //ready部分代码，是为了缩略图管理。UEditor本身就很大，在后台直接加载大文件图片会很卡。
        if ($this->thumbnail)
            $script .= <<<THUMBNAIL
    {$name}.ready(function(){
        this.addListener( "beforeInsertImage", function ( type, imgObjs ) {
            for(var i=0;i < imgObjs.length;i++){
                imgObjs[i].src = imgObjs[i].src.replace(".thumbnail","");
            }
        });
    });
THUMBNAIL;

        $this->clientScript->registerScript('ueditor_' . $name, $script);

        if ($this->hasModel())
            echo CHtml::activeTextarea($this->model, $this->attribute, $this->htmlOptions);
        else
            echo CHtml::textarea($this->name, $this->value, $this->htmlOptions + array('id' => $this->id));
    }
}