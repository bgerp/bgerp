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
        $arguments = core_Crypt::decodeVar($id);
        $thumb = new img_Thumb($arguments);

        redirect($thumb->forceUrl(FALSE));
    }
}