<?php



/**
 * Клас 'cad_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'cad'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cad2_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('cad2_Drawings', 'Фигури', 'powerUser');
    }
}
