<?php


/**
 * Обработвач на шаблона за фактура за митница
 *
 *
 * @category  sales
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за фактура за митница
 */
class sales_tpl_CustomsInvoiceEn extends doc_TplScript
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'sales_Invoices';


    /**
     * Константа за празен тарифен номер
     */
    const EMPTY_TARIFF_NUMBER = '_';


    /**
     * Префикс за тарифен код
     */
    protected $tariffCodeCaption = 'HS Code / CTN';


    /**
     * Метод който подава данните на детайла на мастъра, за обработка на скрипта
     *
     * @param core_Mvc $detail - Детайл на документа
     * @param stdClass $data   - данни
     *
     * @return void
     */
    public function modifyDetailData(core_Mvc $detail, &$data)
    {
        if(!countR($data->recs) || Mode::is('renderHtmlInLine')) return;

        // Извлича се тарифния номер на артикулите
        $length = store_Setup::get('TARIFF_NUMBER_LENGTH');
        $getLiveTariffCode = in_array($data->masterData->rec->state, array('pending', 'draft'));

        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];
            $row->_isLiveTariffNumber = false;
            $tariffNumber = $rec->tariffCode;
            if(empty($tariffNumber) && $getLiveTariffCode){
                $tariffNumber = cat_Products::getParams($rec->productId, 'customsTariffNumber', true);
                $tariffNumber = !empty($tariffNumber) ? $tariffNumber : self::EMPTY_TARIFF_NUMBER;
                $row->_isLiveTariffNumber = true;
            }

            $tariffNumber = !empty($tariffNumber) ? mb_substr($tariffNumber, 0, $length) : self::EMPTY_TARIFF_NUMBER;
            $rec->tariffNumber = $tariffNumber;
            $row->tariffNumber = $tariffNumber;
        }
    }


    /**
     * Преди рендиране на шаблона на детайла
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function beforeRenderListTable(core_Mvc $detail, &$tpl, &$data)
    {
        if(!countR($data->recs) || Mode::is('renderHtmlInLine')) return;
        if($detail instanceof store_DocumentPackagingDetail) return;

        // Скриване на колонките за нето/тара/бруто
        $masterRec = $data->masterData->rec;
        $columnCount = countR($data->listFields) + 2;

        // Извличане на всички уникални тарифни номера и сумиране на данните им
        $data->tariffCodes = array();
        foreach ($data->rows as $id => $row) {
            $rec1 = $data->recs[$id];
            if(!array_key_exists($rec1->tariffNumber, $data->tariffCodes)){
                $data->tariffCodes[$rec1->tariffNumber] = (object)array('tariffNumber' => $rec1->tariffNumber, 'isLive' => false);
            }
            if($row->_isLiveTariffNumber){
                $data->tariffCodes[$rec1->tariffNumber]->isLive = true;
            }
        }

        // Подредба по МТК, като без МТК ще е най-накрая
        if(isset($data->tariffCodes[static::EMPTY_TARIFF_NUMBER])){
            $emptyObject = $data->tariffCodes[static::EMPTY_TARIFF_NUMBER];
            unset($data->tariffCodes[static::EMPTY_TARIFF_NUMBER]);
            ksort($data->tariffCodes, SORT_STRING);
            $data->tariffCodes += array(static::EMPTY_TARIFF_NUMBER => $emptyObject);
        }

        $rows = array();
        $count = 0;

        // За всяко поле за групиране
        foreach ($data->tariffCodes as $tariffRec) {
            $displayTariffCode = core_Type::getByName('varchar')->toVerbal($tariffRec->tariffNumber);

            if($displayTariffCode != self::EMPTY_TARIFF_NUMBER){
                $code = "<span class='quiet small'>{$this->tariffCodeCaption}</span> {$displayTariffCode}";
                $tariffDescription = cond_TariffCodes::getDescriptionByCode($tariffRec->tariffNumber, $masterRec->tplLang);
                if($tariffRec->isLive){
                    $code = ht::createHint("<span style='color:blue'>{$code}</span>", 'Текущата стойност ще се запише към момента на активиране');
                }
                $tariffDescriptionVerbal = core_Type::getByName('varchar')->toVerbal($tariffDescription);
            } else {
                $code = "<span class='small'>" . tr('Без тарифен код') . "</span>";
                $tariffDescriptionVerbal = null;
            }

            $groupBlock = new core_ET("<b>[#code#]</b> <!--ET_BEGIN description--><span class='small'>[#description#]</span><!--ET_END description-->");
            $groupBlock->replace($code, 'code');
            $groupBlock->replace($tariffDescriptionVerbal, 'description');

            // Създаваме по един ред с името му, разпънат в цялата таблица
            $rowAttr = array('class' => ' group-by-field-row');
            $customStyle = "";
            $groupTpl = new ET("<td style='position:relative;background: #eee;padding-top:9px;padding-left:5px; {$customStyle}' colspan='{$columnCount}'>[#block#]</td>");

            $groupTpl->replace($groupBlock, 'block');
            $element = ht::createElement('tr', $rowAttr, $groupTpl);
            $rows['|' . $tariffRec->tariffNumber] = $element;

            // За всички записи
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];
                // Ако стойността на полето им за групиране е същата като текущото
                if ($rec->tariffNumber == $tariffRec->tariffNumber) {
                    if (is_object($data->rows[$id])) {
                        $count++;
                        $rows[$id] = clone $data->rows[$id];
                        $rows[$id]->RowNumb = $count;

                        // Веднъж групирано, премахваме записа от старите записи
                        unset($data->rows[$id]);
                    }
                }
            }
        }

        $data->rows = $rows;
    }
}