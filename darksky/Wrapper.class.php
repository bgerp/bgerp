<?php


/**
 * Клас 'darksky_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'darksky'
 *
 *
 * @category  bgerp
 * @package   darksky
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class darksky_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('darksky_Forecast', 'Прогнози');
        
        $this->title = 'Прогнози за времето';
    }
}
