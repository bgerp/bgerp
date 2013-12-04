<?php 


/**
 * 
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
     * Полета, които ще се клонират
     */
//    var $cloneFields = '';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'label';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'label';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'label';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'label';
    
    
    /**
     * Плъгини за зареждане
     */
//    var $loadList = 'plg_Printing, bgerp_plg_Blank, plg_Search';
    var $loadList = 'label_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
//    var $listFields = '';
    
    
    /**
     * 
     */
//    var $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
//    var $rowToolsSingleField = 'id';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//    var $searchFields = '';
    
    
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
        // Шаблона
        $template = static::fetchField($id, 'template');
        
        // Шаблона
        $tpl = new ET($template);
        
        // Добавяме стиловете
        $tpl->append(static::fetchField($id, 'css'), 'STYLES');
        
        return $tpl;
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
        $row->template = $rec->css . $row->template;
    }
}
