<?php


/**
 * Драйвер за показване на общите цели
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Общи цели
 */
class acc_drivers_TotalRepPortal extends core_BaseClass
{
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('target', 'int(Min=0)', 'caption=Цел, mandatory');
        $fieldset->FLD('gaugeType', 'enum(radial=Скоростомер,linear=Линейно)', 'caption=Показване, mandatory');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if (haveRole('ceo', $userId)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param acc_drivers_TotalRepPortal $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterPrepareSingleFields($Driver, $Embedder, &$res, $data)
    {
        if (!$Driver->canSelectDriver()) {
            unset($data->singleFields['target']);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        if (!$Driver->canSelectDriver()) {
            $data->form->setField('target', 'input=none');
        }
    }
    
    
    /**
     * Подготвя данните
     *
     * @param stdClass $dRec
     * @param null|integer $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        $resData = new stdClass();
        
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $resData->data = new stdClass();
        
        $from = date('Y-m-01');
        $to = dt::getLastDayOfMonth($from);
        $target = $dRec->target;
        
        $query = hr_Indicators::getQuery();
        $deltaId = acc_reports_TotalRep::getDeltaId();
        $query->where(array("(#date >= '[#1#]' AND #date <= '[#2#]') AND #indicatorId = [#3#]", $from, $to, $deltaId));
        
        $query->limit(1);
        
        $query->orderBy('id', 'DESC');
        
        $query->show('id, value');
        
        $iRec = $query->fetch();
        
        $resData->cacheKey = md5($dRec->id . '_' . $dRec->modifiedOn . '_' . $dRec->target . '_' . $userId . '_' . Mode::get('screenMode') . '_' . core_Lg::getCurrent() . '_' . $iRec->id . '_' . $iRec->value . '_' . dt::now(false));
        $resData->cacheType = 'TotalRepPortal';
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            $resData->speed = acc_reports_TotalRep::getDeltaSpeed($from, $to, $target, $deltaId);
        }
        
        $resData->gaugeType = $dRec->gaugeType ? $dRec->gaugeType : 'radial';
        
        $resData->canvasId = 'totalRepPortal_' . $dRec->originIdCalc;
        
        return $resData;
    }
    
    
    /**
     * Рендира данните
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function render($data)
    {
        if (!$data->tpl) {
            $scaleArr = array('title' => "" , 'colorPlate' => 'transparent');
            
            if ($data->gaugeType == 'linear') {
                $scaleArr = $scaleArr + array(  'width' => 420,
                                                'title' => "",
                                                'borders' => false,
                                                'borderShadowWidth' => 0
                );
            }
            
            $scaleArr['canvasId'] = $data->canvasId;
            
            $gauge = acc_reports_TotalRep::getSpeedRatioGauge($data->speed, false, $data->gaugeType, $scaleArr);
            
            $data->tpl = new ET(tr('|*<div class="clearfix21 portal"> <div class="legend">|Общи цели|*</div><div style="text-align: center"> [#GAUGE#]</div></div>'));
            
            $data->tpl->replace($gauge, 'GAUGE');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
    }
    
    
    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName($dRec)
    {
        
        return tr('Общи цели');
    }
}
