<?php


/**
 *
 *
 * @category  bgerp
 * @package   polygonteam
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class polygonteam_Scales extends peripheral_DeviceDriver
{
    public $interfaces = 'wscales_intf_Scales';
    
    public $title = 'Везна на ПолигонТийм';
    
    
    /**
     *
     */
    public $loadList = 'peripheral_DeviceWebPlg';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('user', 'varchar', 'caption=Настройки за връзка с везната->Потребителско име,mandatory');
        $fieldset->FLD('pass', 'password', 'caption=Настройки за връзка с везната->Парола,mandatory');
        $fieldset->FLD('hostName', 'varchar', 'caption=Настройки за връзка с везната->Хост,mandatory');
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
        return true;
    }
    
    
    /**
     * 
     * 
     * @param stdClass
     * 
     * @see wscales_intf_Scales
     */
    public function getJs($params)
    {
        $jsTpl = getTplFromFile('/polygonteam/js/jsTpl.txt');
        
        $jsTpl->placeObject($params);
        
        return $jsTpl;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param peripheral_DeviceDriver    $Driver
     * @param peripheral_Devices          $Embedder
     * @param stdClass                    $data
     */
    protected static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $data->form->setDefault('user', 'admin');
        $data->form->setDefault('pass', 'admin');
        $data->form->setDefault('hostName', 'localhost');
    }
}
