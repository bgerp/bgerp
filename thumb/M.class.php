<?php



/**
 * Клас 'thumb_M' - Контролер за умалени изображения
 *
 *
 * @category  vendors
 * @package   thumb
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 */
class thumb_M extends core_Mvc
{
    function act_R()
    {
        $id = Request::get('t');
        
        // Премахва фиктивното файлово разширение
        list($id, $ext) = explode('.', $id);

        $arguments = core_Crypt::decodeVar($id, thumb_Img::getCryptKey());

        $this->thumb = new thumb_Img($arguments);
        
        if( file_exists($file = $this->thumb->getThumbPath()) ) {
            $ext = fileman_Files::getExt($file);
            self::addTypeHeader($ext);
            header('Content-Length: ' . filesize($file));
            readfile($file);
            flush();

            shutdown();
        } else {

            self::addTypeHeader($ext);

            redirect($this->thumb->getUrl('forced'));
        }
    }

    
    /**
     * Добавя хедър за mime тип, в зависимост от разширението на графичния файл
     */
    static function addTypeHeader($ext)
    {
        $typeByExt = array('jpg' => 'jpeg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'bmp' => 'bmp', 'png' => 'png');
        if($type = $typeByExt[strtolower($ext)]) {
            header("Content-Type: image/{$type}");
        }
    }


    function on_Shutdown()
    {
        $this->thumb->getUrl('forced');
    }
}