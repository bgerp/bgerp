<?php


/**
 * Клас 'common_LocationTypes' -
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
class common_LocationTypes extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, common_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, name, comment";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Типове локации';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Име');
        $this->FLD('comment', 'varchar(255)', 'caption=Коментар');
    }
    
    
    /**
     * След setup на класа се insert-ват няколко типа
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array(
                'name' => 'магазин',
                'comment' => ''
            ),
            array(
                'name' => 'склад',
                'comment' => ''
            ),
            array(
                'name' => 'офис',
                'comment' => ''
            ),
            array(
                'name' => 'цех',
                'comment' => ''
            ),
            array(
                'name' => 'завод',
                'comment' => ''
            ),
            array(
                'name' => 'земя',
                'comment' => ''
            ),
            array(
                'name' => 'строеж',
                'comment' => ''
            ),
            array(
                'name' => 'кариера',
                'comment' => ''
            ),
            array(
                'name' => 'други',
                'comment' => ''
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
            $res .= "<li>Добавени са {$nAffected} тип(а) банкови сметки.</li>";
        }
    }
}