<?php
    class test{
        /**
          * 处理gif图片 需要对每一帧图片处理
          * @param unknown $t_w  缩放宽
          * @param unknown $t_h  缩放高
          * @param string $isCrop  是否裁剪
          * @param number $c_w  裁剪宽
          * @param number $c_h  裁剪高
          * @param number $c_x  裁剪坐标 x
          * @param number $c_y  裁剪坐标 y
        */
        private function _resizeGif($isCrop=false, $c_w=0, $c_h=0, $c_x=0, $c_y=0){
            foreach($this->image as $img){
                if($isCrop){
                    $img->cropImage($c_w, $c_h, $c_x, $c_y);
                    //$img->setImageDispose(0);
                    $img->setImagePage($c_w, $c_h, 0, 0);
                }
            }
            $this->image->coalesceImages();
            $this->image->optimizeImageLayers();
            $this->image->deconstructImages();
            //$this->image->writeImages("/var/www/html/test/tmp.png", false);
        }


        /**
           保存GIF
        */
        private function _saveGIF(){
            $this->crc32    = sprintf("%08x", rand());
            $this->composePath = str_replace(
                $this->pathinfo['extension'],
                $this->crc32 . "." . $this->pathinfo['extension'],
                $this->path);
            if ( $ftmp = fopen($this->composePath, "w") ){ 
                $this->image->writeImagesFile($ftmp);
            }else{
                $this->out["success"] = false;
                $this->out["rettip"] = "写文件失败";
                return $this->out;
            }
        }

        public function __construct(){
            $start = microtime(true);
            $this->path     = "/usr/local/app/test.gif";
            $this->position = "south";
            $this->fonts    = "/usr/local/app/gftx.ttf";
            $this->text     = "Hello!";
            $this->fontsize = 48;
            $this->strokecolor = "#FEFEFE";
            $this->strokewidth = 2;
            $this->fontcolor= "#F49B9B";
            $this->annotate = "+0+12";
            $this->cx       = 73;
            $this->cy       = 22;
            $this->cw       = 349;
            $this->ch       = 267;
            $this->width    = 640;
            $this->height   = 320;

            $this->text_x   = 0;
            $this->text_y   = 12;
            
            $this->pathinfo = pathinfo($this->path );
            $this->crc32    = sprintf("%08x", rand());
            $this->tmpPath  = str_replace(
                $this->pathinfo['extension'],
                $this->crc32 . "." . $this->pathinfo['extension'],
                $this->path);

            $cmd = sprintf("convert %s -coalesce %s", $this->path, $this->tmpPath);
            #echo $cmd."\n";
            shell_exec($cmd);
            $this->path     = $this->tmpPath;




            $this->image = new Imagick(); 
            $this->image->readImage( $this->path );
            $this->image->stripImage (); 
            $this->image->coalesceImages();

            // 左右的时候需要处理text，以及annotate
            if (($this->position == "east") or ($this->position == "west")){
                $this->text_x = 22;
                $this->text_y = 0;
                $tmp = "";
                $text_len = mb_strlen($this->text,'utf-8');
                for($i=0; $i<$text_len; $i++){
                    $item = mb_substr($this->text,$i,1,'utf-8');
                    $tmp .= $item."\n";   
                }
                $tmp = substr($tmp, 0, -2);
                $this->text = $tmp;
            }

            // position转换位Int模式
            if ($this->position == "south"){
                $this->position = Imagick::GRAVITY_SOUTH;
            }elseif ($this->position == "north"){
                $this->position = Imagick::GRAVITY_NORTH;
            }elseif ($this->position == "west"){
                $this->position = Imagick::GRAVITY_WEST;
            }elseif ($this->position == "east"){
                $this->position = Imagick::GRAVITY_EAST;
            }
            
            // Step 1 是否需要裁剪
            if ($this->cx != 0 || $this->cy != 0 || ($this->cw > 0 && $this->cw != $width) || ($this->ch > 0 && $this->ch != $this->height)) {
                $this->_resizeGif(true, $this->cw, $this->ch, $this->cx, $this->cy);
            }

            $this->composePath = str_replace(
                $this->pathinfo['extension'],
                $this->crc32 . "." . $this->pathinfo['extension'],
                $this->path);
            $elapsed = microtime(true) - $start;
            echo "Crop cost $elapsed seconds.<br>\n";
            //$this->_saveGif();
        }

        public function noneAnim() {
            $draw = new ImagickDraw ();
            $draw->setFont($this->fonts);
            $draw->setFillColor($this->fontcolor);
            $draw->setStrokeWidth($this->strokewidth);
            $draw->setStrokeColor($this->strokecolor);
            $draw->setGravity($this->position);
            $draw->setFontSize($this->fontsize);

            if ($this->text != ""){
                foreach ( $this->image as $frame ) {
                    $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
                }
            }

            $this->_saveGIF();
            $this->out["success"] = true;
            $this->out["rettip"]  = "制作成功";
            $this->out["path"]    = $this->composePath;
            return $this->out;
        }
        /*
        闪烁效果
        */
        function blink(){
            $draw = new ImagickDraw ();
            $draw->setFont($this->fonts);
            $draw->setFillColor($this->fontcolor);
            $draw->setStrokeWidth($this->strokewidth);
            $draw->setStrokeColor($this->strokecolor);
            $draw->setGravity($this->position);
            $draw->setFontSize($this->fontsize);


            $flag = 0;
            foreach ( $this->image as $key => $frame ) {
                $flag = $flag + 1;
                if (($flag != 0) and ($flag % 6) == 0){
                    $flag = $flag + 1;
                    continue;
                }
                $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
            }
            $this->_saveGIF();
            $this->out["success"] = true;
            $this->out["rettip"]  = "制作成功";
            $this->out["path"]    = $this->composePath;
            return $this->out;
        }
        /*
        大小变化效果
        */
        public function zoomInOut() {
            $draw = new ImagickDraw ();
            $draw->setFont($this->fonts);
            $draw->setFillColor($this->fontcolor);
            $draw->setStrokeWidth($this->strokewidth);
            $draw->setStrokeColor($this->strokecolor);
            $draw->setGravity($this->position);

            $flag = 0;
            foreach ( $this->image as $frame ) {
                if ($flag %2 == 0){
                    $draw->setFontSize($this->fontsize);
                    $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
                }else{
                    $draw->setFontSize($this->fontsize + 3);
                    $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
                }
                $flag = $flag + 1;
            }
            $this->_saveGIF();
            $this->out["success"] = true;
            $this->out["rettip"]  = "制作成功";
            $this->out["path"]    = $this->composePath;
            return $this->out;
        }


        /*
        摇晃入场效果
        */
        public function sharkMoveIn() {

            $anm_num = 7;
            $count = $this->image->getNumberImages();
            if ($count < 7){
                $this->out["success"] = false;
                $this->out["rettip"] = "图片帧数过少";
                return $this->out;
            }

            $draw = new ImagickDraw ();
            $draw->setFont($this->fonts);
            $draw->setFillColor($this->fontcolor);
            $draw->setStrokeWidth($this->strokewidth);
            $draw->setStrokeColor($this->strokecolor);
            $draw->setGravity($this->position);
            $draw->setFontSize($this->fontsize);
            
            //var_dump($this->image);exit();
            $flag = 0;
            foreach ($this->image as $frame) {
                if ($flag >= $anm_num){
                    break;
                }
                if (($this->position== Imagick::GRAVITY_EAST) or ($this->position == Imagick::GRAVITY_WEST)){
                    $tmp_x = ($flag + 1) * (($this->fontsize + 22)/$anm_num) + 2 - $this->fontize;
                    if($i % 2 == 0){
                        $tmp_y = 6;
                    }else{
                        $tmp_y = -6;
                    }
                }else{
                    $tmp_y = ($flag + 1) * (($this->fontsize + 10)/$anm_num) - $this->fontsize;
                    if($flag % 2 == 0){
                        $tmp_x = 6;
                    }else{
                        $tmp_x = -6;
                    }
                }
                $flag = $flag + 1;
                $frame->annotateImage ( $draw, $tmp_x, $tmp_y, 0, $this->text );
            }

            $flag = 0;
            foreach ($this->image as $frame) {
                if ($flag < $anm_num){
                    $flag = $flag + 1;
                    continue;
                }
                if ($flag >= $count){
                    $flag = $flag + 1;
                    break;
                }
                $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
                $flag = $flag + 1;
            }
            $this->_saveGIF();
            $this->out["success"] = true;
            $this->out["rettip"]  = "制作成功";
            $this->out["path"]    = $this->composePath;
            return $this->out;
        }

        /*
        入场效果
        */
        function moveIn(){
            $anm_num = 7;
            $count = $this->image->getNumberImages();
            $count = 16;
            if ($count < 7){
                $this->out["success"] = false;
                $this->out["rettip"] = "图片帧数过少";
                return $this->out;
            }

            $draw = new ImagickDraw ();
            $draw->setFont($this->fonts);
            $draw->setFillColor($this->fontcolor);
            $draw->setStrokeWidth($this->strokewidth);
            $draw->setStrokeColor($this->strokecolor);
            $draw->setGravity($this->position);
            $draw->setFontSize($this->fontsize);

            $flag = 0;
            foreach ($this->image as $frame) {
                if ($flag >= $anm_num){
                    break;
                }
                if (($this->position== Imagick::GRAVITY_EAST) or ($this->position == Imagick::GRAVITY_WEST)){
                    $tmp_x = ($flag + 1) * (($this->fontsize + 22)/$anm_num) + 2 - $this->fontize;
                    $tmp_y = 0;
                }else{
                    $tmp_y = ($flag + 1) * (($this->fontsize + 10)/$anm_num) - $this->fontsize;
                    $tmp_x = 0;
                }
                $flag = $flag + 1;
                $frame->annotateImage ( $draw, $tmp_x, $tmp_y, 0, $this->text );
            }

            $flag = 0;
            foreach ($this->image as $frame) {
                if ($flag < $anm_num){
                    $flag = $flag + 1;
                    continue;
                }
                if ($flag >= $count){
                    $flag = $flag + 1;
                    break;
                }
                $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
                $flag = $flag + 1;
            }

            $this->_saveGIF();
            $this->out["success"] = true;
            $this->out["rettip"]  = "制作成功";
            $this->out["path"]    = $this->composePath;
            return $this->out;
        }

        /*
        依次出现
        */
        function showIn(){
            // 暂不支持左右
            if (($this->position == Imagick::GRAVITY_EAST) or ($this->position == Imagick::GRAVITY_WEST)){
                $this->out["success"] = false;
                $this->out["rettip"] = "该动画不支持左右";
                return $this->out;
            }
            $pic_num  = $this->image->getNumberImages();;
            $text_len = mb_strlen($this->text);

            if ($text_len > $pic_num/2){
                $this->out["success"] = false;
                $this->out["rettip"] = "图片不支持该动画效果";
                return $this->out;
            }

            $draw = new ImagickDraw ();
            $draw->setFont($this->fonts);
            $draw->setFillColor($this->fontcolor);
            $draw->setStrokeWidth($this->strokewidth);
            $draw->setStrokeColor($this->strokecolor);
            $draw->setGravity($this->position);
            $draw->setFontSize($this->fontsize);

            $flag   = 0;
            $flag_x = 0;
            foreach ($this->image as $frame) {
                if ($flag > $text_len){
                    break;
                }
                $tmp_text = mb_substr($this->text, 0, $flag,'utf-8');
                $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $tmp_text );
                $flag_x = $flag_x + 1;
                if ($flag_x % 2 == 0){
                    $flag = $flag + 1;
                }
            }

            $flag = 0;
            foreach ($this->image as $frame) {
                if ($flag < $text_len*2+1){
                    $flag = $flag + 1;
                    continue;
                }
                if ($flag >= $pic_num){
                    break;
                }
                $frame->annotateImage ( $draw, $this->text_x, $this->text_y, 0, $this->text );
                $flag = $flag + 1;
            }

            $this->_saveGIF();
            $this->out["success"] = true;
            $this->out["rettip"]  = "制作成功";
            $this->out["path"]    = $this->composePath;
            return $this->out;
        }

    }

    $test = new test();
    $start = microtime(true);
    //$test->noneAnim();
    //$test->blink();
    //$test->zoomInOut();
    //$test->sharkMoveIn();
    //$test->MoveIn();
    $test->showIn();
    $elapsed = microtime(true) - $start;
    echo "Add + write $elapsed seconds.<br>\n";

    echo "<br>\n=================<br>\n";
?>
