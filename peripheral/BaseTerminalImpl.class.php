<?php


/**
 * Базова реализация за терминалните устройства
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
class peripheral_BaseTerminalImpl extends core_Mvc
{
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'embed_plg_Extender';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'peripheral_TerminalIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     * 
     * @see peripheral_TerminalIntf
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('authorization', 'enum(yes=Да,no=Не)', 'caption=Оторизация');
        $fieldset->FLD('users', 'keylist(mvc=core_Users, select=nick, where=#state !\\= \\\'rejected\\\')', 'caption=Достъп->Потребители');
        $fieldset->FLD('roles', 'keylist(mvc=core_Roles, select=role, where=#state !\\= \\\'rejected\\\')', 'caption=Достъп->Роли');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     * 
     * @see peripheral_TerminalIntf
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param pos_TerminalImpl $Driver
     * @param peripheral_Devices     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (!$form->rec->users && !$form->rec->roles) {
                $form->setError('users, roles', 'Поне едно от полетата трябва да има стойност');
            }
        }
    }
}
