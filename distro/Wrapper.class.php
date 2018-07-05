<?php


/**
 * Клас 'distro_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'distro'
 *
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class distro_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката от табове
     */
    public function description()
    {
        $this->TAB('distro_Group', 'Групи', 'admin');
        $this->TAB('distro_Automation', 'Автоматизации', 'admin');
        $this->TAB('distro_Repositories', 'Хранилища', 'admin');
    }
}
