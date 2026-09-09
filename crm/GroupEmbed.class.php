<?php


/**
 * Клас 'crm_GroupEmbed'
 *
 * Вграждане на група със визитки
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 */
class crm_GroupEmbed extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_LibraryIntf';
    
    
    /**
     * Заглавие на класа
     */
    public $title = 'Група с визитки';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public static function addFields(&$form)
    {
        $form->FLD('crmGroup', 'key(mvc=crm_Groups,select=name)', 'caption=Група контрагенти');
        $form->FLD('layout', 'enum(standard=Стандартен,short=Кратък,cards=Карти,list=Списък)', 'caption=Изглед,silent,refreshForm,hint=Карти и Списък имат собствени настройки и работят без допълнителна тема');
        $form->FLD('displayFields', 'set(logo=Лого,country=Държава,pCode=Пощенски код,place=Населено място,address=Адрес,tel=Телефони,email=Имейли,website=Уебсайтове,info=Описание от бележките)', 'caption=Съдържание->Полета,columns=2,hint=Името се показва винаги. Описанието публикува цялото поле Бележки от визитката');
        $form->FLD('columns', 'enum(auto=Автоматично,1=Една,2=Две,3=Три)', 'caption=Оформление->Колони,hint=На тесен екран броят колони се намалява');
        $form->FLD('logoSize', 'enum(small=Малко,medium=Средно,large=Голямо)', 'caption=Оформление->Лого');
        $form->FLD('orderBy', 'enum(name=По име,created=По добавяне)', 'caption=Оформление->Подредба');
        $form->FLD('textMode', 'enum(original=Както са въведени,transliterate=Транслитерация на латиница)', 'caption=Оформление->Имена и адреси');
        $form->FLD('backgroundType', 'enum(default=По подразбиране,solid=Едноцветен,linear=Линейна преливка,radial=Радиална преливка,transparent=Прозрачен)', 'caption=Фон на визитките->Вид,silent,refreshForm');
        $form->FLD('backgroundColor', 'color_Type(allowEmpty)', 'caption=Фон на визитките->Основен цвят');
        $form->FLD('backgroundColor2', 'color_Type(allowEmpty)', 'caption=Фон на визитките->Втори цвят');
        $form->FLD('backgroundDirection', 'enum(180=Отгоре надолу,90=Отляво надясно,135=Диагонално надолу,45=Диагонално нагоре)', 'caption=Фон на визитките->Посока');
        $form->FLD('borderStyle', 'enum(default=По подразбиране,none=Без рамка,solid=Плътна,dashed=Прекъсната,dotted=Точкова,gradient=Преливка)', 'caption=Рамка на визитките->Вид,silent,refreshForm');
        $form->FLD('borderWidth', 'int(min=0,max=12)', 'caption=Рамка на визитките->Дебелина,unit=px');
        $form->FLD('borderColor', 'color_Type(allowEmpty)', 'caption=Рамка на визитките->Основен цвят');
        $form->FLD('borderColor2', 'color_Type(allowEmpty)', 'caption=Рамка на визитките->Втори цвят');
        $form->FLD('borderDirection', 'enum(180=Отгоре надолу,90=Отляво надясно,135=Диагонално надолу,45=Диагонално нагоре)', 'caption=Рамка на визитките->Посока');
        $form->FLD('borderRadius', 'int(min=0,max=60)', 'caption=Рамка на визитките->Заобляне,unit=px,hint=Празно запазва текущото заобляне. Нула задава прави ъгли');
        $form->FLD('shadow', 'enum(default=По подразбиране,none=Без сянка,soft=Лека,medium=Средна,strong=Силна,inset=Вътрешна)', 'caption=Сянка на визитките->Вид,silent,refreshForm');
        $form->FLD('shadowColor', 'color_Type(allowEmpty)', 'caption=Сянка на визитките->Цвят');
        $form->FLD('shadowOpacity', 'int(min=0,max=100)', 'caption=Сянка на визитките->Плътност,unit=%');
        $form->FLD('textColor', 'color_Type(allowEmpty)', 'caption=Допълнителни цветове->Текст,hint=Празно запазва текущите цветове на текста и заглавията');
        $form->FLD('linkColor', 'color_Type(allowEmpty)', 'caption=Допълнителни цветове->Връзки,hint=При тъмен фон може да зададете светъл цвят');
        $form->FLD('logoBackground', 'enum(default=По подразбиране,transparent=Прозрачен)', 'caption=Допълнителни цветове->Фон зад логото,hint=Не премахва фона в самото изображение');
    }


    /**
     * Show the independent layout options only where they apply.
     */
    public static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $form = $data->form;
        $layout = $form->rec->layout ?? 'standard';
        $standalone = in_array($layout, array('cards', 'list'));
        if ($standalone) {
            $form->setField('crmGroup', 'mandatory');
        }
        $appearanceFields = 'backgroundType,backgroundColor,backgroundColor2,backgroundDirection,borderStyle,borderWidth,borderColor,borderColor2,borderDirection,borderRadius,shadow,shadowColor,shadowOpacity,textColor,linkColor,logoBackground';
        foreach (arr::make('displayFields,columns,logoSize,orderBy,textMode,' . $appearanceFields) as $field) {
            if (!$standalone || ($field == 'columns' && $layout == 'list')) {
                $form->setField($field, 'input=none');
            }
        }

        if (!property_exists($form->rec, 'displayFields')) {
            $form->setDefault('displayFields', 'logo,place,address,website');
        }
        $form->setDefault('columns', 'auto');
        $form->setDefault('logoSize', 'medium');
        $form->setDefault('orderBy', 'name');
        $form->setDefault('textMode', 'original');
        $form->setDefaults(array(
            'backgroundType' => 'default', 'backgroundColor' => '#ffffff', 'backgroundColor2' => '#eef4fc', 'backgroundDirection' => '135',
            'borderStyle' => 'default', 'borderWidth' => 1, 'borderColor' => '#dce3e9', 'borderColor2' => '#7c3aed', 'borderDirection' => '135',
            'shadow' => 'default', 'shadowColor' => '#1e3246', 'shadowOpacity' => 20, 'logoBackground' => 'default',
        ));

        if ($standalone) {
            $background = $form->rec->backgroundType;
            $border = $form->rec->borderStyle;
            $visibility = array(
                'backgroundColor' => in_array($background, array('solid', 'linear', 'radial')),
                'backgroundColor2' => in_array($background, array('linear', 'radial')),
                'backgroundDirection' => $background == 'linear',
                'borderWidth' => in_array($border, array('solid', 'dashed', 'dotted', 'gradient')),
                'borderColor' => in_array($border, array('solid', 'dashed', 'dotted', 'gradient')),
                'borderColor2' => $border == 'gradient',
                'borderDirection' => $border == 'gradient',
                'shadowColor' => in_array($form->rec->shadow, array('soft', 'medium', 'strong', 'inset')),
                'shadowOpacity' => in_array($form->rec->shadow, array('soft', 'medium', 'strong', 'inset')),
            );
            foreach ($visibility as $field => $visible) {
                if (!$visible) {
                    $form->setField($field, 'input=none');
                }
            }
        }
    }
    
    
    /**
     * Връща HTML представянето на обекта
     *
     * @param stdClass $rec Записа за елемента от модела-библиотека
     * @param $maxWidth int Максимална широчина на елемента
     * @param $isAbsolute bool Дали URL-тата да са абсолютни
     *
     * @return core_ET|string Представяне на обекта в HTML шабло
     */
    public static function render($rec, $maxwidth = 1200, $absolute = false)
    {
        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) {
            
            return '';
        }

        if (in_array($rec->layout ?? null, array('cards', 'list'))) {
            return self::renderCards($rec, $maxwidth, $absolute);
        }

        $tpl =  ($rec->layout === 'standard') ?  new ET(getFileContent('crm/tpl/ContragetExternalList.shtml')) : new ET(getFileContent('crm/tpl/ContragetExternalShort.shtml'));

        $contragents = array();

        // Извличане на визитките
        $cQuery = crm_Companies::getQuery();
        plg_ExpandInput::applyExtendedInputSearch('crm_Companies', $cQuery, $rec->crmGroup);
        while($cRec = $cQuery->fetch()) {
            $contragents[$cRec->id] = crm_Companies::recToVerbal($cRec);
            $contragents[$cRec->id]->name = crm_Companies::getVerbal($cRec, 'name');
            $contragents[$cRec->id]->link = $cRec->website;
            if ($cRec->logo) {
                $thumb = new thumb_Img(array($cRec->logo, 250, 250, 'fileman', $contragents[$cRec->id]->name));
                $contragents[$cRec->id]->logo = $thumb->createImg();
            } else {
                $contragents[$cRec->id]->logo = ht::createImg(array('class' => 'logoImg', 'alt' => $contragents[$cRec->id]->name, 'src' => sbf("img/noimage120.gif", '')));
            }
        }

        // Рендиране
        foreach ($contragents as $row) {
            $row->name = transliterate(tr($row->name));
            $row->country = transliterate(tr($row->country));
            $row->place = transliterate(tr($row->place));
            $row->address = transliterate(tr($row->address));
            $rowTpl = clone $tpl->getBlock('CONTRAGENT');
            $rowTpl->placeObject($row);
            $rowTpl->removeBlocksAndPlaces();
            $rowTpl->append2master();
        }


        $tpl->push("crm/css/groupList.css", 'CSS');
        $tpl->push("crm/js/groupList.js", 'JS');

        return $tpl;
    }


    /**
     * Render public company cards without depending on a CMS theme.
     */
    protected static function renderCards($rec, $maxWidth, $absolute)
    {
        // An unset group must never turn into an export of the whole directory.
        if (empty($rec->crmGroup)) {
            return '';
        }

        $fields = arr::make(property_exists($rec, 'displayFields') ? $rec->displayFields : 'logo,place,address,website', true);
        $columns = in_array($rec->columns ?? null, array('1', '2', '3')) ? $rec->columns : 'auto';
        $logoSize = in_array($rec->logoSize ?? null, array('small', 'medium', 'large')) ? $rec->logoSize : 'medium';
        $tpl = getTplFromFile('crm/tpl/CompanyCards.shtml');
        $tpl->replace($rec->layout, 'LAYOUT');
        $tpl->replace($columns, 'COLUMNS');
        $tpl->replace($logoSize, 'LOGO_SIZE');
        $tpl->replace(max(1, (int) $maxWidth), 'MAX_WIDTH');
        $tpl->replace(ht::escapeAttr(self::getAppearanceStyle($rec)), 'APPEARANCE');
        $appearanceClasses = array();
        foreach (array('textColor' => 'text', 'linkColor' => 'links') as $field => $suffix) {
            if (self::getAppearanceColor($rec->{$field} ?? null) !== null) {
                $appearanceClasses[] = 'crm-group-embed--custom-' . $suffix;
            }
        }
        $tpl->replace(implode(' ', $appearanceClasses), 'APPEARANCE_CLASSES');

        $query = crm_Companies::getQuery();
        plg_ExpandInput::applyExtendedInputSearch('crm_Companies', $query, $rec->crmGroup);
        $query->where("#state != 'rejected'");
        $query->orderBy(($rec->orderBy ?? 'name') == 'created' ? 'id' : 'name', 'ASC');
        $query->orderBy('id', 'ASC');

        $cardTpl = $tpl->getBlock('COMPANY');
        while ($company = $query->fetch()) {
            $row = self::prepareCard($company, $fields, $rec->textMode ?? 'original', $logoSize, $absolute);
            $rowTpl = clone $cardTpl;
            $rowTpl->placeObject($row);
            $rowTpl->removeBlocksAndPlaces();
            $rowTpl->append2master();
        }

        $tpl->removeBlocksAndPlaces();
        $tpl->push('crm/css/companyCards.css', 'CSS');

        return $tpl;
    }


    /**
     * Build scoped CSS variables from typed options; never accept arbitrary CSS.
     * Missing options retain the layout's existing defaults.
     */
    protected static function getAppearanceStyle($rec)
    {
        $style = array();
        $background = $rec->backgroundType ?? 'default';
        if ($background == 'transparent') {
            $style['background'] = 'transparent';
        } elseif (in_array($background, array('solid', 'linear', 'radial'))) {
            $first = self::getAppearanceColor($rec->backgroundColor ?? null, '#ffffff');
            $second = self::getAppearanceColor($rec->backgroundColor2 ?? null, '#eef4fc');
            $style['background'] = $first;
            if ($background == 'linear') {
                $angle = self::getGradientDirection($rec->backgroundDirection ?? null);
                $style['background'] = "linear-gradient({$angle}deg, {$first}, {$second})";
            } elseif ($background == 'radial') {
                $style['background'] = "radial-gradient(ellipse at center, {$first}, {$second})";
            }
        }

        $border = $rec->borderStyle ?? 'default';
        if ($border == 'none') {
            $style['border-style'] = 'none';
        } elseif (in_array($border, array('solid', 'dashed', 'dotted', 'gradient'))) {
            $style['border-width'] = self::getAppearanceNumber($rec->borderWidth ?? null, 1, 12) . 'px';
            $style['border-style'] = $border == 'gradient' ? 'solid' : $border;
            $first = self::getAppearanceColor($rec->borderColor ?? null, '#dce3e9');
            $style['border-color'] = $first;
            if ($border == 'gradient') {
                $second = self::getAppearanceColor($rec->borderColor2 ?? null, '#7c3aed');
                $angle = self::getGradientDirection($rec->borderDirection ?? null);
                $style['border-color'] = 'transparent';
                $style['border-gradient'] = "linear-gradient({$angle}deg, {$first}, {$second})";
                $style['border-overlay'] = 'block';
            }
        }
        if (isset($rec->borderRadius) && $rec->borderRadius !== '') {
            $style['radius'] = self::getAppearanceNumber($rec->borderRadius, ($rec->layout ?? null) == 'list' ? 8 : 12, 60) . 'px';
        }

        $shadow = $rec->shadow ?? 'default';
        $shadows = array('soft' => '0 3px 12px', 'medium' => '0 8px 24px', 'strong' => '0 14px 36px', 'inset' => 'inset 0 2px 10px');
        if ($shadow == 'none') {
            $style['shadow'] = 'none';
        } elseif (isset($shadows[$shadow])) {
            $color = new color_Object(self::getAppearanceColor($rec->shadowColor ?? null, '#1e3246'));
            $opacity = self::getAppearanceNumber($rec->shadowOpacity ?? null, 20, 100) / 100;
            $alpha = number_format($opacity, 2, '.', '');
            $style['shadow'] = "{$shadows[$shadow]} rgba({$color->r}, {$color->g}, {$color->b}, {$alpha})";
        }

        foreach (array('textColor' => 'text-color', 'linkColor' => 'link-color') as $field => $variable) {
            $color = self::getAppearanceColor($rec->{$field} ?? null);
            if ($color !== null) {
                $style[$variable] = $color;
            }
        }
        if (($rec->logoBackground ?? null) == 'transparent') {
            $style['logo-background'] = 'transparent';
        }

        $declarations = array();
        foreach ($style as $name => $value) {
            $declarations[] = "--crm-group-{$name}: {$value};";
        }

        return implode(' ', $declarations);
    }


    /**
     * Normalize colors through the existing color type to hexadecimal CSS.
     */
    protected static function getAppearanceColor($value, $fallback = null)
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return $fallback;
        }
        $color = new color_Object($value);

        return $color->error ? $fallback : $color->getHex();
    }


    protected static function getAppearanceNumber($value, $fallback, $max)
    {
        return is_numeric($value) ? max(0, min($max, (int) $value)) : $fallback;
    }


    protected static function getGradientDirection($value)
    {
        return in_array((string) $value, array('180', '90', '135', '45'), true) ? (int) $value : 135;
    }


    /**
     * Verbalize only the explicitly selected public fields.
     */
    protected static function prepareCard($company, $fields, $textMode, $logoSize, $absolute)
    {
        $company = clone $company;
        if ($textMode == 'transliterate') {
            foreach (array('name', 'place', 'address') as $field) {
                $company->{$field} = transliterate(tr($company->{$field} ?? ''));
            }
        }

        $row = new stdClass();
        $row->name = crm_Companies::getVerbal($company, 'name');
        foreach (array('country', 'pCode', 'place', 'address', 'tel', 'info') as $field) {
            if (isset($fields[$field]) && isset($company->{$field}) && $company->{$field} !== '') {
                $row->{$field} = crm_Companies::getVerbal($company, $field);
            }
        }
        if ($textMode == 'transliterate' && isset($row->country)) {
            $row->country = transliterate(tr($row->country));
        }
        $row->locality = trim(($row->pCode ?? '') . ' ' . ($row->place ?? ''));
        if (isset($fields['email']) && !empty($company->email)) {
            $row->email = cls::get('type_Emails', array('params' => array('showOriginal' => true)))->toVerbal($company->email);
        }

        if (isset($fields['website']) && !empty($company->website)) {
            $links = array();
            foreach (type_Urls::toArray($company->website, type_Urls::ALL) as $url) {
                if (strpos($url, '://') === false) {
                    $url = 'https://' . $url;
                }
                if (!preg_match('~^https?://~i', $url) || !core_Url::isValidUrl($url)) {
                    continue;
                }
                $attr = array('href' => $url, 'target' => '_blank', 'rel' => 'noopener noreferrer');
                if (!count($links)) {
                    $row->name = ht::createElement('a', $attr, $row->name, true);
                }
                $label = type_Varchar::escape(core_Url::decodeUrl(preg_replace('~^https?://~i', '', rtrim($url, '/'))));
                $links[] = ht::createElement('a', $attr, $label, true)->getContent();
            }
            $row->website = implode('<br>', $links);
        }

        if (isset($fields['logo']) && !empty($company->logo)) {
            $sizes = array('small' => 144, 'medium' => 224, 'large' => 320);
            $size = $sizes[$logoSize];
            $thumb = new thumb_Img(array($company->logo, $size, $size, 'fileman', 'isAbsolute' => $absolute, 'mode' => 'small-no-change'));
            $row->logo = $thumb->createImg(array('alt' => $company->name, 'loading' => 'lazy', 'decoding' => 'async'));
        }

        return $row;
    }
}
