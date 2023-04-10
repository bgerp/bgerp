<?php


/**
 * Модел за последна продажба на артикули по контрагенти
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_LastSaleByContragents extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Последна продажба на артикули по контрагенти';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Полета, които се виждат
     */
    public $listFields = 'productId,folderId,lastDate,lastDateContainerId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty)', 'caption=Артикул');
        $this->FLD('folderId', 'key2(mvc=doc_Folders,select=title,coverInterface=crm_ContragentAccRegIntf)', 'caption=Папка');
        $this->FLD('lastDate', 'date', 'caption=Последна продажба->Вальор');
        $this->FLD('lastDateContainerId', 'key(mvc=doc_Containers,select=id)', 'caption=Последна продажба->Документ');

        $this->setDbUnique('productId,folderId');
        $this->setDbIndex('folderId');
        $this->setDbIndex('productId');
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->folderId = doc_Folders::getFolderTitle(doc_Folders::fetch($rec->folderId));
        $row->lastDateContainerId = doc_Containers::getDocument($rec->lastDateContainerId)->getLink(0);
    }


    /**
     * Подредба на записите
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'productId,folderId';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();

        if($filter = $data->listFilter->rec){
            if(isset($filter->productId)){
                $data->query->where("#productId = {$filter->productId}");
            }

            if(isset($filter->folderId)){
                $data->query->where("#folderId = {$filter->folderId}");
            }
        }

        $monthsBefore = sales_Setup::get('DELTA_NEW_PRODUCT_TO');
        $date = dt::getLastDayOfMonth(dt::addMonths(-1 * $monthsBefore));
        $date = dt::mysql2verbal($date, 'd.m.Y');
        $data->title = "Последни продажби на артикули преди|*: <b style='color:green'>{$date}</b>";
    }


    /**
     * Обновяване на датата на последната продажба на артикулите в папката
     *
     * @param array $productArr
     * @param int $folderId
     * @return void
     */
    public static function updateDates($productArr, $folderId)
    {
        // Кои са актуалните времена
        $newRecs = static::getLastSaleDate($productArr, $folderId);

        // Кои са старите времена
        $oQuery = static::getQuery();
        $oQuery->where("#folderId = {$folderId}");
        $oQuery->in('productId', $productArr);
        $res = arr::syncArrays($newRecs, $oQuery->fetchAll(), 'productId,folderId', 'lastDate,lastDateContainerId');

        // Инсърт на новите
        if (countR($res['insert'])) {
            static::saveArray($res['insert']);
        }

        // Ъпдейт на старите
        if (countR($res['update'])) {
            static::saveArray($res['update'], 'id,lastDate,lastDateContainerId');
        }

        // Изтриване на тези дето не се срещат
        if (countR($res['delete'])) {
            $delete = implode(',', $res['delete']);
            static::delete("#id IN ({$delete})");
        }
    }


    /**
     * На коя дата е последната продажба в посочения интервал
     *
     * @param array $productArr       - ид-та на артикули
     * @param int $folderId           - в коя папка да се проверява
     * @param int|null $monthsBefore  - колко месеца назад
     * @return array $result          - масив от обекти
     */
    public static function getLastSaleDate($productArr, $folderId, $monthsBefore = null)
    {
        $monthsBefore = isset($monthsBefore) ? $monthsBefore : sales_Setup::get('DELTA_NEW_PRODUCT_TO');
        $date = dt::getLastDayOfMonth(dt::addMonths(-1 * $monthsBefore));

        $result = array();
        $sQuery = sales_PrimeCostByDocument::getQuery();
        $sQuery->where("#valior <= '{$date}' AND #state IN ('active', 'closed') AND #folderId = {$folderId}");
        if(countR($productArr)){
            $sQuery->in('productId', $productArr);
        } else {
            $sQuery->where("1=2");
        }
        $sQuery->show('productId,containerId,valior,folderId');
        $sQuery->orderBy('valior=DESC,id=DESC');
        while($sRec = $sQuery->fetch()){
            if(array_key_exists($sRec->productId, $result)) continue;
            $result[$sRec->productId] = (object)array('productId' => $sRec->productId, 'folderId' => $sRec->folderId, 'lastDate' => $sRec->valior, 'lastDateContainerId' => $sRec->containerId);
        }

        return $result;
    }


    /**
     * Обновява датите на артикулите от детайла на мастъра
     *
     * @param core_Mvc $mvc
     * @param int|stdClass $masterRec
     * @return void
     */
    public static function updateByMvc($mvc, $masterRec)
    {
        if(!isset($mvc->mainDetail)) return;

        // има ли ид-та на артикулите в детайла
        $Detail = cls::get($mvc->mainDetail);
        if(!isset($Detail->productFld)) return;

        $masterRec = $mvc->fetchRec($masterRec);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$masterRec->id}");
        $dQuery->show($Detail->productFld);
        $pIds = arr::extractValuesFromArray($dQuery->fetchAll(), $Detail->productFld);
        if(!countR($pIds)) return;

        sales_LastSaleByContragents::updateDates($pIds,  $masterRec->folderId);
    }


    public function act_Test()
    {
        requireRole('debug');

        $monthsBefore = sales_Setup::get('DELTA_NEW_PRODUCT_TO');
        $date = dt::getLastDayOfMonth(dt::addMonths(-1 * $monthsBefore));
        $dateFrom = dt::getLastDayOfMonth(dt::addDays(-420));

        $result = array();
        $sQuery = sales_PrimeCostByDocument::getQuery();
        $sQuery->where("#valior >= '{$dateFrom}' AND #valior <= '{$date}' AND #state IN ('active', 'closed')");
        $sQuery->show('productId,containerId,valior,folderId');
        $sQuery->orderBy('valior=DESC,id=DESC');
        $allFound = $sQuery->fetchAll();
        $count = countR($allFound);
        core_App::setTimeLimit($count * 0.2, false, 400);
        foreach($allFound as $sRec){
            if(array_key_exists("{$sRec->productId}|{$sRec->folderId}", $result)) continue;
            $result["{$sRec->productId}|{$sRec->folderId}"] = (object)array('productId' => $sRec->productId, 'folderId' => $sRec->folderId, 'lastDate' => $sRec->valior, 'lastDateContainerId' => $sRec->containerId);
        }

        $oQuery = static::getQuery();
        $exRecs = $oQuery->fetchAll();
        $res = arr::syncArrays($result, $exRecs, 'productId,folderId', 'lastDate,lastDateContainerId');
        bp($res['insert']);
    }
}