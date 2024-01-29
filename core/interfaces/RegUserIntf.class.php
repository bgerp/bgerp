<?php


/**
 * Интерфейс за източници на клиентски карти
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за регистриране на потребители
 */
class core_interfaces_RegUserIntf
{
    /**
     * Клас
     */
    public $class;


    /**
     * Дали може да се създава потребител към този източник
     *
     * @param string $id
     *
     * @return boolean
     */
    public function canCreateUser($id)
    {

        return $this->class->canCreateUser($id);
    }


    /**
     * Връща ролите, които са позволени за този източник
     *
     * @param string $id
     *
     * @return array mixed
     */
    public function getRoles($id)
    {

        return $this->class->getRoles($id);
    }


    /**
     * Връща id-то на фирмата, към която е свързан потребителя
     *
     * @param string $id
     * @param integer $uId
     *
     * @return null|int
     */
    public function getUserBuzCompanyId($id, $uId)
    {

        return $this->class->getUserBuzCompanyId($id, $uId);
    }


    /**
     * Връща масив с имейл за активация на потребителя
     *
     * @param string $id
     * @param integer $uId
     *
     * @return array
     *  ['subject'] - тема на имейла
     *  ['body'] - съдържание на имейла
     *  ['from'] - id на изходяща поща
     *
     * @see core_interfaces_RegUserIntf::getEmailData()
     */
    public function getEmailData($id, $uId)
    {

        return $this->class->getEmailData($id, $uId);
    }


    /**
     * Връща съобщение за активация на потребителя
     *
     * @param string $id
     * @param integer $uId
     *
     * @return string
     *
     * @see core_interfaces_RegUserIntf::getSMSData()
     */
    public function getSMSData($id, $uId)
    {

        return $this->class->getSMSData($id, $uId);
    }


    /**
     * След активиране на акаунта
     *
     * @param string $id
     * @param integer $uId
     *
     * @return string
     *
     * @see core_interfaces_RegUserIntf::getSMSData()
     */
    public function afterActivateAccount($id)
    {

        return $this->class->afterActivateAccount($id);
    }
}