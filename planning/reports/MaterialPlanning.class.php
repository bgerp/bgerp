<?php


/**
 * Мениджър на отчети за Планиране на материали
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Планиране на материали
 */
class planning_reports_MaterialPlanning extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug, acc, planning';


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var string
     */
    protected $sortableListFields;


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var string
     */
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var string
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'week';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'jobses, from, to, groups';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('type', 'enum(byWeeks=По седмици, bySales=По продажби)', 'caption=Вид на справката,removeAndRefreshForm,after=title,silent');
        $fieldset->FLD('weeks', 'int', 'caption=Брой седмици,after=type');
        $fieldset->FLD('slalesDog', 'keylist(mvc=sales_Sales,select=number)', 'caption=Договори,placeholder = Всички,after=weeks,single=none');

        // Групи артикули
        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=slalesDog,single=none');
        } else {
            $fieldset->FLD('groups', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=weeks,single=none');
        }

        $fieldset->FLD('period', 'enum(all=Общо за периода,byWeeks=По седмици)', 'caption=Показване,after=groups,silent');
    }


    /**
     * След въвеждане на данните от формата
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (($form->rec->type ?? 'byWeeks') == 'bySales') {
                $form->rec->period = 'all';
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('weeks', 8);
        $form->setDefault('period', 'byWeeks');
        $form->setDefault('type', 'byWeeks');

        if (($rec->type ?? null) == 'bySales') {
            $form->setField('weeks', 'input=hidden');
            $form->setField('period', 'input=hidden');
            $form->setField('slalesDog', 'mandatory');
        }
        if (($rec->type ?? null) == 'byWeeks') {
            $form->setField('slalesDog', 'input=hidden');
        }

        $salesQuery = sales_Sales::getQuery();
        $salesQuery->where("#state = 'active'");

        $suggestions = array();
        while ($salesDog = $salesQuery->fetch()) {
            $suggestions[$salesDog->id] = $salesDog->id;
        }

        asort($suggestions);

        $form->setSuggestions('slalesDog', $suggestions);
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = $totalQuantity = array();
        $today = dt::today();
        $rec->type = $rec->type ?? 'byWeeks';
        $rec->weeks = max(1, (int)($rec->weeks ?? 8));
        $rec->groups = $rec->groups ?? null;
        $rec->period = $rec->period ?? 'byWeeks';
        $rec->slalesDog = $rec->slalesDog ?? null;

        if ($rec->type == 'byWeeks') {
            $jobsQuery = planning_Jobs::getQuery();
            $jobsQuery->where("#state != 'rejected' AND #state != 'closed' AND #state != 'draft'");

            $thisWeek = date('W', strtotime($today));
            $year = date('Y', strtotime($today));

            // Кои седмици влизат в отчета
            $weeksForCheck = array();
            $weekMarker = 0;
            for ($i = $thisWeek; $i < $thisWeek + $rec->weeks; $i++) {
                $weekNumber = $i - $weekMarker;
                $week = $i - $weekMarker . '-' . $year;
                $endDayOfWeek = self::getStartAndEndDate($weekNumber, $year)[1];

                if ($endDayOfWeek > $year . '-12-31') {
                    $weekMarker = $i;
                    $year = date('Y', strtotime($endDayOfWeek));
                }

                array_push($weeksForCheck, $week);
                unset($week);
            }

            list($lastWeek, $lastYear) = explode('-', end($weeksForCheck));
            $endDay = self::getStartAndEndDate($lastWeek, $lastYear)[1];

            $jobsQuery->where(array("#quantity > #quantityProduced AND #dueDate <= '[#1#]'", $endDay . ' 23:59:59'));
            $jobsQuery->show('quantity,quantityProduced,productId,dueDate');
            $jobsRecsArr = $jobsQuery->fetchAll();

            // Добавяне на виртуалните задания
            $vJobsArr = self::createdVirtualJobs($endDay);

            $jobsRecsArr = array_merge($jobsRecsArr, $vJobsArr);
            $withMaterials = self::getProductsWithMaterials(arr::extractValuesFromArray($jobsRecsArr, 'productId'));

            // Първо материалите на всички задания, за да се заредят записите им с една заявка
            $jobMaterials = array();
            foreach ($jobsRecsArr as $i => $jobsRec) {
                if (isset($withMaterials[$jobsRec->productId ?? null])) {
                    $quantityRemaining = ($jobsRec->quantity ?? 0) - ($jobsRec->quantityProduced ?? 0);
                    $jobMaterials[$i] = cat_Products::getMaterialsForProduction($jobsRec->productId, (double)$quantityRemaining);
                }
            }
            $matRecs = self::getMaterialRecs($jobMaterials);

            foreach ($jobsRecsArr as $i => $jobsRec) {
                $materialsArr = array();

                $jobsRec->quantity = $jobsRec->quantity ?? 0;
                $jobsRec->quantityProduced = $jobsRec->quantityProduced ?? 0;
                $jobsRec->productId = $jobsRec->productId ?? null;
                $jobsRec->dueDate = $jobsRec->dueDate ?? null;
                $jobsRec->week = $jobsRec->week ?? null;
                $jobsRec->id = $jobsRec->id ?? null;
                $jobsRec->saleId = $jobsRec->saleId ?? null;
                if (!$jobsRec->productId || !isset($withMaterials[$jobsRec->productId])) {
                    continue;
                }

                $quantityRemaining = $jobsRec->quantity - $jobsRec->quantityProduced;
                $materialsArr = $jobMaterials[$i] ?? array();
                $totalmaterialQuantiry = 0;

                if (!empty($materialsArr)) {
                    foreach ($materialsArr as $val) {
                        $materialId = $val['productId'] ?? null;
                        $materialQuantity = $val['quantity'] ?? 0;
                        $matRec = $matRecs[$materialId] ?? null;
                        if (!$matRec) {
                            continue;
                        }

                        // Филтрира само складируеми материали
                        if ($matRec->canStore == 'no') {
                            continue;
                        }

                        // Ако има избрана група или групи материали
                        if ($rec->groups) {
                            $groupsArr = keylist::toArray($rec->groups);
                            if (!keylist::isIn($groupsArr, $matRec->groups)) {
                                continue;
                            }
                        }

                        $week = $jobsRec->week ?: date('W', strtotime($jobsRec->dueDate ?: $today)) . '-' . date('Y', strtotime($jobsRec->dueDate ?: $today));

                        // Ако падежът е изтекъл, заданието се отнася към нулева седмица
                        if ($jobsRec->dueDate && $jobsRec->dueDate < $today) {
                            $week = '0-0';
                        }

                        $doc = ($jobsRec->id) ? 'planning_Jobs' . '|' . $jobsRec->id : 'sales_Sales' . '|' . $jobsRec->saleId;
                        $recsKey = ($rec->type == 'byWeeks') ? $week . ' | ' . $materialId : $materialId;
                        $totalmaterialQuantiry += $materialQuantity;

                        // Запис в масива
                        if (!array_key_exists($recsKey, $recs)) {
                            $recs[$recsKey] = (object)array(
                                'week' => $week,
                                'originDoc' => array($doc),
                                'jobProductId' => $jobsRec->productId,              // Id на артикула
                                'quantityRemaining' => $quantityRemaining,          // Оставащо количество
                                'materialId' => $materialId,
                                'materialQuantiry' => $materialQuantity,
                            );
                        } else {
                            $obj = &$recs[$recsKey];

                            $obj->quantityRemaining += $quantityRemaining;
                            $obj->materialQuantiry += $materialQuantity;
                            array_push($obj->originDoc, $doc);
                        }

                        if (!array_key_exists($materialId, $totalQuantity)) {
                            $totalQuantity[$materialId] = (object)array(
                                'week' => null,
                                'originDoc' => array(),
                                'materialId' => $materialId,
                                'materialQuantiry' => $materialQuantity,
                            );
                        } else {
                            $obj = &$totalQuantity[$materialId];

                            $obj->materialQuantiry += $materialQuantity;
                        }
                    }
                }
            }

            arr::sortObjects($recs, 'week');

            if ($rec->period == 'all') {
                $recs = $totalQuantity;
            }
        }

        if ($rec->type == 'bySales' && $rec->slalesDog) {
            $sQuery = sales_SalesDetails::getQuery();
            $sQuery->in('saleId', keylist::toArray($rec->slalesDog));
            $salesDetails = $sQuery->fetchAll();
            $withMaterials = self::getProductsWithMaterials(arr::extractValuesFromArray($salesDetails, 'productId'));

            $saleMaterials = array();
            foreach ($salesDetails as $i => $pRec) {
                if (isset($withMaterials[$pRec->productId])) {
                    $saleMaterials[$i] = cat_Products::getMaterialsForProduction($pRec->productId, (double)$pRec->quantity);
                }
            }
            $matRecs = self::getMaterialRecs($saleMaterials);

            foreach ($salesDetails as $i => $pRec) {
                $materialsArr = $saleMaterials[$i] ?? array();

                if (!empty($materialsArr)) {
                    foreach ($materialsArr as $val) {
                        $materialId = $val['productId'] ?? null;
                        $materialQuantity = $val['quantity'] ?? 0;
                        $matRec = $matRecs[$materialId] ?? null;
                        if (!$matRec) {
                            continue;
                        }

                        // Филтрира само складируеми материали
                        if ($matRec->canStore == 'no') {
                            continue;
                        }

                        // Ако има избрана група или групи материали
                        if ($rec->groups) {
                            $groupsArr = keylist::toArray($rec->groups);
                            if (!keylist::isIn($groupsArr, $matRec->groups)) {
                                continue;
                            }
                        }

                        $doc = 'sales_Sales' . '|' . $pRec->saleId;
                        $recsKey = $materialId;

                        // Запис в масива
                        if (!array_key_exists($recsKey, $recs)) {
                            $recs[$recsKey] = (object)array(
                                'originDoc' => array($doc),
                                'jobProductId' => $pRec->productId,                 // Id на артикула
                                'materialId' => $materialId,
                                'materialQuantiry' => $materialQuantity,
                            );
                        } else {
                            $obj = &$recs[$recsKey];

                            $obj->materialQuantiry += $materialQuantity;
                            array_push($obj->originDoc, $doc);
                        }
                    }
                }
            }
        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        if ($export === false) {
            if (($rec->period ?? 'byWeeks') == 'byWeeks') {
                $fld->FLD('materialId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
                $fld->FLD('docs', 'varchar', 'smartCenter,caption=@Задания');
                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
                $fld->FLD('materialQuantiry', 'double(smartRound,decimals=2)', 'smartCenter,caption=Необходимо Количество');
            } else {
                $fld->FLD('materialId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
                $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
                $fld->FLD('materialQuantiry', 'double(smartRound,decimals=2)', 'smartCenter,caption=Необходимо Количество');
            }
        } else {
            $fld->FLD('materialId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('materialQuantiry', 'double(decimals=2)', 'smartCenter,caption=Необходимо Количество');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();

        $dRecWeek = $dRec->week ?? null;
        $week = $dRecWeek != '0-0' ? $dRecWeek : '0-0 (изтекъл падеж)';

        $row->week = 'Седмица: '.$week;

        if (isset($dRec->materialId)) {
            $row->materialId = cat_Products::getLinkToSingle_($dRec->materialId, 'name').' / '.
                               cat_Products::fetchField("{$dRec->materialId}", 'code');
        }

        if (isset($dRec->originDoc)) {
            $marker = 0;
            foreach ($dRec->originDoc as $originDoc) {
                $marker++;
                list($docClassName, $doc) = explode('|', $originDoc);
                $docRec = $docClassName::fetch($doc);
                if (!$docRec || empty($docRec->containerId)) {
                    continue;
                }

                $handle = $docClassName::getHandle($docRec);
                if ($docClassName != 'planning_Jobs') {
                    $handle = 'VJ-' . $handle;
                }
                $singleUrl = $docClassName::getUrlWithAccess($docRec->id);
                $row->docs = ($row->docs ?? '') . ht::createLink("#{$handle}", $singleUrl);

                if ((countR(($dRec->originDoc)) - $marker) != 0) {
                    $row->docs .= ', ';
                }
            }
        }
        $productRec = isset($dRec->materialId) ? cat_Products::fetch($dRec->materialId) : null;
        $row->measure = $productRec ? cat_UoM::fetchField($productRec->measureId, 'shortName') : null;

        if (isset($dRec->materialQuantiry)) {
            $row->materialQuantiry = $Double->toVerbal($dRec->materialQuantiry);
        }

        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN groups--><div>|Групи продукти|*: <b>[#groups#]</b></div><!--ET_END groups-->
                                        <!--ET_BEGIN totalmaterialQuantiry--><div>|Общо тегло|*: <b>[#totalmaterialQuantiry#] кг.</b></div><!--ET_END totalmaterialQuantiry-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        $marker = 0;
        $groupVerb = '';
        if (isset($data->rec->groups)) {
            foreach (type_Keylist::toArray($data->rec->groups) as $group) {
                $marker++;
                $groupVerb .= (cat_Groups::getTitleById($group));
                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        } else {
            $fieldTpl->append('<b>' . tr('Всички') . '</b>', 'groups');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     * @param core_BaseClass      $ExportClass
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $prodRec = cat_Products::fetch($dRec->materialId ?? null);
        if (!$prodRec) {
            return;
        }
        $code = ($prodRec->code) ? : "Art{$prodRec->id}";
        $res->code = $code;
        $res->measure = cat_UoM::fetchField($prodRec->measureId, 'shortName');
    }


    /**
     * Първият и последният ден на седмица от годината
     *
     * @param int $week
     * @param int $year
     *
     * @return array - [0 => начало, 1 => край] във формат Y-m-d
     */
    public static function getStartAndEndDate($week, $year)
    {
        $dates[0] = date('Y-m-d', strtotime($year.'W'.str_pad($week, 2, 0, STR_PAD_LEFT)));
        $dates[1] = date('Y-m-d', strtotime($year.'W'.str_pad($week, 2, 0, STR_PAD_LEFT).' +6 days'));

        return $dates;
    }


    /**
     * Кои от артикулите имат активна рецепта, проверени групово
     *
     * @param array $productIds
     *
     * @return array $res - [productId => productId]
     */
    private static function getProductsWithMaterials($productIds)
    {
        $res = array();
        $productIds = array_filter($productIds);
        if (empty($productIds)) {

            return $res;
        }

        // Без рецепта материалите идват от драйвера, а никой драйвер не връща такива
        $bomQuery = cat_Boms::getQuery();
        $bomQuery->EXT('canManifacture', 'cat_Products', 'externalName=canManifacture,externalKey=productId');
        $bomQuery->where("#state = 'active' AND #canManifacture != 'no'");
        $bomQuery->in('type', array('production', 'sales'));
        $bomQuery->in('productId', $productIds);
        $bomQuery->show('productId');

        return arr::extractValuesFromArray($bomQuery->fetchAll(), 'productId');
    }


    /**
     * Записите на всички материали от списъците, заредени с една заявка
     *
     * @param array $materialLists - [ключ => [['productId' => ..., 'quantity' => ...], ...]]
     *
     * @return array $res - [productId => stdClass(id, canStore, groups)]
     */
    private static function getMaterialRecs($materialLists)
    {
        $ids = array();
        foreach ($materialLists as $materials) {
            foreach ($materials as $val) {
                if (!empty($val['productId'])) {
                    $ids[$val['productId']] = $val['productId'];
                }
            }
        }

        $res = array();
        if (empty($ids)) {

            return $res;
        }

        $query = cat_Products::getQuery();
        $query->in('id', $ids);
        $query->show('id,canStore,groups');
        while ($pRec = $query->fetch()) {
            $res[$pRec->id] = $pRec;
        }

        return $res;
    }


    /**
     * Виртуални задания от недопроизведените количества по активните продажби до крайната дата
     *
     * @param string $endDay - последният ден от периода
     * @return array $vJobsArr - [saleId|productId => stdClass]
     */
    public static function createdVirtualJobs($endDay)
    {
        // Активни договори за продажба със срок за доставка към края на избраните седмици
        $sQuery = sales_SalesDetails::getQuery();
        $sQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        $sQuery->where("#state = 'active'");
        $sQuery->EXT('deliveryTime', 'sales_Sales', 'externalName=deliveryTime,externalKey=saleId');
        $sQuery->EXT('deliveryTermTime', 'sales_Sales', 'externalName=deliveryTermTime,externalKey=saleId');
        $sQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');

        // Дали датата на доставка е в периода (при NULL записът остава за проверка на срока на доставка)
        $sQuery->where(array("#deliveryTime <= '[#1#]' OR #deliveryTime IS NULL", $endDay . ' 23:59:59'));
        $sQuery->show('saleId,productId,quantityInPack,quantity,deliveryTime,deliveryTermTime,valior');

        $salesDetails = $sQuery->fetchAll();
        $salesIdArr = arr::extractValuesFromArray($salesDetails, 'saleId');

        // Задания за производство към договорите за този период
        $jobsQuery = planning_Jobs::getQuery();
        $jobsQuery->where("#state != 'rejected' AND #state != 'draft'");
        $jobsQuery->in('saleId', $salesIdArr);
        $jobsQuery->show('saleId,productId,quantityInPack,quantity');

        $jobsArr = array();
        while ($jobRec = $jobsQuery->fetch()) {
            $key = $jobRec->saleId.'|'.$jobRec->productId;
            $quantity = ($jobRec->quantity ?? 0) * ($jobRec->quantityInPack ?? 1);

            if (!array_key_exists($key, $jobsArr)) {
                $jobsArr[$key] = (object) array(
                    'saleId' => $jobRec->saleId,
                    'productId' => $jobRec->productId,
                    'quantity' => $quantity
                );
            } else {
                $obj = & $jobsArr[$key];
                $obj->quantity += $quantity;
            }
        }

        // Виртуалните задания са договореното по активните договори минус заявеното в задания към тях
        $vJobsArr = array();
        foreach ($salesDetails as $sDetRec) {
            $deliveryDay = $sDetRec->deliveryTime ?? null;

            // Ако е зададен срок за доставка, крайната дата трябва да е в периода
            if (!$deliveryDay) {
                if (!empty($sDetRec->deliveryTermTime) && !empty($sDetRec->valior)) {
                    $newDeliveryDay = dt::addSecs($sDetRec->deliveryTermTime, $sDetRec->valior);
                    if ($newDeliveryDay > $endDay) {
                        continue;
                    }
                    $deliveryDay = $newDeliveryDay;
                } else {
                    continue;
                }
            }

            $vKey = $sDetRec->saleId.'|'.$sDetRec->productId;
            $assignedQuantity = $jobsArr[$vKey]->quantity ?? 0;
            $quantity = ($sDetRec->quantity ?? 0) * ($sDetRec->quantityInPack ?? 1) - $assignedQuantity;

            // Ако няма недопроизведено количество, не се създава виртуално задание
            if ($quantity <= 0) {
                continue;
            }

            $week = date('W', strtotime($deliveryDay)).'-'.date('Y', strtotime($deliveryDay));

            // Ако срокът за доставка е изтекъл, заданието се отнася към нулева седмица
            if ($deliveryDay < dt::today()) {
                $week = '0-0';
            }

            if (!array_key_exists($vKey, $vJobsArr)) {
                $vJobsArr[$vKey] = (object) array(
                    'productId' => $sDetRec->productId,
                    'quantity' => $quantity,
                    'saleId' => $sDetRec->saleId,
                    'quantityProduced' => 0,
                    'dueDate' => $deliveryDay,
                    'week' => $week
                );
            } else {
                $obj = &$vJobsArr[$vKey];

                $obj->quantity += $quantity;
            }
        }

        return $vJobsArr;
    }
}
