<?php

class techno_specifications_Register
{


    /**
     * Преобразуване на запис на регистър към запис за перо в номенклатура (@see acc_Items)
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @return stdClass запис за модела acc_Items:
     *
     * o num
     * o title
     * o uomId (ако има)
     * o features - списък от признаци за групиране
     */
    function getItemRec($objectId)
    {
        $rec = $this->class->fetchRec($objectId);
        
        $itemRec = (object)array(
            'num' => 'SPC'.$rec->id,
            'title' => $rec->title,
            'uomId' => $rec->measureId,
        );
        
        return $itemRec;
    }
    
    
    /**
     * Хипервръзка към този обект
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @return mixed string или ET (@see ht::createLink())
     */
    function getLinkToObj($objectId)
    {
        return array($this->class, 'single', $objectId);
    }
    
    
    /**
     * Нотифицира регистъра, че обекта е станал (или престанал да бъде) перо
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @param boolean $inUse true - обекта е перо; false - обекта не е перо
     */
    function itemInUse($objectId, $inUse)
    {
        /* TODO */
    }
    
    
    /**
     * Имат ли обектите на регистъра размерност?
     *
     * @return boolean
     */
    static function isDimensional()
    {
        return TRUE;
    }
    
}