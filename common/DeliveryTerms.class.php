<?php


/**
 * Клас 'common_DeliveryTerms' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class common_DeliveryTerms extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, common_Wrapper';

    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, title, description, tools=Пулт';

    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Условия на доставка';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',       'varchar', 'caption=Име');
        $this->FLD('description', 'text',    'caption=Oписание');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array(
                'title' => 'code1',
                'description' => 'до дестинация с код 1'
            )            
        );
        
        if(!$mvc->fetch("1=1")) {
            
            $nAffected = 0;
            
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$this->fetch("#title='{$rec->title}'")) {
                    if ($this->save($rec)) {
                        $nAffected++;
                    }
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} записа.</li>";
        }
    }
}