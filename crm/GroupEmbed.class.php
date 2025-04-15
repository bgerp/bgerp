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
        $cQuery->where("#groupList LIKE '%|{$rec->crmGroup}|%'");
        while($cRec = $cQuery->fetch()) {
            $contragents[$cRec->id] = crm_Companies::recToVerbal($cRec);
            $contragents[$cRec->id]->name = crm_Companies::getVerbal($cRec, 'name');
            if ($cRec->logo) {
                $thumb = new thumb_Img(array($cRec->logo, 100, 100, 'fileman', $contragents[$cRec->id]->name));
                $contragents[$cRec->id]->logo = $thumb->createImg();
            } else {
                $contragents[$cRec->id]->logo = ht::createImg(array('class' => 'logoImg', 'alt' => $contragents[$cRec->id]->name, 'src' => sbf("img/noimage120.gif", '')));
            }
        }

        // Рендиране
        foreach ($contragents as $row) {
            $row->name = transliterate(tr($row->name));
            $row->country = transliterate(tr($row->country));
            $row->place = transliterate(tr($row->place));
            $row->address = transliterate(tr($row->address));
            $rowTpl = clone $tpl->getBlock('CONTRAGENT');
            $rowTpl->placeObject($row);
            $rowTpl->removeBlocksAndPlaces();
            $rowTpl->append2master();
        }


        $tpl->push("crm/css/groupList.css", 'CSS');
        $tpl->push("crm/js/groupList.js", 'JS');

        return $tpl;
    }
}
