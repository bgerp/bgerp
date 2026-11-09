<?php


/**
 * Shared, permission-aware source data for domestic transport documents.
 */
class trans_LineDocuments extends core_BaseClass
{
    public static function isCargoDocument($document)
    {
        return $document->haveInterface('store_iface_DocumentIntf') || $document->isInstanceOf('trans_Waybills');
    }


    public static function getCargoDocuments($lineId)
    {
        trans_Lines::requireRightFor('single', $lineId);
        $query = trans_LineDetails::getQuery();
        $query->where(array("#lineId = [#1#] AND #status != 'removed' AND #containerState != 'rejected'", (int) $lineId));
        $query->orderBy('createdOn,id', 'ASC');
        $documents = array();
        while ($rec = $query->fetch()) {
            $document = doc_Containers::getDocument($rec->containerId);
            if (self::isCargoDocument($document)) $documents[$rec->containerId] = $document;
        }

        return $documents;
    }


    public static function getWarehouseDocuments($lineId)
    {
        return array_filter(self::getCargoDocuments($lineId), function ($document) {
            return $document->haveInterface('store_iface_DocumentIntf');
        });
    }


    public static function plain($value)
    {
        return trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8'));
    }


    public static function getAddress($data, $part)
    {
        return trim(implode(', ', array_filter(array($data[$part . 'Country'] ?? '',
            trim(($data[$part . 'PCode'] ?? '') . ' ' . ($data[$part . 'Place'] ?? '')), $data[$part . 'Address'] ?? ''))));
    }


    /**
     * Returns actual movement directions, including reverse warehouse documents.
     */
    public static function getShipmentData($document, $lineId, $includeGoods = true)
    {
        $document->getInstance()->requireRightFor('single', $document->that);
        $rec = $document->fetch();
        $mvc = $document->getInstance();
        if ($mvc instanceof trans_Waybills) return (array) $rec;
        $info = $document->getTransportLineInfo($lineId);
        $logistics = $document->haveInterface('trans_LogisticDataIntf') ? $document->getLogisticData() : array();
        $from = 'from';
        $to = 'to';
        if (($rec->isReverse ?? 'no') == 'yes' && ($mvc instanceof store_ShipmentOrders || $mvc instanceof store_Receipts)) {
            $from = 'to';
            $to = 'from';
        }
        $data = array('senderName' => $logistics[$from . 'Company'] ?? '', 'senderAddress' => self::getAddress($logistics, $from),
            'recipientName' => $logistics[$to . 'Company'] ?? '', 'recipientAddress' => self::getAddress($logistics, $to),
            'totalWeight' => $rec->weightInput ?? $info['weight'] ?? null,
            'volume' => $rec->volumeInput ?? $info['volume'] ?? null,
            'notes' => self::plain($info['notes'] ?? ''), 'goods' => array(), 'transportUnits' => $info['transportUnits'] ?? array());
        if ($mvc instanceof store_Transfers) {
            $data['senderName'] = store_Stores::fetchField($rec->fromStore, 'name');
            $data['recipientName'] = store_Stores::fetchField($rec->toStore, 'name');
        }
        $data['loadingPlace'] = trim($data['senderName'] . ', ' . $data['senderAddress'], ', ');
        $data['unloadingPlace'] = trim($data['recipientName'] . ', ' . $data['recipientAddress'], ', ');
        $data['loadingTime'] = ($mvc instanceof store_Receipts) ? ($rec->loadingOn ?? null) : ($rec->deliveryTime ?? $logistics['loadingTime'] ?? null);
        $data['unloadingTime'] = ($mvc instanceof store_Receipts) ? ($rec->deliveryTime ?? null) : ($rec->deliveryOn ?? null);
        if ($from == 'to') {
            $time = $data['loadingTime'];
            $data['loadingTime'] = $data['unloadingTime'];
            $data['unloadingTime'] = $time;
        }
        foreach (array($from, $to) as $part) {
            $contact = trim(($logistics[$part . 'Person'] ?? '') . ' ' . ($logistics[$part . 'PersonPhones'] ?? ''));
            $extra = self::plain($logistics[$part . 'AddressInfo'] ?? '');
            if ($contact || $extra) $data['notes'] .= "\n" . ($part == $from ? 'Товарене: ' : 'Разтоварване: ') . trim($contact . ' ' . $extra);
        }
        if ($includeGoods && !empty($mvc->mainDetail)) {
            $detail = cls::get($mvc->mainDetail);
            if ($detail->getField('productId', false)) {
                $query = $detail->getQuery();
                $query->where(array("#{$detail->masterKey} = [#1#]", (int) $document->that));
                $query->orderBy('id', 'ASC');
                while ($item = $query->fetch()) {
                    $quantity = $item->quantity ?? null;
                    $inPack = $item->quantityInPack ?? 1;
                    $data['goods']['name'][] = cat_Products::fetchField($item->productId, 'name');
                    $data['goods']['packaging'][] = !empty($item->packagingId) ? cat_UoM::fetchField($item->packagingId, 'shortName') : '';
                    $data['goods']['quantity'][] = isset($quantity) && $inPack > 0 ? (string) round($quantity / $inPack, 6) : '';
                    $data['goods']['weight'][] = '';
                    $data['goods']['marks'][] = '';
                }
            }
        }
        $data['goods'] = json_encode($data['goods'], JSON_UNESCAPED_UNICODE);

        return $data;
    }


    public static function getGoodsText($goods)
    {
        $lines = array();
        foreach (type_Table::toArray($goods) as $item) {
            $text = ($item->name ?? '') . ' — ' . ($item->quantity ?? '') . ' ' . ($item->packaging ?? '');
            if (strlen((string) ($item->weight ?? ''))) $text .= '; ' . $item->weight . ' kg';
            if (!empty($item->marks)) $text .= '; маркировка: ' . $item->marks;
            $lines[] = trim($text);
        }

        return implode("\n", $lines);
    }


