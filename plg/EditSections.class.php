<?php


/**
 * Плъгин за редактиране само на полетата от избрана секция
 *
 * Секциите могат да се декларират по два начина:
 *
 * - с параметър editSection върху едно от полетата. В този случай се включват
 *   всички полета със същата първа част на caption-а;
 * - с масива $editSections на MVC класа. Този вариант е подходящ и за полета,
 *   добавени от други плъгини.
 *
 * В single шаблона се очаква placeholder [#editSection_{key}#].
 *
 * @category  ef
 * @package   plg
 *
 * @license   GPL 3
 */
class plg_EditSections extends core_Plugin
{
    /**
     * Име на екшъна за редактиране на секция
     */
    const ACTION = 'editsection';


    /**
     * Добавя бутоните за редактиране в single изгледа
     *
     * @param core_Master $mvc
     * @param mixed       $res
     * @param stdClass    $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        if (Mode::isReadOnly() || empty($data->rec->id) || empty($data->row)) {
            return;
        }

        if (!$mvc->haveRightFor('edit', $data->rec)) {
            return;
        }

        $sections = self::getSections($mvc);
        foreach ($sections as $key => $section) {
            if (!self::haveExistingFields($mvc, $section['fields'])) {
                continue;
            }

            $url = array($mvc, self::ACTION, $data->rec->id, 'section' => $key, 'ret_url' => true);
            $caption = tr($section['caption']);
            $attr = array(
                'ef_icon' => 'img/16/edit-icon.png',
                'title' => tr('Промяна на') . ': ' . $caption,
            );

            Request::setProtected('section');
            $data->row->{"editSection_{$key}"} = ht::createLink('', $url, false, $attr);
            Request::removeProtected('section');
        }
    }


    /**
     * Обработва общия екшън за редактиране на секция
     *
     * @param core_Manager $mvc
     * @param mixed        $res
     * @param string       $action
     *
     * @return false|void
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if (strtolower($action) != self::ACTION) {
            return;
        }

        expect($id = Request::get('id', 'int'), 'Липсва запис за редактиране');
        expect($sectionKey = Request::get('section', 'identifier'), 'Липсва секция за редактиране');
        expect($rec = $mvc->fetch($id), 'Несъществуващ запис');

        $mvc->requireRightFor('edit', $rec);

        $sections = self::getSections($mvc);
        expect(isset($sections[$sectionKey]), 'Недекларирана секция за редактиране', $sectionKey);
        $section = $sections[$sectionKey];

        $data = new stdClass();
        $data->action = 'manage';
        $data->editSection = $sectionKey;
        $mvc->prepareEditForm($data);
        $form = &$data->form;
        $form->editSection = $sectionKey;

        // При refresh формата може да съдържа само изпратените полета. Допълваме
        // останалите стойности, за да запазим очакванията на validation/save hooks.
        foreach ((array) $rec as $name => $value) {
            if (!property_exists($form->rec, $name)) {
                $form->rec->{$name} = $value;
            }
        }

        $editFields = self::getEditFields($mvc, $form, $sectionKey, $section);
        expect(countR($editFields), 'В секцията няма достъпни полета за редактиране', $sectionKey);

        $form->showFields = implode(',', array_keys($editFields));
        $form->input($form->showFields);

        $uniqueFields = null;
        if ($form->isSubmitted() && !$mvc->isUnique($form->rec, $uniqueFields)) {
            $form->setError($uniqueFields, 'Вече съществува запис със същите данни');
        }

        $mvc->invoke('AfterInputEditForm', array($form));
        $mvc->requireRightFor('edit', $form->rec);

        $data->cmd = 'Edit';

        if ($form->isSubmitted()) {
            $saveFields = self::getSaveFields($mvc, $form, $sectionKey, $section, $editFields);
            expect(countR($saveFields), 'В секцията няма полета за запис', $sectionKey);

            // Подаваме полетата като отделна променлива, за да могат BeforeSave
            // hooks да добавят свързани полета към списъка за запис.
            $saveFieldsStr = implode(',', array_keys($saveFields));
            expect($savedId = $mvc->save($form->rec, $saveFieldsStr), 'Грешка при запис на секцията');

            $mvc->logInAct('Промяна на "' . tr($section['caption']) . '"', $form->rec);
            $mvc->prepareRetUrl($data, $savedId);

            $res = new Redirect($data->retUrl);

            return false;
        }

        $mvc->prepareRetUrl($data);
        $mvc->prepareEditToolbar($data);
        $mvc->prepareEditTitle($data);
        $form->title = 'Промяна на|* <b>' . tr($section['caption']) . '</b> |на|* ' . $mvc->getFormTitleLink($id);

        $tpl = $form->renderHtml();
        $mvc->invoke('AfterRenderPrepareEditForm', array($tpl, $form));
        core_Form::preventDoubleSubmission($tpl, $form);
        $res = $mvc->renderWrapping($tpl, $data);

        return false;
    }


    /**
     * Използва правата за edit и за екшъна editSection
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (strtolower($action) == self::ACTION) {
            $requiredRoles = $mvc->getRequiredRoles('edit', $rec, $userId);
        }
    }


    /**
     * Връща декларациите на секциите
     *
     * @param core_Mvc $mvc
     *
     * @return array
     */
    protected static function getSections($mvc)
    {
        $sections = array();

        // Кратка декларация чрез параметър editSection на поле.
        foreach ((array) $mvc->fields as $name => $field) {
            if (empty($field->editSection)) {
                continue;
            }

            $key = $field->editSection;
            if ($key === true || $key == 'yes' || $key == 'editSection') {
                $key = $name;
            }

            self::validateSectionKey($key);
            $sections[$key] = array(
                'key' => $key,
                'caption' => self::getCaptionRoot($field->caption),
                'fields' => self::getFieldsByAnchor($mvc, $name),
            );
        }

        // Изрична декларация. Тя има приоритет над параметрите на полетата.
        $declaredSections = $mvc->editSections ?? array();
        if (!is_array($declaredSections)) {
            $declaredSections = arr::make($declaredSections, true);
        }

        foreach ($declaredSections as $key => $definition) {
            self::validateSectionKey($key);

            if (is_string($definition)) {
                $definition = array('fields' => $definition);
            } elseif (is_object($definition)) {
                $definition = (array) $definition;
            }

            expect(is_array($definition), 'Некоректна декларация на секция', $key, $definition);

            $anchor = $definition['anchor'] ?? null;
            if (empty($definition['fields']) && $anchor) {
                $definition['fields'] = self::getFieldsByAnchor($mvc, $anchor);
            }

            if (empty($definition['caption']) && $anchor && isset($mvc->fields[$anchor])) {
                $definition['caption'] = self::getCaptionRoot($mvc->fields[$anchor]->caption);
            }

            $definition['key'] = $key;
            $definition['caption'] = $definition['caption'] ?? $key;
            $definition['fields'] = arr::make($definition['fields'] ?? array(), true);
            $sections[$key] = array_merge($sections[$key] ?? array(), $definition);
        }

        return $sections;
    }


