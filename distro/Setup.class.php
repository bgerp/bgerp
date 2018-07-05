<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с distro
 *
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'distro_Group';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разпределена файлова група';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'ssh=0.1';
    
    
    /**
     * Мениджъри за инсталиране
     */
    public $managers = array(
            'distro_Group',
            'distro_Files',
            'distro_Automation',
            'distro_Repositories',
            'distro_Actions',
            'distro_RenameDriver',
            'distro_DeleteDriver',
            'distro_CopyDriver',
            'distro_AbsorbDriver',
            'distro_ArchiveDriver',
            'migrate::reposToKey',
            'migrate::syncFiles',
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.9, 'Документи', 'Дистрибутив', 'distro_Group', 'default', 'admin'),
    );
    
    
    /**
     * Миграция за превръщане от keylist в key поле
     */
    public static function reposToKey()
    {
        // Ако полето липсва в таблицата на модела да не се изпълнява
        $cls = cls::get('distro_Files');
        $cls->db->connect();
        $reposField = str::phpToMysqlName('repos');
        if (!$cls->db->isFieldExists($cls->dbTableName, $reposField)) {
            
            return ;
        }

        $fQuery = $cls->getQuery();
        
        unset($fQuery->fields['repos']);
        $fQuery->FLD('repos', 'keylist(mvc=distro_Repositories, select=name)');
        
        $fQuery->where('#repoId IS NULL');
        
        while ($fRec = $fQuery->fetch()) {
            $reposArr = type_Keylist::toArray($fRec->repos);
            
            foreach ($reposArr as $repoId) {
                $fRec->repos = null;
                $fRec->repoId = $repoId;
                
                $cls->save($fRec);
                
                unset($fRec->id);
            }
        }
    }
    
    
    /**
     * Миграция за синхронизиране на файловете с новите имена на директориите
     */
    public static function syncFiles()
    {
        $Files = cls::get('distro_Files');
        $gQuery = distro_Group::getQuery();
        
        while ($gRec = $gQuery->fetch()) {
            $reposArr = type_Keylist::toArray($gRec->repos);
            
            foreach ($reposArr as $repoId) {
                try {
                    $Files->forceSync($gRec->id, $repoId);
                } catch (ErrorException $e) {
                    continue;
                }
            }
        }
    }
}
