<?php


/**
 * Клас 'visualcrossing_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'visualcrossing'
 *
 *
 * @category  bgerp
 * @package   visualcrossing
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class visualcrossing_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('visualcrossing_Forecast', 'Прогнози');

        $this->title = 'Прогнози за времето';
    }
}

