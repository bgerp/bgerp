<?php


/**
 * Синхронизиране на речника от replace пакета
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
 * @title     Синхронизиране на речника от replace пакета
 */
class sync_Dictionary extends sync_Helper
{
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
//         self::requireRight();

        expect(core_Packs::isInstalled('replace'));
        
        core_App::setTimeLimit(100);
        
        $res = array();
        
        core_Users::forceSystemUser();
        
        $dQuery = replace_Dictionary::getQuery();
        
        while ($rec = $dQuery->fetch()) {
            sync_Map::exportRec('replace_Dictionary', $rec->id, $res, $this);
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
        
        expect(core_Packs::isInstalled('replace'));
        
        core_App::setTimeLimit(100);
        
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
