<?php

class AsiaConnect_Gallery_Helper_Image extends Mage_Core_Helper_Abstract
{
    protected $_model;
    protected $_scheduleResize = false;
    protected $_scheduleWatermark = false;
    protected $_scheduleRotate = false;
    protected $_angle;
    protected $_watermark;
    protected $_watermarkPosition;
    protected $_watermarkSize;
    protected $_imageFile;
    protected $_placeholder;
	protected $set_background_color;
	protected $_view_mode;
    /**
     * Reset all previos data
     */
    protected function _reset()
    {
        $this->_model = null;
        $this->_scheduleResize = false;
        $this->_scheduleWatermark = false;
        $this->_scheduleRotate = false;
        $this->_angle = null;
        $this->_watermark = null;
        $this->_watermarkPosition = null;
        $this->_watermarkSize = null;
        $this->_imageFile = null;
        return $this;
    }

    public function init($imageFile, $mode="detail")
    {
    	$this->_reset();
    	$this->_view_mode = $mode;
        $this->_setModel(Mage::getModel('gallery/image'));
        //$this->_getModel()->setDestinationSubdir($attributeName);
        $this->keepTransparency(true);
        $this->_getModel()->setBackgroundColor($this->getStoreBackgroundColor());
		$this->setImageFile($imageFile);
        return $this;
    }
    public function resize($width, $height = null, $background = true)
    {
        $this->_getModel()->setWidth($width)->setHeight($height);
        $this->_scheduleResize = true;
        $this->set_background_color = $background;
        return $this;
    }
    public function keepAspectRatio($flag)
    {
        $this->_getModel()->setKeepAspectRatio($flag);
        return $this;
    }
    public function keepFrame($flag, $position = array('center', 'middle'))
    {
        $this->_getModel()->setKeepFrame($flag);
        return $this;
    }
    public function keepTransparency($flag, $alphaOpacity = null)
    {
        $this->_getModel()->setKeepTransparency($flag);
        return $this;
    }

    public function constrainOnly($flag)
    {
        $this->_getModel()->setConstrainOnly($flag);
        return $this;
    }

    public function backgroundColor($colorRGB)
    {
        // assume that 3 params were given instead of array
        if (!is_array($colorRGB)) {
            $colorRGB = func_get_args();
        }
        $this->_getModel()->setBackgroundColor($colorRGB);
        return $this;
    }

    public function rotate($angle)
    {
        $this->setAngle($angle);
        $this->_getModel()->setAngle($angle);
        $this->_scheduleRotate = true;
        return $this;
    }

    public function watermark($fileName, $position, $size=null)
    {
        $this->setWatermark($fileName)
            ->setWatermarkPosition($position)
            ->setWatermarkSize($size);
        $this->_scheduleWatermark = true;
        return $this;
    }

    public function placeholder($fileName)
    {
        $this->_placeholder = $fileName;
    }

    public function getPlaceholder()
    {
        if (!$this->_placeholder) {
            $attr = $this->_getModel()->getDestinationSubdir();
            $this->_placeholder = 'images/catalog/product/placeholder/'.$attr.'.jpg';
        }
        return $this->_placeholder;
    }

    public function __toString()
    {
    	try {
            if( $this->getImageFile() ) {				
                $this->_getModel()->setBaseFile( $this->getImageFile() );
            }
            
            if(!$this->set_background_color || $this->_view_mode=='detail'?!strlen(Mage::getStoreConfig('gallery/info/photo_background_color')):!strlen(Mage::getStoreConfig('gallery/info/simple_photo_background_color'))){
				$info = getimagesize($this->_getModel()->getBaseFile());
				
				$oldWidth 	= $info[0];
				$oldHeight 	= $info[1];
				$newWidth = $this->_getModel()->getWidth();
				$newHeight = $this->_getModel()->getHeight();
	
				if($oldWidth*1.0/$newWidth < $oldHeight*1.0/$newHeight) $newWidth = 1.0*$oldWidth * $newHeight/$oldHeight;
				else $newHeight = 1.0*$oldHeight * $newWidth/$oldWidth;
				
				$this->_getModel()->setWidth($newWidth);
				$this->_getModel()->setHeight($newHeight);
            }
            
            if( $this->_getModel()->isCached() ) {
                return $this->_getModel()->getUrl();
            } else {
                if( $this->_scheduleRotate ) {
                    $this->_getModel()->rotate( $this->getAngle() );
                }

                if ($this->_scheduleResize) {
                    $this->_getModel()->resize();
                }

                if( $this->_scheduleWatermark ) {
                    $this->_getModel()
                        ->setWatermarkPosition( $this->getWatermarkPosition() )
                        ->setWatermarkSize($this->parseSize($this->getWatermarkSize()))
                        ->setWatermark($this->getWatermark(), $this->getWatermarkPosition());
                } else {
                    if( $watermark = Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_image") ) {
                        $this->_getModel()
                            ->setWatermarkPosition( $this->getWatermarkPosition() )
                            ->setWatermarkSize($this->parseSize($this->getWatermarkSize()))
                            ->setWatermark($watermark, $this->getWatermarkPosition());
                    }
                }
                $url = $this->_getModel()->saveFile()->getUrl();
            }
        } catch( Exception $e ) {
			//Mage::log($e);
            $url = Mage::getDesign()->getSkinUrl($this->getPlaceholder());
        }
        return $url;
    }
    protected function _setModel($model)
    {
        $this->_model = $model;
        return $this;
    }
    protected function _getModel()
    {
        return $this->_model;
    }

    protected function setAngle($angle)
    {
        $this->_angle = $angle;
        return $this;
    }

