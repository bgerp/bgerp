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
class label_TemplateFormats extends core_Detail
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Формати за параметрите';
    
    
    /**
     * 
     */
    var $singleTitle = 'Формати';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'debug';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'label_Wrapper';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'templateId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'id';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
//    var $fetchFieldsBeforeDelete = '';
    
    
    /**
     * 
     */
//    var $listFields = 'id, name, description, maintainers';
    
    
    /**
     * 
     */
//    var $currentTab = '';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон, mandatory');
        $this->FLD('placeHolder', 'varchar', 'caption=Плейсхолдер, title=Име на плейсхолдер');
        $this->FLD('type', 'enum(caption=Надпис,counter=Брояч,image=Картинка)', 'caption=Тип');
        $this->FLD('formatParams', 'blob', 'caption=Параметри, title=Параметри за конвертиране на шаблона');
    }
}