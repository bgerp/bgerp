<?php


/**
 * Драйвър за артикул - производствен етап
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производствен етап
 */
class planning_interface_StageDriver extends cat_GeneralProductDriver
{
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, planningMaster';
    
    
    /**
     * Мета данни по подразбиране
     *
     * @param string $defaultMetaData
     */
    protected $defaultMetaData = 'canManifacture,canConvert,canSell,canStore';
    
    
    /**
     * Клас екстендър, който да се закача
     *
     * @param string $defaultMetaData
     */
    public $extenderClass = 'planning_Stages';
    
    
    /**
     * Икона на артикулите
     */
    protected $icon = 'img/16/paste_plain.png';
    
    
    /**
     * Подготвяне на вербалните стойности
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param stdClass       $row
     * @param stdClass       $rec
     */
    protected function on_AfterRecToVerbal(cat_ProductDriver $Driver, $mvc, $row, $rec)
    {
        unset($row->editMetaBtn);
    }
}