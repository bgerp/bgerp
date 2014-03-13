<?php



/**
 * Клас 'marketing_InquirySourceIntf' - Интерфейс за документи генератори на заявки
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title:    Интерфейс за мениджърите на документи
 */
class marketing_InquirySourceIntf
{
    
    
    /**
     * Връща кустомизиращите параметри за запитването
     * 
     * @param int $id - ид на документ
     * @return array - масив със стойности
     */
    function getCustomizationParams($id)
    {
        $this->class->getCustomizationParams($id);
    }
}