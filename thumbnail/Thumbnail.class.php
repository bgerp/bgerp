<?php

cls::load('fileman_Download');


/**
 * @todo Чака за документация...
 */
defIfNot('THUMBNAIL_FOLDER', EF_DOWNLOAD_DIR . '/' . 'TB');


/**
 * @todo Чака за документация...
 */
defIfNot('THUMBNAIL_URL', EF_DOWNLOAD_ROOT . '/' . 'TB');


/**
 * Клас 'thumbnail_Thumbnail' -
 *
 *
 * @category  vendors
 * @package   thumbnail
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class thumbnail_Thumbnail extends core_Manager {
    
    
    /**
     * Връща елемент IMG с оказаната големина (max)
     */
    static function getImg($fh, $size, $attr = array())
    {
        $attr['src'] = thumbnail_Thumbnail::getLink($fh, $size, $attr);
        
        if(!isset($attr['alt'])) {
            $attr['alt'] = $attr['baseName'];
        }
        
        unset($attr['baseName']);
        
        return ht::createElement('img', $attr);
    }
    
    
    /**
     * Преоразмерява картинките да се хванат в размер 120х120
     */
    static function getLink($fh, $size, &$attr)
    {
        $fileName = fileman_Files::fetchByFh($fh, 'name');
        $ext = mb_substr($fileName, mb_strrpos($fileName, '.') + 1);
        
        if($attr['baseName']) {
            $baseName = $attr['baseName'];
        } else {
            $baseName = baseName($fileName, "." . $ext);
            $attr['baseName'] = $baseName;
        }
        
        $ext = mb_strtolower($ext);
        
        // Очакваме да е от познатите разширения за растерни файлове
        expect($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif' || $ext == 'bmp', $ext);
        
        // Ако не са зададени параметрите
        if (!isset($attr['isAbsolute'])) $attr['isAbsolute'] = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        if(is_array($size)) {
            $thumbFilePath = THUMBNAIL_FOLDER . "/" . $baseName . "-" . $fh . "-" . $size[0] . "-" . $size[1] . "." . $ext;
            $thumbFileUrl = sbf(THUMBNAIL_URL . "/" . $baseName . "-" . $fh . "-" . $size[0] . "-" . $size[1] . "." . $ext, $attr['qt'], $attr['isAbsolute']);
        } else {
            $thumbFilePath = THUMBNAIL_FOLDER . "/" . $baseName . "-" . $fh . "-" . $size . "." . $ext;
            $thumbFileUrl = sbf(THUMBNAIL_URL . "/" . $baseName . "-" . $fh . "-" . $size . "." . $ext, $attr['qt'], $attr['isAbsolute']);
        }

        if(!file_exists($thumbFilePath)) {
            $filePath = fileman_Files::fetchByFh($fh, 'path');
            
            if (($thumbFile = self::makeThumbnail($filePath, $size)) === FALSE) {
                // Неуспех при създаването на тъмбнейл
                /**
                 * @TODO: До тук се стига при невъзможност за създаване на thumbnail. Може
                 * да настроим $thumbFilePath и $thumbFileUrl така, че да сочат към стандартна
                 * картинка, изобразяваща липсващо изображение.
                 */
                return FALSE;
            } else {
                static::saveImage($thumbFile, $thumbFilePath);
            }
        }
        
        $info = getimagesize($thumbFilePath);
        
        $attr['width'] = isset($info['width']) ? $info['width'] : $info[0];
        $attr['height'] = isset($info['height']) ? $info['height'] : $info[1];
        
        return $thumbFileUrl;
    }
    
    
    /**
     * Create a thumbnail image from $inputFileName no taller or wider than
     * $maxSize. Returns the new image resource or false on error.
     * Author: mthorn.net
     */
    static function makeThumbnail($inputFileName, $size)
    {
        if(is_array($size)) {
            $maxWidth = $size[0];
            $maxHeight = $size[1];
            
            if(!$maxHeight) $maxHeight = $maxWidth;
            
            if(!$maxWidth) $maxWidth = $maxHeight;
        } else {
            $maxWidth = $maxHeight = $size;
        }
        
        if(!is_resource($inputFileName)) {
            if (!file_exists($inputFileName)) {
                // Файлът с изображението не може да бъде прочетен
                return FALSE;
            }
            
            // Using imagecreatefromstring will automatically detect the file type
            if (($sourceImage = @imagecreatefromstring(file_get_contents($inputFileName))) === FALSE) {
                // Could not load image
                return FALSE;
            }
            
            $info = getimagesize($inputFileName);
            
            if(!$info['type']) {
                $info['type'] = exif_imagetype($inputFileName);
            }
        } else {
            $sourceImage = $inputFileName;
        }
        
        if($info == FALSE) {
            $info['width']  = imagesx($sourceImage);
            $info['height'] = imagesy($sourceImage);
        }
        
        $type = isset($info['type']) ? $info['type'] : $info[2];
        
        // Check support of file type
        if (!(imagetypes() & $type)) {
            // Server does not support file type
            return FALSE;
        }
        
        $width = isset($info['width']) ? $info['width'] : $info[0];
        $height = isset($info['height']) ? $info['height'] : $info[1];
        
        // Calculate aspect ratio
        $wRatio = $maxWidth / $width;
        $hRatio = $maxHeight / $height;
        
    	// Ако е FALSE взимаме по малкото отношение да стане с размери максимум
    	// подадените, иначе взимаме по-голямото отношение и изображението става
    	// до минимум подадените размери
       
        if($size['max'] !== TRUE)
        {$ratio = min($wRatio, $hRatio, 1);}
	    else 
	    {$ratio = max($wRatio, $hRatio, 1);}
        
        $tHeight = ceil($ratio * $height);
        $tWidth = ceil($ratio * $width);
        
        $thumb = imagecreatetruecolor($tWidth, $tHeight);
        
        // Copy resampled makes a smooth thumbnail
        thumbnail_Thumbnail::fastimagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $tWidth, $tHeight, $width, $height);
        imagedestroy($sourceImage);
        
        return $thumb;
    }
    
    
    /**
     * Функция, която получава гд ресурс - картинка и я смалява до определения размер
     */
    static function resample($img, $size)
    {
        if(is_array($size)) {
            $maxWidth = $size[0];
            $maxHeight = $size[1];
            
            if(!$maxHeight) $maxHeight = $maxWidth;
            
            if(!$maxWidth) $maxWidth = $maxHeight;
        } else {
            $maxWidth = $maxHeight = $size;
        }
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        // Calculate aspect ratio
        $wRatio = $maxWidth / $width;
        $hRatio = $maxHeight / $height;
        
        // Using imagecreatefromstring will automatically detect the file type
        $sourceImage = $img;
        
        $ratio = min($wRatio, $hRatio, 1);
        
        $tHeight = ceil($ratio * $height);
        $tWidth = ceil($ratio * $width);
        
        $thumb = imagecreatetruecolor($tWidth, $tHeight);
        
        if ($sourceImage === false) {
            // Could not load image
            return false;
        }
        
        // Copy resampled makes a smooth thumbnail
        thumbnail_Thumbnail::fastimagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $tWidth, $tHeight, $width, $height);
        imagedestroy($sourceImage);
        
        return $thumb;
    }
    
    
    /**
     * Save the image to a file. Type is determined from the extension.
     * $quality is only used for jpegs.
     * Author: mthorn.net
     */
    static function saveImage($im, $fileName, $quality = 90)
    {
        if (!$im || file_exists($fileName)) {
            return false;
        }
        
        $ext = fileman_Files::getExt($fileName);
      
        switch ($ext) {
            case 'gif' :
                $res = imagegif($im, $fileName);
                break;
            case 'jpg' :
            case 'jpeg' :
                $res = imagejpeg($im, $fileName, $quality);
                break;
            case 'png' :
                $res = imagepng($im, $fileName);
                break;
            case 'bmp' :
                $res = imagewbmp($im, $fileName);
                break;
            default :
            $res = FALSE;
        }

        return $res;
    }
    
    
    /**
     * Създаваме папката, където ще слагаме умалените изображения
     */
    static function on_AfterSetupMVC($mvc, &$result)
    {
        if(!is_dir(THUMBNAIL_FOLDER)) {
            if (mkdir(THUMBNAIL_FOLDER, 0777, TRUE)) {
            	$result .= "<li style='color:green;'> Създадена папка за умалени изображения: " . THUMBNAIL_FOLDER;
            } else {
            	$result .= "<li style='color:red;'> Не е създадена папка за умалени изображения: " . THUMBNAIL_FOLDER;
            }
        } else {
            $result .= "<li> Папката за умалени изображения съществува от преди: " . THUMBNAIL_FOLDER;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function fastimagecopyresampled(&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3)
    {
        // Plug-and-Play fastimagecopyresampled function replaces much slower imagecopyresampled.
        // Just include this function and change all "imagecopyresampled" references to "fastimagecopyresampled".
        // Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
        // Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
        //
        // Optional "quality" parameter (defaults is 3). Fractional values are allowed, for example 1.5. Must be greater than zero.
        // Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
        // 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
        // 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
        // 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
        // 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
        // 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.
        
        if (empty($src_image) || empty($dst_image) || $quality <= 0) { return false;
        }
        
        if ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
            $temp = imagecreatetruecolor ($dst_w * $quality + 1, $dst_h * $quality + 1);
            imagecopyresized ($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1, $dst_h * $quality + 1, $src_w, $src_h);
            imagecopyresampled ($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $dst_w * $quality, $dst_h * $quality);
            imagedestroy ($temp);
        } else imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        
        return true;
    }
}