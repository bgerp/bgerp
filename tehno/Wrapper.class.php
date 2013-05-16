<?php



/**
 * Производствени технологии
 *
 *
 * @category  bgerp
 * @package   tehno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tehno_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('tehno_GeneralProducts', 'Нестандартни продукти', 'admin,tehno');
        $this->title = 'Не специфични продукти';
    }
}