<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @link www.crazydb.com
 * UEditor版本v1.4.3
 * Yii版本v1.1.14
 * 使用widget请配置容器的id，如果在一个页面使用多个ueditor，
 * 需要配置name属性，默认的name属性为editor。
 */
class UeditorWidget extends CWidget {

    /**
     * 资源地址，也是UE的UEDITOR_HOME_URL，自动生成，一般情况不要修改。
     * @var string
     */
    private $_assetUrl;
    /**
     * 生成的ueditor对象的名称，默认为editor。
     * 主要用于同一个页面的多个editor实例的管理。
     * @var string
     */
    public $name = 'editor';
    /**
     * 需要引入的JS文件列表，为以后升级添加配置保证兼容。
     * 可以单独引入patch文件
     * @var array js列表
     */
    public $jsFiles = array (
        '/ueditor.all.min.js',
    );
    /**
     * ueditor的初始化配置选项。默认配置为个人喜好，可以根据需求修改。
     * 语言默认中文，修改最大字符为10240，修改提示信息。
     * @var String
     */
    public $options;

    /**
     * UEditor 1.4+的统一后台入口
     * @var string
     */
    public $serverUrl;
    /**
     * 初始化高度
     * @var string
     */
    public $initialFrameHeight = '400';
    /**
     * 初始化宽度
     * 默认为100%，会自动匹配父容器宽度
     * @var string
     */
    public $initialFrameWidth = '100%';

    /**
     * 初始化配置，发布资源文件
     */
    public function init() {
        parent::init();
        //发布资源文件
        $assetManager = Yii::app()->assetManager;
        $assetManager->excludeFiles = array(
            'action_crawler.php',
            'action_upload.php',
            'action_list.php',
            'controller.php',
            'Uploader.class.php',
            'config.json',
            'index.html'
        );
        $this->_assetUrl = $assetManager->publish( __DIR__ . DIRECTORY_SEPARATOR . 'resources' );

        //注册资源文件
        $cs = Yii::app()->clientScript;
        foreach( $this->jsFiles as $jsFile)
            $cs->registerScriptFile( $this->_assetUrl . $jsFile, CClientScript::POS_END );

        //拼接UE配置
        if($this->serverUrl==null){
            $this->serverUrl = Yii::app()->createUrl('ueditor/index');
        }
        if($this->options==null){
            // toolbar
            $this->options = "toolbars:[
            ['fullscreen','source','undo','redo','|','customstyle','paragraph','fontfamily','fontsize'],
			['bold','italic','underline','fontborder','strikethrough','superscript','subscript','removeformat',
			'formatmatch', 'autotypeset', 'blockquote', 'pasteplain','|',
			'forecolor','backcolor','insertorderedlist','insertunorderedlist','|',
            'rowspacingtop','rowspacingbottom', 'lineheight','|',
            'directionalityltr','directionalityrtl','indent','|'],
            ['justifyleft','justifycenter','justifyright','justifyjustify','|','link','unlink','|',
            'insertimage','emotion','scrawl','insertvideo','music','attachment','map',
            'insertcode','pagebreak','|',
            'horizontal','inserttable','|',
            'print','preview','searchreplace','help']
        	]";
            //others
            $this->options .= "
            ,lang:'zh-cn'
            ,wordCountMsg: '已经输入{#count}个字符。'
            ,maximumWords: 10240
            ,wordOverFlowMsg: '输入字符数目已经超过10240，过大可能会导致提交失败！'";
        }

        $options = $this->options;
        $options .= ",initialFrameHeight:'{$this->initialFrameHeight}'
            ,initialFrameWidth:'{$this->initialFrameWidth}'";

        $js = <<<UEDITOR
        var {$this->name} = UE.getEditor('{$this->id}',{
            UEDITOR_HOME_URL:'{$this->_assetUrl}/'
            ,serverUrl: '{$this->serverUrl}'
            ,{$options}
        });
        {$this->name}.ready(function(){
            this.addListener( "beforeInsertImage", function ( type, imgObjs ) {
                for(var i=0;i < imgObjs.length;i++){
                    imgObjs[i].src = imgObjs[i].src.replace(".thumbnail","");
                }
            });
        });
UEDITOR;
        $cs->registerScript('ueditor_'.$this->id, $js, CClientScript::POS_END);
    }

    /**
     * 获取assetUrl
     * @return string
     */
    public function getAssetUrl(){
        return $this->_assetUrl;
    }
}