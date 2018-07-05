<?php



/**
 * @todo Чака за документация...
 */
defIfNot('FLVPLAYER_PATH', sbf('flvplayer/1.6.0/player_flv_maxi.swf'));


/**
 * Генерира необходимият код за плейване на flv файлове
 *
 *
 * @category  vendors
 * @package   flvplayer
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class flvplayer_Embedder
{
    
    
    /**
     * @todo Чака за документация...
     */
    public static function render($flvFile, $width, $height, $startImage, $params = array())
    {
        $swfObj = cls::get('swf_Object');
        
        $swfObj->setSwfFile(FLVPLAYER_PATH);
        
        $altHtml = new ET("<a href='[#altVideoFile#]'" .
            "style='background-color: black;'>" .
            "<img src='[#startImage#]' width=[#width#] height=[#height#]></a>
                    ");
        
        $flashvars = array(
            'flv' => $flvFile,
            'startimage' => $startImage,
            'width' => $width,
            'height' => $height
        );
        
        $swfObj->setAlternativeContent($altHtml);
        $swfObj->setWidth($width);
        $swfObj->setHeight($height);
        $swfObj->setFlashvars($flashvars);
        $swfObj->others = $params;
        
        return $swfObj->getContent();
    }
}
