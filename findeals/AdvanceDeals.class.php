<?php


/**
 * Клас 'findeals_AdvanceDeals'
 *
 * Мениджър за служебни аванси (вид Финансова сделка)
 *
 *
 * @category  bgerp
 * @package   findeals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class findeals_AdvanceDeals extends findeals_Deals
{
    /**
     * Заглавие
     */
    public $title = 'Служебни аванси';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ad';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Служебен аванс';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/kwallet.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.2|Финанси';


    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_PersonAccRegIntf';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = true;


    /**
     * Базово системно ид на сметка
     */
    const BASE_ACCOUNT_SYS_ID = 422;


    /**
     * Може ли документа да се добави в посочената папка?
     *
     * Документи-финансови сделки могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        if (cls::haveInterface('crm_PersonAccRegIntf', $coverClass)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-single']) {
            $row->contragentCaption = tr('Подотчетно лице');
        }
    }


    /**
     * Връщане на сметките, по които може да се създава ФД
     *
     * @return array $options
     */
    protected function getDefaultAccountOptions($folderId)
    {
        $accountRec = acc_Accounts::getRecBySystemId(static::BASE_ACCOUNT_SYS_ID);
        $options = array($accountRec->id => acc_Accounts::getRecTitle($accountRec, false));

        return $options;
    }
}
