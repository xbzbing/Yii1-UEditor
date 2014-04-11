<?php
/**
 * Ueditor的controller
 * @author xbzbing<xbzbing@gmail.com>
 * @link www.crazydb.com
 */
class UeditorController extends CExtController{
    /**
     * 允许上传的文件后缀，为二维数组。
     * image 为图片上传所允许的文件扩展名。
     * 附件上传时，将同时运行file和image的白名单上传。
     * <pre>
     *    array(
     *        'file' => array( ".rar" , ".doc" , ".docx" , ".zip" , ".pdf" , ".txt" , ".swf" , ".wmv" ),
     *        'image' => array(".gif", ".png", ".jpg", ".jpeg", ".bmp"),
     *    )
     * </pre>
     * @var array 允许的文件后缀，包括“.”，如.gif
     */
    public $allowFiles;
    /**
     * 文件保存位置，相对路径。
     * @var string
     */
    public $savePath;
    /**
     * 以 $this->savePath 为父路径的图片保存位置的数组。
     * 支持多个保存位置
     * @var array
     */
    protected $imgPath;
    /**
     * 以 $this->savePath 为父路径的文件保存位置。
     * @var string
     */
    protected $filePath;
    /**
     * 上传文件最大限制，单位KB。
     * @var int 默认10000
     */
    public $fileMaxSize = 10000;
    /**
     * 上传图片最大限制，单位KB。
     * @var int 默认2048
     */
    public $imageMaxSize = 2048;
    /**
     * 上传文件表单，Ueditor默认为upfile，如果未修改ueditor，则不必设置。
     * @var string
     */
    public $fileField = 'upfile';
    /**
     * 上传涂鸦表单，Ueditor默认为content，如果未修改ueditor，则不必设置。
     * @var string
     */
    public $scrawlField = 'content';
    /**
     * 获取远程照片表单，Ueditor默认为upfile，如果未修改ueditor，则不必设置。
     *
     * @var string
     */
    public $remoteImageField = 'upfile';

    public $fileNameFormat;

    public function init(){
        error_reporting( E_ERROR | E_WARNING );
        header( "Content-Type: text/json; charset=utf-8" );

        //权限判断
        //这里仅判断是否登录
        //更多的权限判断需自行扩展
        if( isset( $_POST['PHPSESSID'] ) ){
            session_id( $_POST['PHPSESSID'] );
        }
        if( Yii::app()->user->isGuest ){
            echo '{"url":"null","fileType":"null","original":"null","state":"Failed:没有上传权限！"}';
            Yii::app()->end();
        }

        require_once 'Uploader.php';
        date_default_timezone_set( "PRC" );
        $this->layout = null;
        if( $this->allowFiles == null || empty( $this->allowFiles['file'] ) || empty( $this->allowFiles['image'] ) ){
            $this->allowFiles = array(
                'file' => array( ".rar" , ".doc" , ".docx" , ".zip" , ".pdf" , ".txt" , ".swf" , ".wmv" ),
                'image' => array(".gif", ".png", ".jpg", ".jpeg", ".bmp"),
            );
        }
        //保存文件/图片的根路径
        //这里支持从Yii的 params 中获取保存位置设定。
        if($this->savePath==null)
            $this->savePath = isset(Yii::app()->params['UESavePath'])?Yii::app()->params['UESavePath']:'upload/';

        if(!is_string($this->savePath))
            throw new CHttpException('500','保存路径设置错误！');

        if(strrchr( $this->savePath, '/') != '/')
            $this->savePath .= '/';

        //修正图片保存路径
        //默认的图片保存位置是跟路径的image和ugc个人用户文件夹
        $this->imgPath = array(
            '上传目录'=>$this->savePath.'image/',
            '个人文件夹'=>$this->savePath.Yii::app()->user->id.'/image/'
        );
        //img是file文件夹
        $this->filePath = $this->savePath.'file/';

    }

