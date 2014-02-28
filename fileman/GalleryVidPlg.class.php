<?php


/**
 * Максимална дължина на полето "Вербален идентификатор"
 */
defIfNot('FILEMAN_GALLERY_VID_LEN', 128);


/**
 * Хендлър за генериране на уникален идентификатор
 */
defIfNot('FILEMAN_GALLERY_VID_HANDLER_PTR', 'dddd');


/**
 * Клас 'cms_VerbalIdPlg' - Вербално id за ред
 * 
 * Добавя възможност за уникален вербален идентификатор на записите.
 * По подразбиране за уникален идентификатор се използва титлата на записа.
 * 
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class fileman_GalleryVidPlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Записваме стойността в инстанцията
        $this->vidFieldName = $mvc->galleryVidFieldName ? $mvc->galleryVidFieldName : 'vid';
        
        // Добавяне на полето
        $mvc->FLD($this->vidFieldName, 'varchar(' . FILEMAN_GALLERY_VID_LEN . ')', 'caption=Вербално ID, column=none, width=100%');
        
        // Полето да е уникално
        $mvc->setDbUnique($this->vidFieldName);
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
        $vidFieldName = $this->vidFieldName;
        
        // Вземаме адреса на записа
        $recVid = &$rec->{$vidFieldName};
        
        setIfNot($this->mvc, $mvc);
        
        // Нулираме брояча
        $i = 0;
        
        // Ако не е зададено Вербално ID от потребителя
        if(!$recVid) {
            
            // Вземаме титлата
            $recVid = $mvc->getRecTitle($rec);
            
            // Канононизираме - на латиница и само с букви и цифри
            $recVid = str::canonize($recVid);
            
            do {
                // Ако достигнем максималния брой опити
                if(16 < $i++) error('Unable to generate random file handler', $rec);
                
                // Генерирам псевдо-случаен стринг
                $hash = str::getRand(FILEMAN_GALLERY_VID_HANDLER_PTR);
                
                // Добавяме хеша след
                $recVidNew = $recVid . '-' . $hash;
            } while ($mvc->fetch("#{$vidFieldName} = '$recVidNew'"));
        } else {
            
            // Вербализираме вербалното ID - само букви и цифри на латиница или кирилица
            $recVidNew = $recVid = trim(preg_replace('/[^\p{L}0-9]+/iu', '-', " {$recVid} "), '-');
            
            // Ако има такъв запис
            while ($fRec = ($mvc->fetch("#{$vidFieldName} = '$recVidNew'"))) {
                
                // Ако редактираме текущия запис, да не се порменя
                if ($fRec->id == $rec->id) break;
                
                $i++;
                
                // Добавяме новото име
                $recVidNew = $recVid . '-' . $i;
            }
        }
        
        $mdPart = max(4, round(FILEMAN_GALLERY_VID_LEN / 8));
        
        // Ограничавае дължината
        $recVid = str::convertToFixedKey($recVidNew, FILEMAN_GALLERY_VID_LEN - 10, $mdPart);
    }
}
