<?php



/**
 * Производствени технологии
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
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
        $this->TAB('techno_Specifications', 'Спецификации', 'ceo,techno');
    	$this->TAB('techno_GeneralProducts', 'Универсални продукти', 'ceo,techno');
        $this->title = 'Спецификации';
    }
}