    public function filters(){
		return array(
				'ImportUploader + FileUp,ImageUp,ScrawlUp',
        );
    }

    /**
     * 检测上传必须项
     * @param $filterChain CFilterChain
     */
    public function filterImportUploader( $filterChain ){
        //获取存储目录 1.3.5以上版本新增接口
        if( isset( $_GET['fetch'] ) ){
            header( 'Content-Type: text/javascript' );
            echo 'updateSavePath('.CJSON::encode( array_keys( $this->imgPath ) ).');';
            Yii::app()->end();
        }
        /**
         * 检测上传表单，是否存在必要的项目。
         * 目前检测$this->fileField，即文件项目的名称。
         * $this->scrawlField 涂鸦的名称
         */
        if( !isset( $_POST[$this->fileField] ) && !isset( $_FILES[$this->fileField] ) && !isset( $_POST[$this->scrawlField] ) ){
            echo '{"url":"null","fileType":"null","original":"null","state":"Failed:Missing File Field"}';
            Yii::app()->end();
        }
        $filterChain->run();
    }

    /**
     * 文件上传。
     * 默认地址/upload/file/日期
     * 得到上传文件所对应的各个参数,数组结构
     */
    public function actionFileUp(){
        //上传配置
		$config = array(
				"savePath" => $this->filePath, //保存路径
				"allowFiles" => $this->allowFiles['image']+$this->allowFiles['file'] , //文件允许格式
				"maxSize" => $this->fileMaxSize, //文件大小限制，单位KB
		);
		//生成上传实例对象并完成上传
        $up = new Uploader( $this->fileField, $config );
        $info = $up->getFileInfo();

        echo '{"url":"'.$info["url"].'","fileType":"'.$info["type"].'","original":"'.$info["originalName"].'","state":"'.$info["state"].'"}';
    }

    /**
     * 上传图片。
     * 默认地址/ugc/upload/file/日期
     * 向浏览器返回数据json数据
     * {
     *   'url'      :'a.rar',        //保存后的文件路径
     *   'fileType' :'.rar',         //文件描述，对图片来说在前端会添加到title属性上
     *   'original' :'编辑器.jpg',   //原始文件名
     *   'state'    :'SUCCESS'       //上传状态，成功时返回SUCCESS,其他任何值将原样返回至图片上传框中
     * }
     */
    public function actionImageUp(){
        //上传图片框中的描述表单名称
        $title = htmlspecialchars( $_POST['pictitle'], ENT_QUOTES );
        //@todo 已知BUG，opera浏览器无法post dir
        $path = $_POST['dir'] ? $_POST['dir'] : array_shift( array_keys( $this->imgPath ) );
        if( !array_key_exists( $path, $this->imgPath ) ){
            echo '{"state":"\u975e\u6cd5\u4e0a\u4f20\u76ee\u5f55"}';
            Yii::app()->end();
        }

        $path = $this->imgPath[$path];
        //上传配置
		$config = array(
				"savePath" => $path,
				"maxSize" => $this->imageMaxSize, //单位KB
				"allowFiles" => $this->allowFiles['image'],
		);
		//生成上传实例对象并完成上传
        $up = new Uploader( $this->fileField, $config );
        $info = $up->getFileInfo();
        echo "{'url':'".$info["url"]."','title':'".$title."','original':'".$info["originalName"]."','state':'".$info["state"]."'}";
    }

