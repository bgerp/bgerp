<?php


/**
 * Клас 'drdata_type_Uic' - тип за ЕИК кодове или Национални номера
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Ivelin Dimov
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_type_Uic extends type_Varchar
{
    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 26;


    /**
     * За коя държава
     */
    protected $countryId;


    /**
     * Инициализиране на дължината
     */
    public function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params[0], $this->dbFieldLen);
    }


    /**
     * Проверка и сетване на грешки във формата
     *
     * @param core_Form $form
     * @param string $string
     * @param int $countryId
     * @param string $fieldname
     * @return void
     */
    public static function check($form, $string, $countryId, $fieldname = 'uicId')
    {
        $isError = $msg = null;
        self::checkUicId($string, $countryId, $msg, $isError);

        if (!empty($msg)) {
            if ($isError === true) {
                $form->setError($fieldname, $msg);
            } else {
                $form->setWarning($fieldname, $msg);
            }
        }
    }


    /**
     * Премахваме HTML елементите при визуализацията на всички типове,
     * които не предефинират тази функция
     */
    public function toVerbal_($value)
    {
        if ($value === null) {

            return;
        }

        $value = parent::toVerbal_($value);

        if (!empty($value)) {
            $msg = $isError = null;
            static::checkUicId($value, $this->params['countryId'], $msg, $isError);

            if (!empty($msg) && !Mode::isReadOnly()) {
                $value = "<span class='red'>{$value}</span>";
                $icon = ($isError === true) ? 'error' : 'warning';
                $value = ht::createHint($value, $msg, $icon);
            }
        }

        return $value;
    }


    /**
     * Проверява дали подадения национален номер е валиден
     * В случая че държавата е България или няма държава, проверяваме
     * дали е валиден ЕИК номер. Във всички други случаи приемаме че е валиден
     *
     * @param string $uicNo     - националния номер на контрагента
     * @param string $countryId - id на държавата, NULL за България
     *
     * @return bool - валиден ли е националния номер
     */
    private static function checkUicId($uicNo, $countryId = null, &$msg, &$isError)
    {
        $msg = null;
        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');

        // Ако няма държава или държавате е България, провряваме дали е валиден ЕИК номер
        if (empty($countryId) || $countryId == $bgId) {

            // Дали е валидно ЕИК ?
            $res = drdata_Vats::isBulstat($uicNo);
            if ($res) {

                return true;
            }

            $msg = 'Невалиден ЕИК';

            // Ако не е валидно и с 10 символа, се проверява дали не е ЕГН
            if (mb_strlen($uicNo) == 10) {
                $Egn = cls::get('bglocal_EgnType');
                $res = $Egn->isValid($uicNo);
                if (!isset($res['error'])) {

                    return true;
                }
                $msg = 'ДДС номер (9,10 или 13 символа): въведени са 10 символа, които не са валидно ЕИК/ЕГН';
            }

            $isError = true;

            return false;
        }

        // Ако се стигне до тук, винаги номера е валиден
        return true;
    }
}