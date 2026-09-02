<?php


/**
 * Unit тестове за формите
 *
 * @category  ef
 * @package   core
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 */
class core_tests_Form extends unit_Class
{
    /**
     * Проверява групирането по първите две части на трикомпонентните кепшъни
     */
    public static function test_MiddleCaptionGrouping(core_Form $Form)
    {
        $fields = array(
            'first' => self::getField('first', 'Section->Shared group->Allow'),
            'different' => self::getField('different', 'Section->Other group->Choose'),
            'second' => self::getField('second', 'Section->Shared group->Alternative'),
            'otherSection' => self::getField('otherSection', 'Other section->Shared group->Allow'),
            'legacyFirst' => self::getField('legacyFirst', 'Legacy section->First'),
            'legacySecond' => self::getField('legacySecond', 'Legacy section->Second'),
        );

        $html = $Form->renderFieldsLayout($fields, array())->getContent();

        ut::expectEqual(substr_count($html, "<div class='formMiddleCaption'>Shared group</div>"), 2);
        ut::expectEqual(substr_count($html, "<div class='formMiddleCaption'>Other group</div>"), 1);
        ut::expectEqual(substr_count($html, '<div class="formGroup" >Section'), 1);
        ut::expectEqual(substr_count($html, '<div class="formGroup" >Legacy section'), 1);

        $firstPos = strpos($html, 'filed-first');
        $secondPos = strpos($html, 'filed-second');
        $differentPos = strpos($html, 'filed-different');
        ut::expectEqual($firstPos !== false && $firstPos < $secondPos, true);
        ut::expectEqual($secondPos !== false && $secondPos < $differentPos, true);
    }


    /**
     * Негрупираните полета след именувана секция получават автоматичен кепшън
     */
    public static function test_DefaultCaptionForUngroupedFields(core_Form $Form)
    {
        $fields = array(
            'first' => self::getField('first', 'First section->First'),
            'otherFirst' => self::getField('otherFirst', 'Other first'),
            'otherSecond' => self::getField('otherSecond', 'Other second'),
            'last' => self::getField('last', 'Last section->Last'),
        );

        Mode::push('screenMode', 'wide');
        try {
            $wideHtml = $Form->renderFieldsLayout($fields, array())->getContent();
        } finally {
            Mode::pop('screenMode');
        }

        Mode::push('screenMode', 'narrow');
        try {
            $narrowHtml = $Form->renderFieldsLayout($fields, array())->getContent();
        } finally {
            Mode::pop('screenMode');
        }

        $otherCaption = "<div class='formGroup'>" . tr('Други') . '</div>';
        ut::expectEqual(substr_count($wideHtml, $otherCaption), 1);
        ut::expectEqual(substr_count($narrowHtml, $otherCaption), 1);
        ut::expectEqual(strpos($wideHtml, "<div class='formGroup'>&nbsp;</div>") === false, true);
        ut::expectEqual(strpos($narrowHtml, "<div class='formGroup'>&nbsp;</div>") === false, true);
    }


    /**
     * Проверява allowEmpty радиогрупа в стандартна add/edit форма
     */
    public static function test_AllowEmptyRadioInStandardForm(core_Form $Form)
    {
        $editForm = clone $Form;
        $editForm->FLD('choice', 'varchar(allowEmpty)', 'caption=Избор,placeholderType=all');
        $editForm->setOptions('choice', array('first' => 'Първа', 'second' => 'Втора'));

        $html = $editForm->renderHtml()->getContent();

        ut::expectEqual(substr_count($html, 'type="radio"'), 3);
        ut::expectEqual(strpos($html, '>Всички</label>') !== false, true);
        ut::expectEqual((bool) preg_match('/<input(?=[^>]*type="radio")(?=[^>]*value="")(?=[^>]*checked="checked")[^>]*>/', $html), true);
    }


