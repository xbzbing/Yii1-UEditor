<?php

/**
 * @author xbzbing<xbzbing@gmail.com>
 * @link www.crazydb.com
 * UEditor版本v1.4.3
 * Yii版本v1.1.15
 * 使用widget请配置容器的id，如果在一个页面使用多个ueditor，
 * 需要配置name属性，默认的name属性为editor。
 */
class UeditorWidget extends CInputWidget
{

    /**
     * 生成的ueditor对象的名称，默认为editor。
     * 主要用于同一个页面的多个editor实例的管理。
     * @var string
     */
    public $name;
    /**
     * 需要引入的JS文件列表，为以后升级添加配置保证兼容。
     * 可以单独引入patch文件
     * @var array js列表
     */
    public $jsFiles = array(
        '/ueditor.all.min.js',
    );

    /**
     * @var bool
     */
    public $thumbnail = true;

    /**
     * @var array
     */
    public $config;

    /**
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
        $_assetUrl = $assetManager->publish(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'resources') . DIRECTORY_SEPARATOR;

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

        if (!is_array($this->htmlOptions) || empty($this->htmlOptions)) {
            $this->htmlOptions = array();
        }

    }

    /**
     * 输出widget页面，注册相关JS代码。
     */
    public function run()
    {
        $id = $name = null;
        if ($this->hasModel())
            list($name, $id) = CHtml::getIdByName($this->resolveNameID());
        else {
            $id = $this->id;
            $name = $this->name ? $this->name : 'editor';
        }

        $config = json_encode($this->config);

        $script = "var {$name} = UE.getEditor('{$id}',{$config});";

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
