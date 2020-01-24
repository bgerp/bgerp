<?php


/**
 * Синхронизиране на е-магазин между bgERP системи
 *
 *
 * @category  bgerp
 * @package   synck
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2020 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Синхронизиране на е-магазин между bgERP системи
 */
class sync_Eshop extends sync_Helper
{
    
    /**
     * Какво друго да експортираме?
     */
    public $exportAlso = array(
            'eshop_Products' => array(
                    array('eshop_ProductDetails' => 'eshopProductId'),
            ),
            'price_Lists' => array(
                    array('price_ListRules' => 'listId'),
            ),
            'cat_Products' => array(
                    array('cat_products_Packagings' => 'productId'),
                    array('price_ListRules' => 'productId'),
                    array('cat_products_Params' => 'classId|productId'),
            ),
    );
    
    
    /**
     * На кои класове да се търси аналог в системата
     */
    public $mapClass = array('cms_Domains' => array('domain', 'lang'));
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        self::requireRight();

        expect(core_Packs::isInstalled('eshop'));
        
        core_App::setTimeLimit(1000);
        
        $res = array();
        
        core_Users::forceSystemUser();
        
        $eQuery = eshop_Products::getQuery();
        
        $groups = sync_Setup::get('ESHOP_GRPUPS');
        
        if ($groups) {
            $eQuery->in('groupId', type_Keylist::toArray($groups));
        }
        
        while ($rec = $eQuery->fetch()) {
            sync_Map::exportRec('eshop_Products', $rec->id, $res, $this);
        }
        
        core_Users::cancelSystemUser();
        
        return self::outputRes($res);
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        self::requireRight('import');
        
        expect(core_Packs::isInstalled('eshop'));
        
        core_App::setTimeLimit(1000);
        
        $resArr = self::getDataFromUrl(get_called_class());
        
        core_Users::forceSystemUser();
        
        Mode::set('preventNotifications', true);
        
        foreach ($resArr as $class => $objArr) {
            foreach ($objArr as $id => $rec) {
                sync_Map::importRec($class, $id, $resArr, $this);
            }
        }
    }
}
