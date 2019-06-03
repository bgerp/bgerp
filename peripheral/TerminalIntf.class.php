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
class peripheral_TerminalIntf extends peripheral_DeviceIntf
{
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     */
    public function getTerminalUrl($pointId)
    {
        return $this->class->getTerminalUrl($pointId);
    }
}