    /**
     * Проверява стандартния select над лимита за радиогрупа в add/edit форма
     */
    public static function test_AllowEmptySelectInStandardForm(core_Form $Form)
    {
        $editForm = clone $Form;
        $editForm->FLD('choice', 'varchar(allowEmpty)', 'caption=Избор,placeholderType=all');
        $editForm->setOptions('choice', array(
            'first' => 'Първа',
            'second' => 'Втора',
            'third' => 'Трета',
            'fourth' => 'Четвърта',
        ));

        $html = $editForm->renderHtml()->getContent();

        ut::expectEqual((bool) preg_match('/<select(?=[^>]*name="choice")[^>]*>/', $html), true);
        ut::expectEqual(strpos($html, '>Всички</option>') !== false, true);
        ut::expectEqual((bool) preg_match('/<input(?=[^>]*type="radio")(?=[^>]*name="choice")[^>]*>/', $html), false);
    }


    /**
     * Проверява ориентиращия текст за общата празна стойност в хоризонтален GET филтър
     */
    public static function test_AllPlaceholderInHorizontalFilter(core_Form $Form)
    {
        $filterForm = clone $Form;
        $filterForm->method = 'GET';
        $filterForm->view = 'horizontal';
        $filterForm->FLD('choice', 'varchar(allowEmpty)', 'caption=Артикул,placeholderType=all');
        $filterForm->setOptions('choice', array('first' => 'Първа', 'second' => 'Втора'));

        $html = $filterForm->renderHtml()->getContent();

        ut::expectEqual(strpos($html, 'Артикул (всички)') !== false, true);
    }


    /**
     * Проверява, че конкретният placeholder има приоритет в хоризонтален GET филтър
     */
    public static function test_CustomPlaceholderInHorizontalFilter(core_Form $Form)
    {
        $filterForm = clone $Form;
        $filterForm->method = 'GET';
        $filterForm->view = 'horizontal';
        $filterForm->FLD('choice', 'varchar(allowEmpty)', 'caption=Група,placeholder=Всички групи');
        $filterForm->setOptions('choice', array('first' => 'Първа', 'second' => 'Втора'));

        $html = $filterForm->renderHtml()->getContent();

        ut::expectEqual(strpos($html, 'Всички групи') !== false, true);
        ut::expectEqual(strpos($html, 'Група (всички)') !== false, false);
    }


    /**
     * Служебната стойност запазва вече зададена mandatory парола
     */
    public static function test_MandatoryPasswordNoChangeOnEdit(core_Form $Form)
    {
        $editForm = clone $Form;
        $editForm->fields = array();
        $editForm->rec = (object) array('password' => 'old-secret');
        $editForm->FLD('password', 'password', 'caption=Парола,mandatory');
        $editForm->fields['password']->type->params['checkPassAfterLogin'] = false;

        $requestName = 'coreTestsFormPasswordNoChange';
        $hadRequestMethod = array_key_exists('REQUEST_METHOD', $_SERVER);
        $oldRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Request::push(array(
            'Cmd' => array('save' => 1),
            'password' => type_Password::EF_PASS_NO_CHANGE,
        ), $requestName);
        Mode::push('haveErrInAct', Mode::get('haveErrInAct'));

        try {
            $editForm->input();
        } finally {
            Mode::pop('haveErrInAct');
            Request::pop($requestName);
            if ($hadRequestMethod) {
                $_SERVER['REQUEST_METHOD'] = $oldRequestMethod;
            } else {
                unset($_SERVER['REQUEST_METHOD']);
            }
        }

        ut::expectEqual($editForm->gotErrors('password'), false);
        ut::expectEqual($editForm->rec->password, 'old-secret');
    }


    /**
     * Създава минимално поле за проверка на общия рендер на формите
     */
    private static function getField($name, $caption)
    {
        return (object) array(
            'name' => $name,
            'kind' => 'FNC',
            'caption' => $caption,
            'input' => 'input',
        );
    }
}
