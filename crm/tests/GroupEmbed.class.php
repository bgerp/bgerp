<?php


/**
 * Public field selection and markup regressions for the library element.
 */
class crm_tests_GroupEmbed extends unit_Class
{
    public static function test_HiddenFields()
    {
        $company = self::company();
        $row = self::prepare($company, array());
        ut::expectEqual($row->name, 'Тест &amp; партньори');
        foreach (array('logo', 'email', 'tel', 'info', 'website', 'country', 'pCode', 'place', 'address') as $field) {
            ut::expectEqual(isset($row->{$field}), false);
        }
        ut::expectEqual(strpos(self::markup($row), '<a '), false);
    }


    public static function test_EmptyBlocks()
    {
        $html = self::markup(self::prepare(self::company(), array()));
        foreach (array('__logo', '__description', '__locality', '__contact', 'ET_BEGIN', '[#') as $marker) {
            ut::expectEqual(strpos($html, $marker), false);
        }
    }


    public static function test_AddressSelection()
    {
        $company = self::company();
        $row = self::prepare($company, array('place' => 'place'));
        ut::expectEqual($row->locality, 'Велико Търново');
        $row = self::prepare($company, array('pCode' => 'pCode'));
        ut::expectEqual($row->locality, '5000');
        $row = self::prepare($company, array('pCode' => 'pCode', 'place' => 'place', 'address' => 'address'));
        ut::expectEqual($row->locality, '5000 Велико Търново');
        ut::expectEqual(strpos($row->address, '<script>'), false);
        ut::expectEqual(strpos($row->address, '&lt;script>') !== false, true);
    }


    public static function test_Websites()
    {
        $company = self::company();
        $company->website = 'https://example.com/a?q=one&b=two, http://example.org, javascript:alert(1), ftp://example.net';
        $row = self::prepare($company, array('website' => 'website'));
        $html = self::markup($row);
        ut::expectEqual(substr_count($html, '<a '), 3);
        ut::expectEqual(substr_count($html, 'noopener noreferrer'), 3);
        ut::expectEqual(strpos($html, 'javascript:'), false);
        ut::expectEqual(strpos($html, 'ftp://'), false);
        ut::expectEqual(strpos($html, 'q=one&amp;b=two') !== false, true);

        $company->website = 'example.com';
        $html = self::markup(self::prepare($company, array('website' => 'website')));
        ut::expectEqual(strpos($html, 'href="https://example.com"') !== false, true);
    }


    public static function test_OriginalAndTransliteratedText()
    {
        $company = self::company();
        $original = clone $company;
        $fields = array('place' => 'place', 'address' => 'address');
        $expected = array(
            'bg' => array('name' => 'Тест &amp; партньори', 'place' => 'Велико Търново', 'address' => 'ул. &lt;script> 1'),
            'en' => array('name' => 'Test &amp; partnyori', 'place' => 'Veliko Tarnovo', 'address' => 'ul. &lt;script> 1'),
        );
        foreach ($expected as $lg => $values) {
            core_Lg::push($lg);
            try {
                $row = self::prepare($company, $fields);
                ut::expectEqual($row->name ?? null, 'Тест &amp; партньори');
                ut::expectEqual($row->place ?? null, 'Велико Търново');

                $row = self::prepare($company, $fields, 'transliterate');
                foreach ($values as $field => $value) {
                    ut::expectEqual($row->{$field} ?? null, $value);
                }
                ut::expectEqual($company == $original, true);
            } finally {
                core_Lg::pop();
            }
        }
    }


    public static function test_FormCompatibility()
    {
        foreach (array('standard', 'short', 'cards', 'list') as $layout) {
            $form = cls::get('core_Form');
            $form->rec = (object) array('layout' => $layout, 'displayFields' => null);
            crm_GroupEmbed::addFields($form);
            $data = (object) array('form' => $form);
            crm_GroupEmbed::on_AfterPrepareEditForm(null, null, $data);
            ut::expectEqual($form->rec->layout, $layout);
            ut::expectEqual($form->rec->displayFields, null);
            ut::expectEqual(($form->getField('displayFields')->input ?? null) == 'none', in_array($layout, array('standard', 'short')));
            ut::expectEqual(($form->getField('columns')->input ?? null) == 'none', $layout != 'cards');
        }
    }


    public static function test_MissingGroupAndPlainText()
    {
        ut::expectEqual(crm_GroupEmbed::render((object) array('layout' => 'cards')), '');
        Mode::push('text', 'plain');
        try {
            ut::expectEqual(crm_GroupEmbed::render((object) array('layout' => 'cards', 'crmGroup' => 1)), '');
        } finally {
            Mode::pop('text');
        }
    }


