<?php


/**
 * Local integration checks, using the application bootstrap and native models.
 * TRANS_TRIP_TESTS=1; pass the test user's nickname as the fourth CLI argument.
 */
class trans_tests_TransportDocuments extends core_BaseClass
{
    private $checks = 0;


    private function check($condition, $message)
    {
        if (!$condition) throw new RuntimeException($message);
        $this->checks++;
    }


    /**
     * Exercise the native cargo description and input hook on an existing draft.
     */
    private function inputCargoForm($trips, $trip, $values)
    {
        $input = array('id' => $trip->id, 'Cmd' => 'save');
        $fields = array();
        foreach ($values as $field => $value) {
            $input[$field] = $value;
            $fields[] = $field;
        }
        Request::push($input, 'cargoEditTest');
        try {
            $form = $trips->prepareEditForm((object) array())->form;
            $form->input(implode(',', $fields));
            $trips->invoke('AfterInputEditForm', array($form));

            return $form;
        } finally {
            Request::pop('cargoEditTest');
        }
    }


    /**
     * Reject only the explicitly selected preview fixtures belonging to the test account.
     */
    public function cli_Cleanup()
    {
        global $argv;
        expect(getenv('TRANS_TRIP_TESTS') === '1', 'Local test opt-in required');
        $user = core_Users::fetch(array("#nick = '[#1#]'", $argv[4] ?? ''));
        expect($user && core_Users::haveRole('ceo', $user->id), 'A local ceo test account is required');
        $line = trans_Lines::fetch((int) ($argv[5] ?? 0));
        expect($line && $line->createdBy == $user->id && strpos($line->title, 'TEST - Товарителница ') === 0, 'Expected an owned preview line');
        core_Users::sudo($user->id);
        try {
            foreach (array('trans_Waybills', 'trans_TripSheets', 'store_Transfers') as $class) {
                $mvc = cls::get($class);
                $query = $mvc->getQuery();
                $query->where(array("#lineId = [#1#] AND #state != 'rejected'", (int) $line->id));
                while ($rec = $query->fetch()) {
                    expect($rec->createdBy == $user->id, 'Only owned preview documents may be cleaned up');
                    $mvc->reject($rec->id);
                }
            }
            trans_Lines::reject($line->id);
            $vehicle = trans_Vehicles::fetch(array("#name = '[#1#]'", $line->vehicle));
            if ($vehicle && strpos($vehicle->name, 'Waybill test ') === 0 && $vehicle->createdBy == $user->id) trans_Vehicles::reject($vehicle->id);
            echo "Preview fixtures rejected\n";
        } finally {
            core_Cls::shutdown();
            core_Users::exitSudo();
        }
    }


