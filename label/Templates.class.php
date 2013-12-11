<?php 


/**
 * Шаблони за създаване на етикети
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Templates extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Шаблони';
    
    
    /**
     * 
     */
    var $singleTitle = 'Шаблон';
    
    
    /**
     * Път към картинка 16x16
     */
//    var $singleIcon = 'img/16/.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'label/tpl/SingleLayoutTemplates.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'label_Wrapper, plg_RowTools, plg_Created, plg_State, plg_Search, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'title, template=Шаблон, createdOn, createdBy';
    
    
    /**
     * 
     */
    var $rowToolsField = 'title';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, template, css';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'label_TemplateFormats';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('template', 'html', 'caption=Шаблон->HTML');
        $this->FLD('css', 'text', 'caption=Шаблон->CSS');
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        // Ако имаме права за добавяне на етикет
        if (label_Labels::haveRightFor('add')) {
        
        	// Добавяме бутон за нов етикет
            $data->toolbar->addBtn('Нов етикет', array('label_Labels', 'add', 'templateId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/star_2.png');
        }
    }
    
    
    /**
     * Връща шаблона
     * 
     * @param integer $id - id на записа
     * 
     * @return core_Et - Шаблона на записа
     */
    static function getTemplate($id)
    {
        // Масив с шаблоните
        static $tplArr = array();
        
        // Ако преди е бил извлечен
        if ($tplArr[$id]) return $tplArr[$id];
        
        // Шаблона
        $template = static::fetchField($id, 'template');
        
        // Шаблона
        $tpl = new ET($template);
        
        // Добавяме стиловете
        $tpl->append(static::fetchField($id, 'css'), 'STYLES');
        
        // Добавяме в масива
        $tplArr[$id] = $tpl;
        
        return $tplArr[$id];
    }
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $row
     * @param unknown_type $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Добавяме CSS' а към шаблона
        $row->template = "<style>" . $rec->css . "</style>" . $row->template;
    }
    
    
 	/**
 	 * Изпълнява се след подготовката на формата за филтриране
 	 * 
 	 * @param unknown_type $mvc
 	 * @param unknown_type $data
 	 */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Формата
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search';
        
        // Инпутваме полетата
        $form->input(NULL, 'silent');
    }
    
    
    /**
     * Активира шаблона
     * 
     * @param integer $id - id на записа
     * 
     * @retunr integer - id на записа
     */
    public static function activateTemplate($id)
    {
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Очакваме да не е оттеглен
        expect($rec->state != 'rejected');
        
        // Ако състоянието е 'draft'
        if ($rec->state != 'active') {
            
            // Сменяме състоянито на активно
            $rec->state = 'active';
            
            // Записваме
            $id = static::save($rec);
            
            return $id;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param label_Labels $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис
        if ($rec) {
            
            // Ако редактираме
            if ($action == 'edit') {
                
                // Ако е оттеглено
                if ($rec->state == 'rejected') {
                    
                    // Оттеглените да не могат да се редактират
                    $requiredRoles = 'no_one';
                }
            }
            
            // Ако оттегляме
            if ($action == 'reject') {
                
                // Ако е активно
                if ($rec->state == 'active') {
                    
                    // Активните да не могат да се оттеглят
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
