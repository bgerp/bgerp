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
     * Име на перото за неразпределени разходи
     */
    const UNALLOCATED_ITEM_NAME = 'Неразпределени разходи';


    /**
     * Заглавие
     */
    public $title = 'Генерични артикули в документи';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper';


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
    public $listFields = 'detailClassId, detailRecId, containerId, productId, genericProductId';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 300;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory,silent,input=hidden,remember');
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
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->containerId)){
            $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        }
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->genericProductId = cat_Products::getHyperlink($rec->genericProductId, true);
    }

    function act_Test()
    {
        requireRole('debug');
        $this->truncate();
        return;
        static::recalc();
    }

    public static function recalc()
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