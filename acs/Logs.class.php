<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_Logs extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = '';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = '';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, acs_Wrapper, plg_SelectPeriod';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'acs, admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'acs, admin';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('time', 'datetime', 'caption=Време');
        $this->FLD('companyId', 'key(mvc=crm_Companies, select=name, allowEmpty)', 'caption=Фирма, refreshForm');
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'caption=Лице, refreshForm');
        $this->FLD('cardId', 'int', 'caption=Карта, refreshForm'); //@todo
        $this->FLD('zoneId', 'key(mvc=acs_Zones, select=name, allowEmpty)', 'caption=Зона, refreshForm');
        $this->FLD('type', 'enum(,allowed=Разрешен достъп,denied=Забранен достъп,movement=Движение в зоната,empty=Зоната е празна,
                                openedDoor=Отворена врата,closedDoor=Затворена врата,openedWindow=Отворен прозорец, closedWindow=Затворен прозорец,
                                floor=Наводнение,fire=Пожар)', 'caption=Вид, refreshForm');
    }
    
    
    /**
     * 
     * 
     * @param stdClass $data
     * 
     * @return stdClass
     */
    function prepareListFilter($data)
    {
        parent::prepareListFilter_($data);
        
        $data->listFilter->FNC('from', 'datetime', 'caption=От, formOrder=-5');
        $data->listFilter->FNC('to', 'datetime', 'caption=До, formOrder=-4');
        
        return parent::prepareListFilter($data);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->fields['selectPeriod']->formOrder = -10;
        
        // Да се показва полето за търсене
        $data->listFilter->showFields .= ',companyId, personId, cardId, zoneId, type';
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->view = 'vertical';
        
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        $rec = $data->listFilter->rec;
        
        if ($rec->from) {
            $from = dt::mysql2timestamp($rec->from);
            $data->query->where(array("#time >= '[#1#]'", $from));
        }
        
        if ($rec->to) {
            $to = dt::mysql2timestamp($rec->to);
            $data->query->where(array("#time <= '[#1#]'", $to));
        }
        
        foreach (array('companyId', 'personId', 'cardId', 'zoneId', 'type') as $fName) {
            if ($rec->{$fName}) {
                $data->query->where(array("#{$fName} = '[#1#]'", $rec->{$fName}));
            }
        }
    }
}
