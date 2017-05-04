<?php



/**
 * Клас 'uiext_Wrapper' 
 *
 *
 * @category  bgerp
 * @package   uiext
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class uiext_Wrapper extends plg_ProtoWrapper
{
	
	
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        $this->TAB('uiext_Labels', 'Тагове', 'uiext, admin, ceo');
        $this->TAB('uiext_DocumentLabels', 'Документи', 'debug');
    }
}