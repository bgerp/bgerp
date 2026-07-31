<?php


/**
 * Преобразува публична CMS статия в Article JSON-LD възел
 */
class jsonld_adapters_CmsArticle
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'jsonld_ProviderIntf';


    /**
     * Връща JSON-LD възела за подготвена публична CMS статия
     *
     * @param stdClass $data
     *
     * @return jsonld_Node[]
     */
    public function getJsonLdNodes($data)
    {
        if (empty($data->rec)
            || !$data->rec instanceof stdClass
            || $data->rec->state != 'active') {
            return array();
        }

        $rec = $data->rec;
        $headline = $this->getCleanText($rec->title);
        $articleBody = $this->getArticleBody($rec);
        $url = $this->getCanonicalUrl($rec);

        if ($headline === null || $articleBody === null || $url === null) {
            return array();
        }

        $properties = array(
            'headline' => $headline,
            'articleBody' => $articleBody,
            'url' => $url,
            'mainEntityOfPage' => $url,
        );
        $description = $this->getDescription($rec);

        if ($description !== null) {
            $properties['description'] = $description;
        }

        $imageUrl = $this->getImageUrl($rec);

        if ($imageUrl !== null) {
            $properties['image'] = $imageUrl;
        }

        $language = $this->getLanguage($rec);

        if ($language !== null) {
            $properties['inLanguage'] = $language;
        }

        $keywords = $this->getCleanText($rec->seoKeywords ?? null);

        if ($keywords !== null) {
            $properties['keywords'] = $keywords;
        }

        return array(
            new jsonld_Node(
                'Article',
                $properties,
                null,
                'cms-article-' . $rec->id
            ),
        );
    }


    /**
     * Връща чистия публичен текст на статията
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected function getArticleBody($rec)
    {
        if (empty($rec->body)) {
            return null;
        }

        Mode::push('text', 'plain');

        try {
            $body = cls::get('type_Richtext')->toVerbal($rec->body);
        } finally {
            Mode::pop('text');
        }

        return $this->getCleanText($body);
    }


    /**
     * Връща специфичното за статията публично описание
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected function getDescription($rec)
    {
        $description = $this->getCleanText(
            $rec->seoDescription ?? null
        );

        if ($description !== null) {
            return $description;
        }

        return $this->getCleanText(
            cms_Content::getSeoDescription($rec->body)
        );
    }


    /**
     * Връща каноничния абсолютен URL на статията
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected function getCanonicalUrl($rec)
    {
        $url = cms_Articles::getUrl($rec, true);

        if (empty($url)) {
            return null;
        }

        return toUrl($url, 'absolute');
    }


    /**
     * Връща публичната илюстрация на статията
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected function getImageUrl($rec)
    {
        $fh = $rec->seoThumb ?? null;

        if (empty($fh)) {
            $fh = cms_Content::getSeoThumb($rec->body);
        }

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
     * Връща езика на публичния домейн на статията
     *
     * @param stdClass $rec
     *
     * @return string|null
     */
    protected function getLanguage($rec)
    {
        $domainId = cms_Content::fetchField($rec->menuId, 'domainId');

        if (!$domainId) {
            return null;
        }

        return $this->getCleanText(
            cms_Domains::fetchField($domainId, 'lang')
        );
    }


    /**
     * Преобразува публична скаларна стойност в чист текст
     *
     * @param mixed $value
     *
     * @return string|null
     */
    protected function getCleanText($value)
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = html_entity_decode(
            strip_tags((string) $value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
