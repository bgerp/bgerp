<?php


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с support модула
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'support_Issues';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Поддръжка на системи: сигнали и проследяването им';
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'support_Issues',
            'support_Systems',
            'support_IssueTypes',
            'support_Corrections',
            'support_Preventions',
            'support_Ratings',
            'support_Resolutions',
            'migrate::markUsedComponents',
            'migrate::componentsToResources'
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'support';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(2.14, 'Обслужване', 'Поддръжка', 'support_Tasks', 'default', 'support, admin, ceo'),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'support_TaskType';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'planning=0.1';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Support', 'Прикачени файлове в поддръжка', null, '300 MB', 'powerUser', 'every_one');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция, за маркиране на използваните компоненти
     */
    public static function markUsedComponents()
    {
        if (!cls::load('support_Components', true)) {
            
            return ;
        }
        
        $Components = cls::get('support_Components');
        if (!$Components->db->tableExists($Components->dbTableName)) {
            
            return ;
        }
        
        $query = support_Issues::getQuery();
        $query->where('#componentId IS NOT NULL');
        $query->where("#componentId != ''");
        while ($rec = $query->fetch()) {
            support_Components::markAsUsed($rec->componentId);
        }
    }
    
    
    /**
     * Миграция за мигриране на компонентите към ресурси
     */
    public static function componentsToResources()
    {
        if (!cls::load('support_Components', true)) {
            
            return ;
        }
        
        $Components = cls::get('support_Components');
        if (!$Components->db->tableExists($Components->dbTableName)) {
            
            return ;
        }
        
        $compName = 'Компоненти на системата';
        $groupId = planning_AssetGroups::fetchField("#name = '{$compName}'", 'id');
        if (!$groupId) {
            $groupId = planning_AssetGroups::save((object) array('name' => $compName));
        }
        
        $query = support_Components::getQuery();
        
        $Resources = cls::get('planning_AssetResources');
        
        while ($rec = $query->fetch()) {
            $pRec = new stdClass();
            $pRec->name = $rec->name;
            $pRec->groupId = $groupId;
            if ($rec->state == 'active') {
                $pRec->lastUsedOn = dt::now();
            }
            if ($rec->systemId) {
                $sRec = support_Systems::fetch($rec->systemId);
                $pRec->folderId = $sRec->folderId;
            }
            
            $pRec->users = $rec->maintainers;
            $pRec->state = 'active';
            
            $pRec->code = $rec->id;
            $i = 0;
            while (!$Resources->isUnique($pRec)) {
                $pRec->code = $rec->id . '_' . $rec->name;
                
                if ($i++) {
                    $pRec->code .= '_' . $i++;
                }
            }
            
            try {
                planning_AssetResources::save($pRec);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
    }
}
