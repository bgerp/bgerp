<?php


/**
 * Общи помощни функции за JSON-LD доставчиците
 *
 * @category  bgerp
 * @package   jsonld
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jsonld_Helper
{
    /**
     * Преобразува публична скаларна стойност в чист текст
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public static function getCleanText($value)
    {
        return cms_Content::getCleanText($value);
    }


    /**
     * Връща абсолютния URL на публична снимка или null, ако файлът не е годен
     *
     * @param string|null $fh
     *
     * @return string|null
     */
    public static function getImageUrl($fh)
    {
        if (empty($fh) || !fileman_Files::isImage($fh)) {
            return null;
        }

        $path = fileman::fetchByFh($fh, 'path');

        if (!$path || !file_exists($path)) {
            return null;
        }

        $image = new thumb_Img(array(
            $fh,
            1600,
            1200,
            'fileman',
            'isAbsolute' => true,
            'mode' => 'small-no-change',
        ));

        return $image->getUrl('forced');
    }


    /**
     * Връща richtext като чист публичен текст, както се вижда на страницата
     *
     * Текстовият режим на richtext добавя маркировка (====, *, адреси на връзките),
     * затова се рендира HTML и се махат таговете.
     *
     * @param string|null $richtext
     *
     * @return string|null
     */
    public static function getPlainText($richtext)
    {
        return cms_Content::getPlainText($richtext);
    }


    /**
     * Връща дата и час във формат ISO 8601 с часовата зона
     *
     * @param string|null $datetime
     *
     * @return string|null
     */
    public static function getIsoDate($datetime)
    {
        if (empty($datetime)) {
            return null;
        }

        return date('c', dt::mysql2timestamp($datetime));
    }


    /**
     * Връща собствената фирма като Organization възел
     *
     * @return jsonld_Node|null
     */
    public static function getOrganization()
    {
        $company = crm_Companies::fetchOwnCompany();
        $name = self::getCleanText(tr($company->company ?? null));

        if ($name === null) {
            return null;
        }

        $properties = array('name' => $name);

        // Полето може да има няколко адреса - сайтът на фирмата е първият
        $websites = preg_split(type_Urls::$pattern, trim((string) ($company->website ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $website = $websites[0] ?? '';

        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL)) {
            $properties['url'] = $website;
        }

        $logoFh = !empty($company->companyId) ? crm_Companies::fetchField($company->companyId, 'logo') : null;
        $logo = self::getImageUrl($logoFh);

        if ($logo !== null) {
            $properties['logo'] = $logo;
        }

        $legalName = self::getCleanText($company->company ?? null);
        $emails = type_Emails::toArray($company->email ?? '');
        $tel = trim((string) ($company->tel ?? ''));
        $tel = ($tel !== '') ? (string) drdata_PhoneType::getNumberStr($tel, 0) : '';

        $properties += array_filter(array(
            'legalName' => $legalName,
            'email' => reset($emails) ?: null,
            'telephone' => (strpos($tel, '+') === 0) ? $tel : null,
            'vatID' => self::getCleanText($company->vatNo ?? null),
            'taxID' => self::getCleanText($company->uicId ?? null),
        ));

        $address = self::getPostalAddress($company);

        if (isset($address)) {
            $properties['address'] = $address;
        }

        // Еднаквият @id на всички домейни казва, че е една и съща фирма - затова е по сайта ѝ, а не по домейна
        $base = $properties['url'] ?? getBoot(true, false, true);
        $id = rtrim($base, '/') . '/#organization';

        return new jsonld_Node('Organization', $properties, $id, 'organization');
    }


    /**
     * Връща адреса на фирмата като PostalAddress възел
     *
     * @param stdClass $company данните от crm_Companies::fetchOwnCompany()
     *
     * @return jsonld_Node|null
     */
    protected static function getPostalAddress($company)
    {
        $countryCode = !empty($company->countryId) ? drdata_Countries::fetchField($company->countryId, 'letterCode2') : null;

        $address = array_filter(array(
            'streetAddress' => self::getCleanText($company->address ?? null),
            'addressLocality' => self::getCleanText($company->place ?? null),
            'postalCode' => self::getCleanText($company->pCode ?? null),
            'addressCountry' => self::getCleanText($countryCode),
        ));

        // Само държавата не е адрес
        if (empty($address['streetAddress']) && empty($address['addressLocality'])) {
            return null;
        }

        return new jsonld_Node('PostalAddress', $address);
    }


    /**
     * Връща препратка към собствената фирма - самият възел се добавя отделно на всяка страница
     *
     * @return array|null
     */
    public static function getOrganizationRef()
    {
        $organization = self::getOrganization();

        return isset($organization) ? array('@id' => $organization->getId()) : null;
    }


    /**
     * Връща текущия домейн като WebSite възел с адреса на търсенето в него
     *
     * Еднаквият @id на всички страници на домейна казва, че са един сайт
     *
     * @return jsonld_Node|null
     */
    public static function getWebSite()
    {
        $domainRec = cms_Domains::getPublicDomain();

        if (!is_object($domainRec) || empty($domainRec->id)) {
            return null;
        }

        $url = rtrim(getBoot(true, false, true), '/') . '/';
        $organization = self::getOrganization();
        $name = self::getCleanText($domainRec->seoTitle ?? null);

        if ($name === null && isset($organization)) {
            $name = $organization->toArray()['name'] ?? null;
        }

        $properties = array('url' => $url);

        if ($name !== null) {
            $properties['name'] = $name;
        }

        // Домейнът е другото име, с което сайтът е познат
        $host = preg_replace('/^www\./i', '', (string) parse_url($url, PHP_URL_HOST));

        $isRealDomain = (strpos($host, '.') !== false) && !filter_var($host, FILTER_VALIDATE_IP);

        if ($isRealDomain && mb_strtolower($host) !== mb_strtolower((string) $name)) {
            $properties['alternateName'] = $host;
        }

        $language = self::getCleanText($domainRec->lang ?? null);

        if ($language !== null) {
            $properties['inLanguage'] = $language;
        }

        if (isset($organization)) {
            $properties['publisher'] = array('@id' => $organization->getId());
        }

        $searchUrl = cms_Content::getSearchUrlTemplate($domainRec->id, '{search_term_string}');

        if (isset($searchUrl)) {
            $properties['potentialAction'] = new jsonld_Node('SearchAction', array(
                'target' => $searchUrl,
                'query-input' => 'required name=search_term_string',
            ));
        }

        return new jsonld_Node('WebSite', $properties, $url . '#website', 'website-' . $domainRec->id);
    }



    /**
     * Връща общите свойства на публична статия - CMS или блог
     *
     * Записът трябва да има title, body и по желание SEO полетата и modifiedOn.
     *
     * @param stdClass    $rec
     * @param string|null $url      каноничен абсолютен адрес
     * @param string|null $language езикът на домейна
     *
     * @return array|null null, ако няма заглавие, текст или адрес
     */
    public static function getArticleProperties($rec, $url, $language)
    {
        $headline = self::getCleanText($rec->title ?? null);
        $articleBody = self::getPlainText($rec->body ?? null);

        if ($headline === null || $articleBody === null || empty($url)) {
            return null;
        }

        $properties = array(
            'headline' => $headline,
            'articleBody' => $articleBody,
            'url' => $url,
            'mainEntityOfPage' => $url,
        );

        $description = self::getCleanText($rec->seoDescription ?? null);

        if ($description === null) {
            $description = self::getCleanText(cms_Content::getSeoDescription($rec->body));
        }

        if ($description !== null) {
            $properties['description'] = $description;
        }

        $fh = !empty($rec->seoThumb) ? $rec->seoThumb : cms_Content::getSeoThumb($rec->body);
        $imageUrl = self::getImageUrl($fh);

        if ($imageUrl !== null) {
            $properties['image'] = $imageUrl;
        }

        $language = self::getCleanText($language);

        if ($language !== null) {
            $properties['inLanguage'] = $language;
        }

        $keywords = self::getCleanText($rec->seoKeywords ?? null);

        if ($keywords !== null) {
            $properties['keywords'] = $keywords;
        }

        $dateModified = self::getIsoDate($rec->modifiedOn ?? null);

        if ($dateModified !== null) {
            $properties['dateModified'] = $dateModified;
        }

        $organization = self::getOrganizationRef();

        if (isset($organization)) {
            $properties['publisher'] = $organization;
        }

        return $properties;
    }


    /**
     * Връща BreadcrumbList възел от видимата пътечка на страницата
     *
     * @param array  $items масив от array('name' => ..., 'url' => ... или bgERP URL масив)
     * @param string $key   вътрешен ключ за дедупликация
     *
     * @return jsonld_Node|null
     */
    public static function getBreadcrumbList($items, $key)
    {
        $elements = array();

        foreach ($items as $item) {
            $name = self::getCleanText($item['name'] ?? null);

            if ($name === null) {
                continue;
            }

            $properties = array('position' => countR($elements) + 1, 'name' => $name);
            $url = $item['url'] ?? null;

            if (is_array($url)) {
                $url = toUrl($url, 'absolute');
            }

            if (is_string($url) && preg_match('#^https?://#i', $url)) {
                $properties['item'] = $url;
            }

            $elements[] = new jsonld_Node('ListItem', $properties);
        }

        if (countR($elements) < 2) {
            return null;
        }

        return new jsonld_Node('BreadcrumbList', array('itemListElement' => $elements), null, $key);
    }
}