    public function cli_Run()
    {
        global $argv;
        expect(getenv('TRANS_TRIP_TESTS') === '1', 'Local test opt-in required');
        $user = core_Users::fetch(array("#nick = '[#1#]'", $argv[4] ?? ''));
        expect($user && core_Users::haveRole('ceo', $user->id), 'A local ceo test account is required');
        core_Users::sudo($user->id);
        $bills = cls::get('trans_Waybills');
        $trips = cls::get('trans_TripSheets');
        $bills->setupMVC();
        $trips->setupMVC();
        $line = $vehicle = $bill = $trip = $summaryTrip = $transfer = $sourceBill = $standalone = null;
        $passed = false;

        try {
            $existingLine = trans_Lines::getQuery()->fetch();
            $drivers = trans_Vehicles::getDriverOptions();
            expect(!empty($existingLine->folderId) && count($drivers), 'Existing transport folder and driver required');
            $vehicle = (object) array('name' => 'Waybill test ' . uniqid(), 'number' => 'TESTWB01', 'type' => 'truck', 'state' => 'active');
            trans_Vehicles::save($vehicle);
            $line = (object) array('title' => 'TEST - Товарителница ' . date('Y-m-d H:i:s'), 'start' => '2026-11-02 08:00:00',
                'vehicle' => $vehicle->name, 'forwarderPersonId' => key($drivers), 'description' => 'ТЕСТ: обадете се преди пристигане.',
                'folderId' => $existingLine->folderId, 'state' => 'draft');
            trans_Lines::save($line);
            $line = trans_Lines::fetch($line->id, '*', false);
            doc_Threads::doUpdateThread($line->threadId);
            $context = array('lineId' => $line->id, 'originId' => $line->containerId, 'threadId' => $line->threadId, 'folderId' => $line->folderId);
            $goods = json_encode(array('name' => array('ТЕСТ: външен товар'), 'packaging' => array('палет'),
                'marks' => array('WB-TEST'), 'quantity' => array('2'), 'weight' => array('640')), JSON_UNESCAPED_UNICODE);
            $bill = (object) ($context + array('series' => 'TEST', 'issueDate' => '2026-11-02', 'issuePlace' => 'София',
                'senderName' => 'ТЕСТОВ ИЗПРАЩАЧ', 'senderAddress' => 'София, Тестов адрес 1',
                'recipientName' => 'ТЕСТОВ ПОЛУЧАТЕЛ', 'recipientAddress' => 'Пловдив, Тестов адрес 2',
                'carrierName' => 'ТЕСТОВ ПРЕВОЗВАЧ', 'carrierAddress' => 'София, Тестов адрес 3', 'carrierNumber' => '000000000',
                'loadingPlace' => 'София, Тестов адрес 1', 'unloadingPlace' => 'Пловдив, Тестов адрес 2',
                'loadingDate' => '2026-11-02', 'unloadingDate' => '2026-11-02', 'goods' => $goods, 'totalWeight' => 640, 'volume' => 1.25,
                'vehicleModel' => $vehicle->name, 'vehicleNumber' => $vehicle->number, 'state' => 'draft'));
            $this->check((bool) $bills->save($bill), 'External cargo can be saved without a warehouse document');
            $bill = $bills->fetch($bill->id, '*', false);
            $this->check(!$bills->getValidationErrors($bill, true), 'Waybill may activate before a trip sheet exists');
            $lineDetail = trans_LineDetails::fetch(array('#lineId = [#1#] AND #containerId = [#2#]', $line->id, $bill->containerId));
            $this->check($lineDetail && $lineDetail->status == 'ready', 'Waybill is a cargo row in the transport line');
            $this->check(trans_Lines::fetchField($line->id, 'countStoreDocuments', false) == 1, 'External cargo participates in the line document count');
            $lineView = trans_Lines::prepareSingle((object) array('rec' => trans_Lines::fetch($line->id, '*', false)));
            $this->check(strpos(strip_tags($lineView->row->weight), '640') !== false, 'External cargo participates in the line weight');
            $this->check(!cls::haveInterface('acc_TransactionSourceIntf', $bills) && !cls::haveInterface('store_iface_DocumentIntf', $bills), 'External cargo does not create own stock or accounting movements');
            foreach (array('0', '-1', 'not a number') as $quantity) {
                $bad = clone $bill;
                $bad->goods = json_encode(array('name' => array('Goods'), 'packaging' => array('box'), 'quantity' => array($quantity)));
                $this->check(isset($bills->getValidationErrors($bad)['goods']), 'Invalid quantity is rejected: ' . $quantity);
            }
            $goodsType = $bills->getFieldType('goods');
            $this->check(empty($goodsType->isValid($goods)['error']), 'Complete goods rows pass the native field validator');
            $partial = clone $bill;
            $partial->goods = $goodsType->fromVerbal(array('name' => array(''), 'packaging' => array('box'), 'quantity' => array('2')));
            $this->check(isset($bills->getValidationErrors($partial)['goods']), 'Incomplete table rows are not silently discarded');
            $unsafe = json_encode(array('name' => array('<script>alert(1)</script>'), 'packaging' => array('box'), 'quantity' => array('1')));
            $rendered = $goodsType->toVerbal($unsafe);
            $this->check(strpos($rendered, '<script>') === false && strpos($rendered, '&lt;script&gt;') !== false, 'Goods print safely as text');
            $manyGoods = array('name' => array(), 'packaging' => array(), 'marks' => array(), 'quantity' => array());
            for ($i = 0; $i < 400; $i++) {
                $manyGoods['name'][] = 'UNIQUE PRODUCT ' . $i;
                $manyGoods['packaging'][] = $i < 300 ? 'кашон' : 'палет';
                $manyGoods['marks'][] = 'UNIQUE MARK ' . $i;
                $manyGoods['quantity'][] = '1';
            }
            $manyCargo = array('goods' => json_encode($manyGoods, JSON_UNESCAPED_UNICODE), 'totalWeight' => 1200, 'volume' => 8.5);
            $manyManifest = trans_LineDocuments::getManifestText('#TEST400', $manyCargo);
            $this->check(trans_LineDocuments::getPackagingText($manyCargo) === '300 кашон, 100 палет'
                && substr_count($manyManifest, '#TEST400') === 1 && strpos($manyManifest, "\n") === false
                && strpos($manyManifest, 'UNIQUE') === false, 'Hundreds of goods rows become one summary with packaging totals and no names or marks');
            $this->check(strpos(trans_LineDocuments::getManifestText('#UNKNOWN', array()), 'бруто: неуточнено; обем: неуточнен') !== false
                && strpos(trans_LineDocuments::getManifestText('#ZERO', array('totalWeight' => 0, 'volume' => 0)), 'бруто: 0 kg; обем: 0 m³') !== false,
                'Missing weight and volume remain distinct from explicit zero');
            $safeSummary = trans_TripSheets::renderCargoSummary(array(
                array('reference' => '<script>alert(1)</script>', 'packaging' => '<img src=x onerror=alert(1)>',
                    'description' => '<svg onload=alert(1)>', 'totalWeight' => null, 'volume' => null),
                array('reference' => '#ZERO', 'packaging' => '', 'totalWeight' => 0, 'volume' => 0)), true);
            $this->check(strpos($safeSummary, '<script') === false && strpos($safeSummary, '<img') === false
                && strpos($safeSummary, '<svg') === false && strpos($safeSummary, '&lt;svg') !== false
                && strpos($safeSummary, '&lt;script') !== false && strpos($safeSummary, '&lt;img') !== false,
                'Summary references, packaging and descriptions are escaped');
            preg_match_all('~<td style="text-align:right">(.*?)</td>~s', $safeSummary, $numericCells);
            $numericTexts = array_map(function ($value) { return trim(strip_tags($value)); }, $numericCells[1]);
            $this->check($numericTexts === array('—', '—', '0', '0'),
                'Summary renders missing totals as unknown and explicit zero as zero');
            $bad = clone $bill;
            $bad->loadingLocationId = -1;
            $this->check(isset($bills->getValidationErrors($bad)['loadingLocationId']), 'An unrelated external store cannot be substituted');

            $defaults = trans_LineDocuments::getTripDefaults($line->id);
            $this->check($defaults['transportType'] === 'public' && $defaults['vehicleId'] == $vehicle->id
                && $defaults['driverId'] == key($drivers), 'Vehicle, driver and public transport mode come from the line');
            $this->check(strpos($defaults['route'], $bill->loadingPlace) !== false && strpos($defaults['route'], $bill->unloadingPlace) !== false,
                'Both ends of the cargo movement appear in the route');
            $this->check($defaults['cargo'] === '' && $defaults['sourceData']['cargoSummary'][0]['packaging'] === '2 палет'
                && $defaults['sourceData']['cargoSummary'][0]['totalWeight'] == 640
                && $defaults['sourceData']['cargoSummary'][0]['volume'] == 1.25
                && $defaults['sourceData']['cargoSummary'][0]['description'] === '',
                'Source provenance retains packaging, weight and volume without guessing a description or copying goods');
            $this->check(count($defaults['sourceData']['cargoSummary']) === 1
                && $defaults['sourceData']['cargoSummary'][0]['loadingTime'] === '2026-11-02'
                && $defaults['sourceData']['cargoSummary'][0]['unloadingTime'] === '2026-11-02'
                && $defaults['sourceData']['cargoSummary'][0]['documentDate'] === $bill->issueDate
                && $defaults['sourceData']['cargoSummary'][0]['containerId'] == $bill->containerId
                && !$defaults['sourceData']['cargoSummary'][0]['isOwn'],
                'Each source document retains its identity, date, ownership and planned dates');
            $this->check($defaults['sourceData']['manifestVersion'] === 4
                && $defaults['sourceData']['cargoSource'] === $defaults['sourceData']['cargoSummary'],
                'New simplified trips retain the raw source provenance');
            $this->check($defaults['lineNotes'] === '' && isset($defaults['sourceData']['waybills'][$bill->id]),
                'Cargo provenance is retained without automatically copying line notes');
            Request::push(array('threadId' => $line->threadId), 'transportTest');
            try {
                $formData = $trips->prepareEditForm((object) array());
                $this->check($formData->form->rec->transportType === 'public' && $formData->form->rec->route === $defaults['route'], 'Native new-trip form receives defaults');
                foreach (array('packaging', 'totalWeight', 'volume', 'description') as $field) {
                    $name = trans_TripSheets::getCargoFieldName($defaults['sourceData']['cargoSummary'][0], $field);
                    $this->check(!$formData->form->getField($name, false),
                        'Simplified form has no per-document cargo input: ' . $field);
                }
                $this->check(($formData->form->getField('cargo')->input ?? '') !== 'none'
                    && $formData->form->getField('cargoManifest')->input === 'none'
                    && $formData->form->getField('transportDocuments')->input === 'none',
                    'The form exposes one cargo description and generates document references');
                $this->check($formData->form->rec->authorizedBy === $user->names,
                    'The current user is offered as the person authorizing the journey');
            } finally {
                Request::pop('transportTest');
            }
            $trip = (object) ($context + $defaults + array('series' => 'TEST', 'companyName' => 'ТЕСТОВ ПРЕВОЗВАЧ',
                'companyNumber' => '000000000', 'companyAddress' => 'Тестов адрес', 'authorizedBy' => 'Тестов ръководител', 'state' => 'draft'));
            $cargoDescription = 'Опаковки и консумативи';
            $trip->cargo = 'Първоначално общо описание';
            $this->check((bool) $trips->save($trip), 'Trip draft is saved with the source snapshot and a short description');
            $trip = $trips->fetch($trip->id, '*', false);
            $editForm = $this->inputCargoForm($trips, $trip, array('cargo' => $cargoDescription, 'lineNotes' => 'Бележка на диспечера'));
            $this->check(!$editForm->gotErrors() && $editForm->isSubmitted(), 'The common cargo description passes native form input');
            $this->check((bool) $trips->save($editForm->rec), 'The reviewed cargo description can be saved');
            $trip = $trips->fetch($trip->id, '*', false);
            $this->check($trip->cargo === $cargoDescription && $trip->lineNotes === 'Бележка на диспечера'
                && $trip->cargoManifest === '' && strpos($trip->transportDocuments, '02.11.2026') !== false,
                'Reloaded trip retains the common description and dated references without a cargo appendix');
            $this->check($trip->sourceData['hash'] === $defaults['sourceData']['hash']
                && $trip->sourceData['cargoSource'] === $defaults['sourceData']['cargoSource']
                && $trip->sourceData['cargoSummary'] === $defaults['sourceData']['cargoSummary'],
                'Editing the common description never changes the source hash or raw provenance');
            Request::push(array('id' => $trip->id), 'cargoReloadTest');
            try {
                $savedForm = $trips->prepareEditForm((object) array())->form;
                $this->check($savedForm->rec->cargo === $cargoDescription
                    && ($savedForm->getField('cargo')->input ?? '') !== 'none',
                    'Reopening the native form preserves the editable common description');
                $this->check($savedForm->rec->authorizedBy === 'Тестов ръководител',
                    'Reopening a draft does not replace the previously selected authorizer');
            } finally {
                Request::pop('cargoReloadTest');
            }
            Request::push(array('id' => $trip->id, 'Cmd' => 'save', 'cargoSourceHash' => 'stale source'), 'cargoStaleFormTest');
            try {
                $staleForm = $trips->prepareEditForm((object) array())->form;
                $staleForm->input('cargoSourceHash');
                $trips->invoke('AfterInputEditForm', array($staleForm));
                $this->check($staleForm->gotErrors(), 'A form submitted for a different source snapshot is rejected');
            } finally {
                Request::pop('cargoStaleFormTest');
            }
            // Simulate previously saved compact drafts using only this test fixture.
            foreach (array(2, 3) as $legacyVersion) {
                $legacyTrip = clone $trip;
                $legacyTrip->sourceData['manifestVersion'] = $legacyVersion;
                $trips->save_($legacyTrip, 'sourceData');
                Request::push(array('id' => $trip->id), 'legacyCargoFormTest');
                try {
                    $legacyForm = $trips->prepareEditForm((object) array())->form;
                    $this->check($legacyForm->rec->sourceData['manifestVersion'] === 4
                        && $legacyForm->rec->cargo === $cargoDescription
                        && !$legacyForm->getField(trans_TripSheets::getCargoFieldName($trip->sourceData['cargoSummary'][0], 'description'), false),
                        'Editing a legacy compact draft keeps its common text and uses the simplified form: ' . $legacyVersion);
                } finally {
                    Request::pop('legacyCargoFormTest');
                    $trips->save_($trip, 'sourceData');
                }
            }
            $this->check(isset($bills->getTripBindingErrors($trip)['transportDocuments']), 'Trip issue waits for its cargo documents to be active');
            $bill->notes = 'ТЕСТ: нова бележка за товара';
            $this->check((bool) $bills->save($bill, 'notes'), 'Draft cargo notes can be updated');
            $trip->state = 'pending';
            $this->check(!$trips->save($trip, 'state'), 'A changed source prevents issuing a stale trip sheet');
            $trip = $trips->fetch($trip->id, '*', false);
            Request::push(array('id' => $trip->id, 'fromLine' => 1), 'transportTest');
            try {
                $formData = $trips->prepareEditForm((object) array());
                $this->check($formData->form->rec->sourceData['hash'] !== $defaults['sourceData']['hash']
                    && $formData->form->rec->fromLine == 1, 'Refresh form keeps its marker and updates the source snapshot');
                $refreshed = $formData->form->rec->sourceData['cargoSummary'][0];
                $this->check($formData->form->rec->cargo === $cargoDescription && $formData->form->rec->lineNotes === ''
                    && $refreshed['description'] === '' && $refreshed['notes'] === $bill->notes
                    && $refreshed['packaging'] === '2 палет' && $refreshed['totalWeight'] == 640 && $refreshed['volume'] == 1.25,
                    'Native refresh retains the common description, clears notes and updates raw source cargo');
                $this->check($formData->form->rec->sourceData['cargoSource'][0]['totalWeight'] == 640
                    && $formData->form->rec->sourceData['cargoSource'][0]['volume'] == 1.25,
                    'Native refresh retains the actual source totals as its baseline');
                $refreshedTrip = $formData->form->rec;
            } finally {
                Request::pop('transportTest');
            }
            $trip = $refreshedTrip;
            $trip->state = 'draft';
            $trips->save($trip);
            $trip->state = 'pending';
            $bill->state = 'active';
            $this->check($bills->haveRightFor('edit', $bill), 'Native activation retains edit rights until the draft is saved');
            $this->check((bool) $bills->save($bill), 'Waybill activates before the trip is issued');
            $bill = $bills->fetch($bill->id, '*', false);
            $this->check(empty($bill->tripSheetId), 'No manual trip selection is needed');
            $bad = clone $trip;
            $bad->vehicleId = null;
            $this->check(isset($bills->getTripBindingErrors($bad)['vehicleId']), 'Trip vehicle must match the activated external cargo');
            $bad = clone $trip;
            $bad->transportType = 'own';
            $this->check(isset($bills->getTripBindingErrors($bad)['transportType']), 'External cargo cannot be declared as own-account carriage');
            $bad = clone $trip;
            $bad->cargo = '';
            $this->check(!$trips->save($bad, 'state'), 'Issuing requires the common cargo description');
            $this->check($trips->haveRightFor('edit', $trip), 'Native issue command retains rights while the saved trip is still a draft');
            $this->check((bool) $trips->save($trip, 'state'), 'A refreshed trip with a common description and blank per-document descriptions can be issued');
            $trip = $trips->fetch($trip->id, '*', false);
            $bill = $bills->fetch($bill->id, '*', false);
            $this->check($bill->tripSheetId == $trip->id, 'Trip issue automatically links the included waybill');
            $bad = clone $trip;
            unset($bad->id);
            $this->check(isset($bills->getTripBindingErrors($bad)['transportDocuments']), 'A waybill cannot be assigned to two issued trip sheets');
            trans_Waybills::attachIssuedTrip($trip);
            $this->check($bills->fetchField($bill->id, 'tripSheetId', false) == $trip->id, 'Repeating trip attachment is idempotent');
            $lineDetail = trans_LineDetails::fetch($lineDetail->id, '*', false);
            $this->check(!$bills->haveRightFor('changeline', $bill) && !trans_LineDetails::haveRightFor('delete', $lineDetail), 'Issued-trip cargo cannot be moved or removed');
            $this->check(!empty($bill->issuedOn) && strpos($bill->tripReference, 'TEST №') === 0, 'Issued waybill stores trip number and date');
            $this->check(!$bills->haveRightFor('edit', $bill), 'Issued waybill is read only');
            $bill->senderName = 'tampered';
            $bills->save($bill, 'senderName');
            $bill = $bills->fetch($bill->id, '*', false);
            $this->check($bill->senderName === 'ТЕСТОВ ИЗПРАЩАЧ', 'Partial saves cannot rewrite issued contents');
            $issuedManifest = $trip->cargoManifest;
            $issuedSource = $trip->sourceData;
            $issuedCargo = $trip->cargo;
            $trip->cargoManifest = 'tampered';
            $trip->cargo = 'tampered';
            $trip->sourceData['cargoSummary'][0]['description'] = 'tampered';
            $trip->sourceData['cargoSummary'][0]['totalWeight'] = 1;
            $trip->sourceData['cargoSource'][0]['volume'] = 99;
            $trips->save($trip, 'cargoManifest,cargo,sourceData');
            $trip = $trips->fetch($trip->id, '*', false);
            $this->check($trip->cargoManifest === $issuedManifest && $trip->cargo === $issuedCargo
                && $trip->sourceData === $issuedSource,
                'Issued common description, empty appendix and raw source snapshot are immutable');
            core_Users::sudo(core_Users::ANONYMOUS_USER);
            try {
                $this->check(!$bills->haveRightFor('single', $bill) && !$bills->haveRightFor('activate', $bill), 'Anonymous access is denied');
            } finally {
                core_Users::exitSudo();
            }
            $bills->reject($bill->id);
            $this->check(!trans_LineDocuments::getTripDefaults($line->id)['sourceData']['waybills'], 'Rejected waybill leaves the next snapshot');
            $this->check((bool) $bills->restore($bill->id), 'Issued waybill can be restored without rewriting it');

            $stores = store_Stores::getQuery()->fetchAll();
            $product = cat_Products::fetch("#canStore = 'yes' AND #state = 'active'");
            expect(count($stores) >= 2 && $product, 'Two local stores and a stock product are required');
            $storeIds = array_keys($stores);
            $transfer = (object) array('fromStore' => $storeIds[0], 'toStore' => $storeIds[1], 'valior' => '2026-11-02',
                'deliveryTime' => '2026-11-02 08:00:00', 'deliveryOn' => '2026-11-02 10:00:00',
                'folderId' => store_Stores::forceCoverAndFolder($storeIds[0]), 'state' => 'draft', 'lineId' => $line->id,
                'note' => 'TEST - Товарителница');
            store_Transfers::save($transfer);
            $transfer = store_Transfers::fetch($transfer->id, '*', false);
            $detail = (object) array('transferId' => $transfer->id, 'productId' => $product->id, 'packagingId' => $product->measureId,
                'quantity' => 7, 'quantityInPack' => 1);
            store_TransfersDetails::save($detail);
            trans_LineDetails::sync($line->id, $transfer->containerId);
            $source = trans_LineDocuments::getShipmentData(doc_Containers::getDocument($transfer->containerId), $line->id);
            $this->check(strpos($source['loadingPlace'], $stores[$storeIds[0]]->name) !== false
                && strpos($source['unloadingPlace'], $stores[$storeIds[1]]->name) !== false, 'Warehouse transfer includes both store names');
            $this->check(type_Table::toArray($source['goods'])[0]->quantity == 7, 'Warehouse product quantity is copied');
            $compactSource = trans_LineDocuments::getShipmentData(doc_Containers::getDocument($transfer->containerId), $line->id, false);
            $this->check(!type_Table::toArray($compactSource['goods'])
                && $compactSource['totalWeight'] === $source['totalWeight'] && $compactSource['volume'] === $source['volume'],
                'Compact warehouse reads omit product rows while preserving transport totals');
            $mixed = trans_LineDocuments::getTripDefaults($line->id);
            $this->check($mixed['transportType'] === 'mixed' && strpos($mixed['sourceData']['own']['transportDocuments'], '#Str') !== false
                && strpos($mixed['sourceData']['own']['transportDocuments'], '#Wb') === false
                && strpos($mixed['sourceData']['own']['cargo'], '#Wb') === false, 'Mixed trip keeps own and external cargo distinguishable');
            $this->check(count($mixed['sourceData']['cargoSummary']) === 2 && $mixed['cargo'] === ''
                && $mixed['sourceData']['cargoSource'] === $mixed['sourceData']['cargoSummary'],
                'Mixed trip retains each source document without guessing a cargo description');
            $summaryTrip = clone $trip;
            foreach (arr::make('id,containerId,createdOn,createdBy,modifiedOn,modifiedBy,issuedOn,issuedBy,issuerName') as $field) unset($summaryTrip->{$field});
            foreach ($mixed as $field => $value) $summaryTrip->{$field} = $value;
            $summaryTrip->cargo = $cargoDescription;
            $summaryTrip->state = 'draft';
            $this->check((bool) $trips->save($summaryTrip), 'Mixed draft saves one reviewed cargo description');
            $summaryTrip = $trips->fetch($summaryTrip->id, '*', false);
            $this->check($summaryTrip->cargo === $cargoDescription && $summaryTrip->cargoManifest === ''
                && $summaryTrip->sourceData['own']['cargo'] === $cargoDescription
                && $summaryTrip->sourceData['hash'] === $mixed['sourceData']['hash']
                && $summaryTrip->sourceData['cargoSummary'] === $mixed['sourceData']['cargoSummary'],
                'Saving retains the common description for the order and preserves raw provenance without an appendix');
            $previewTrip = clone $summaryTrip;
            // Preview the order using an in-memory issued state; no second trip is issued.
            $previewTrip->issuedOn = $trip->issuedOn;
            $previewTrip->state = 'pending';
            $previewData = $trips->prepareSingle((object) array('rec' => $previewTrip));
            $previewHtml = $trips->renderSingle($previewData)->getContent();
            $this->check(strpos($previewHtml, 'trip-cargo-summary') === false
                && substr_count($previewHtml, '#' . store_Transfers::getHandle($transfer->id)) === 1
                && substr_count($previewHtml, '#Wb' . $bill->id) === 1, 'Simplified trip prints each document reference once without a cargo table');
            $this->check(substr_count($previewHtml, $cargoDescription) === 1
                && strpos($previewHtml, '02.11.2026') !== false && strpos($previewHtml, 'ТЕСТ: външен товар') === false
                && strpos($previewHtml, 'WB-TEST') === false && strpos($previewHtml, 'page-break-before:always') === false
                && strpos($previewHtml, 'Маршрут и товари към пътен лист') === false
                && strpos($previewHtml, $line->description) === false && strpos($previewHtml, $bill->notes) === false,
                'Simplified trip prints the common description and dates without goods dumps, copied notes or forced appendices');
            $movementCases = array(
                array('places' => array(array('Склад А', 'Клиент Б')), 'rows' => 4),
                array('places' => array(array('Склад А', 'Клиент Б'), array('Клиент Б', 'Клиент В'),
                    array('Клиент В', 'Клиент Г'), array('Клиент Г', 'Клиент Д'), array('Клиент Д', 'Клиент Е'),
                    array('Склад А', 'Клиент Б'), array('', '')), 'rows' => 7));
            foreach ($movementCases as $case) {
                $movementPreview = clone $previewTrip;
                $movementPreview->sourceData['cargoSummary'] = array_map(function ($places) {
                    return array('loadingPlace' => $places[0], 'unloadingPlace' => $places[1]);
                }, $case['places']);
                $movementData = $trips->prepareSingle((object) array('rec' => $movementPreview));
                $movementHtml = $trips->renderSingle($movementData)->getContent();
                preg_match('~<tr>\s*<th>Водач</th>\s*<th>Маршрут от — до</th>.*?</table>~s', $movementHtml, $movementTable);
                $this->check(!empty($movementTable[0]) && substr_count($movementTable[0], '<tr') - 1 === $case['rows'],
                    'The rendered movement table has at least four rows and one extra row beyond distinct nonempty places: ' . $case['rows']);
            }
            foreach (array(2, 3) as $legacyVersion) {
                $legacyPreview = clone $previewTrip;
                $legacyPreview->sourceData['manifestVersion'] = $legacyVersion;
                foreach ($legacyPreview->sourceData['cargoSummary'] as &$item) $item['description'] = $cargoDescription;
                unset($item);
                $legacySource = $legacyPreview->sourceData;
                $legacyData = $trips->prepareSingle((object) array('rec' => $legacyPreview));
                $legacyHtml = $trips->renderSingle($legacyData)->getContent();
                preg_match('~<table\b[^>]*\btrip-cargo-summary\b[^>]*>.*?<tbody>(.*?)</tbody></table>~s', $legacyHtml, $legacyTable);
                $this->check(!empty($legacyTable[1]) && substr_count($legacyTable[1], '<tr') === 2
                    && $legacyPreview->sourceData === $legacySource,
                    'Previously issued compact trips retain their saved cargo table: ' . $legacyVersion);
            }
            Request::push(array('tripView' => 'order'), 'transportOrderTest');
            try {
                $ownTrip = clone $previewTrip;
                $ownTrip->transportType = 'own';
                $ownTrip->transportDocuments = $mixed['sourceData']['own']['transportDocuments'];
                $ownTrip->cargo = $summaryTrip->sourceData['own']['cargo'];
                $ownData = $trips->prepareSingle((object) array('rec' => $ownTrip));
                $ownHtml = $trips->renderSingle($ownData)->getContent();
                $this->check(strpos($ownHtml, '#' . store_Transfers::getHandle($transfer->id)) !== false
                    && strpos($ownHtml, '#Wb') === false, 'Own-account order retains its warehouse document references');
                $mixedOrderData = $trips->prepareSingle((object) array('rec' => $previewTrip));
                $mixedOrderHtml = $trips->renderSingle($mixedOrderData)->getContent();
                $this->check(strpos($mixedOrderHtml, '#' . store_Transfers::getHandle($transfer->id)) !== false
                    && strpos($mixedOrderHtml, '#Wb') === false, 'Mixed order includes only own-account document references');
                $this->check(substr_count($mixedOrderHtml, $cargoDescription) === 1
                    && strpos($mixedOrderHtml, 'trip-cargo-summary') === false,
                    'Mixed own-account order uses the reviewed common description without a cargo table');
            } finally {
                Request::pop('transportOrderTest');
            }
            $sourceBill = clone $bill;
            foreach (arr::make('id,containerId,createdOn,createdBy,modifiedOn,modifiedBy,issuedOn,tripSheetId,tripReference') as $field) unset($sourceBill->{$field});
            $sourceBill->state = 'draft';
            $sourceBill->sourceContainerId = $transfer->containerId;
            $sourceBill->goods = $source['goods'];
            $sourceBill->sourceContainerId = null;
            $sourceBill->goods = $goods;
            $this->check((bool) $bills->save($sourceBill), 'A second external cargo can be included in the same line');
            $represented = trans_LineDocuments::getTripDefaults($line->id);
            $this->check($represented['sourceData']['own']['transportDocuments'] !== ''
                && substr_count($represented['transportDocuments'], '#' . store_Transfers::getHandle($transfer->id)) === 1,
                'Own warehouse documents remain separate and occur only once');
            $this->check(substr_count($represented['transportDocuments'], '#Wb' . $sourceBill->id) === 1, 'Each external cargo is copied once from the line');
            $detailRec = trans_LineDetails::fetch(array('#lineId = [#1#] AND #containerId = [#2#]', $line->id, $sourceBill->containerId));
            trans_LineDetails::delete($detailRec->id);
            $this->check(!isset(trans_LineDocuments::getTripDefaults($line->id)['sourceData']['waybills'][$sourceBill->id]), 'A removed waybill cannot leak into the trip through a separate query');
            $this->check(!$bills->fetchField($sourceBill->id, 'lineId', false), 'Removal clears the current line assignment');
            $bills->reject($sourceBill->id);

            $company = crm_Companies::fetch('#folderId IS NOT NULL AND #state = \'active\'');
            expect($company && crm_Companies::haveRightFor('single', $company), 'Accessible company folder required');
            $this->check(trans_Waybills::canAddToFolder($company->folderId), 'Waybills can start independently in a company folder');
            Request::push(array('folderId' => $company->folderId, 'senderCompanyId' => $company->id), 'transportTest');
            try {
                $billForm = $bills->prepareEditForm((object) array())->form;
                $this->check($billForm->rec->senderName === $company->name && empty($billForm->rec->lineId), 'Company data fills an independent waybill draft');
                $this->check($billForm->getField('sourceContainerId')->input == 'none' && $billForm->getField('tripSheetId')->input == 'none', 'No warehouse-copy or manual trip selection is offered');
            } finally {
                Request::pop('transportTest');
            }
            $standalone = clone $sourceBill;
            foreach (arr::make('id,containerId,threadId,originId,lineId,issuedOn,tripSheetId,tripReference,createdOn,createdBy,modifiedOn,modifiedBy') as $field) unset($standalone->{$field});
            $standalone->folderId = $company->folderId;
            $standalone->state = 'draft';
            $this->check((bool) $bills->save($standalone), 'Independent waybill saves without a transport line');
            $standalone = $bills->fetch($standalone->id, '*', false);
            doc_Threads::doUpdateThread($standalone->threadId);
            $standalone->state = 'active';
            $this->check((bool) $bills->save($standalone, 'state'), 'A complete independent waybill may also activate before line assignment');
            $standalone = $bills->fetch($standalone->id, '*', false);
            $line->state = 'pending';
            trans_Lines::save($line, 'state');
            $this->check($bills->haveRightFor('changeline', $standalone), 'Independent waybill exposes the native Transport action');
            $changeForm = trans_plg_LinesPlugin::prepareChangeLineForm($bills, $standalone);
            $changeForm->rec->lineId = $line->id;
            $changeForm->rec->lineNotes = 'TEST - инструкции за външен склад';
            trans_plg_LinesPlugin::saveChangeLineForm($bills, $standalone, $changeForm, null);
            $standalone = $bills->fetch($standalone->id, '*', false);
            $this->check($standalone->lineId == $line->id && trans_LineDetails::fetch(array('#lineId = [#1#] AND #containerId = [#2#] AND #status = \'ready\'', $line->id, $standalone->containerId)), 'Native Transport action adds the cargo row');
            $assignedDefaults = trans_LineDocuments::getTripDefaults($line->id);
            $this->check(strpos(implode("\n", array_column($assignedDefaults['sourceData']['cargoSummary'], 'notes')), 'инструкции за външен склад') !== false,
                'Transport notes remain in the saved summary without expanding the cargo manifest');
            $this->check($standalone->threadId != $line->threadId, 'Line assignment preserves the independent document thread');
            $bills->reject($standalone->id);

            $lineData = trans_Lines::prepareSingle((object) array('rec' => trans_Lines::fetch($line->id, '*', false)));
            $lineHtml = trans_Lines::renderSingle($lineData)->getContent();
            $this->check(strpos($lineHtml, 'Товарителници') !== false && strpos($lineHtml, '#Wb' . $bill->id) !== false, 'Native line renders the external cargo group');
            $data = $bills->prepareSingle((object) array('rec' => $bill));
            $html = $bills->renderSingle($data)->getContent();
            $this->check(strpos($html, 'ТЕСТОВ ИЗПРАЩАЧ') !== false && strpos($html, 'WB-TEST') !== false, 'Native waybill single view renders');
            echo 'PASS ' . $this->checks . " checks\n";
            echo json_encode(array('line' => $line->id, 'waybill' => $bill->id, 'trip' => $trip->id,
                'summaryTrip' => $summaryTrip->id, 'vehicle' => $vehicle->id, 'transfer' => $transfer->id), JSON_UNESCAPED_UNICODE) . "\n";
            $passed = true;
        } catch (Throwable $error) {
            echo 'FAIL after ' . $this->checks . ' checks: ' . $error->getMessage() . "\n";
            echo $error->getTraceAsString() . "\n";
            return 1;
        } finally {
            if (!$passed || getenv('TRANS_DOCUMENT_PREVIEW') !== '1') {
                if (!empty($sourceBill->id)) $bills->reject($sourceBill->id);
                if (!empty($standalone->id)) $bills->reject($standalone->id);
                if (!empty($bill->id)) $bills->reject($bill->id);
                if (!empty($trip->id)) $trips->reject($trip->id);
                if (!empty($summaryTrip->id)) $trips->reject($summaryTrip->id);
                if (!empty($transfer->id)) store_Transfers::reject($transfer->id);
                if (!empty($line->id)) trans_Lines::reject($line->id);
                if (!empty($vehicle->id)) trans_Vehicles::reject($vehicle->id);
            }
            core_Cls::shutdown();
            core_Users::exitSudo();
        }

        return 0;
    }
}
