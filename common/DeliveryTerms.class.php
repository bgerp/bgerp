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
    var $title = 'Начини на доставка';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',       'varchar', 'caption=Име');
        $this->FLD('description', 'text',    'caption=Oписание');
        
        $this->setDbUnique('name');
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
                'title' => 'ExWork',
                'description' => 'до завода на Екстрапак във В. Търново'
            ),
            array(
                'title' => 'Franko BG',
                'description' => 'до склад на територията на България'
            ),
            array(
                'title' => 'Franko SF',
                'description' => 'до склада на Екстрапак в София'
            ),
            array(
                'title' => 'DDU EU',
                'description' => 'до склад в европейска държава, без платени мита',
            array(
                'title' => 'DDU via DHL',
                'description' => 'до склад в европейска държава, чрез DHL',
            ),            
            array(
                'title' => 'FOB',
                'description' => 'натоварено на кораб на пристанище Варна',
            ),            
            array(
                'title' => 'other',
                'description' => 'по отделна уговорка',
            ),            
            array(
                'title' => 'DDU FoPlGr',
                'description' => 'до ФормПластГруп, Модена, Италия',
            ),            
            array(
                'title' => 'DDU 3A Pac',
                'description' => 'до 3A Packaging, St. Etienne, Франция',
            ),            
            array(
                'title' => 'DDU V-PACK',
                'description' => 'до VARIA-PACK, Belgium',
            ),            
            array(
                'title' => 'DDU AT',
                'description' => 'DDU Австрия',
            ),            
            array(
                'title' => 'DDU BE',
                'description' => 'DDU Белгия',
            ),            
            array(
                'title' => 'DDU CY',
                'description' => 'DDU Кипър',
            ),            
            array(
                'title' => 'DDU CH',
                'description' => 'DDU Швейцария',
            ),            
            array(
                'title' => 'DDU CZ',
                'description' => 'DDU Чехия',
            ),            
            array(
                'title' => 'DDU DE',
                'description' => 'DDU Германия',
            ),            
            array(
                'title' => 'DDU DK',
                'description' => 'DDU Дания',
            ),            
            array(
                'title' => 'DDU EE',
                'description' => 'DDU Естония',
            ),            
            array(
                'title' => 'DDU ES',
                'description' => 'DDU Испания',
            ),            
            array(
                'title' => 'DDU IS',
                'description' => 'DDU Исландия',
            ),            
            array(
                'title' => 'DDU IE',
                'description' => 'DDU Ирландия',
            ),            
            array(
                'title' => 'DDU IT',
                'description' => 'до склад в Италия, без платени мита',
            ),            
            array(
                'title' => 'DDU FI',
                'description' => 'DDU Финландия',
            ),            
            array(
                'title' => 'DDU FR',
                'description' => 'до склад в Франция, без платени мита',
            ),            
            array(
                'title' => 'DDU GR',
                'description' => 'DDU Гърция',
            ),            
            array(
                'title' => 'DDU HR',
                'description' => 'DDU Словения',
            ),            
            array(
                'title' => 'DDU LI',
                'description' => 'DDU Лихтенщайн',
            ),            
            array(
                'title' => 'DDU LU',
                'description' => 'DDU Люксембург',
            ),            
            array(
                'title' => 'DDU LT',
                'description' => 'DDU Литва',
            ),            
            array(
                'title' => 'DDU LV',
                'description' => 'DDU Латвия',
            ),            
            array(
                'title' => 'DDU MC',
                'description' => 'DDU Македония',
            ),            
            array(
                'title' => 'DDU MT',
                'description' => 'DDU Малта',
            ),            
            array(
                'title' => 'CIF MT',
                'description' => 'CIF Малта',
            ),            
            array(
                'title' => 'DDU NO',
                'description' => 'DDU Норвегия',
            ),            
            array(
                'title' => 'DDU NL',
                'description' => 'DDU Холандия',
            ),            
            array(
                'title' => 'DDU PT',
                'description' => 'DDU Португалия',
            ),            
            array(
                'title' => 'DDU RO',
                'description' => 'DDU Румъния',
            ),            
            array(
                'title' => 'DDU SE',
                'description' => 'DDU Швеция',
            ),            
            array(
                'title' => 'DDU SI',
                'description' => 'DDU Словения',
            ),            
            array(
                'title' => 'DDU SK',
                'description' => 'DDU Словакия',
            ),            
            array(
                'title' => 'DDU UK',
                'description' => 'DDU Великобритания',
            )            
        );
        
        if(!$mvc->fetch("1=1")) {
            
            $nAffected = 0;
            
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$this->fetch("#name='{$rec->name}'")) {
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