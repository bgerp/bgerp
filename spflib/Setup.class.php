<?php


/**
 * SPF (Sender Policy Framework) библиотека
 *
 * @category  vendors
 * @package   spflib
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class spflib_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'SPF проверка и създаване на DNS записи';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Инсталираме библиотеката чрез композера
        if (core_Composer::isInUse()) {
            $html .= core_Composer::install('mlocati/spf-lib', '3.1.1');
        } else {
            $html .= "<li class='red'>Не е инталиран композер!</li>";
        }

        return $html;
    }
}
