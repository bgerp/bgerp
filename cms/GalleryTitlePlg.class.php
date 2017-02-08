<?php


/**
 * Максимална дължина на полето "Вербален идентификатор"
 */
defIfNot('CMS_GALLERY_TITLE_LEN', 128);


/**
 * Хендлър за генериране на уникален идентификатор
 */
defIfNot('CMS_GALLERY_TITLE_HANDLER_PTR', 'dddd');


/**
 * Добавя възможност за уникален вербален идентификатор на записите.
 * По подразбиране за уникален идентификатор се използва титлата на записа.
 * 
 * @category  bgerp
 * @package   cms
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cms_GalleryTitlePlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Записваме стойността в инстанцията
        $mvc->galleryTitleFieldName = $mvc->galleryTitleFieldName ? $mvc->galleryTitleFieldName : 'title';
        
        // Ако няма такова поле
        if(!$mvc->fields[$mvc->galleryTitleFieldName]) {
            
            // Добавяне на полето
            $mvc->FLD($mvc->galleryTitleFieldName, 'varchar(' . CMS_GALLERY_TITLE_LEN . ')', 'caption=Заглавие, width=100%');
        }
        
        // Дължината на полето
        $mvc->galleryTitleLen = $mvc->fields[$mvc->galleryTitleFieldName]->type->getDbFieldSize();
        
        // Полето да е уникално
//        $mvc->setDbUnique($this->galleryTitleFieldName);

        // @todo - да се премахне след като се прмахне добавката в on_AfterSetupMvc
        if(!$mvc->fields['vid']) {
            $mvc->FLD('vid', 'varchar(128)', 'caption=Вербално ID, width=100%, input=none');
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     * 
     * @param core_Mvc $mvc
     * @param integer $id
     * @param object $rec
     * @param mixed $fields
     */
    function on_BeforeSave(&$mvc, &$id, &$rec, &$fields = NULL)
    {
        // Името на полето
        $titleFieldName = $mvc->galleryTitleFieldName;
        
        // Вземаме адреса на записа
        $recTitle = &$rec->{$titleFieldName};
        
        // Нулираме брояча
        $i = 0;
        
        // Ако не е зададено Вербално ID от потребителя
        if(!$recTitle) {
            
            $mvc->prepareRecTitle($rec);
            
            // Вземаме титлата
            $recTitle = &$rec->{$titleFieldName};
            
            // Канононизираме - на латиница и само с букви и цифри
            $recTitle = static::canonizeTitle($recTitle);
            
            do {
                // Ако достигнем максималния брой опити
                if(16 < $i++) error('@Unable to generate random file handler', $rec);
                
                // Генерирам псевдо-случаен стринг
                $hash = str::getRand(CMS_GALLERY_TITLE_HANDLER_PTR);
                
                // Добавяме хеша след
                $recTitleNew = $recTitle . '-' . $hash;
            } while ($mvc->fetch("#{$titleFieldName} = '$recTitleNew'"));
        } else {
            
            // Вербализираме вербалното ID - само букви и цифри на латиница или кирилица
            $recTitleNew = $recTitle = static::canonizeTitle($recTitle);
            
            $dash = '-';
            
            // Ако има такъв запис
            while ($fRec = ($mvc->fetch("#{$titleFieldName} = '$recTitleNew'"))) {
                
                // Ако редактираме текущия запис, да не се порменя
                if ($fRec->id == $rec->id) break;
                
                if(($dashPos = strrpos($recTitleNew, $dash)) !== FALSE) {
                    $left = substr($recTitleNew, 0, $dashPos);
                    $right = substr($recTitleNew, $dashPos+1);
                    
                    if (is_numeric($right) && (int)$right == $right) {
                        $right++;
                        $recTitleNew = $left . $dash . $right;
                        continue;
                    }
                }
                
                $i++;
                // Добавяме новото име
                $recTitleNew = $recTitle . $dash . $i;
            }
        }
        
        $mdPart = max(4, round($mvc->galleryTitleLen / 8));
        
        // Ограничавае дължината
        $rec->{$titleFieldName} = str::convertToFixedKey($recTitleNew, CMS_GALLERY_TITLE_LEN - 10, $mdPart);
    }
    
    
    /**
     * Канонизира заглавието
     * Само букви и цифри на латиница или кирилица
     * 
     * @param string $title
     * 
     * @return string
     */
    function canonizeTitle($title)
    {
        $title = trim(preg_replace('/[^\p{L}0-9]+/iu', '-', " {$title} "), '-');
        
        return $title;
    }
    
    
    /**
     * Метод по подразбиране за викане на prepareRecTitle($rec)
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $rec
     */
    function on_AfterPrepareRecTitle($mvc, $res, &$rec)
    {
        
        return ;
    }
    
    
    /**
     * @todo - Да се премахне
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    static function on_AfterSetupMvc($mvc, &$res) 
    {
        $changed = 0;
        
        // Вземаме всички записи, които няма заглавие
        $query = $mvc->getQuery();
        $query->where("#vid != '' AND #vid IS NOT NULL");
        
        while($rec = $query->fetch()) {
            
            // Флаг, дали да се запише
            $mustSave = FALSE;
            
            // Ако няма заглавие
            if (!$rec->{$mvc->galleryTitleFieldName}) {
                
                // Вдигаме флага, за да се запише
                $mustSave=TRUE;
                
                // Задаваме заглавието от полето Vid
                $rec->{$mvc->galleryTitleFieldName} = $rec->vid;
            } else {
                
                // Ако не са равни, вдигаме флага
                if ($rec->vid != $rec->{$mvc->galleryTitleFieldName}) {
                    
                    // Ако няма запис със съответното име от полето
                    if (!$mvc->fetch("#{$mvc->galleryTitleFieldName} = '{$rec->vid}'")) {
                        
                        // Задаваме заглавието
                        $rec->{$mvc->galleryTitleFieldName} = $rec->vid;
                        
                        // Премахваме ненужните полето
                        unset($rec->id);
                        unset($rec->vid);
                        unset($rec->createdOn);
                        unset($rec->createdBy);
                        
                        // Вдигаме флага
                        $mustSave=TRUE;
                    }
                }
            }
            
            // Добавяме стойността на полето vid в заглавието
            if ($mustSave && $mvc->save($rec)) {
                $changed++;
            }
        }
        
        if ($changed) {
            $res .= "<li>Бяха създадени {$changed} записа със заглавия от стойността на 'vid'";
        }
    }
}
