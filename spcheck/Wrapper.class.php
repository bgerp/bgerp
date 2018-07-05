<?php



/**
 * Клас 'spcheck_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'eshop'
 *
 * @category  bgerp
 * @package   spcheck
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class spcheck_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('spcheck_Dictionary', 'Речник', 'powerUser');
    }
}
