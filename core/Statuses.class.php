<?php


/**
 * Клас 'core_Statuses' - Работа със статъс съобщения
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_Statuses extends core_BaseClass
{
    /**
     * Добавя статус съобщение към избрания потребител
     *
     * @param string $text     - Съобщение, което ще добавим
     * @param enum   $type     - Типа на съобщението - success, notice, warning, error
     * @param int    $userId   - Потребителя, към когото ще се добавя. Ако не е подаден потребител, тогава взема текущия потребител.
     * @param int    $lifeTime - След колко време да е неактивно
     * @param string $hitId    - Уникално ID на хита
     *
     * @return int|FALSE - При успешен запис връща id' то на записа
     */
    public static function newStatus($text, $type = 'notice', $userId = null, $lifeTime = 60, $hitId = null)
    {
        $res = 0;
        
        // Инстанция на самия клас
        $me = cls::get('core_Statuses');
        
        // Извикваме функцията
        $addeded = $me->invoke('AfterNewStatus', array(&$res, $text, $type, $userId, $lifeTime, $hitId));
        
        // Ако няма такава функция
        if ($addeded === -1) {
            
            // Записваме в лога
            log_System::add('core_Statuses', "Не е закачен плъгин за прихващане на 'AfterNewStatus'", null, 'warning');
            
            return false;
        }
        
        return $res;
    }
    
    
    /**
     * Абонира за извличане на статус съобщения
     *
     * @return core_ET|FALSE
     */
    public static function subscribe()
    {
        // Инстанция на самия клас
        $me = cls::get('core_Statuses');
        
        $tpl = new ET();
        
        // Извикваме функцията
        $subscribed = $me->invoke('AfterSubscribe', array(&$tpl));
        
        // Ако няма такава функция
        if ($subscribed === -1) {
            
            // Записваме в лога
            log_System::add('core_Statuses', "Не е закачен плъгин за прихващане на 'AfterSubscribe'", null, 'warning');
            
            return false;
        }
        
        return $tpl;
    }
}
