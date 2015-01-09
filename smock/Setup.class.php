<?php

 


/**
 * class smock_Setup
 *
 * Инсталиране/Деинсталиране mokyp
 *
 *
 * @package   smock
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class smock_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане мокъп";
    
    var $startCtr = 'smock_SMS';
      
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'smock_SMS',
        );

    
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