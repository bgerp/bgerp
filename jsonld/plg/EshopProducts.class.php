<?php


/**
 * JSON-LD за публичната страница на е-артикул
 *
 * Добавя jsonld_ProviderIntf към eshop_Products със стандартна реализация -
 * Product/ProductGroup и Offer възли. ProductGroup се ползва само за истински
 * варианти - различни по показваните параметри.
 */
class jsonld_plg_EshopProducts extends core_Plugin
{
    /**
     * Добавя интерфейса за JSON-LD доставчик
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('jsonld_ProviderIntf');
    }


    /**
     * След рендиране на продукта в онлайн магазина
     */
    public static function on_AfterRenderProduct($mvc, &$res, $data)
    {
        if (!Mode::is('wrapper', 'cms_page_External')) {
            return;
        }

        jsonld_Aggregator::addFromProvider($mvc, $data);
    }


    /**
     * След рендиране на каталога без навигация - е-артикулите по групи
     */
    public static function on_AfterRenderAllProducts($mvc, &$res, $data)
    {
        if (!Mode::is('wrapper', 'cms_page_External') || empty($data->menuId)) {
            return;
        }

        jsonld_Aggregator::addFromProvider('eshop_Groups', (object) array(
            'menuId' => $data->menuId,
            'products' => jsonld_plg_EshopGroups::getVisibleProducts((array) ($data->groups ?? array())),
        ));
    }


    /**
     * Стандартна реализация на jsonld_ProviderIntf::getJsonLdNodes()
     *
     * Класът или друг плъгин може да подаде свой резултат преди нея.
     */
    public static function on_AfterGetJsonLdNodes($mvc, &$res, $data)
    {
        if (isset($res)) {
            return;
        }

        $res = self::getNodes($data);
    }


    /**
     * Връща JSON-LD възлите за подготвените данни
     *
     * @param stdClass $data
     *
     * @return jsonld_Node[]
     */
    protected static function getNodes($data)
    {
        $nodes = self::getProductNodes($data);
        $breadcrumbs = self::getBreadcrumbs($data);

        if (isset($breadcrumbs)) {
            $nodes[] = $breadcrumbs;
        }

        return $nodes;
    }


    /**
     * Връща пътечката до артикула, както я строи eshop_Products::prepareProduct()
     *
     * @param stdClass $data
     *
     * @return jsonld_Node|null
     */
    protected static function getBreadcrumbs($data)
    {
        $groupRec = eshop_Groups::fetch($data->rec->groupId ?? null);

        if (!$groupRec) {
            return null;
        }

        $items = jsonld_plg_EshopGroups::getBreadcrumbItems($groupRec);
        $items[] = array('name' => tr(eshop_Products::getDisplayTitle($data->rec)));

        return jsonld_Helper::getBreadcrumbList($items, 'eshop-breadcrumb-' . $data->rec->id);
    }


