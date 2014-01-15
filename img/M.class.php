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
        list($id,) = explode('.', $id);

        $arguments = core_Crypt::decodeVar($id, img_Thumb::getCryptKey());

        $thumb = new img_Thumb($arguments);
        
        if( file_exists($file = $thumb->getThumbPath()) ) {
            $type = fileman_Files::getExt($file);
            if($type == 'jpg') {
                $type = 'jpeg';
            }
            header("Content-Type: image/{$type}");
            header('Content-Length: ' . filesize($file));
            readfile($file);
            flush();
            $this->thumb = $thumb;

            shutdown();
        }

        redirect($thumb->getUrl('forced'));
    }


    function on_Shutdown()
    {
        $this->thumb->getUrl('forced');
    }
}