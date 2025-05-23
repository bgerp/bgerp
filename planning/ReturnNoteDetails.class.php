<?php


/**
 * Клас 'planning_ReturnNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за връщане
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_ReturnNoteDetails extends deals_ManifactureDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за връщане';
    
    
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
                        planning_plg_ReplaceProducts, plg_PrevAndNext,cat_plg_ShowCodes,import2_Plugin';
    
    
    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'planning_interface_ImportDetailIntf';
    
    
    /**
     * Кои операции от задачите ще се зареждат
     */
    public $taskActionLoad = 'production';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,consumption,store';
    
    
    /**
     * Кой има право да променя взаимно заменяемите артикули?
     */
    public $canReplaceproduct = 'ceo,consumption,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,consumption,store';
    
    
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
    public $currentTab = 'Протоколи->Връщане';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Да се забрани ли създаването на нова партида
     */
    public $cantCreateNewBatch = true;
    
    
    /**
     * Какви продукти да могат да се избират в детайла
     */
    protected $defaultMeta = 'canConvert';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ReturnNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
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
        }
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $recs = &$data->recs;
        if (!countR($recs)) return;
        if($data->masterData->rec->useResourceAccounts != 'yes') return;
        if (!in_array($data->masterData->rec->state, array('draft', 'pending'))) return;

        planning_WorkInProgress::applyQuantityHintIfNegative($data->rows, $data->recs);
    }


    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $masterRec = $mvc->Master->fetch($rec->noteId);
        if($masterRec->useResourceAccounts == 'yes'){
            if($res->operation['out'] != batch_Items::WORK_IN_PROGRESS_ID){
                $res->operation['in'] = $res->operation['out'];
                $res->operation['out'] = batch_Items::WORK_IN_PROGRESS_ID;
            }
        } else {
            unset($res->operation['out']);
            $res->operation['in'] = $masterRec->storeId;
        }
    }
}
