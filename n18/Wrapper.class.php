<?php


/**
 * Клас 'n18_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'n18'
 *
 *
 * @category  bgplus
 * @package   n18
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class n18_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('n18_Register', 'УНП', 'sales,napodit,ceo');
        $this->TAB('n18_PrintedReceipts', 'Фискални бонове', 'sales,napodit,ceo');
        
        $this->title = 'Регистър УНП';
    }
}
