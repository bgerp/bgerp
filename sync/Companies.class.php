<?php


/**
 * Синхронизиране на фирми между bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2020 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Синхронизиране на фирми между bgERP системи
 */
class sync_Companies extends sync_Helper
{
    /**
     * Какво друго да експортираме?
     */
    public $exportAlso = array(
            'crm_Companies' => array(
                    array('crm_Locations' => 'contragentCls|contragentId'),
                    array('bank_Accounts' => 'contragentCls|contragentId'),
                    array('cond_ConditionsToCustomers' => 'cClass|cId'),
                    array('price_ListToCustomers' => 'cClass|cId'),
                    array('crm_ext_Cards' => 'contragentClassId|contragentId'),
            ),
            
            'cat_Listings' => array(
                    array('cat_ListingDetails' => 'listId'),
            ),

            'crm_Locations' => array(
                    array('sales_Routes' => 'locationId'),
            ),
            'price_Lists' => array(
                    array('price_ListRules' => 'listId'),
            ),
            'cat_Products' => array(
                    array('cat_products_Packagings' => 'productId'),
                    array('cat_products_Params' => 'classId|productId'),
            ),
    );
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        $this->requireRight();
        
        expect(core_Packs::isInstalled('crm'));
        
        core_App::setTimeLimit(1000);
        
        $groupId = sync_Setup::get('COMPANY_GROUP');
        
        expect($groupId);
        
        $res = array();
        
        core_Users::forceSystemUser();
        
        $cQuery = crm_Companies::getQuery();
        while ($rec = $cQuery->fetch("#groupList LIKE '%|{$groupId}|%'")) {
            sync_Map::exportRec('crm_Companies', $rec->id, $res, $this);
            $folderId = $rec->folderId;
            $lRec = cat_Listings::fetch("#state = 'active' AND #folderId = {$folderId}");
            if($lRec) {
                $lRec->_companyId = $rec->id;
                sync_Map::exportRec('cat_Listings', $lRec, $res, $this);
            }
        }
        
        core_Users::cancelSystemUser();
        
        return $this->outputRes($res);
    }
    
    
    /**
     * Вика се от act_Import
     */
    public static function import($update = true)
    {
        $resArr = self::getDataFromUrl(get_called_class());
        
        Mode::set('preventNotifications', true);
        
        $me = cls::get(get_called_class());
        
        foreach ($resArr as $class => $objArr) {
            self::logDebug($class . ': ' . countR($objArr));
            foreach ($objArr as $id => $rec) {
                sync_Map::importRec($class, $id, $resArr, $me, $update);
            }
        }
        
        cat_ListingDetails::delete("#productId = 0");
        
        crm_Groups::updateGroupsCnt('crm_Persons', 'personsCnt');
    }
    
    
    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        $this->requireRight('import');
        
        ini_set('memory_limit', '2048M');
        
        expect(core_Packs::isInstalled('crm'));
        
        core_App::setTimeLimit(1000);
        
        $update = (Request::get('update') == 'none') ? false : true;
        
        core_Users::forceSystemUser();
        
        return $this->import($update);
    }
}
