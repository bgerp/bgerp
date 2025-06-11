<?php


/**
 * Дъми клас за периферия за банково плащане
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_interface_PosDummy extends peripheral_DeviceDriver
{


    /**ю
     * @var string
     */
    public $interfaces = 'bank_interface_POS';


    /**
     * @var string
     */
    public $title = 'POS Дъми';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('btnName', 'varchar', 'caption=Кратко заглавие на бутон за плащане->Заглавие,mandatory');
        $fieldset->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Наша сметка от която да се очакват плащанията->Сметка,mandatory');
    }


    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }


    /**
     * Връща JS за изпращане на стойност
     *
     * @param stdClass $pRec
     * @param string $funcName
     * @param string $resFuncName
     * @param string $errorFuncName
     *
     * @return core_ET
     */
    public function getJs($pRec, $funcName = 'getAmount', $resFuncName = 'getAmountRes', $errorFuncName = 'getAmountError')
    {
        $jsTpl = getTplFromFile('/bank/tpl/js/PosDummy.shtml');

        $pRec->_funcName = $funcName;
        $pRec->_resFuncName = $resFuncName;
        $pRec->_errorFuncName = $errorFuncName;
        $jsTpl->placeObject($pRec);

        return $jsTpl;
    }


    /**
     * Заглавие на бутона за плащане
     *
     * @param stdClass $pRec
     * @return string
     */
    public function getBtnName($pRec)
    {
        $pRec = peripheral_Devices::fetchRec($pRec);
        if(!empty($pRec->btnName)) return $pRec->btnName;
        if(empty($pRec)) return tr('Карта');

        return peripheral_Devices::getRecTitle($pRec);
    }


    /**
     * След вербализирането на данните
     *
     * @param peripheral_DeviceDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $row
     * @param stdClass          $rec
     * @param array             $fields
     */
    protected static function on_AfterRecToVerbal($Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        if(isset($rec->accountId)){
            $row->accountId = bank_OwnAccounts::getHyperlink($rec->accountId, true);
        }
    }


    /**
     * Коя е функцията за изпращане на сумата
     *
     * @param stdClass|int $pRec
     *
     * @return string|null
     */
    public function getSendAmountFncName($pRec)
    {
        return 'sendAmountDummy';
    }
}