    /**
     * 涂鸦上传。
     * 先上传背景图，背景图存放在上传目录的/tmp/目录下。
     * 再用base64上传涂鸦。
     * 上传完毕后删除/tmp/目录及其子目录和文件。
     */
    public function actionScrawlUp(){

		$config = array(
				"savePath" => $this->savePath . Yii::app()->user->id. '/scrawl/' ,
				"maxSize" => $this->imageMaxSize ,                   //允许的文件最大尺寸，单位KB
				"allowFiles" => $this->allowFiles['image']  //允许的文件格式
        );
        //临时文件目录
        $tmpPath = $this->savePath."tmp/";

        //获取当前上传的类型
        $action = htmlspecialchars( $_GET["action"] );

        if( $action == "tmpImg" ){ // 背景上传
            //背景保存在临时目录中
            $config["savePath"] = $tmpPath;
            $up = new Uploader( $this->fileField, $config );
            $info = $up->getFileInfo();
            /**
             * 返回数据，调用父页面的ue_callback回调
             */
            echo "<script>parent.ue_callback('".$info["url"]."','".$info["state"]."')</script>";
        }else{
            //涂鸦上传，上传方式采用了base64编码模式，所以第三个参数设置为true
            $up = new Uploader( $this->scrawlField, $config, true );
            //上传成功后删除临时目录
            if( file_exists( $tmpPath ) ){
                $this->delDir( $tmpPath );
            }
            $info = $up->getFileInfo();
            echo "{'url':'".$info["url"]."',state:'".$info["state"]."'}";
        }
    }

    /**
     * 远程抓取图片
     * 返回数据格式
     * {
     *   'url'   : '新地址一ue_separate_ue新地址二ue_separate_ue新地址三',
     *   'srcUrl': '原始地址一ue_separate_ue原始地址二ue_separate_ue原始地址三'，
     *   'tip'   : '状态提示'
     * }
     */
    public function actionGetRemoteImage(){
		$config = array(
				"savePath" => $this->savePath . 'remoteimage/' ,
				"allowFiles" => $this->allowFiles['image'] ,
				"maxSize" => $this->imageMaxSize
		);
        if( !isset( $_POST[$this->remoteImageField] ) ){
            echo '{"url":"error","tip":"远程图片抓取失败！","srcUrl":""}';
            Yii::app()->end();
        }
        $uri = htmlspecialchars( $_POST[$this->remoteImageField] );
        $uri = str_replace( "&amp;", "&", $uri );

        //忽略抓取时间限制
        set_time_limit( 0 );
        //ue_separate_ue  ue用于传递数据分割符号
        $imgUrls = explode( 'ue_separate_ue', $uri );
        $tmpNames = array();
        foreach( $imgUrls as $imgUrl ){
            //http开头验证
            if( strpos( $imgUrl, "http" ) !== 0 ){
                array_push( $tmpNames, "error" );
                continue;
            }
            //获取请求头
            $heads = get_headers( $imgUrl );
            //死链检测
            if( !( stristr( $heads[0], "200" ) && stristr( $heads[0], "OK" ) ) ){
                array_push( $tmpNames, "error" );
                continue;
            }

            //格式验证(扩展名验证和Content-Type验证)
            $fileType = strtolower( strrchr( $imgUrl, '.' ) );
            if( !in_array( $fileType, $config['allowFiles'] ) || stristr( $heads['Content-Type'], "image" ) ){
                array_push( $tmpNames, "error" );
                continue;
            }

            //打开输出缓冲区并获取远程图片
            ob_start();
			$context = stream_context_create(
					array(
							'http' => array('follow_location' => false)
					)
			);
            //请确保php.ini中的fopen wrappers已经激活
            readfile( $imgUrl, false, $context );
            $img = ob_get_contents();
            ob_end_clean();

            //大小验证
            $uriSize = strlen( $img ); //得到图片大小
            $allowSize = 1024 * $config['maxSize'];
            if( $uriSize > $allowSize ){
                array_push( $tmpNames, 'error' );
                continue;
            }
            //创建保存位置
            $savePath = $config['savePath'];
            if( !file_exists( $savePath ) ){
                mkdir( $savePath, 0777 );
            }
            //写入文件
            $r_name = time().rand( 1, 10000 ).strrchr( $imgUrl, '.' );
            $tmpName = $savePath.$r_name;
            try{
                @file_put_contents( $tmpName, $img );
                //对远程图片获取单独处理缩略图
                require_once( dirname( __FILE__ ).'/tpImage.php' );
                $image = new tpImage();
                $image->open( $tmpName );
                //由于保存规则中图片名字是数字，所以这里可以直接替换
                $tmp = explode( '.', $r_name );
                $tmp = $savePath.$tmp[0].'.thumbnail.'.$tmp[1];
                $image->thumb( 200, 200 );
                $image->save( $tmp );
                array_push( $tmpNames, $tmpName );
            }catch( Exception $e ){
                array_push( $tmpNames, "error" );
            }
        }
        /**
         * 返回数据格式
         * {
         *   'url'   : '新地址一ue_separate_ue新地址二ue_separate_ue新地址三',
         *   'srcUrl': '原始地址一ue_separate_ue原始地址二ue_separate_ue原始地址三'，
         *   'tip'   : '状态提示'
         * }
         */
        echo "{'url':'".implode( "ue_separate_ue", $tmpNames )."','tip':'远程图片抓取成功！','srcUrl':'".$uri."'}";
    }

