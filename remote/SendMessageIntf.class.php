<?php

/**
 * Интерфейс за изпращане на съобщение, през отдалечена услуга
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_SendMessageIntf extends embed_DriverIntf
{

    /**
     * Изпраща съобщение до потребителя, към който е закачена услугата
     *
     *
     * @param  object $rec запис от модела remote_Authorisations
     * @return state
     */
    public function sendMessage($rec, $msg)
    {
        return $this->class->sendMessage($rec, $msg);
    }
}
