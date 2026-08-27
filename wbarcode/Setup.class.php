<?php


/**
 * Маските на тегловните баркодове
 */
defIfNot('WBARCODE_MASKS', '');


/**
 * Как да се търси артикулът по кода от тегловния баркод
 */
defIfNot('WBARCODE_CODE_MODE', 'smart');


/**
 * Колко от последните цифри на тегловната част са след десетичния знак
 */
defIfNot('WBARCODE_WEIGHT_DECIMALS', 3);


/**
 * Клас 'wbarcode_Setup'
 *
 * Инсталиране/деинсталиране на пакета за тегловни баркодове
 *
 *
 * @category  bgerp
 * @package   wbarcode
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wbarcode_Setup extends core_ProtoSetup
{
    /**
     * Необходими пакети
     */
    public $depends = 'cat=0.1,gs1=0.1';


    /**
     * Версията на пакета
     */
    public $version = '0.1';


    /**
     * Описание на модула
     */
    public $info = 'Разпознаване на EAN-13 баркодове с променливо тегло';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'WBARCODE_MASKS' => array('table(columns=mask,captions=Маска,widths=14em,validate=wbarcode_Helper::validateMasks)', array('caption' => 'Тегловни баркодове|* (EAN-13)->|например|* <b>28PPPPWWWWWWC</b>: <b>28</b> |е префиксът на везната|*, <b>P</b> |код на артикула|*, <b>W</b> |тегло|*, <b>C</b> |контролно число->Маски')),
        'WBARCODE_CODE_MODE' => array('enum(smart=Умно - с и без водещите нули,padded=Точно - с падинга от баркода,trimmed=Без падинг - без водещите нули)', array('caption' => 'Тегловни баркодове|* (EAN-13)->Търсене по кода', 'maxRadio' => 1)),
        'WBARCODE_WEIGHT_DECIMALS' => array('int(min=0,max=6)', array('caption' => 'Тегловни баркодове|* (EAN-13)->Десетичен знак', 'unit' => 'цифри след него|* (3 |означава, че теглото е в грамове|*)')),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        $Plugins = cls::get('core_Plugins');

        if (core_Packs::isInstalled('store')) {
            $html .= $Plugins->installPlugin('Добавяне по тегловен баркод в междускладовите трансфери', 'wbarcode_plg_AddByBarcode', 'store_TransfersDetails', 'private');
        }

        return $html;
    }


    /**
     * Проверяваме дали всичко е сетнато, за да работи пакета
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        if (!countR(wbarcode_Helper::getMasks())) {

            return 'Не е въведена нито една маска на тегловен баркод|*!';
        }
    }
}
