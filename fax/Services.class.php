<?php


/**
 * Шаблона, който се използва за факса на получателя
 */
defIfNot('RECIPIENT_FAX_NUMBER_TEMPLATE', 'RECIPIENT_FAX');


/**
 * Факс адреси
 *
 *
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fax_Services extends core_Master
{
    
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'fax_Wrapper, plg_State, plg_Created, plg_Modified, doc_FolderPlg, plg_RowTools';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Факс адреси";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, fax';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, fax';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, fax';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, fax';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, fax';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, fax';
    
    
    /**
     * Кой има права за
     */
    var $canFax = 'admin, fax';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'name, template, boxFrom';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Факс кутии';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/fax.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'boxFrom';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, boxFrom, name, template, folderId, inCharge, access, shared, createdOn, createdBy';
        
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('template', 'varchar', 'caption=Шаблон,hint=Шаблона на факса на получателя, width=100%');
        $this->FLD('boxFrom', 'key(mvc=email_Inboxes, select=email)', 'caption=Имейл');
    }
    
    
    /**
     * Изпълнява се след подготвяне на формата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, $res, &$data)
    {
        if (!$data->form->rec->id) {
            //По подразбиране да е избран текущия имейл на потребителя
            $data->form->setDefault('boxFrom', email_Inboxes::getUserEmailId());
            
            $data->form->setDefault('template', "[#" . RECIPIENT_FAX_NUMBER_TEMPLATE . "#]@");
            
        }
    }
}