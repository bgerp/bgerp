<?php



/**
 * Производствени технологии
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('techno_GeneralProducts', 'Нестандартни продукти', 'admin,techno');
        $this->title = 'Не специфични продукти';
    }
}