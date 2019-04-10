<?php


/**
 * Терминал за "Точки на продажба"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class pos_TerminalImpl extends peripheral_BaseTerminalImpl
{
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, pos';
    
    
    /**
     * Клас екстендър, който да се закача
     *
     * @param string
     */
    public $extenderClass = 'pos_Points';
    
    
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     *
     * @see peripheral_TerminalIntf
     */
    public function openTerminal($pointId, $userId)
    {
        $posId = pos_Points::fetchField(array("#classId = '[#1#]' AND #objectId = '[#2#]'", peripheral_Devices::getClassId(), $pointId));
        
        return new Redirect(array('pos_Points', 'openTerminal', $posId));
    }
    
    
    /**
     * 
     * @param pos_TerminalImpl   $Driver
     * @param peripheral_Devices $Embedder
     * @param stdClass           $data
     */
    protected static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $Extender = cls::get($Driver->extenderClass);
        
        $data->form->setField("{$Extender->className}_name", array('mandatory' => null));
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param pos_TerminalImpl   $Driver
     * @param peripheral_Devices $Embedder
     * @param core_Form          $form
     * @param stdClass           $data
     */
    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            $Extender = cls::get($Driver->extenderClass);
            $nameFld = $Extender->className . "_name";
            if (!$form->rec->{$nameFld}) {
                $form->rec->{$nameFld} = $form->rec->name;
            }
        }
    }
}
