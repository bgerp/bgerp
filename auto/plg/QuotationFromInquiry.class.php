<?php


/**
 * Плъгин 'auto_plg_QuotationFromInquiry' - За автоматично създаване на оферта от запитване
 *
 *
 * @category  bgerp
 * @package   auto
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class auto_plg_QuotationFromInquiry extends core_Plugin
{
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        $Driver = $mvc->getDriver($rec);
        
        // Ако има драйвър
        if (is_object($Driver)) {
            
            if(isset($rec->proto)){
                $protoState = cat_Products::fetchField($rec->proto, 'state');
                if($protoState == 'active' && $rec->customizeProto == 'no') return;
            }
            
            // И той може да върне цена за артикула, връща се
            $Cover = doc_Folders::getCover($rec->folderId);
            if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
                if ($Driver->canAutoCalcPrimeCost($rec) === true) {

                    $rec->_domainId = Mode::is('wrapper', 'cms_page_External') ? cms_Domains::getPublicDomain()->id : cms_Domains::getCurrent('id', false);
                    auto_Calls::setCall('createdInquiryByPartner', $rec, false, true);
                }
            }
        }
    }
}
