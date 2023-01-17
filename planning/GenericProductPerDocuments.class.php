<?php


/**
 * Регистър за отнесени разходи
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_GenericProductPerDocuments extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Генерични артикули в документи';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper,plg_RowTools2,plg_Sorting';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'containerId, productId, genericProductId';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 300;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('detailClassId', 'class(interface=core_ManagerIntf,select=title)', 'caption=Детайл,mandatory,silent,remember');
        $this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory,silent,input=hidden');
        $this->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,input=hidden,tdClass=leftCol');
        $this->FLD('genericProductId', 'int', 'caption=Генеричен,mandatory,silent,input=hidden,tdClass=leftCol');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'mandatory,caption=Документ,silent,input=hidden');

        $this->setDbUnique('detailClassId,detailRecId');
        $this->setDbIndex('containerId');
    }


    public static function getRec($detailClassId, $detailRecId)
    {
        $Detail = cls::get($detailClassId);
        $res = static::fetchField("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$detailRecId}", 'genericProductId');

        return !empty($res) ? $res : null;
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('id', 'DESC');

        $data->listFilter->FLD('docId', 'varchar', 'caption=Документ');
        $data->listFilter->FLD('product', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Артикул');
        $data->listFilter->showFields .= "docId,product,detailClassId";
        $data->listFilter->input(null, 'silent');

        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        if ($filter = $data->listFilter->rec) {
            if(!empty($filter->docId)){
                if ($document = doc_Containers::getDocumentByHandle($filter->docId)) {
                    $data->query->where("#containerId = {$document->fetchField('containerId')}");
                }
            }
            if(!empty($filter->product)){
                $data->query->where("#productId = {$filter->product} OR #genericProductId = {$filter->product}");
            }

            if(!empty($filter->detailClassId)){
                $data->query->where("#detailClassId = {$filter->detailClassId}");
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->containerId)){
            $Document = doc_Containers::getDocument($rec->containerId);
            $row->containerId = $Document->getLink(0);
            $row->ROW_ATTR['class'] = "state-{$Document->fetchField('state
            ')}";
        }
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->genericProductId = cat_Products::getHyperlink($rec->genericProductId, true);
    }

    function act_Test()
    {
        requireRole('debug');

        static::recalc();
    }


    /**
     * Рекалкулиране на записите
     */
    private static function recalc()
    {
        $map = $save = array();
        $gQuery = planning_GenericMapper::getQuery();
        while ($gRec = $gQuery->fetch()){
            $map[$gRec->productId] = $gRec->genericProductId;
            $map[$gRec->genericProductId] = $gRec->genericProductId;
        }

        $usedProducts = array_keys($map);
        $classes = array('cat_BomDetails', 'planning_ProductionTaskProducts', 'planning_ConsumptionNoteDetails', 'planning_ReturnNoteDetails', 'planning_DirectProductNoteDetails');
        foreach ($classes as $clsName){
            $Detail = cls::get($clsName);
            $classId = $Detail->getClassId();

            $dQuery = $Detail->getQuery();
            $dQuery->EXT('containerId', $Detail->Master->className, "externalName=containerId,externalKey={$Detail->masterKey}");
            setIfNot($Detail->productFld, 'productId');
            $dQuery->in($Detail->productFld, $usedProducts);
            $dQuery->show("{$Detail->productFld},containerId,id");
            while($dRec = $dQuery->fetch()){
                $save[] = (object)array('detailClassId' => $classId,
                                        'detailRecId' => $dRec->id,
                                        'productId' => $dRec->{$Detail->productFld},
                                        'genericProductId' => $map[$dRec->{$Detail->productFld}],
                                        'containerId' => $dRec->containerId);
            }
        }


        // Какви са наличните записи на документа
        $exQuery =  static::getQuery();
        $exRecs = $exQuery->fetchAll();

        // Синхронизиране на старите със новите записи
        $me = cls::get(get_called_class());
        $synced = arr::syncArrays($save, $exRecs, 'detailClassId,detailRecId', 'detailClassId,detailRecId,productId,genericProductId');

        if(countR($synced['insert'])){
            $me->saveArray($synced['insert']);
        }

        if(countR($synced['update'])){
            $me->saveArray($synced['update'], 'id,detailClassId,detailRecId,productId,genericProductId,containerId');
        }

        if(countR($synced['delete'])){
            $deleteIds = implode(',', $synced['delete']);
            $me->delete("#id IN ({$deleteIds})");
        }
    }


    /**
     * Синхронизира документа
     *
     * @param mixed $detailClassId
     * @param int $detailRecId
     * @param int $productId
     * @param int $containerId
     * @param int|null $genericProductId
     * @return void
     */
    public static function sync($detailClassId, $detailRecId, $productId, $containerId, $genericProductId = null)
    {
        $Detail = cls::get($detailClassId);
        if(empty($genericProductId)){
            static::delete("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$detailRecId}");
        } else {
            $rec = (object)array('detailClassId' => $Detail->getClassId(), 'detailRecId' => $detailRecId, 'productId' => $productId, 'containerId' => $containerId, 'genericProductId' => $genericProductId);
            cls::get(get_called_class())->save($rec, null, 'REPLACE');
        }
    }
}