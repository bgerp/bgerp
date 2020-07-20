<?php


/**
 * Плъгин за генериране на номера на документите според зададен диапазон
 * @see cond_Ranges
 * 
 * @category  bgerp
 * @package   doc
 * 
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class doc_plg_Sequencer2 extends core_Plugin
{
    

    /**
     * След инициализирането на модела
     *
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->rangeNumFld, 'numberRange');
        setIfNot($mvc->numberFld, 'number');
        setIfNot($mvc->addNumberOnActivation, false);
        
        if (!isset($mvc->fields[$mvc->rangeNumFld])) {
            $mvc->FLD($mvc->rangeNumFld, "key(mvc=cond_Ranges,select=id)", 'caption=Диапазон,input=hidden');
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $options = cond_Ranges::getAvailableRanges($mvc);
        
        if(countR($options)){
            $form->setField($mvc->rangeNumFld, 'input');
            $form->setOptions($mvc->rangeNumFld, $options);
        }
        
        $form->setDefault($mvc->rangeNumFld, cond_Ranges::getDefaultRangeId($mvc));
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        if($mvc->addNumberOnActivation === true){
            
            if ($rec->state == 'active') {
                if (empty($rec->{$mvc->numberFld})) {
                    
                    try{
                        $rec->{$mvc->numberFld} = cond_Ranges::getNextNumber($rec->{$mvc->rangeNumFld}, $mvc, $mvc->numberFld);
                        $rec->_isNumberGenerated = true;
                    } catch(core_exception_Expect $e){
                        
                        return new Redirect(array($mvc, 'single', $rec->id), 'Изберете друг диапазон, този е запълнен', 'error');
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }
        
        // Добавяне на кода към ключовите думи
        if(!empty($rec->{$mvc->numberFld})){
            $res .= ' ' . plg_Search::normalizeText($rec->{$mvc->numberFld});
            
            // Ако вербалния код е различен от този в базата добавя се и вербалния
            $numberVerbal = $mvc->getVerbal($rec, $mvc->numberFld);
            $numberVerbal = plg_Search::normalizeText($numberVerbal);
            if(strpos($res, ' ' . $numberVerbal) === false){
                $res .= ' ' . $numberVerbal;
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Първичния ключ на направения запис
     * @param stdClass     $rec     Всички полета, които току-що са били записани
     * @param string|array $fields  Имена на полетата, които sa записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if($rec->_isNumberGenerated){
            if($rec->_rollback !== true){
                
                // Маркиране на диапазона, като използван
                cond_Ranges::updateRange($rec->{$mvc->rangeNumFld}, $rec->{$mvc->numberFld});
            
                // Обновяване на ключовите думи, да се добави номера към тях
                if($mvc->hasPlugin('plg_Search')){
                    plg_Search::forceUpdateKeywords($mvc, $rec);
                }
            }
        }
    }
}