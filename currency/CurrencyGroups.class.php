<?php



/**
 * Мениджър за групи на валутите
 *
 *
 * @category  all
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_CurrencyGroups extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, Currencies=currency_Currencies, currency_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, name";
    
    
    /**
     * Заглавие
     */
    var $title = 'Валутни групи';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Линк, който води към съдържанието на групите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        $row->name = Ht::createLink($row->name, array('currency_Currencies', 'list', 'groupId' => $rec->id));
    }
    
    
    /**
     * Добавяне три групи при инсталиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array(
                'name' => 'Основни',
            ),
            array(
                'name' => 'За източна Европа',
            ),
            array(
                'name' => 'За Русия',
            )
        );
        
        $nAffected = 0;
        
        foreach ($data as $rec) {
            $rec = (object)$rec;
            
            if (!$this->fetch("#name='{$rec->name}'")) {
                if ($this->save($rec)) {
                    $nAffected++;
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} групи.</li>";
        }
    }
}