<?php



/**
 * Клас 'help_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'help'
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class help_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('help_Info', 'Помощ', 'debug, help,admin');
        $this->TAB('help_Log', 'Лог', 'debug, help,admin');
    }
}
