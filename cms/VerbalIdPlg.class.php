<?php


/**
 * Максимална дължина на полето "Вербален идентификатор"
 */
defIfNot('EF_VID_LEN', 100);


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
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cms_VerbalIdPlg extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        $this->fieldName = isset($mvc->vidFieldName) ? $mvc->vidFieldName : 'vid';
        
        $mvc->FLD($this->fieldName, 'varchar(' . EF_VID_LEN . ')', 'caption=SEO->Вербално ID, column=none, width=100%,autohide=any');
        
        // SEO Заглавие
        $mvc->FLD('seoTitle', 'varchar(128)', 'caption=SEO->Title,column=none, width=100%,autohide');
        
        // SEO Описание
        $mvc->FLD('seoDescription', 'text(500,rows=3)', 'caption=SEO->Description,column=none, width=100%,autohide');
        
        // SEO Ключови думи
        $mvc->FLD('seoKeywords', 'text(500,rows=3)', 'caption=SEO->Keywords,column=none, width=100%,autohide');
        
        // SEO Илюстрация
        $mvc->FLD('seoThumb', 'fileman_FileType(bucket=cmsFiles)', 'caption=SEO->Илюстрация,column=none, width=100%,autohide');
        
        $mvc->setDbUnique($this->fieldName);
        
        $mvc->searchFields = arr::make($mvc->searchFields);
        $mvc->searchFields[] = $this->fieldName;
        $mvc->searchFields[] = 'seoTitle';
        $mvc->searchFields[] = 'seoDescription';
        $mvc->searchFields[] = 'seoKeywords';

        if(isset($mvc->changableFields)) {
            $mvc->changableFields = arr::make($mvc->changableFields, true);
            $mvc->changableFields['seoTitle'] = 'seoTitle';
            $mvc->changableFields['seoDescription'] = 'seoDescription';
            $mvc->changableFields['seoKeywords'] = 'seoKeywords';
            $mvc->changableFields['seoThumb'] = 'seoThumb';
            $mvc->changableFields[$this->fieldName] = $this->fieldName;
        }

        // Да не се кодират id-тата, когато се използва verbalId
        $mvc->protectId = false;
    }


    /**
     * Връща дефолтното вербално ид
     */
    public static function on_AfterGetDefaultVerbalId($mvc, &$res, $rec)
    {
        if(!$res){
            $res = $mvc->getRecTitle($rec);
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public function on_BeforeSave(&$mvc, &$id, &$rec, &$fields = null)
    {
        $fieldName = $this->fieldName;

        if ($fields) {
            $fArr = arr::make($fields, true);
            
            // Ако полето не участва - не правим нищо
            if (!$fArr[$fieldName]) {
                
                return;
            }
        }
        
        $recVid = &$rec->{$fieldName};
        
        setIfNot($this->mvc, $mvc);
        
        $recVid = trim(preg_replace('/[^\p{L}0-9]+/iu', '-', " {$recVid} "), '-');

        if (!$recVid) {
            $recVid = $mvc->getDefaultVerbalId($rec);
            $recVid = str::canonize($recVid);
        }

        if (!strlen($recVid)) {
            $recVid = $mvc->className . '_' . $mvc->fieldName;
        }

        expect(strlen($recVid), $recVid);
        
        $cond = "#{$this->fieldName} LIKE '[#1#]'";
        
        if ($rec->id) {
            $cond .= " AND #id != {$rec->id}";
        }
        
        $i = 0;

        // Дали вербалното ид е над допустимото
        if(mb_strlen($recVid) > EF_VID_LEN){

            // Ако е намаля се до допустимата дължина
            $recVid = mb_substr($recVid, 0, EF_VID_LEN);
        }

        // Докато има същото вербално ид - генерираме ново, докато стане уникално
        while ($mvc->fetchField(array($cond, $recVid), 'id') || is_numeric($recVid) || empty($recVid)) {

            $i++;
            $suffix = '-' . $i;

            // Проверява се колко ще стане дължината на новия уникален стринг
            $newLen = mb_strlen($recVid) + mb_strlen($suffix);
            if($newLen > EF_VID_LEN){

                // Ако е над допустимото, съкращаваме го до допустимата дължина като запазваме наставката
                $recVid = mb_substr($recVid, 0, mb_strlen($recVid) - mb_strlen($suffix));
            }

            $recVid = $recVid . $suffix;
            if (is_numeric($recVid)) {
                $recVid .= '_';
            }

            if ($i > 3000) {
                expect(false, $recVid, $rec, $i);
            }
        }

        expect($rec->{$fieldName});
        
        cms_VerbalId::saveVid($recVid, $mvc, $rec->id);
    }
    
    
    /**
     * Преди екшън, ако id-то не е цифрово го приема, че е vid и извлича id
     * Поставя, коректното id в Request
     */
    public function on_BeforeAction($mvc, $action)
    {
        $vid = Request::get('id');
        
        if ($vid && !is_numeric($vid)) {
            $vid = urldecode($vid);
            
            $id = $mvc->fetchField(array("#vid COLLATE {$mvc->db->dbCharset}_general_ci LIKE '[#1#]'", $vid), 'id');
            
            if (!$id) {
                $id = cms_VerbalId::fetchId($vid, $mvc);
            }
            
            Request::push(array('id' => $id));
        }
    }
    
    
    /**
     * След извличане на ключовите думи
     */
    public static function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        $syn = cms_Setup::get('SEO_SYNONYMS');
        
        if ($syn) {
            $cKey = md5($syn);
            
            if (!($synArr = core_Cache::get('SEO-SYN', $cKey))) {
                $syn = json_decode($syn);
                $i = 0;
                while ($syn->s1[$i]) {
                    $synArr[$i] = array();
                    $synArr[$i][] = plg_Search::normalizeText($syn->s1[$i]);
                    if ($syn->s2[$i]) {
                        $synArr[$i][] = plg_Search::normalizeText($syn->s2[$i]);
                    }
                    if ($syn->s3[$i]) {
                        $synArr[$i][] = plg_Search::normalizeText($syn->s3[$i]);
                    }
                    if ($syn->s4[$i]) {
                        $synArr[$i][] = plg_Search::normalizeText($syn->s4[$i]);
                    }
                    if ($syn->s5[$i]) {
                        $synArr[$i][] = plg_Search::normalizeText($syn->s5[$i]);
                    }
                    $i++;
                }
                core_Cache::set('SEO-SYN', $cKey, $synArr, 24 * 60);
            }
            
            $rec = $mvc->fetchRec($rec);
            
            if (!isset($searchKeywords)) {
                $searchKeywords = plg_Search::getKeywords($mvc, $rec);
            }
            
            if ($searchKeywords && countR($synArr)) {
                foreach ($synArr as $group) {
                    foreach ($group as $word) {
                        if (strpos($searchKeywords, $word) !== false) {
                            self::addKeyWords($searchKeywords, $group);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавя без повторение масив от думи към стринг с ключови думи
     */
    private static function addKeyWords(&$searchKeywords, $group)
    {
        foreach ($group as $word) {
            if (strpos($searchKeywords, $word) === false) {
                $searchKeywords .= ' ' . $word;
            }
        }
    }
}
