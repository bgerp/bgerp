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
     * На кои класове да се търси аналог в системата
     */
    public $mapClass = array('cms_Domains' => array('domain', 'lang'));
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        self::requireRight();
        
        core_App::setTimeLimit(1000);
        
        $res = array();
        
        core_Users::forceSystemUser();
        
        $cQuery = cat_Products::getQuery();
        while ($rec = $cQuery->fetch()) {
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
