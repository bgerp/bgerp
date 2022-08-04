<?php


/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('trans_Lines', 'Линии', 'ceo,trans');
        $this->TAB('trans_Cmrs', 'ЧМР', 'ceo,trans');
        $this->TAB('trans_IntraCommunitySupplyConfirmations', 'ВОД', 'ceo,trans');
        $this->TAB('trans_TransportUnits', 'ЛЕ', 'ceo,trans');
        $this->TAB('trans_Vehicles', 'МПС', 'ceo,trans');
        $this->TAB('trans_TransportModes', 'Видове', 'ceo,trans');
        $this->TAB('trans_Features', 'Особености', 'ceo,trans');

        $this->title = 'Транспорт';
    }
}