    public static function test_AppearanceDefaultsAndReset()
    {
        ut::expectEqual(self::appearance((object) array('layout' => 'cards')), '');
        ut::expectEqual(self::appearance((object) array('layout' => 'list')), '');
        $rec = (object) array(
            'backgroundType' => 'default', 'backgroundColor' => '#000000', 'backgroundColor2' => '#ff0000',
            'borderStyle' => 'default', 'borderWidth' => 12, 'borderColor' => '#ff0000',
            'shadow' => 'default', 'shadowColor' => '#ff0000', 'shadowOpacity' => 100,
        );
        ut::expectEqual(self::appearance($rec), '');

        $rec->backgroundType = 'transparent';
        $rec->borderStyle = 'gradient';
        $rec->borderWidth = 0;
        $rec->borderRadius = 0;
        $rec->shadow = 'soft';
        $rec->shadowOpacity = 0;
        $css = self::appearance($rec);
        foreach (array('background: transparent;', 'border-width: 0px;', 'radius: 0px;', ', 0.00)') as $marker) {
            ut::expectEqual(strpos($css, $marker) !== false, true);
        }
    }


    public static function test_AppearanceRejectsCssInjection()
    {
        $payload = '\");background:url(https://example.com);/*';
        $rec = (object) array(
            'backgroundType' => 'linear', 'backgroundColor' => $payload, 'backgroundColor2' => '#def', 'backgroundDirection' => $payload,
            'borderStyle' => 'gradient', 'borderColor' => $payload, 'borderColor2' => 'blue', 'borderWidth' => $payload, 'borderDirection' => $payload,
            'borderRadius' => 9999, 'shadow' => 'medium', 'shadowColor' => $payload, 'shadowOpacity' => 1000,
            'textColor' => $payload, 'linkColor' => $payload,
        );
        $css = self::appearance($rec);
        foreach (array('url(', 'https:', '/*', '"', 'text-color:', 'link-color:') as $marker) {
            ut::expectEqual(strpos($css, $marker), false);
        }
        ut::expectEqual(strpos($css, '#ddeeff') !== false, true);
        ut::expectEqual(strpos($css, '#0000ff') !== false, true);
        ut::expectEqual(strpos($css, 'radius: 60px;') !== false, true);
        ut::expectEqual(strpos($css, ', 1.00)') !== false, true);
    }


    public static function test_AppearanceFormVisibility()
    {
        foreach (array('standard', 'short', 'cards', 'list') as $layout) {
            $form = cls::get('core_Form');
            $form->rec = (object) array('layout' => $layout, 'backgroundType' => 'radial', 'borderStyle' => 'gradient', 'shadow' => 'none');
            crm_GroupEmbed::addFields($form);
            $data = (object) array('form' => $form);
            crm_GroupEmbed::on_AfterPrepareEditForm(null, null, $data);
            $legacy = in_array($layout, array('standard', 'short'));
            foreach (array('backgroundType', 'backgroundColor2', 'borderColor2', 'borderRadius', 'textColor') as $field) {
                ut::expectEqual(($form->getField($field)->input ?? null) == 'none', $legacy);
            }
            ut::expectEqual(($form->getField('backgroundDirection')->input ?? null), 'none');
            ut::expectEqual(($form->getField('shadowOpacity')->input ?? null), 'none');
        }
    }


    private static function appearance($rec)
    {
        $method = new ReflectionMethod('crm_GroupEmbed', 'getAppearanceStyle');
        $method->setAccessible(true);

        return $method->invoke(null, $rec);
    }


    private static function company()
    {
        return (object) array(
            'name' => 'Тест & партньори',
            'place' => 'Велико Търново',
            'pCode' => '5000',
            'address' => 'ул. <script> 1',
            'email' => 'private@example.com',
            'tel' => '0888 123456',
            'info' => 'Internal notes must stay hidden unless selected.',
            'website' => 'https://example.com',
            'logo' => 'unused-logo',
        );
    }


    private static function prepare($company, $fields, $textMode = 'original')
    {
        $method = new ReflectionMethod('crm_GroupEmbed', 'prepareCard');
        $method->setAccessible(true);

        return $method->invoke(null, $company, $fields, $textMode, 'medium', false);
    }


    private static function markup($row)
    {
        $tpl = getTplFromFile('crm/tpl/CompanyCards.shtml');
        $card = $tpl->getBlock('COMPANY');
        $card->placeObject($row);

        return $card->getContent();
    }
}
