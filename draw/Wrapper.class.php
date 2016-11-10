<?php



/**
 * Клас 'draw_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Draw'
 *
 *
 * @category  bgerp
 * @package   draw
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class draw_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('draw_Designs', 'Дизайни', 'draw, ceo, admin');
        $this->TAB('draw_Pens', 'Моливи', 'draw, ceo, admin');
    }
}