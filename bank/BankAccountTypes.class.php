<?php


/**
 * Клас 'bank_BankAccountTypes' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    bank
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class bank_BankAccountTypes extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, bank_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Типове банкови сметки';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Тип');
        $this->FLD('note', 'text', 'caption=Забележка');
    }
    
    
    /**
     *  Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $data = array(
            array(
                'name' => 'Разплащателна',
                'note' => ''
            ),
            array(
                'name' => 'Депозитна',
                'note' => 'за съхранение на пари'
            ),
            array(
                'name' => 'Бюджетна',
                'note' => 'за съхранение на пари на разпоредителите с бюджетни средства и пари, отпуснати от бюджета на други лица'
            ),
            array(
                'name' => 'Спестовна',
                'note' => 'за съхранение на пари на граждани срещу издаване на лична спестовна книжка'
            ),
            array(
                'name' => 'Набирателна',
                'note' => 'за съхранение на пари, предоставени за разпореждане от клиента на негово поделение'
            ),
            array(
                'name' => 'Акредитивна',
                'note' => 'за предоставени за разплащане на клиента с трето лице, което има право да получи средствата при изпълнение на условията, поставени при откриване на акредитива'
            ),
            array(
                'name' => 'Ликвидационна',
                'note' => 'за съхранение на пари на лица, обявени в ликвидация'
            ),
            array(
                'name' => 'Особенa',
                'note' => 'за съхранение на пари на лица, за които е открито производство по несъстоятелност'
            ),
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