    protected function getAngle()
    {
        return $this->_angle;
    }

    protected function setWatermark($watermark)
    {
        $this->_watermark = $watermark;
        return $this;
    }

    protected function getWatermark()
    {
        return $this->_watermark;
    }

    protected function setWatermarkPosition($position)
    {
        $this->_watermarkPosition = $position;
        return $this;
    }

    protected function getWatermarkPosition()
    {
        if( $this->_watermarkPosition ) {
            return $this->_watermarkPosition;
        } else {
            return Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_position");
        }
    }

    public function setWatermarkSize($size)
    {
        $this->_watermarkSize = $size;
        return $this;
    }

    protected function getWatermarkSize()
    {
        if( $this->_watermarkSize ) {
            return $this->_watermarkSize;
        } else {
            return Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_size");
        }
    }

    protected function setImageFile($file)
    {
        $this->_imageFile = $file;
        return $this;
    }

    protected function getImageFile()
    {
        return $this->_imageFile;
    }

    /**
     * Enter description here...
     *
     * @return array
     */
    protected function parseSize($string)
    {
        $size = explode('x', strtolower($string));
        if( sizeof($size) == 2 ) {
            return array(
                'width' => ($size[0] > 0) ? $size[0] : null,
                'heigth' => ($size[1] > 0) ? $size[1] : null,
            );
        }
        return false;
    }
    public function getStoreBackgroundColor()
    {
    	$color = $this->_view_mode=="detail"?explode(',',Mage::getStoreConfig('gallery/info/photo_background_color')):explode(',',Mage::getStoreConfig('gallery/info/simple_photo_background_color'));
    	if(sizeof($color)==3)
    	{
    		foreach($color as $item){
    			if(!is_numeric($item) || $item >255) return array(192, 192, 192);
    		}
    		return array((int)$color[0], (int)$color[1], (int)$color[2]);
    	}
    	return array(192, 192, 192);
    }
    function imagecreatefromfile($image_path) {
        // retrieve the type of the provided image file
        list($width, $height, $image_type) = getimagesize($image_path);

        // select the appropriate imagecreatefrom* function based on the determined
        // image type
        switch ($image_type)
        {
          case IMAGETYPE_GIF: return imagecreatefromgif($image_path); break;
          case IMAGETYPE_JPEG: return imagecreatefromjpeg($image_path); break;
          case IMAGETYPE_PNG: return imagecreatefrompng($image_path); break;
          default: return ''; break;
        }
    }
    public function watermark_image_test($target, $watermark, $newcopy) {
        // load source image to memory
        $image = $this->imagecreatefromfile($target);
        if (!$image) die('Unable to open image');

        // load watermark to memory
        $watermark = $this->imagecreatefromfile($watermark);
        if (!$image) die('Unable to open watermark');

        // calculate the position of the watermark in the output image (the
        // watermark shall be placed in the lower right corner)
        $watermark_pos_x = imagesx($image) - imagesx($watermark) - 8;
        $watermark_pos_y = imagesy($image) - imagesy($watermark) - 10;

        // merge the source image and the watermark
        imagecopy($image, $watermark,  $watermark_pos_x, $watermark_pos_y, 0, 0,
        imagesx($watermark), imagesy($watermark),50);

        // output watermarked image to browser
        header('Content-Type: image/jpeg');
        imagejpeg($image, $newcopy, 100);  // use best image quality (100)

        // remove the images from memory
        imagedestroy($image);
        imagedestroy($watermark);
    }
    public function watermark_image($target) {
        $watermark = 'watermark2k.png';
        $watermark = Mage::getBaseDir('media').DS.'watermark'.DS."watermark2k.png";
        //$image = '/usr/www/users/schnew/shop/media/gallery/29/2016-01-09-16-53-20.JPG';
        Mage::log("target = ".$target,NULL,"ptg.log");
        $image = Mage::getBaseDir('media').DS.$target;
        Mage::log("image main = ".$image,NULL,"ptg.log");
        $save_watermark_photo_address = Mage::getBaseDir('media').DS.'watermark'.DS.$target;
        // create image:

        $_image = new Varien_Image($image);
        $_image->save($save_watermark_photo_address);



        //$save_watermark_photo_address = 'watermark_photo.jpg';
        // load source image to memory
        $image = $this->imagecreatefromfile($image);
        if (!$image) {
            echo $image;
            echo "<br>";
            echo $target;
            die('Unable to open image');
        }
        Mage::log("watermark = ".$watermark,NULL,"ptg.log");
        Mage::log("image = ".$image,NULL,"ptg.log");
        Mage::log("save_watermark_photo_address = ".$save_watermark_photo_address,NULL,"ptg.log");

        // load watermark to memory
        $watermark = $this->imagecreatefromfile($watermark);
        if (!$watermark) die('Unable to open watermark');

        // calculate the position of the watermark in the output image (the
        // watermark shall be placed in the lower right corner)
        $watermark_pos_x = imagesx($image) - imagesx($watermark) - 8;
        $watermark_pos_y = imagesy($image) - imagesy($watermark) - 10;

        // merge the source image and the watermark
        imagecopy($image, $watermark,  $watermark_pos_x, $watermark_pos_y, 0, 0,imagesx($watermark), imagesy($watermark));

        // output watermarked image to browser
        //header('Content-Type: image/jpeg');
        imagejpeg($image, $save_watermark_photo_address, 100);  // use best image quality (100)

        // remove the images from memory
        imagedestroy($image);
        imagedestroy($watermark);
        
        return 'watermark'.DS.$target;
    }

}