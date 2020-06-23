<?php


/**
 * Точки на продажба - опаковка
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('pos_Points', 'Точки', 'ceo,pos');
        $this->TAB('pos_Receipts', 'Бележки', 'ceo,pos');
        $this->TAB('pos_Reports', 'Отчети', 'ceo,pos');
        $this->TAB('pos_Stocks', 'Наличности', 'ceo,pos');
        
        $this->title = 'Точки на продажба';
    }
}
