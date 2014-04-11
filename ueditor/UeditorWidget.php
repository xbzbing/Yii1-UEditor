<?php
/**
 * 封装了Ueditor的编辑器的引用和uploader的controller。
 * ext的controller需要在config/main.php中进行配置。
 * 封装时尽量保持第三方中立，但是由于ueditor使用了swfupload作为附件上传组件，
 * swfupload在上传时不发送cookies导致controller认证失败，所以修改了编辑器的一个文件。
 * 解决方法是修改controller的filters，将FileUp改为任何人都可以访问（不推荐），
 * 或者使用patch目录下修改过的表单覆盖编辑器。
 * patch主要修改了swfupload提交参数，多了个editor.session，所以在配置options时会默认添加session选项。
 * 此扩展配置略繁琐，适合有经验/耐心的开发者使用。
 * 当ueditor升级时，可以直接覆盖resources文件夹，然后使用patch文件夹中的内容再次覆盖。
 *
 * @author xbzbing<xbzbing@gmail.com>
 * @link www.crazydb.com
 *
 */
class UeditorWidget extends CWidget {
    /**
     * 资源地址，也是UE的UEDITOR_HOME_URL，自动生成，一般情况不要修改。
     * @var string
     */
    private $_assetUrl;
    /**
     * 需要引入的JS文件列表，为以后升级添加配置保证兼容。
     * @var array js列表
     */
    public $jsFiles = array (
        '/ueditor.all.min.js'
    );
    /**
     * ueditor的初始化配置选项。默认配置为个人喜好，可以根据需求修改。
     * 语言默认中文，修改最大字符为10240，修改提示信息。
     * <pre>
     * ,toolbars:[
     * ['fullscreen', 'source', '|','customstyle', 'paragraph', 'fontfamily', 'fontsize'],
     * ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
     * 		'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', '|',
     * 		'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
     * 		'directionalityltr', 'directionalityrtl', 'indent', '|'],
     * ['justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|','link', 'unlink', '|',
     * 		'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'gmap', 'insertcode', 'pagebreak', 'background', '|',
     * 		'horizontal', 'inserttable', '|',
     * 		'print', 'preview', 'searchreplace', 'help']
     * ]
     * ,theme:'default'
     * ,lang:'zh_cn'
     * ,wordCountMsg: '已经输入{#count}个字符。'
     * ,maximumWords: 10240
     * ,wordOverFlowMsg: '输入字符数目已经超过10240，过大可能会导致提交失败！'
     * </pre>
     * @var String
     */
    public $options;
    /**
     * ueditor的修正路径。默认为web app的相对路径，自动生成，一般情况无需修改。
     * @var string
     */
    public $fixedPath;
    /**
     * 默认为true，使用swfUpload作为附件上传组件。
     * @var boolen
     */
    public $useSwfUpload = true;

    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $imageUrl;
    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $scrawlUrl;
    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $fileUrl;
    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $catcherUrl;
    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $imageManagerUrl;
    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $wordImageUrl;
    /**
     * @var string 自定义上传接口，方便利用已有接口
     */
    public $movieUrl;
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
     * 图片保存路径
     * @var string
     */
    public $savePath;
    /**
     * 图片保存路径
     * @var string
     */
    public $ueController = 'ueditor';
    /**
     * 额外要注册的JS
     * @var string
     */
    public $js = '';

    public function init() {
        parent::init();
        //发布资源文件
        $basePath = dirname( __FILE__ );
        $this->_assetUrl = Yii::app()->assetManager->publish( $basePath . DIRECTORY_SEPARATOR . 'resources' );

        //注册资源文件
        $cs = Yii::app()->clientScript;
        foreach( $this->jsFiles as $jsFile)
            $cs->registerScriptFile( $this->_assetUrl . $jsFile, CClientScript::POS_END );

        //拼接UE配置
        if($this->fixedPath==null){
            $this->fixedPath = Yii::app()->baseUrl.'/';
        }

        if($this->options==null){
            // toolbar
            $this->options = "
			toolbars:[
            ['fullscreen', 'source', '|','customstyle', 'paragraph', 'fontfamily', 'fontsize'],
			['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
			'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', '|',
            'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
            'directionalityltr', 'directionalityrtl', 'indent', '|'],
            ['justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|','link', 'unlink', '|',
            'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'gmap', 'insertcode', 'pagebreak', 'background', '|',
            'horizontal', 'inserttable', '|',
            'print', 'preview', 'searchreplace', 'help']
        	]";
            //others
            $this->options .= "
					,lang:'zh-cn'
					,wordCountMsg: '已经输入{#count}个字符。'
					,maximumWords: 10240
					,wordOverFlowMsg: '输入字符数目已经超过10240，过大可能会导致提交失败！'";
        }

        if($this->imageUrl==null)
            $this->imageUrl = Yii::app()->createUrl($this->ueController.'/imageup');
        if($this->scrawlUrl==null)
            $this->scrawlUrl = Yii::app()->createUrl($this->ueController.'/scrawlup');
        if($this->fileUrl==null)
            $this->fileUrl = Yii::app()->createUrl($this->ueController.'/fileup');
        if($this->catcherUrl==null)
            $this->catcherUrl = Yii::app()->createUrl($this->ueController.'/getremoteimage');
        if($this->imageManagerUrl==null)
            $this->imageManagerUrl = Yii::app()->createUrl($this->ueController.'/imagemanager');
        if($this->wordImageUrl==null)
            $this->wordImageUrl = Yii::app()->createUrl($this->ueController.'/imageup');
        if($this->movieUrl==null)
            $this->movieUrl = Yii::app()->createUrl($this->ueController.'/getmovie');

        $options = ','.$this->options;
        $options .= "
					,initialFrameHeight:'{$this->initialFrameHeight}'
					,initialFrameWidth:'{$this->initialFrameWidth}'
			        ,imageUrl:'{$this->imageUrl}'
			        ,imagePath:'{$this->fixedPath}'
			        ,scrawlUrl:'{$this->scrawlUrl}'
			        ,scrawlPath:'{$this->fixedPath}'
			        ,fileUrl:'{$this->fileUrl}'
			        ,filePath:'{$this->fixedPath}'
			        ,catcherUrl:'{$this->catcherUrl}'
			        ,catcherPath:'{$this->fixedPath}'
			        ,imageManagerUrl:'{$this->imageManagerUrl}'
			        ,imageManagerPath:'{$this->fixedPath}'
			        ,wordImageUrl:'{$this->wordImageUrl}'
			        ,wordImagePath:'{$this->fixedPath}'
			        ,getMovieUrl:'{$this->movieUrl}'";
        if($this->savePath!=null){
            $options .= ",savePath:'{$this->savePath}'";
        }
        if($this->useSwfUpload)
            $options .= ',sessionId:"'.session_id().'"';


        $js  = "\r\n"."var editor = UE.getEditor('{$this->id}',{UEDITOR_HOME_URL:'".$this->_assetUrl."/'".$options."});";
        $js .= "\r\n".<<<JS
        editor.addListener( "beforeInsertImage", function ( type, imgObjs ) {
            for(var i=0;i < imgObjs.length;i++){
                imgObjs[i].src = imgObjs[i].src.replace(".thumbnail","");
            }
        });
        editor.addListener( "beforeInsertImage", function ( type, imgObjs ) {
            for(var i=0;i < imgObjs.length;i++){
                console.debug(imgObjs[i].src);
            }
        });
JS;
        $this->js = $js.$this->js;
        $cs->registerScript('ueditor_'.$this->id, $this->js, CClientScript::POS_END);
    }
}