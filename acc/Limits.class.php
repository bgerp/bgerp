<?php



/**
 * Служи за да можем да задаваме лимити за салдото на определени сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Limits extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Лимити";
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, Accounts=acc_Accounts, 
                     acc_WrapperSettings, plg_State2, Items=acc_Items, 
                     Lists=acc_Lists, plg_AutoFilter';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('limitType', 'enum(min=Минимум,max=Максимум)', 'caption=Тип');
        $this->FLD('limitQuantity', 'double', 'caption=Лимит');
        $this->FLD('startDate', 'combodate', 'caption=От начална дата');
        $this->FLD('limitDuration', 'int', 'caption=Продължителност');
        
        $this->FLD('acc', 'key(mvc=acc_Accounts, select=title, allowEmpty)', 'caption=Сметка->Име, silent, autoFilter');
        $this->FLD('item1', 'key(mvc=acc_Items, select=title, allowEmpty)', 'caption=Сметка->Перо 1, input=none');
        $this->FLD('item2', 'key(mvc=acc_Items, select=title, allowEmpty)', 'caption=Сметка->Перо 2, input=none');
        $this->FLD('item3', 'key(mvc=acc_Items, select=title, allowEmpty)', 'caption=Сметка->Перо 3, input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        // Ако е зададена с-ка
        if (!empty($data->form->rec->acc)) {
            $accRec = $mvc->Accounts->fetch($data->form->rec->acc);
            
            $data->form->addAttr('acc', array('onchange' => "addCmdRefresh(this.form); this.form.submit();"));
            
            if (!empty($accRec->groupId1)) {
                $data->form->setField('item1', 'input');
                $data->form->setField('item1', 'caption=Сметка->' . $mvc->Lists->fetchField($accRec->groupId1, 'title'));
            }
            
            if (!empty($accRec->groupId2)) {
                $data->form->setField('item2', 'input');
                $data->form->setField('item2', 'caption=Сметка->' . $mvc->Lists->fetchField($accRec->groupId2, 'title'));
            }
            
            if (!empty($accRec->groupId3)) {
                $data->form->setField('item3', 'input');
                $data->form->setField('item3', 'caption=Сметка->' . $mvc->Lists->fetchField($accRec->groupId3, 'title'));
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'acc';
        
        $form->input('acc', 'silent');
        
        if($form->rec->acc == ""){
            $data->query->groupBy("acc");
        }else {
            $data->query->where(array("#acc = '[#1#]'", $form->rec->acc));
        }
    }
}