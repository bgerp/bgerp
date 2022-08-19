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
        $this->FLD('lastDate', 'datetime', 'caption=Последна продажба преди->На');
        $this->FLD('lastDateContainerId', 'key(mvc=doc_Containers,select=id)', 'caption=Последна продажба преди->Документ');

        $this->setDbIndex('productId,folderId');
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

        $secondsBefore = sales_Setup::get('CALC_NEW_PRODUCT_FROM');
        $date = dt::verbal2mysql(dt::addSecs(-1 * $secondsBefore), false);
        $date = dt::mysql2verbal($date, 'd.m.Y');
        $data->listFields['lastDate'] = "Продажба преди|* '{$date}'->На";
        $data->listFields['lastDateContainerId'] = "Продажба преди|* '{$date}'->Документ";

    }

    public static function updateDates($productArr, $folderId)
    {
        $newRecs = static::getLastSaleDate($productArr, $folderId);

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
            static::saveArray($res['update'], 'lastDate,lastDateContainerId');
        }

        // Изтриване на тези дето не се срещат
        if (countR($res['delete'])) {
            $delete = implode(',', $res['delete']);
            static::delete("#id IN ({$delete})");
        }
    }


    public static function getLastSaleDate($productArr, $folderId, $secondsBefore = null)
    {
        $secondsBefore = isset($secondsBefore) ? $secondsBefore : sales_Setup::get('CALC_NEW_PRODUCT_FROM');
        $date = dt::verbal2mysql(dt::addSecs(-1 * $secondsBefore), false);

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
}