    /**
     * Връща полетата, които да се покажат във формата
     */
    protected static function getEditFields($mvc, $form, $sectionKey, $section)
    {
        $fields = arr::make($section['fields'], true);
        $mvc->invoke('AfterGetEditSectionFields', array(&$fields, $sectionKey, $form->rec, $form));
        $fields = arr::make($fields, true);

        foreach ($fields as $name => $dummy) {
            $field = $form->fields[$name] ?? null;
            if (!$field || $name == 'id') {
                unset($fields[$name]);
                continue;
            }

            $input = $field->input ?? 'input';
            if ($input == 'none' || $input == 'hidden') {
                unset($fields[$name]);
            }
        }

        return $fields;
    }


    /**
     * Връща постоянните полета, които да се запишат
     */
    protected static function getSaveFields($mvc, $form, $sectionKey, $section, $editFields)
    {
        $fields = isset($section['saveFields']) ? arr::make($section['saveFields'], true) : $editFields;
        $mvc->invoke('AfterGetEditSectionSaveFields', array(&$fields, $sectionKey, $form->rec, $form));
        $fields = arr::make($fields, true);

        foreach ($fields as $name => $dummy) {
            if ($name == 'id' || !isset($mvc->fields[$name]) || ($mvc->fields[$name]->kind ?? null) != 'FLD') {
                unset($fields[$name]);
            }
        }

        // При частичен save plg_Modified променя тези стойности, но не ги добавя
        // автоматично към подадения списък с полета.
        if ($mvc->hasPlugin('plg_Modified')) {
            foreach (array('modifiedOn', 'modifiedBy') as $name) {
                if (isset($mvc->fields[$name])) {
                    $fields[$name] = $name;
                }
            }
        }

        return $fields;
    }


    /**
     * Връща полетата от caption групата на посоченото поле
     */
    protected static function getFieldsByAnchor($mvc, $anchor)
    {
        if (!isset($mvc->fields[$anchor])) {
            return array();
        }

        $field = $mvc->fields[$anchor];
        $caption = $field->caption ?? '';
        if (strpos($caption, '->') === false) {
            return array($anchor => $anchor);
        }

        $root = self::getCaptionRoot($caption);
        $fields = array();
        foreach ((array) $mvc->fields as $name => $candidate) {
            $candidateCaption = $candidate->caption ?? '';
            if (strpos($candidateCaption, '->') !== false && self::getCaptionRoot($candidateCaption) == $root) {
                $fields[$name] = $name;
            }
        }

        return $fields;
    }


    /**
     * Връща първата част на caption-а
     */
    protected static function getCaptionRoot($caption)
    {
        $parts = explode('->', (string) $caption);

        return $parts[0];
    }


    /**
     * Има ли поне едно съществуващо поле в секцията
     */
    protected static function haveExistingFields($mvc, $fields)
    {
        foreach (arr::make($fields, true) as $name => $dummy) {
            if (isset($mvc->fields[$name])) {
                return true;
            }
        }

        return false;
    }


    /**
     * Проверява ключа, защото той участва в име на placeholder и URL
     */
    protected static function validateSectionKey($key)
    {
        expect(is_string($key) && preg_match('/^[a-z][a-zA-Z0-9_]*$/', $key), 'Некоректен ключ на секция', $key);
    }
}
