<?php


/**
 * Клас 'wtime_Wrapper'
 *
 * Поддържа табове-те на пакета 'wtime'
 *
 * @category  bgerp
 * @package   wtime
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class wtime_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('wtime_OnSiteEntries', 'Вход/Изход', 'ceo,wtime');
        $this->TAB('wtime_Summary', 'Обобщения', 'ceo,wtime');

        $this->title = 'Работно време';
    }
}
