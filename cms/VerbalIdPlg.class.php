<?php



/**
 * Максимална дължина на полето "Вербален идентификатор"
 */
defIfNot('EF_VID_LEN', 128);


/**
 * Клас 'cms_VerbalIdPlg' - Вербално id за ред
 *
 * Добавя възможност за уникален вербален идентификатор на записите,
 * управлявани от MVC мениджъри. По подразбиране полето в което се поддържа
 * този идентификатор е с име 'vid'. Друго име може да се окаже в $mvc->vidFieldName
 * По подразбиране за уникален идентификатор се използва титлата на записа, 
 * конвертирана до латиница и съкратена до EF_VID_LEN символа
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cms_VerbalIdPlg extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        $this->fieldName = $mvc->vidFieldName ? $mvc->vidFieldName : 'vid';
        
        $mvc->FLD($this->fieldName, 'varchar(' . EF_VID_LEN . ')', 'caption=Вербално ID, column=none, width=100%');

        $mvc->setDbUnique($this->fieldName);
        
        // Да не се кодират id-тата, когато се използва verbalId
        $mvc->protectId = FALSE;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$mvc, &$id, &$rec, &$fields = NULL)
    {
        $fieldName = $this->fieldName;

        $recVid = &$rec->{$fieldName};

        setIfNot($this->mvc, $mvc);

        if(!$recVid) {
            $recVid = $mvc->getRecTitle($rec);
            $recVid = str::canonize($recVid);
        } else {
            $recVid = trim(preg_replace('/[^\p{L}0-9]+/iu', '-', " {$recVid} "), '-');
        }

        $cond = "#{$this->fieldName} LIKE '[#1#]'";

        if($rec->id) {
            $cond .= " AND #id != {$rec->id}";
        }

        $baseVid = $recVid;

        $i=0;
        
        while ($mvc->fetchField(array($cond, $recVid), 'id') || is_numeric($recVid) || empty($recVid)) {
            $i++;
            $recVid = $baseVid . '-' . $i;
            if(is_numeric($recVid)) $recVid .= '_'; 
            if($i>3000) bp($recVid, $rec, $i);
        }

        expect($rec->{$fieldName});

        cms_VerbalId::saveVid($recVid, $mvc, $rec->id);
    }


    /**
     * Преди екшън, ако id-то не е цифрово, го приема че е vid и извлича id
     * Поставя, коректното id в Request
     */
    function on_BeforeAction($mvc, $action)
    {
        $vid = Request::get('id');

        if($vid && !is_numeric($vid)) {
            
            $vid = urldecode($vid);

            $id = $mvc->fetchField(array("#vid COLLATE utf8_general_ci LIKE '[#1#]'", $vid), 'id');

            if(!$id) {
                $id = cms_VerbalId::fetchId($vid, $mvc);
            }
            
            Request::push(array('id' => $id));
        }
    }



}