    /**
     * Връща Product/ProductGroup възлите на страницата
     *
     * @param stdClass $data
     *
     * @return jsonld_Node[]
     */
    protected static function getProductNodes($data)
    {
        $products = array();
        $offers = array();
        $url = self::getCanonicalUrl($data);
        $currency = self::getCurrency($data);

        foreach ((array) ($data->detailData->recs ?? array()) as $detailRec) {
            $productId = $detailRec->productId;

            if (!isset($products[$productId])) {
                $product = array(
                    'name' => self::getProductName($data, $productId),
                    'url' => $url,
                );
                $sku = self::getProductCode($data, $productId);

                if (isset($sku) && $sku !== '') {
                    $product['sku'] = $sku;
                }

                $additionalProperties = self::getAdditionalProperties(
                    $data,
                    $productId
                );

                if (countR($additionalProperties)) {
                    $product['additionalProperty'] = $additionalProperties;
                }

                $products[$productId] = $product;
                $offers[$productId] = array();
            }

            $availability = self::getAvailability($detailRec);

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
            $schemaAvailability = self::getSchemaAvailability(
                $availability->stockState
            );

            if (isset($schemaAvailability)) {
                $offerProperties['availability'] = $schemaAvailability;
            }

            $priceSpecification = self::getPriceSpecification(
                $detailRec,
                $availability->priceInfo->price,
                $currency
            );

            if (isset($priceSpecification)) {
                $offerProperties['priceSpecification'] = $priceSpecification;
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

        if (!countR($products)) {
            return array();
        }

        // С една опция колоните "Артикул" и "Код" са скрити - вижда се само заглавието на е-артикула
        $optionsCount = $data->detailData->optionsProductsCount ?? countR($products);

        if ($optionsCount == 1 && countR($products) == 1) {
            reset($products);
            $productId = key($products);
            $properties = array_merge(
                $products[$productId],
                self::getPageProperties($data)
            );

            return array(
                new jsonld_Node(
                    'Product',
                    $properties,
                    null,
                    'eshop-product-' . $productId
                ),
            );
        }

        // Google изисква снимка за всеки продукт - на страницата се виждат тези на е-артикула
        $imageUrl = self::getImageUrl($data);
        $description = self::getDescription($data);
        $variants = array();

        foreach ($products as $productId => $properties) {
            if (isset($description)) {
                $properties['description'] = $description;
            }

            if (isset($imageUrl)) {
                $properties['image'] = $imageUrl;
            }

            $variants[] = new jsonld_Node(
                'Product',
                $properties,
                null,
                'eshop-product-' . $productId
            );
        }

        // Различни артикули на една страница не са непременно варианти на един продукт
        $variesBy = self::getVariesBy($data, array_keys($products));

        if (!countR($variesBy)) {
            return $variants;
        }

        $group = self::getPageProperties($data);
        $group['url'] = $url;
        $groupCode = jsonld_Helper::getCleanText($data->rec->code ?? null);

        if ($groupCode !== null) {
            $group['productGroupID'] = $groupCode;
        }

        $group['variesBy'] = $variesBy;
        $group['hasVariant'] = $variants;

        return array(
            new jsonld_Node(
                'ProductGroup',
                $group,
                null,
                'eshop-product-group-' . $data->rec->id
            ),
        );
    }


    /**
     * Връща имената на параметрите, по които опциите се различават
     *
     * Празен резултат означава, че не са варианти - някой артикул няма показван
     * отличаващ параметър или два артикула имат еднакви стойности.
     *
     * @param stdClass $data
     * @param array    $productIds
     *
     * @return array
     */
    protected static function getVariesBy($data, $productIds)
    {
        $displayParams = eshop_Products::getSettingField($data->rec->id, null, 'showParams');

        if (!countR($displayParams)) {
            return array();
        }

        $commonParams = eshop_Products::getCommonParams($data->rec->id);
        $paramIds = $combinations = array();

        foreach ($productIds as $productId) {
            $productParams = cat_Products::getParams($productId);
            $diff = array_diff_key(
                array_intersect_key((array) $productParams, $displayParams),
                $commonParams
            );

            if (!countR($diff)) {
                return array();
            }

            ksort($diff);
            $combination = serialize($diff);

            if (isset($combinations[$combination])) {
                return array();
            }

            $combinations[$combination] = true;
            $paramIds += array_combine(array_keys($diff), array_keys($diff));
        }

        $res = array();

        foreach ($paramIds as $paramId) {
            $name = jsonld_Helper::getCleanText(
                tr(cat_Params::getVerbal($paramId, 'name'))
            );

            if ($name !== null) {
                $res[] = $name;
            }
        }

        return $res;
    }


    /**
     * Връща видимите данни на самата страница - заглавие, описание и снимка
     *
     * @param stdClass $data
     *
     * @return array
     */
    protected static function getPageProperties($data)
    {
        $properties = array();
        $name = jsonld_Helper::getCleanText(
            tr(eshop_Products::getDisplayTitle($data->rec))
        );

        if ($name !== null) {
            $properties['name'] = $name;
        }

        $description = self::getDescription($data);

        if (isset($description)) {
            $properties['description'] = $description;
        }

        $imageUrl = self::getImageUrl($data);

        if (isset($imageUrl)) {
            $properties['image'] = $imageUrl;
        }

        return $properties;
    }


    /**
     * Връща за какво количество от основната мярка е цената на опаковката
     *
     * @param stdClass $detailRec
     * @param float    $price
     * @param string   $currency
     *
     * @return jsonld_Node|null
     */
    protected static function getPriceSpecification($detailRec, $price, $currency)
    {
        $measureId = cat_Products::fetchField($detailRec->productId, 'measureId');

        if (!$measureId) {
            return null;
        }

        // Цената е за опаковката, а мярката може да е производна (напр. хил. бр.)
        $quantity = round(cat_UoM::convertToBaseUnit((float) ($detailRec->quantityInPack ?? 1), $measureId), 6);
        $baseUnitId = cat_UoM::fetchField($measureId, 'baseUnitId');
        $unitId = $baseUnitId ? $baseUnitId : $measureId;

        if ($quantity <= 0) {
            return null;
        }

        $referenceQuantity = array(
            'value' => ($quantity == round($quantity)) ? (int) $quantity : $quantity,
        );
        $unitText = jsonld_Helper::getCleanText(cat_UoM::getShortName($unitId));

        if ($unitText !== null) {
            $referenceQuantity['unitText'] = $unitText;
        }

        return new jsonld_Node(
            'UnitPriceSpecification',
            array(
                'price' => $price,
                'priceCurrency' => $currency,
                'referenceQuantity' => new jsonld_Node('QuantitativeValue', $referenceQuantity),
            )
        );
    }


    /**
     * Връща избраните публични параметри на артикула
     *
     * @param stdClass $data
     * @param int      $productId
     *
     * @return jsonld_Node[]
     */
    protected static function getAdditionalProperties($data, $productId)
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

            if (!$paramRec || !self::isSafePublicParam($paramRec)) {
                continue;
            }

            $name = jsonld_Helper::getCleanText(
                tr(cat_Params::getVerbal($paramRec, 'name'))
            );
            $value = jsonld_Helper::getCleanText(
                $productParams[$paramId]
            );

            if ($name === null || $value === null) {
                continue;
            }

            if (!empty($paramRec->suffix)) {
                $suffix = jsonld_Helper::getCleanText(tr($paramRec->suffix));

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
    protected static function isSafePublicParam($paramRec)
    {
        $driver = cat_Params::getDriver($paramRec);

        return $driver
            && !$driver instanceof cond_type_File
            && !$driver instanceof cond_type_Files
            && !$driver instanceof cond_type_Image
            && !$driver instanceof cond_type_Html;
    }


    /**
     * Връща чистото описание на страницата
     */
    protected static function getDescription($data)
    {
        return jsonld_Helper::getPlainText($data->rec->info ?? null);
    }


    /**
     * Връща абсолютния URL на първата валидна снимка на страницата
     */
    protected static function getImageUrl($data)
    {
        $fields = array('image', 'image2', 'image3', 'image4', 'image5');

        foreach ($fields as $field) {
            $url = jsonld_Helper::getImageUrl($data->rec->{$field} ?? null);

            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }


    /**
     * Връща името на варианта, както е в колоната "Артикул" - с отличаващите параметри
     */
    protected static function getProductName($data, $productId)
    {
        $name = jsonld_Helper::getCleanText(
            eshop_ProductDetails::getPublicProductTitle($data->rec->id, $productId)
        );
        $params = jsonld_Helper::getCleanText(
            eshop_CartDetails::getUniqueParamsAsText($data->rec->id, $productId)
        );

        if ($params === null) {
            return $name;
        }

        return ($name !== null) ? "{$name}, {$params}" : $params;
    }


    /**
     * Връща суровия код на артикула
     */
    protected static function getProductCode($data, $productId)
    {
        return cat_Products::fetchField($productId, 'code');
    }


    /**
     * Връща каноничния абсолютен URL на продуктовата страница
     */
    protected static function getCanonicalUrl($data)
    {
        return toUrl(
            eshop_Products::getUrl($data->rec, true),
            'absolute'
        );
    }


    /**
     * Връща валутата на публичния домейн
     */
    protected static function getCurrency($data)
    {
        $settings = cms_Domains::getSettings($data->rec->domainId);

        return $settings->currencyId;
    }


    /**
     * Връща публичната бизнес наличност на опаковката
     */
    protected static function getAvailability($detailRec)
    {
        // Подготвена при рендирането на опциите, за да не се смята повторно
        return $detailRec->_availability
            ?? eshop_ProductDetails::getPublicAvailability($detailRec);
    }


    /**
     * Преобразува бизнес състоянието на наличността към Schema.org
     *
     * @param string $stockState
     *
     * @return string|null
     */
    protected static function getSchemaAvailability($stockState)
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
