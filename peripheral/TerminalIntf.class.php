<?php


/**
 *
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_TerminalIntf
{
    /**
     * Връща всички достъпни за текущия потребител id-та на обекти, отговарящи на записи
     *
     * @return array
     */
    public function getTerminalOptions()
    {
        return $this->class->getTerminalOptions();
    }
    
    
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     * 
     * @return Redirect
     */
    public function openTerminal($pointId, $userId)
    {
        return $this->class->openTerminal($pointId, $userId);
    }
}
