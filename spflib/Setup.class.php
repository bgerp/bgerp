<?php


/** Досегашната версия остава default за съществуващите инсталации. */
defIfNot('SPFLIB_VERSION', '3.1.1');


/**
 * SPF (Sender Policy Framework) библиотека
 *
 * @category  vendors
 * @package   spflib
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
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


    /** Версията се инсталира в общия Composer при инициализиране на пакета. */
    public $configDescription = array(
        'SPFLIB_VERSION' => array('enum(3.1.1,3.3.0)', 'mandatory,caption=SPF Library->Версия'),
    );


    /** Показва липсваща или различна версия по стандартния начин в core_Packs. */
    public function checkConfig()
    {
        if (!core_Composer::isInstalled('mlocati/spf-lib', self::get('VERSION'), true)) {
            return 'Липсва избраната версия на SPF Library. Инициализирайте пакета spflib.';
        }
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Инсталираме библиотеката чрез композера
        if (core_Composer::isInUse()) {
            $html .= core_Composer::install('mlocati/spf-lib', self::get('VERSION'), true);
        } else {
            $html .= "<li class='red'>Не е инталиран композер!</li>";
        }

        return $html;
    }
}
