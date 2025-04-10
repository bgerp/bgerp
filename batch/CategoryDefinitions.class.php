<?php


/**
 * Дефиниции на партиди за категориите, всички артикули в категорията
 * ако са складируеми ще им се форсира след създаването дефиниция за партида
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_CategoryDefinitions extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Партиди на категории';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Партидност към категория';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,batch_Wrapper';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'categoryId, templateId';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batchMaster,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch,ceo';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'id';


    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('categoryId', 'key(mvc=cat_Categories, select=name)', 'caption=Категория,silent,mandatory,input=hidden');
        $this->FLD('templateId', 'key(mvc=batch_Templates, select=name,allowEmpty)', 'caption=Партидност,mandatory');
        $this->setDbUnique('categoryId,templateId');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        $data->form->title = core_Detail::getEditTitle('cat_Categories', $rec->categoryId, $mvc->singleTitle, $rec->id, ' ');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec->categoryId)) {
            if ($mvc->fetch("#categoryId = '{$rec->categoryId}'")) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->categoryId = cat_Categories::getHyperlink($rec->categoryId, true);
        $row->templateId = batch_Templates::getHyperlink($rec->templateId, true);
    }
}
