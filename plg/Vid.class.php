<?php



/**
 * Максимална дължина на полето "Вербален идентификатор"
 */
defIfNot('EF_VID_LEN', 64);


/**
 * Клас 'plg_Vid' - Вербално id за ред
 *
 * Добавя възможност за уникален вербален идентификатор на записите,
 * управлявани от MVC мениджъри. По подразбиране полето в което се поддържа
 * този идентификатор е с име 'vid'. Друго име може да се окаже в $mvc->vidFieldName
 * За уникален идентификатор се използва титлата на записа, конвертирана до латиница
 * и съкратена до EF_VID_LEN символа
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Vid extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        $this->fieldName = $mvc->vidFieldName ? $mvc->vidFieldName : 'vid';
        
        $mvc->FLD($this->fieldName, 'varchar(64)', 'caption=Verbal ID,  column=none');
        $mvc->setDbUnique($this->fieldName);
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$mvc, &$id, &$rec, &$fields = NULL)
    {
        $fieldName = $this->fieldName;
        
        // Ако полето  id не е попълнено, означава че вкарваме нов запис
        if (!$rec->id || !$mvc->fetchField($rec->id, $fieldName) || $rec->vid === FALSE) {
            if ($titleField = $mvc->vidTitle) {
                $title = $rec->{$titleField};
            } else {
                $title = $mvc->getRecTitle($rec);
            }
            
            if (!$title)
            error('Невъзможно да се определи титлата', $rec);
            
            $title = str::utf2ascii($title);
            $title = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', " {$title} "), '-');
            
            $mdPart = max(4, round(EF_VID_LEN / 8));
            
            $title = str::convertToFixedKey($title, EF_VID_LEN - 9, $mdPart);
            
            $i = 1;
            
            $title1 = $title;
            
            while ($mvc->fetchField("#{$this->fieldName} = '{$title1}'", 'id')) {
                $title1 = $title . '-' . $i;
                $i++;
            }
            
            $rec->{$fieldName} = $title1;
        }
    }
}