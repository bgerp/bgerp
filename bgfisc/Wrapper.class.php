<?php


/**
 * Клас 'bgfisc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'bgfisc'
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('bgfisc_Register', 'УНП', 'sales,ceo');
        $this->TAB('bgfisc_PrintedReceipts', 'Фискални бонове', 'sales,ceo');
        
        $this->title = 'Регистър УНП';
    }
}
