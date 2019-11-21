<?php


/**
 * 
 *
 *
 * @category  bgerp
 * @package   peripheal
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_TerminalChoicePlg extends core_Plugin
{
    function on_PrepareLoginForm($mvc, &$form)
    {
        if ($_GET['ret_url']) {
            
            return ;
        }
        
        $dArr = peripheral_Devices::getDevices('peripheral_TerminalIntf');
        
        if (empty($dArr)) {
            
            return ;
        }
        
        $form->FNC('terminal', 'key(mvc=peripheral_Devices, select=name, allowEmpty)', 'caption=Терминал, input');
        
        $tArr = array();
        foreach ($dArr as $dId => $dRec) {
            $tArr[$dId] = $dRec->name;
        }
        
        $form->InputFields .= ',terminal';
        
        $form->setOptions('terminal', $tArr);
        
        if (!empty($tArr)) {
            $form->setDefault('terminal', key($tArr));
        }
    }
    
    
    /**
     * Прихващаме всяко логване в системата
     */
    public static function on_AfterLogin($mvc, $userRec, $inputs, $refresh)
    {
        if ($refresh || !$inputs->terminal) {
            
            return;
        }
        
        $dRec = peripheral_Devices::fetch($inputs->terminal);
        
        if ($dRec->driverClass) {
            $inst = cls::getInterface('peripheral_TerminalIntf', $dRec->driverClass);
            
            $rUrl = $inst->getTerminalUrl($dRec->data['objVal']);
            core_Request::push(array('ret_url' => toUrl($rUrl, 'local')));
        }
    }
}
