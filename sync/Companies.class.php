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
class sync_Companies extends core_Manager
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

        'crm_Locations' => array(
            array('sales_Routes' => 'locationId'),
        ),
        'price_Lists' => array(
            array('price_ListRules' => 'listId'),
        ),
    );


    /**
     * Глобални уникални ключове
     */
    public $globalUniqKeys = array(
        'drdata_Countries' => 'letterCode2',
        'core_Roles' => 'role',
        'core_Classes' => 'name',
        'currency_Currencies' => 'code',
    );


    /**
     * Полета от моделите, които не трябва да се експортират
     */
    public $fixedExport = array(
        '*::createdOn' => null,
        '*::createdBy' => null,
        '*::modifiedOn' => null,
        '*::modifiedBy' => null,
        '*::searchKeywords' => null,
        '*::folderId' => null,
        '*::containerId' => null,
        '*::threadId' => null,
        '*::ps5Enc' => null,
        '*::exSysId' => null,
        '*::lastLoginTime' => null,
        '*::lastLoginTime' => null,
        '*::lastLoginIp' => null,
        '*::lastActivityTime' => null,
        '*::lastUsedOn' => null,
        '*::id' => null,
    );


 
    /**
     *  Връща Json-a на филтрираните обекти
     */
    public function act_Export()
    {
        if (haveRole('user')) {
            requireRole('admin');
        } else {
            expect($remoteAddr = $_SERVER['REMOTE_ADDR']);
            
            if (defined('SYNC_EXPORT_ADDR')) {
                expect(SYNC_EXPORT_ADDR == $remoteAddr);
            } else {
                expect(core_Url::isPrivate($remoteAddr));
            }
        }
        
        core_App::setTimeLimit(1000);
        
        $groupId = sync_Setup::get('COMPANY_GROUP');
   
        $res = array();
        
        core_Users::forceSystemUser();
        
        $cQuery = crm_Companies::getQuery();
        while ($rec = $cQuery->fetch("#groupList LIKE '%|{$groupId}|%'")) {
            sync_Map::exportRec('crm_Companies', $rec->id, $res, $this);
        }
        
        core_Users::cancelSystemUser();
        
        $res = array_reverse($res, true);
        
        $res = gzcompress(serialize($res));
        
        echo $res;
        die;
    }


    /**
     * Синхронизира двете системи
     */
    public function act_Import()
    {
        requireRole('admin');
        
        $url = sync_Setup::get('EXPORT_URL');
        
        ini_set('default_socket_timeout', 600);
        
        core_App::setTimeLimit(1000);
        
        $res = file_get_contents($url);
        $res = unserialize(gzuncompress($res));
        
        core_Users::forceSystemUser();
        
        Mode::set('preventNotifications', true);
        
        foreach ($res as $class => $objArr) {
            foreach ($objArr as $id => $rec) {
                sync_Map::importRec($class, $id, $res, $this);
            }
        }
        
        crm_Groups::updateGroupsCnt('crm_Persons', 'personsCnt');
        
        core_Users::cancelSystemUser();
    }
}
