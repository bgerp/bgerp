<?php


/**
 * Конвертиране на файлове
 *
 * @category  vendors
 * @package   fconv
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class fconv_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('fconv_Processes', 'Процеси', 'admin');
        $this->TAB('fconv_Remote', 'Отдалечени', 'admin');
        
        $this->title = 'Процеси';
    }
}
