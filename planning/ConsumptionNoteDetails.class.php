<?php


/**
 * Клас 'planning_ConsumptionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за влагане
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_ConsumptionNoteDetails extends deals_ManifactureDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за влагане';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_RequestDetail, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals2,
                        planning_plg_ReplaceProducts, cat_plg_LogPackUsage, plg_PrevAndNext,cat_plg_ShowCodes,import2_Plugin';
    
    
    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'planning_interface_ImportDetailIntf';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,consumption,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,consumption,store';
    
    
    /**
     * Кой има право да подменя артикула?
     */
    public $canReplaceproduct = 'ceo,consumption,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,consumption,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity=К-во';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Влагане';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Какви продукти да могат да се избират в детайла
     */
    protected $defaultMeta = 'canConvert';
    
    
    /**
     * Кои операции от задачите ще се зареждат
     */
    public $taskActionLoad = 'input';


    /**
     * Дали артикула ще произвежда при експедиране артикулите с моментна рецепта
     */
    public $manifactureProductsOnShipment = true;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ConsumptionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (!countR($data->rows)) {
            
            return;
        }

        // от коя нишка да се игнорират запазените количества
        $firstDoc = doc_Threads::getFirstDocument($data->masterData->rec->threadId);
        $ignoreThreadId = $firstDoc->fetchField('threadId');
        if($firstDoc->isInstanceOf('planning_Tasks')){
            $taskOriginId = $firstDoc->fetchField('originId');
            $ignoreThreadId = planning_Jobs::fetchField("#containerId = {$taskOriginId}", 'threadId');
        }

        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            $deliveryDate = (!empty($data->masterData->rec->deadline)) ? $data->masterData->rec->deadline : $data->masterData->rec->valior;
            deals_Helper::getQuantityHint($row->packQuantity, $mvc, $rec->productId, $data->masterData->rec->storeId, $rec->quantity, $data->masterData->rec->state, $deliveryDate, $ignoreThreadId);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        if (isset($rec->productId)) {
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            $masterRec = planning_ConsumptionNotes::fetch($rec->noteId, 'storeId,deadline,valior');
            
            if (isset($masterRec->storeId) && $canStore == 'yes') {
                $deliveryDate = (!empty($masterRec->deadline)) ? $masterRec->deadline : $masterRec->valior;
                $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->storeId, $deliveryDate);
                $form->info = $storeInfo->formInfo;
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if(empty($data->masterRec->storeId)){
            unset($data->defaultMeta);
            $data->form->setFieldTypeParams('productId', array('hasProperties' => 'canConvert', 'hasnotProperties' => 'canStore'));
        } else {
            if(isset($data->masterRec->originId)) {
                $origin = doc_Containers::getDocument($data->masterRec->originId);
                if ($origin->isInstanceOf('cal_Tasks')) {

                    // Ако има избрано оборудване към сигнала, ще излизат като препоръчани резервните части за него
                    if($taskAssetId = $origin->fetchField('assetResourceId')){
                        $sQuery = planning_AssetSparePartsDetail::getQuery();
                        $sQuery->where("#assetId = {$taskAssetId}");
                        $sQuery->show('productId');
                        $sparePartsIds = arr::extractValuesFromArray($sQuery->fetchAll(), 'productId');

                        if(countR($sparePartsIds)){
                            $data->form->setFieldTypeParams('productId', array('favourites' => $sparePartsIds));
                        }
                    }
                }
            }
        }
    }


    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $masterRec = $mvc->Master->fetch($rec->noteId);
        if($masterRec->useResourceAccounts == 'yes'){
            $res->operation['in'] = batch_Items::WORK_IN_PROGRESS_ID;
        }
    }
}
