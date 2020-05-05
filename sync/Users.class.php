<?php


/**
 * Синхронизиране на потребители между bgERP системи
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
 * @title     Синхронизиране на потребители между bgERP системи
 */
class sync_Users extends sync_Helper
{
    
    
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        self::requireRight();
        
        core_App::setTimeLimit(1000);
        
        $res = array();
        
        core_Users::forceSystemUser();
        
        $query = crm_Profiles::getQuery();
        
        $query->EXT('groupList', 'crm_Persons', 'externalName=groupList,externalKey=personId');
        
        $groups = sync_Setup::get('CRM_GROUPS');
        
        if ($groups) {
            $query->likeKeylist('groupList', $groups);
        }
        
        while ($rec = $query->fetch()) {
            sync_Map::exportRec('crm_Profiles', $rec->id, $res, $this);
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
