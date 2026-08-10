<?php


/**
 * Плъгин за документите, при които себестойността на един вложен артикул се
 * разпределя между няколко произведени - рецептата и протоколът за разпад
 *
 * (@see cat_plg_DisassemblyDocDetail за страната на детайла)
 *
 * Инвокерът може да укаже:
 * - `$disassemblyDetailClass` - детайлът с редовете (по подразбиране $mainDetail)
 * - `$disassemblyRowCond`     - кои редове се разпределят
 * - `$disassemblyDateField`   - от кое поле са цените (null - към сега)
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_DisassemblyDoc extends core_Plugin
{
    /**
     * След описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->disassemblyDetailClass, $mvc->mainDetail);
        setIfNot($mvc->disassemblyRowCond, "#type = 'production'");
        setIfNot($mvc->disassemblyDateField, null);

        $mvc->FLD('allocationBy', 'enum(price=По ценова политика,manual=Ръчно (%),quantity=По количество)', 'caption=Разпределяне на себестойността->Начин,notNull,value=price,mandatory,silent,maxRadio=0,removeAndRefreshForm=priceListId,after=detailOrderBy');
        $mvc->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Разпределяне на себестойността->Ценова политика,input=hidden,after=allocationBy');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $form = &$data->form;

        $listOptions = price_Lists::getAccessibleOptions();
        $form->setOptions('priceListId', array('' => '') + $listOptions);

        $form->setDefault('allocationBy', 'price');
        static::setPriceListField($form);
    }


    /**
     * Показва полето за ценова политика само когато по нея се разпределя
     *
     * Извиква се наново от документ, който подменя начина след плъгина - той се
     * изпълнява преди класа (@see planning_DisassemblyNote)
     *
     * @param core_Form $form
     *
     * @return void
     */
    public static function setPriceListField(core_Form $form)
    {
        // От заявката - prepareEditForm_ връща стария след наливането от базата
        $allocationBy = Request::get('allocationBy', 'varchar') ?: $form->rec->allocationBy;
        $form->setField('priceListId', ($allocationBy == 'price') ? 'input' : 'input=hidden');
    }


    /**
     * След въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        if (!$form->isSubmitted()) return;

        if ($rec->allocationBy == 'price' && empty($rec->priceListId)) {
            $form->setError('allocationBy,priceListId', 'При разпределяне по сб-ст, трябва да е посочена ценова политика|*!');
        }

        if (!isset($rec->id)) return;

        // Какво става с вече въведеното в редовете при смяна на начина
        $exRec = $mvc->fetch($rec->id, '*', false);
        if ($rec->allocationBy == 'quantity' && $exRec->allocationBy != 'quantity' && !static::areRowsInTheSameUom($mvc, $rec->id)) {

            // Количествата не са сравними
            $form->setError('allocationBy', 'Себестойността не може да се разпредели по количество|*, |защото произведените артикули не са в производни една на друга мерки|*!');
        } elseif ($exRec->allocationBy == 'manual' && $rec->allocationBy != 'manual' && static::countRowsWithPercent($mvc, $rec->id)) {

            // Въведените проценти повече не важат (@see on_AfterSave)
            $rec->_resetCostPercents = true;
            $form->setWarning('allocationBy', 'Ръчно зададените проценти на редовете ще бъдат занулени|*, |защото вече ще се изчисляват автоматично|*!');
        } elseif ($exRec->allocationBy != 'manual' && $rec->allocationBy == 'manual') {

            // Обратната посока - изчисленото се записва като ръчно, за да се коригира
            $rec->_fillCostPercents = static::getPercentsToFill($mvc, $exRec);
        }
    }


    /**
     * Изчислените проценти, готови да се запишат като ръчни. Последният ред обира
     * разликата от закръглянето, за да е сборът точно 100%
     *
     * @param core_Mvc $mvc
     * @param stdClass $exRec - записът с настройките, по които са изчислени
     *
     * @return array - процент по ид на ред, или празен масив, ако не се получават
     */
    private static function getPercentsToFill(core_Mvc $mvc, $exRec)
    {
        $statuses = array();
        $percentsArr = static::getPercents($mvc, $exRec, null, $statuses);
        if (isset($statuses['error'])) return array();

        $sum = 0;
        $res = array();
        foreach ($percentsArr as $rowId => $obj) {
            if (!isset($obj->percent)) return array();

            $res[$rowId] = round($obj->percent, 4);
            $sum += $res[$rowId];
        }

        if (countR($res)) {
            end($res);
            $res[key($res)] = round($res[key($res)] + 1 - $sum, 4);
        }

        return $res;
    }


    /**
     * След успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if (empty($rec->_resetCostPercents) && empty($rec->_fillCostPercents)) return;

        // Промените по редовете не са редакции от потребителя - без версии
        $Detail = cls::get($mvc->disassemblyDetailClass);
        if (!empty($rec->_resetCostPercents)) {

            // Оттеглените редове не се пипат, за да остане видимо какво е било
            $dQuery = static::getRowQuery($mvc, $rec->id);
            $dQuery->where('#costPercent IS NOT NULL');
            while ($dRec = $dQuery->fetch()) {
                $dRec->costPercent = null;
                $dRec->_skipDetailRevision = true;
                $Detail->save($dRec, 'costPercent');
            }
        } else {
            foreach ($rec->_fillCostPercents as $rowId => $percent) {
                $dRec = $Detail->fetch($rowId);
                $dRec->costPercent = $percent;
                $dRec->_skipDetailRevision = true;
                $Detail->save($dRec, 'costPercent');
            }

            core_Statuses::newStatus('Изчислените проценти са записани като ръчни|*!');
        }

        // Процентите се смятат на живо и се кешират с документа
        if ($containerId = ($rec->containerId ?? $mvc->fetchField($rec->id, 'containerId'))) {
            doc_DocumentCache::cacheInvalidation($containerId);
        }
    }


    /**
     * Процентите от себестойността, които се падат на разпределяните редове
     *
     * @param core_Mvc      $mvc
     * @param mixed         $id       - ид или запис на мастъра
     * @param datetime|null $date     - към коя дата са цените (null - по подразбиране)
     * @param array         $statuses - ['error'] и ['warning'] (@see cat_DisassemblyBoms::calcPercents)
     *
     * @return array - обекти, ключирани по ид на ред от детайла
     */
    public static function getPercents(core_Mvc $mvc, $id, $date = null, &$statuses = array())
    {
        core_Debug::startTimer('DISASSEMBLY_GET_PERCENTS');

        $statuses = array();
        $rec = $mvc->fetchRec($id);
        if (!isset($date) && !empty($mvc->disassemblyDateField)) {
            $date = $rec->{$mvc->disassemblyDateField};
        }

        $dQuery = static::getRowQuery($mvc, $rec->id);
        $dQuery->show('productId,quantity,costPercent');

        $res = array();
        while ($dRec = $dQuery->fetch()) {

            // Незададеният се нормализира до null (0% е валидно зададен)
            $costPercent = (isset($dRec->costPercent) && $dRec->costPercent !== '') ? (float) $dRec->costPercent : null;

            $res[$dRec->id] = (object) array('productId' => $dRec->productId,
                                             'quantity' => $dRec->quantity,
                                             'costPercent' => $costPercent,
                                             'percent' => null,
                                             'amount' => null);
        }

        if (!countR($res)) {
            $statuses['error'] = 'Не е посочен нито един произведен артикул|*!';
            core_Debug::stopTimer('DISASSEMBLY_GET_PERCENTS');

            return $res;
        }

        cat_DisassemblyBoms::calcPercents($res, $rec->allocationBy, $rec->priceListId, $date, $statuses);

        core_Debug::stopTimer('DISASSEMBLY_GET_PERCENTS');

        return $res;
    }


    /**
     * Дали разпределяните редове са в производни една на друга мерки
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param int|null $skipRowId    - ред, който да не се брои (редактираният)
     * @param int|null $addProductId - артикул, който още не е записан (въвежданият)
     * @param int|null $baseUomId    - базовата мярка, към която се сравнява
     *
     * @return bool
     */
    public static function areRowsInTheSameUom(core_Mvc $mvc, $id, $skipRowId = null, $addProductId = null, &$baseUomId = null)
    {
        $dQuery = static::getRowQuery($mvc, $id);
        if (isset($skipRowId)) {
            $dQuery->where("#id != {$skipRowId}");
        }
        $dQuery->show('productId');

        $productIds = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');

        // Сравнява се с мярката на първия вече добавен ред
        if (countR($productIds)) {
            $measureId = cat_Products::fetchField(reset($productIds), 'measureId');
            $baseUomId = cat_UoM::fetchField($measureId, 'baseUnitId') ?: $measureId;
        }

        if (isset($addProductId)) {
            $productIds[] = $addProductId;
        }

        return cat_Products::areProductsInTheSameUom($productIds);
    }


    /**
     * Сборът на ръчно зададените проценти по редовете
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param int|null $skipRowId - ред, който да не се брои (редактираният)
     *
     * @return float
     */
    public static function sumRowsPercent(core_Mvc $mvc, $id, $skipRowId = null)
    {
        $dQuery = static::getRowQuery($mvc, $id);
        if (isset($skipRowId)) {
            $dQuery->where("#id != {$skipRowId}");
        }
        $dQuery->XPR('sumPercent', 'double', 'SUM(#costPercent)');
        $dQuery->show('sumPercent');

        return (float) $dQuery->fetch()->sumPercent;
    }


    /**
     * Колко от разпределяните редове имат ръчно зададен процент
     *
     * @param core_Mvc $mvc
     * @param int      $id
     *
     * @return int
     */
    public static function countRowsWithPercent(core_Mvc $mvc, $id)
    {
        $dQuery = static::getRowQuery($mvc, $id);
        $dQuery->where('#costPercent IS NOT NULL');

        return $dQuery->count();
    }


    /**
     * Заявка към разпределяните редове на мастъра, без оттеглените ревизии
     *
     * @param core_Mvc $mvc
     * @param int      $id
     *
     * @return core_Query
     */
    public static function getRowQuery(core_Mvc $mvc, $id)
    {
        $Detail = cls::get($mvc->disassemblyDetailClass);

        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$id}");
        $dQuery->where($mvc->disassemblyRowCond);
        $dQuery->where("#state != 'rejected'");

        return $dQuery;
    }
}
