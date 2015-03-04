<?php



/**
 * Производствени технологии
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
    	$this->TAB('techno2_SpecificationFolders', 'Спецификации', 'ceo,techno');
    	$this->TAB('techno2_SpecificationDoc', 'Спецификации на артикули', 'ceo,techno');
    }
}