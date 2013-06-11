<?php


/**
 * До колко минути след първото гласуване, потребителя може да си смени
 * гласа
 */
defIfNot('SURVEY_VOTE_CHANGE', '2');


/**
 * class survey_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Survey
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'survey_Surveys';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    
    /**
     * Описание на модула
     */
    var $info = "Анкети и Гласувания";
    
	
	/**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'survey_Surveys',
            'survey_Alternatives',
            'survey_Votes',
        	'survey_Options',
        );
        
        // Роля за power-user на този модул
        $role = 'survey';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('survey_Images', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2.46, 'Обслужване', 'Анкети', 'survey_Surveys', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}