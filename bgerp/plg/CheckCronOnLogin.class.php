<?php


/**
 * Плъгин за проверка дали крон работи коректно, след логване в системата
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_CheckCronOnLogin extends core_Plugin
{
    /**
     * Прихващаме всяко логване в системата
     */
    public function on_AfterLogin($mvc, $userRec, $inputs, $refresh)
    {
        // Ако не се логва, а се рефрешва потребителя
        if ($refresh) {
            
            return ;
        }
        
        $adminId = core_Users::getCurrent();
        
        if (!haveRole('admin', $adminId)) {
            $adminId = core_Users::getFirstAdmin();
        }
        
        $lastStart = core_Cron::getLastStartTime();
        
        if (!$lastStart) {
            
            return ;
        }
        
        $conf = core_Packs::getConfig('bgerp');
        
        $cTime = dt::subtractSecs($conf->BGERP_NON_WORKING_CRON_TIME);
        
        if ($cTime > $lastStart) {
            $urlArr = array('core_Cron');
            
            $lastClosedTime = bgerp_Notifications::getLastClosedTime($urlArr);
            
            if (!isset($lastClosedTime) || ($cTime > $lastClosedTime)) {
                $msg = '|Внимание! Периодичните процеси не са стартирани скоро.|*';
                
                // Форсираме системния потребител за да може да се нотифицира текущия потребител
                core_Users::sudo(core_Users::SYSTEM_USER);
                try {
                    bgerp_Notifications::add($msg, $urlArr, $adminId, 'warning');
                } catch (core_exception_Expect $e) {
                    core_Users::exitSudo();
                    
                    return ;
                }
                
                core_Users::exitSudo();
            }
        }
    }
}
