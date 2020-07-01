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
        setIfNot($mvc->canChangerangenum, 'ceo,admin');
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
       
        if($mvc->haveRightFor('changerangenum', $form->rec)){
            $options = cond_Ranges::getAvailableRanges($mvc);
            $form->setField($mvc->rangeNumFld, 'input');
            if(countR($options) > 1){
                $form->setOptions($mvc->rangeNumFld, $options);
            }
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
                        $rec->{$mvc->numberFld} = cond_Ranges::getNextNumber($rec->{$mvc->rangeNumFld}, $mvc, $mvc->numberFld, $mvc->rangeNumFld);
                    
                        cond_Ranges::updateRange($rec->{$mvc->rangeNumFld}, $rec->{$mvc->numberFld});
                    } catch(core_exception_Expect $e){
                        
                        return new Redirect(array($mvc, 'single', $rec->id), 'Изберете друг диапазон, този е запълнен', 'error');
                    }
                    
                    if($mvc->hasPlugin('plg_Search')){
                        $rec->searchKeywords .= ' ' . plg_Search::normalizeText($rec->{$mvc->numberFld});
                        
                        $numberVerbal = $mvc->getVerbal($rec, $mvc->numberFld);
                        if(strpos($rec->searchKeywords, ' ' . $numberVerbal) === false){
                            $rec->searchKeywords .= ' ' . plg_Search::normalizeText($numberVerbal);
                        }
                    }
                }
            }
        }
    }
}