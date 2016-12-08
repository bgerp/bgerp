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
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'support_Issues';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Поддръжка на системи: сигнали и проследяването им";
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'support_Issues',
            'support_Components',
            'support_Systems',
            'support_IssueTypes',
            'support_Corrections',
            'support_Preventions',
            'support_Ratings',
            'support_Resolutions',
            'migrate::markUsedComponents'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'support';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.14, 'Обслужване', 'Поддръжка', 'support_Issues', 'default', "support, admin, ceo"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
      	$html = parent::install();
        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Support', 'Прикачени файлове в поддръжка', NULL, '300 MB', 'powerUser', 'every_one');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
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
        $query = support_Issues::getQuery();
        $query->where("#componentId IS NOT NULL");
        $query->where("#componentId != ''");
        while ($rec = $query->fetch()) {
            support_Components::markAsUsed($rec->componentId);
        }
    }
}