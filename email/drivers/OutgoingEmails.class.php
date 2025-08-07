<?php


/**
 * Абстрактен драйвер за изходящи имейли
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class email_drivers_OutgoingEmails extends core_BaseClass
{
    /**
     * Поле, което се обновява
     */
    protected $updateField = '';

    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_ServiceRulesIntf';


    /**
     *
     *
     * @param email_Mime  $mime
     * @param stdClass  $serviceRec
     *
     * @return string|null
     *
     * @see email_ServiceRulesIntf
     */
    public function process($mime, $serviceRec)
    {

        return null;
    }


    /**
     * След добавяне на запис
     *
     * @param $Driver
     * @param $mvc
     * @param $id
     * @param $rec
     * @param $saveFields
     * @return void
     */
    public static function on_AfterSave($Driver, $mvc, &$id, $rec, $saveFields = null)
    {
        $uRec = array();
        $fNameArr = arr::make($Driver->updateField);
        foreach ($fNameArr as $fName => $addrFName) {
            $uRec[$addrFName] = $rec->{$fName};
        }

        email_AddressesInfo::updateRecFor($rec->email, $uRec);
    }


    /**
     * След изтриване в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    protected static function on_AfterDelete($Driver, $mvc, &$numRows, $query, $cond)
    {
        foreach ((array) $query->getDeletedRecs() as $rec) {
            $uRec = array();
            $fNameArr = arr::make($Driver->updateField);
            foreach ($fNameArr as $addrFName) {
                $uRec[$addrFName] = null;
            }

            email_AddressesInfo::updateRecFor($rec->email, $uRec);
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param tremol_FiscPrinterDriverWeb $Driver
     * @param peripheral_Devices     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setField('emailTo', 'input=none');
        $data->form->setField('subject', 'input=none');
        $data->form->setField('body', 'input=none');

        $data->form->fields['email']->caption = 'Получател';
        $data->form->info = '';

        $data->form->rec->_systemId = $Driver->getClassId();

        if (!$data->form->rec->id) {
            $dRec = $Embedder->fetch(array("#email = '[#1#]' AND #driverClass = '[#2#]'", $data->form->rec->email, $Driver->getClassId()));
            if ($dRec) {
                $data->form->setDefault('id', $dRec->id);
            }
        }

        $data->form->input('email');

        if ($data->form->rec->email) {
            $rRec = email_AddressesInfo::getRecFor($data->form->rec->email);
            if ($rRec) {
                $fArr = arr::make($Driver->updateField);
                foreach ($fArr as $fName => $addrFName) {
                    $data->form->setDefault($fName, $rRec->{$addrFName});
                }
            }
        }
    }
}
