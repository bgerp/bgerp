<?php

defIfNot('CONTACTS_GROUPS_PREFIX', 'КТ');


/**
 * Мениджър на групи с визитки
 */
class contacts_Groups extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Групи с визитки";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Групи";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, contacts_Wrapper';
    
    
    /**
     * Права
     */
    var $canWrite = 'contacts,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'contacts,admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име на групата');
        $this->FLD('info', 'text', 'caption=Описание');
        
        $this->setDbUnique("name");
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = ht::createLink($row->name, array('contacts_Contacts', 'groupId' => $rec->id));
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        core_Classes::addClass($mvc);
        
        // BEGIN В случай, че няма данни в таблицата, зареждаме от масив.
        if (!$mvc->fetch('1=1')) {
            // BEGIN масив с данни за инициализация
            $data = array(
                array(
                    'name' => 'КЛИЕНТИ',
                    'sortId' => 30
                ),
                array(
                    'name' => 'ДОСТАВЧИЦИ',
                    'sortId' => 31
                ),
                array(
                    'name' => 'ДЕБИТОРИ',
                    'sortId' => 32
                ),
                array(
                    'name' => 'КРЕДИТОРИ',
                    'sortId' => 33
                ),
                array(
                    'name' => 'СЛУЖИТЕЛИ',
                    'sortId' => 34
                )
            );
            // END масив с данни за инициализация
            
            
            $nAffected = 0;
            
            // BEGIN За всеки елемент от масива
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                $rec->info = $rec->name ;
                
                $this->save($rec, NULL, 'ignore');
                
                $nAffected++;
            }
            // END За всеки елемент от масива
            
            if ($nAffected) {
                $res .= "<li style='color:green;'>Добавени са {$nAffected} групи.</li>";
            }
        }
        // END В случай, че няма данни в таблицата, зареждаме от масив.        
    }
}