    /**
     * Keep transport units distinct from product quantities. Waybill packaging is
     * free text, so its quantities are grouped only by the exact packaging label.
     */
    public static function getPackagingText($data)
    {
        if (!empty($data['transportUnits'])) {
            return self::plain(trans_Helper::displayTransUnits($data['transportUnits'], false, ', '));
        }
        $packages = array();
        foreach (type_Table::toArray($data['goods'] ?? null) as $item) {
            $label = trim($item->packaging ?? '');
            $quantity = core_Type::getByName('double')->fromVerbal($item->quantity ?? '');
            if ($label === '' || $quantity === false || $quantity === null) continue;
            $packages[$label] = ($packages[$label] ?? 0) + $quantity;
        }
        $text = array();
        foreach ($packages as $label => $quantity) $text[] = round($quantity, 6) . ' ' . $label;

        return implode(', ', $text);
    }


    /**
     * A bounded summary per source document; never a copy of its product rows.
     */
    public static function getManifestText($reference, $data)
    {
        $parts = array($reference);
        $packaging = array_key_exists('packaging', $data) ? (string) $data['packaging'] : self::getPackagingText($data);
        if ($packaging !== '') $parts[] = $packaging;
        $parts[] = 'бруто: ' . (isset($data['totalWeight']) ? $data['totalWeight'] . ' kg' : 'неуточнено');
        $parts[] = 'обем: ' . (isset($data['volume']) ? $data['volume'] . ' m³' : 'неуточнен');
        if (!empty($data['description'])) $parts[] = $data['description'];

        return implode('; ', $parts);
    }


    /**
     * Snapshot candidates are editable until the trip sheet is issued.
     * Document order is not an inferred/optimized driving itinerary.
     */
    public static function getTripDefaults($lineId)
    {
        trans_Lines::requireRightFor('single', $lineId);
        $line = trans_Lines::fetch($lineId);
        $result = array('departure' => $line->start, 'driverId' => $line->forwarderPersonId ?? null,
            'lineNotes' => '', 'transportType' => 'own');
        $vehicle = trans_Vehicles::fetch(array("#name = '[#1#]' OR #number = '[#1#]'", $line->vehicle ?? ''));
        if ($vehicle) {
            $result['vehicleId'] = $vehicle->id;
            $result['vehicleModel'] = $vehicle->name;
        }
        $routes = $references = $manifest = $summary = $unavailable = $waybillHashes = array();
        $ownRoutes = $ownCargo = $ownReferences = array();
        foreach (self::getCargoDocuments($lineId) as $containerId => $document) {
            if (!$document->haveRightFor('single')) {
                $unavailable[] = '#' . $document->getHandle();
                continue;
            }
            $data = self::getShipmentData($document, $lineId, false);
            $reference = '#' . $document->getHandle();
            $route = $data['loadingPlace'] . ' → ' . $data['unloadingPlace'];
            if ($document->isInstanceOf('trans_Waybills')) {
                $reference = 'Товарителница ' . trans_Waybills::getNumber((object) $data) . ' / ' . $reference;
                $waybillHashes[$document->that] = trans_Waybills::getCargoHash((object) $data);
            } else {
                $ownRoutes[$route] = $route;
                $ownCargo[] = self::getManifestText($reference, $data);
                $ownReferences[] = $reference;
            }
            // Документи до едно и също място са един маршрут
            $routes[$route] = $route;
            $references[] = $reference;
            $manifest[] = self::getManifestText($reference, $data);
            $documentRec = $document->fetch();
            $isOwn = !$document->isInstanceOf('trans_Waybills');
            $dateField = $isOwn ? ($document->getInstance()->valiorFld ?? 'valior') : 'issueDate';
            $summary[] = array('containerId' => $containerId, 'documentDate' => $documentRec->{$dateField} ?? null,
                'isOwn' => $isOwn, 'description' => '', 'reference' => $reference, 'packaging' => self::getPackagingText($data),
                'totalWeight' => $data['totalWeight'] ?? null, 'volume' => $data['volume'] ?? null,
                'loadingPlace' => $data['loadingPlace'], 'unloadingPlace' => $data['unloadingPlace'],
                'loadingTime' => $data['loadingTime'] ?? $data['loadingDate'] ?? null,
                'unloadingTime' => $data['unloadingTime'] ?? $data['unloadingDate'] ?? null,
                'notes' => trim(($data['notes'] ?? '') . "\n" . self::plain($data['lineNotes'] ?? '') . "\n" . ($data['instructions'] ?? '')));
        }
        $own = array('route' => implode("\n", $ownRoutes), 'cargo' => implode("\n", $ownCargo), 'transportDocuments' => implode("\n", $ownReferences));
        if ($waybillHashes) $result['transportType'] = $own['transportDocuments'] ? 'mixed' : 'public';
        $result['route'] = implode("\n", $routes);
        // A human or an AI supplies the short cargo description, including any
        // hazard classification. Product names cannot establish that classification.
        $result['cargo'] = '';
        $result['transportDocuments'] = implode("\n", $references);
        $result['cargoManifest'] = implode("\n", $manifest);
        $result['sourceData'] = array('hash' => md5(serialize(array($result, $summary, $waybillHashes, $unavailable))), 'own' => $own,
            'manifestVersion' => 4, 'cargoSummary' => $summary, 'cargoSource' => $summary,
            'waybills' => $waybillHashes, 'unavailable' => $unavailable);

        return $result;
    }
}
