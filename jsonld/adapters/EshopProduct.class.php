<?php


/**
 * Преобразува подготвени e-shop данни в Product и Offer JSON-LD възли
 *
 * Бъдещи Article и BreadcrumbList доставчици следва да са отделни
 * source-specific реализации, използващи същия общ pipeline.
 */
class jsonld_adapters_EshopProduct
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'jsonld_ProviderIntf';


    /**
     * Връща JSON-LD възлите за подготвена продуктова страница
     *
     * @param stdClass $data
     *
     * @return jsonld_Node[]
     */
    public function getJsonLdNodes($data)
    {
        $products = array();
        $offers = array();
        $url = $this->getCanonicalUrl($data);
        $currency = $this->getCurrency($data);

        foreach ($data->detailData->recs as $detailRec) {
            $productId = $detailRec->productId;

            if (!isset($products[$productId])) {
                $product = array(
                    'name' => $this->getProductName($data, $productId),
                    'url' => $url,
                );
                $sku = $this->getProductCode($data, $productId);

                if (isset($sku) && $sku !== '') {
                    $product['sku'] = $sku;
                }

                $additionalProperties = $this->getAdditionalProperties(
                    $data,
                    $productId
                );

                if (countR($additionalProperties)) {
                    $product['additionalProperty'] = $additionalProperties;
                }

                $products[$productId] = $product;
                $offers[$productId] = array();
            }

            $availability = $this->getAvailability($detailRec);

            if (!$availability->actionAllowsBuy
                || !$availability->hasPublicPrice
                || $availability->saleStopped) {
                continue;
            }

            $offerProperties = array(
                'price' => $availability->priceInfo->price,
                'priceCurrency' => $currency,
                'url' => $url,
            );
            $schemaAvailability = $this->getSchemaAvailability(
                $availability->stockState
            );

            if (isset($schemaAvailability)) {
                $offerProperties['availability'] = $schemaAvailability;
            }

            $offers[$productId][] = new jsonld_Node(
                'Offer',
                $offerProperties
            );
        }

        foreach ($products as $productId => $product) {
            if (countR($offers[$productId])) {
                $products[$productId]['offers'] = $offers[$productId];
            }
        }

        if (count($products) === 1) {
            reset($products);
            $productId = key($products);
            $description = $this->getDescription($data);

            if (isset($description)) {
                $products[$productId]['description'] = $description;
            }

            $imageUrl = $this->getImageUrl($data);

            if (isset($imageUrl)) {
                $products[$productId]['image'] = $imageUrl;
            }
        }

        $nodes = array();

        foreach ($products as $productId => $properties) {
            $nodes[] = new jsonld_Node(
                'Product',
                $properties,
                null,
                'eshop-product-' . $productId
            );
        }

        return $nodes;
    }


    /**
     * Връща избраните публични параметри на артикула
     *
     * @param stdClass $data
     * @param int      $productId
     *
     * @return jsonld_Node[]
     */
    protected function getAdditionalProperties($data, $productId)
    {
        $selectedParams = eshop_Products::getSettingField(
            $data->rec->id,
            null,
            'showParams'
        );

        if (!countR($selectedParams)) {
            return array();
        }

        $publicParams = cat_Params::getPublic();
        $selectedParams = array_intersect_key(
            $selectedParams,
            $publicParams
        );

        if (!countR($selectedParams)) {
            return array();
        }

        Mode::push('text', 'plain');

        try {
            $productParams = cat_Products::getParams(
                $productId,
                null,
                true
            );
        } finally {
            Mode::pop('text');
        }

        $properties = array();

        foreach ($selectedParams as $paramId) {
            if (!array_key_exists($paramId, $productParams)) {
                continue;
            }

            $paramRec = cat_Params::fetch(
                $paramId,
                'name,suffix,driverClass'
            );

            if (!$paramRec || !$this->isSafePublicParam($paramRec)) {
                continue;
            }

            $name = $this->getCleanScalarText(
                tr(cat_Params::getVerbal($paramRec, 'name'))
            );
            $value = $this->getCleanScalarText(
                $productParams[$paramId]
            );

            if ($name === null || $value === null) {
                continue;
            }

            if (!empty($paramRec->suffix)) {
                $suffix = $this->getCleanScalarText(tr($paramRec->suffix));

                if ($suffix !== null) {
                    $value .= ' ' . $suffix;
                }
            }

            $properties[] = new jsonld_Node(
                'PropertyValue',
                array(
                    'name' => $name,
                    'value' => $value,
                )
            );
        }

        return $properties;
    }


    /**
     * Проверява дали типът може да се представи като чист публичен текст
     *
     * @param stdClass $paramRec
     *
     * @return bool
     */
    protected function isSafePublicParam($paramRec)
    {
        $driver = cat_Params::getDriver($paramRec);

        return $driver
            && !$driver instanceof cond_type_File
            && !$driver instanceof cond_type_Files
            && !$driver instanceof cond_type_Image
            && !$driver instanceof cond_type_Html;
    }


    /**
     * Преобразува скаларна публична стойност в чист текст
     *
     * @param mixed $value
     *
     * @return string|null
     */
    protected function getCleanScalarText($value)
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


    /**
     * Връща чистото описание на страницата
     */
    protected function getDescription($data)
    {
        if (empty($data->rec->info)) {
            return null;
        }

        Mode::push('text', 'plain');

        try {
            $description = cls::get('type_Richtext')
                ->toVerbal($data->rec->info);
        } finally {
            Mode::pop('text');
        }

        $description = trim($description);

        return $description !== '' ? $description : null;
    }


    /**
     * Връща абсолютния URL на първата валидна снимка на страницата
     */
    protected function getImageUrl($data)
    {
        $fields = array('image', 'image2', 'image3', 'image4', 'image5');

        foreach ($fields as $field) {
            $fh = $data->rec->{$field} ?? null;

            if (empty($fh) || !fileman_Files::isImage($fh)) {
                continue;
            }

            $path = fileman::fetchByFh($fh, 'path');

            if (!$path || !file_exists($path)) {
                continue;
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

        return null;
    }


    /**
     * Връща публичното име на артикула
     */
    protected function getProductName($data, $productId)
    {
        return eshop_ProductDetails::getPublicProductTitle(
            $data->rec->id,
            $productId
        );
    }


    /**
     * Връща суровия код на артикула
     */
    protected function getProductCode($data, $productId)
    {
        return cat_Products::fetchField($productId, 'code');
    }


    /**
     * Връща каноничния абсолютен URL на продуктовата страница
     */
    protected function getCanonicalUrl($data)
    {
        return toUrl(
            eshop_Products::getUrl($data->rec, true),
            'absolute'
        );
    }


    /**
     * Връща валутата на публичния домейн
     */
    protected function getCurrency($data)
    {
        $settings = cms_Domains::getSettings($data->rec->domainId);

        return $settings->currencyId;
    }


    /**
     * Връща публичната бизнес наличност на опаковката
     */
    protected function getAvailability($detailRec)
    {
        return eshop_ProductDetails::getPublicAvailability($detailRec);
    }


    /**
     * Преобразува бизнес състоянието на наличността към Schema.org
     *
     * @param string $stockState
     *
     * @return string|null
     */
    protected function getSchemaAvailability($stockState)
    {
        $map = array(
            'local' => 'https://schema.org/InStock',
            'remote' => 'https://schema.org/InStock',
            'mixed' => 'https://schema.org/InStock',
            'expected' => 'https://schema.org/BackOrder',
            'outOfStock' => 'https://schema.org/OutOfStock',
        );

        return $map[$stockState] ?? null;
    }
}