    /**
     * 插入视频
     */
    public function actionGetMovie(){
        $key = htmlspecialchars( $_POST["searchKey"] );
        $type = htmlspecialchars( $_POST["videoType"] );
        $html = file_get_contents( 'http://api.tudou.com/v3/gw?method=item.search&appKey=myKey&format=json&kw='.$key.'&pageNo=1&pageSize=20&channelId='.$type.'&inDays=7&media=v&sort=s' );
        echo $html;
    }

    /**
     * 图片管理
     */
    public function actionImageManager(){
        //需要遍历的目录列表，最好使用缩略图地址，否则当网速慢时可能会造成严重的延时
        //开启缩略图模式，缩略图特征为 .thumbnail.
		$paths = array(
					$this->savePath.'image/',
					$this->savePath.Yii::app()->user->id,
					$this->savePath.'remoteimage/',
        );

        $action = $_POST["action"];
        if( $action == 'get' ){
            $files = array();
            foreach( $paths as $path ){
                $tmp = $this->getfiles( $path );
                if( $tmp ){
                    $files = array_merge( $files, $tmp );
                }
            }
            if( !count( $files ) ) return;
            rsort( $files, SORT_STRING );
            $str = '';
            foreach( $files as $file ){
                $str .= $file.'ue_separate_ue';
            }
            echo $str;
        }
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param array $files
     * @return array
     */
    private function getfiles( $path, &$files = array() ){
        if( !is_dir( $path ) ) return null;
        $handle = opendir( $path );
        while( false !== ( $file = readdir( $handle ) ) ){
            if( $file != '.' && $file != '..' ){
                $path2 = $path.'/'.$file;
                if( is_dir( $path2 ) ){
                    $this->getfiles( $path2, $files );
                }else{
                    if( preg_match( '/\.thumbnail\.(gif|jpeg|jpg|png|bmp)$/i', $file ) ){
                        //判断是否存在缩略图对应的原图
                        $tmp = str_replace( '.thumbnail', '', $file );
                        if( file_exists( $path.'/'.$tmp ) ) $files[] = $path2;
                    }
                }
            }
        }
        return $files;
    }

    /**
     * 删除整个目录。在上传涂鸦中在删除临时文件的地方调用。
     * @param $dir
     * @return bool
     */
    public function delDir( $dir ){
        //先删除目录下的所有文件：
        $dh = opendir( $dir );
        while( $file = readdir( $dh ) ){
            if( $file != "." && $file != ".." ){
                $fullpath = $dir."/".$file;
                if( !is_dir( $fullpath ) ){
                    unlink( $fullpath );
                }else{
                    $this->delDir( $fullpath );
                }
            }
        }
        closedir( $dh );
        //删除当前文件夹：
        return rmdir( $dir );
    }
}
