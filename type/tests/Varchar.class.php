<?php


/**
 * Unit тестове за type_Varchar
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class type_tests_Varchar extends unit_Class
{
    /**
     * Масив (напр. name[]=... в URL-то) се отхвърля с грешка на типа
     */
    public static function test_FromVerbalRejectsArray(type_Varchar $Varchar)
    {
        foreach (array('varchar', 'varchar(64,noTrim)', 'varchar(nullIfEmpty)') as $typeName) {
            foreach (array(array('test'), array('a' => 'x')) as $value) {
                $Type = core_Type::getByName($typeName);

                ut::expectEqual($Type->fromVerbal($value) === false, true);
                ut::expectEqual($Type->error, 'Очаква се текст');
            }
        }
    }


    /**
     * Скаларните стойности и обектите с __toString се обработват както преди
     */
    public static function test_FromVerbalKeepsText(type_Varchar $Varchar)
    {
        $Type = core_Type::getByName('varchar');
        ut::expectEqual($Type->fromVerbal('  test  '), 'test');
        ut::expectEqual($Type->fromVerbal('') === '', true);
        ut::expectEqual($Type->fromVerbal(null) === '', true);
        ut::expectEqual($Type->fromVerbal(5) === '5', true);
        ut::expectEqual($Type->fromVerbal(new ET('test')) === 'test', true);
        ut::expectEqual($Type->error, '');

        $Type = core_Type::getByName('varchar(noTrim)');
        ut::expectEqual($Type->fromVerbal('  test  ') === '  test  ', true);

        $Type = core_Type::getByName('varchar(nullIfEmpty)');
        ut::expectEqual($Type->fromVerbal('') === null, true);
    }


    /**
     * Масив за текстово поле дава грешка във формата, а типовете с масиви работят
     */
    public static function test_FormInputWithArray(type_Varchar $Varchar)
    {
        foreach (array(array('test'), array('a' => 'x')) as $value) {
            $form = self::inputSilent(array('search' => $value, 'flags' => array('a' => 'a', 'b' => 'b')));

            ut::expectEqual($form->gotErrors('search'), true);
            ut::expectEqual(isset($form->rec->search), false);
            ut::expectEqual($form->gotErrors('flags'), false);
            ut::expectEqual($form->rec->flags, 'a,b');
        }

        $form = self::inputSilent(array('search' => ' test '));
        ut::expectEqual($form->gotErrors(), false);
        ut::expectEqual($form->rec->search, 'test');
    }


    /**
     * Чете тиха форма с текстово поле и поле, което приема масив
     */
    private static function inputSilent($vars)
    {
        $form = cls::get('core_Form');
        $form->FNC('search', 'varchar', 'caption=Търсене,input,silent');
        $form->FNC('flags', 'set(a=A,b=B)', 'caption=Флагове,input,silent');

        $requestName = 'typeTestsVarcharInput';
        Request::push($vars, $requestName);
        Mode::push('haveErrInAct', Mode::get('haveErrInAct'));

        try {
            $form->input(null, 'silent');
        } finally {
            Mode::pop('haveErrInAct');
            Request::pop($requestName);
        }

        return $form;
    }
}
