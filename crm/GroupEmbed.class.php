<?php


/**
 * Клас 'crm_GroupEmbed'
 *
 * Вграждане на група със визитки
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 */
class crm_GroupEmbed extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Група с визитки';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('crmGroup', 'key(mvc=crm_Groups,select=name)', 'caption=Група контрагенти');
        $form->FLD('layout', 'enum(standard=Стандартен)', 'caption=Лейаут');
    }
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET|string Представяне на обекта в HTML шабло
     */
    public static function render($rec, $maxwidth = 1200, $absolute = false)
    {
        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) {
            
            return '';
        }
        
       
        
        $tpl = new ET(getFileContent('crm/tpl/ContragetExternalList.shtml'));
 
        $contragents = array();

        // Извличане на визитките
        $cQuery = crm_Companies::getQuery();
        while($cRec = $cQuery->fetch("#groupList LIKE '%|{$rec->crmGroup}|%'")) {
            $contragents[$rec->id] = crm_Companies::recToVerbal($cRec);
        }

 
        // Подредба на визитките

        // Рендиране
        foreach ($contragents as $row) {
            $rowTpl = $tpl->getBlock('CONTRAGENT');
            $rowTpl->placeObject($row); 
            $rowTpl->append2master();$rowTpl->removeBlocks();
        }
            
 
       
        $tpl->push("crm/css/groupList.css", 'CSS');
        $tpl->push("crm/js/groupList.js", 'JS');

        return $tpl;
    }
}
