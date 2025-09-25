<?
class img {

	var $image = '';
	var $formato = '';

	function img($sourceFile,$formato=''){
		
		
	
		
	      if($formato){
            $this->formato=strtoupper($formato);
        }else{
            //detect image format
            
        	$arr = getimagesize($sourceFile);
        	
        	$this->formato = $arr["mime"];
                    	
            /*$this->formato=ereg_replace(".*\.(.*)$","\\1",$sourceFile);
            $this->formato=strtoupper($this->formato);*/
        }

		//echo "??".$sourceFile."??";

			if ($this->formato=="JPG" || $this->formato=="JPEG" || $this->formato=="image/jpg") {
				//JPEG
				$this->image = ImageCreateFromJPEG ($sourceFile);
				$this->formato="JPEG";
			} elseif ($this->formato=="PNG" || $this->formato=="png" || $this->formato=="image/png") {
				//PNG
				$this->image = ImageCreateFromPNG ($sourceFile);
				$this->formato="PNG";
			} elseif ($this->formato=="GIF" || $this->formato=="gif" || $this->formato=="image/gif") {
				//GIF
				$this->image = ImageCreateFromGIF ($sourceFile);
				$this->formato="GIF";
			} elseif ($this->formato=="WBMP" || $this->formato=="image/wbmp") {
				//WBMP
				$this->image  = ImageCreateFromWBMP ($sourceFile);
				$this->formato="WBMP";
			} else {
			//DEFAULT
				$this->image = ImageCreateFromJPEG ($sourceFile);
				$this->formato="JPEG";
			}



		return;
	}

	function resize($width = 100, $height = 100, $aspectradio = true,$option=false){
		$o_wd = imagesx($this->image);
		$o_ht = imagesy($this->image);
		if(isset($aspectradio)&&$aspectradio) {
			$w = round($o_wd * $height / $o_ht);
			$h = round($o_ht * $width / $o_wd);
			if(($height-$h)<($width-$w)){
				$width =& $w;
			} else {
				$height =& $h;
			}
		}
		
		
		// *** if option is 'crop', then crop too

		
		$this->temp = imageCreateTrueColor($width,$height);
		
		if ($option == 'crop') {
			imageCopyResampled($this->temp, $this->image, 0, 0, 0, 0, $o_wd, $o_ht, $width, $height);
		}else{
			imageCopyResampled($this->temp, $this->image, 0, 0, 0, 0, $width, $height, $o_wd, $o_ht);
		}
		
		$this->sync();
		return;
	}
	
	function calcularX(){
	
		$x = imagesx($this->image);
		return $x;
	
	}
	
	function rotar($grados){
	    
	    $grados = $grados <= 0 ? abs($grados) : -$grados ;
	    
	    $this->image = imagerotate($this->image, $grados, 0);
	    
	}
	
	function calcularY(){
	
		$y = imagesy($this->image);
		return $y;
	
	}	
	
	function sync(){
		$this->image =& $this->temp;
		unset($this->temp);
		$this->temp = '';
		return;
	}

	function show(){
		$this->_sendHeader();
		ImageJPEG($this->image);
		return;
	}

	function _sendHeader(){
		header('Content-Type: image/jpeg');
	}

	function errorHandler(){
		echo "error";
		exit();
	}

	function store($file,$calidad){

		if ($this->formato=="jpg" || $this->formato=="JPG" || $this->formato=="JPEG") {
			//JPEG
			//echo $this->image;
			ImageJPEG($this->image,$file,$calidad);
			//$this->formato="JPEG";

		} elseif ($this->formato=="PNG" || $this->formato=="png") {
			//PNG
			$calidad = round(($calidad * 9)/100);
			ImagePNG($this->image,$file, $calidad);
			//$this->formato="PNG";
		} elseif ($this->formato=="GIF" || $this->formato=="gif") {
			//GIF
			ImageGIF($this->image,$file);
			//$this->formato="GIF";
		} elseif ($this->formato=="WBMP") {
			//WBMP
			ImageWBMP($this->image,$file);
			//$this->formato="WBMP";
		} else {
			//DEFAULT
			echo "Not Supported File";
			exit();
		}

		return;
	}

	function watermark($pngImage, $left = 0, $top = 0){
		ImageAlphaBlending($this->image, true);
		$layer = imagecreatefrompng($pngImage);
		$logoW = imagesx($layer);
		$logoH = imagesy($layer);
		imagecopy($this->image, $layer, $left, $top, 0, 0, $logoW, $logoH);
	}
}
?>