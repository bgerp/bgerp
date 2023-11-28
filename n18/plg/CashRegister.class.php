<?php


/**
 * Клас 'n18_plg_CashRegister' - за добавяне на функционалност от наредба 18 към ПОС бележките към касите
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
class n18_plg_CashRegister extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('cashRegNum', 'varchar(nullIfEmpty)', 'caption=Фискално устройство->Избор,after=name');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        $cashRegOptions = n18_Setup::getFiscDeviceOptins();
        if (count($cashRegOptions)) {
            $form->setOptions('cashRegNum', $cashRegOptions);
        } else {
            $form->setField('cashRegNum', 'input=none');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->cashRegNum = n18_Register::getFuLinkBySerial($rec->cashRegNum, false);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $block = $tpl->getBlock('CAPTION_VALUE');
        $block->replace(tr('ФУ'), 'CAPTION');
        $block->replace($data->row->cashRegNum, 'CAPTION_VALUE');
        $block->removeBlocksAndPlaces();
        $tpl->append($block, 'INFO_BLOCK');
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова"
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($deviceRec = n18_Register::getFiscDevice($data->rec->id)) {
            if (is_object($deviceRec)) {
                
                // Добавяне на бутони за зареждане на средства и генериране на отчети от ФУ
                $fiscDriver = peripheral_Devices::getDriver($deviceRec);
                if (haveRole($fiscDriver->canMakeReport)) {
                    $data->toolbar->addBtn('Отчети ФУ', array($fiscDriver, 'Reports', 'pId' => $deviceRec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/report.png, title=Отпечатване на отчети');
                }
                
                if (haveRole($fiscDriver->canCashReceived) || haveRole($fiscDriver->canCashPaidOut)) {
                    $data->toolbar->addBtn('Средства ФУ', array($fiscDriver, 'CashReceivedOrPaidOut', 'pId' => $deviceRec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/money.png, title=Вкарване или изкарване на пари от касата');
                }
                
                if (haveRole($fiscDriver->canPrintDuplicate)) {
                    $data->toolbar->addBtn('Дубликат ФУ', array($fiscDriver, 'printduplicate', $deviceRec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/report.png, title=Дубликат на последната отпечатана фискална бележка');
                }
            }
        }
    }
}
