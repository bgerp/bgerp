<?php



/**
 * Клас 'img_M' - Контролер за умалени изображения
 *
 *
 * @category  vendors
 * @package   img
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 */
class img_M extends core_Mvc
{
    function act_R()
    {
        $id = Request::get('t');
        
        // Премахва фиктивното файлово разширение
        list($id, $ext) = explode('.', $id);

        $arguments = core_Crypt::decodeVar($id, img_Thumb::getCryptKey());

        $thumb = new img_Thumb($arguments);
        
        if( file_exists($file = $thumb->getThumbPath()) ) {
            $ext = fileman_Files::getExt($file);
            self::addTypeHeader($ext);
            header('Content-Length: ' . filesize($file));
            readfile($file);
            flush();
            $this->thumb = $thumb;

            shutdown();
        } else {

            self::addTypeHeader($ext);

            redirect($thumb->getUrl('forced'));
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