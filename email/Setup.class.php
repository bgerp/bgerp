<?php

/**
 *  class email_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с 'email'
 *
 */
class email_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'email_Messages';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Електронна поща";
   
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'email_Messages',
            'email_Accounts',
            'email_Unsorted',
        	'email_Inboxes'
           // 'email_Boxes'
        );
        
        // Роля ръководител на организация 
        // Достъпни са му всички папки и документите в тях
        $role = 'email';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
 
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        		
    	//Инсталиране на пакета Fileman
        $packs = "fileman";
        
        set_time_limit(120);
        
        $Packs = cls::get('core_Packs');
        
    	foreach( arr::make($packs) as $p) {
            if(cls::load("{$p}_Setup", TRUE)) {
                $html .= $Packs->setupPack($p);
            }
        }
        
        //инсталиране на кофата
    	$Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Email', 'Прикачени файлове в имейлите', NULL, '104857600', 'every_one', 'every_one');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Документи', 'Е-мейл', 'email_Messages', 'default', "user");
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $Plugins->installPlugin('UserInbox', 'email_UserInboxPlg', 'core_Users', 'private');
        $html .= "<li>Закачане на UserInbox към полетата за данни - core_Users (Активно)";
        
        return $html;
    }
        
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);

        return $res;
    }
}