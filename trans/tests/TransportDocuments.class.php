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
        $line = $vehicle = $bill = $trip = $transfer = $sourceBill = $standalone = null;
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
                'loadingDate' => '2026-11-02', 'unloadingDate' => '2026-11-02', 'goods' => $goods, 'totalWeight' => 640,
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
            $bad = clone $bill;
            $bad->loadingLocationId = -1;
            $this->check(isset($bills->getValidationErrors($bad)['loadingLocationId']), 'An unrelated external store cannot be substituted');

            $defaults = trans_LineDocuments::getTripDefaults($line->id);
            $this->check($defaults['transportType'] === 'public' && $defaults['vehicleId'] == $vehicle->id
                && $defaults['driverId'] == key($drivers), 'Vehicle, driver and public transport mode come from the line');
            $this->check(strpos($defaults['route'], $bill->loadingPlace) !== false && strpos($defaults['route'], $bill->unloadingPlace) !== false,
                'Both ends of the cargo movement appear in the route');
            $this->check(strpos($defaults['cargoManifest'], '2 палет') !== false && strpos($defaults['cargoManifest'], '640 kg') !== false
                && strpos($defaults['cargoManifest'], '02.11.2026') !== false, 'Manifest includes quantities, weight and planned dates');
            $this->check($defaults['lineNotes'] === $line->description && isset($defaults['sourceData']['waybills'][$bill->id]), 'Line notes and cargo provenance are retained');
            Request::push(array('threadId' => $line->threadId), 'transportTest');
            try {
                $formData = $trips->prepareEditForm((object) array());
                $this->check($formData->form->rec->transportType === 'public' && $formData->form->rec->route === $defaults['route'], 'Native new-trip form receives defaults');
            } finally {
                Request::pop('transportTest');
            }
            $trip = (object) ($context + $defaults + array('series' => 'TEST', 'companyName' => 'ТЕСТОВ ПРЕВОЗВАЧ',
                'companyNumber' => '000000000', 'companyAddress' => 'Тестов адрес', 'authorizedBy' => 'Тестов ръководител', 'state' => 'draft'));
            $this->check((bool) $trips->save($trip), 'Trip draft is saved with the line snapshot');
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
            } finally {
                Request::pop('transportTest');
            }
            foreach (trans_LineDocuments::getTripDefaults($line->id) as $field => $value) $trip->{$field} = $value;
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
            $this->check($trips->haveRightFor('edit', $trip), 'Native issue command retains rights while the saved trip is still a draft');
            $this->check((bool) $trips->save($trip, 'state'), 'Refreshed trip can be issued');
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
            $trip->cargoManifest = 'tampered';
            $trips->save($trip, 'cargoManifest');
            $trip = $trips->fetch($trip->id, '*', false);
            $this->check(strpos($trip->cargoManifest, 'ТЕСТ: външен товар') !== false, 'Issued manifest is immutable');
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
            $mixed = trans_LineDocuments::getTripDefaults($line->id);
            $this->check($mixed['transportType'] === 'mixed' && strpos($mixed['sourceData']['own']['transportDocuments'], '#Str') !== false
                && strpos($mixed['sourceData']['own']['cargo'], 'ТЕСТ: външен товар') === false, 'Mixed trip keeps own and external cargo distinguishable');
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
                && substr_count($represented['cargoManifest'], '#' . store_Transfers::getHandle($transfer->id)) === 1,
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
            $this->check(strpos(trans_LineDocuments::getTripDefaults($line->id)['cargoManifest'], 'инструкции за външен склад') !== false, 'Transport notes flow through the line snapshot');
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
                'vehicle' => $vehicle->id, 'transfer' => $transfer->id), JSON_UNESCAPED_UNICODE) . "\n";
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
