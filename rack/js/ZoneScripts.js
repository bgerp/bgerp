// ===== SessionStorage lock + cooldown (само за текущия TAB) =====
var TM_LOCK_PREFIX = 'tm_lock_move_';
var TM_COOL_PREFIX = 'tm_cool_move_';

var TM_LOCK_TTL_MS      = 15000;
var TM_COOLDOWN_MS      = 700;

// След колко време да се каже, че сървърът се бави, и кога заявката се прекъсва
var TM_SLOW_MS          = 8000;
var TM_TIMEOUT_MS       = 60000;

// Опресняването на редовете след успешно действие: таймаут и брой опити
var TM_REFRESH_TIMEOUT_MS = 20000;
var TM_REFRESH_TRIES      = 3;

// Активните заявки се пазят и при повторно зареждане на скрипта по AJAX.
var tmPendingMoves = window.tmPendingMoves || {};

function lockKey(moveId) { return TM_LOCK_PREFIX + String(moveId); }
function coolKey(moveId) { return TM_COOL_PREFIX + String(moveId); }
function nowMs() { return Date.now(); }

function _isFreshTs(raw, ttl) {
	if (!raw) return false;
	var ts = parseInt(raw, 10);
	if (!ts) return false;
	return (nowMs() - ts) <= ttl;
}

function isLocked(moveId) {
	if (!moveId) return false;
	if (tmPendingMoves[moveId]) return true;

	var key = lockKey(moveId);
	var raw = sessionStorage.getItem(key);

	if (!_isFreshTs(raw, TM_LOCK_TTL_MS)) {
		sessionStorage.removeItem(key);
		return false;
	}
	return true;
}

function isInCooldown(moveId) {
	if (!moveId) return false;

	var key = coolKey(moveId);
	var raw = sessionStorage.getItem(key);

	if (!_isFreshTs(raw, TM_COOLDOWN_MS)) {
		sessionStorage.removeItem(key);
		return false;
	}
	return true;
}

function lock(moveId) {
	if (!moveId) return false;

	if (isLocked(moveId) || isInCooldown(moveId)) {
		console.log('Blocked (lock/cooldown): ' + moveId);
		return true;
	}

	sessionStorage.setItem(lockKey(moveId), String(nowMs()));
	console.log('Lock: ' + moveId);
	return false;
}

function unlock(moveId) {
	if (!moveId) return;
	sessionStorage.removeItem(lockKey(moveId));
	sessionStorage.setItem(coolKey(moveId), String(nowMs()));
	console.log('Unlock(+cooldown): ' + moveId);
}


// ===== _rid/_ts/_tab генерация + добавяне към URL =====
function tmRandId() {
	return Date.now().toString(36) + '_' + Math.random().toString(16).slice(2);
}

function tmGetTabId() {
	var tabId = sessionStorage.getItem('tm_tab_id');
	if (!tabId) {
		tabId = 'tab_' + tmRandId();
		sessionStorage.setItem('tm_tab_id', tabId);
	}
	return tabId;
}


// ===== bind-ове =====
function zoneActions() {

	function getMoveId($btn) {
		var mid = $btn.attr('data-moveid') || $btn.data('moveid');
		if (mid == null || mid === '') return null;
		return String(mid);
	}

	$(document.body)
		.off('pointerdown.toggleMovement', '.toggle-movement')
		.on('pointerdown.toggleMovement', '.toggle-movement', function (e) {
			var $btn = $(this);
			var moveId = getMoveId($btn);

			if (moveId && (isLocked(moveId) || isInCooldown(moveId))) {
				e.preventDefault();
				e.stopImmediatePropagation();
				return false;
			}

		});

	$(document.body)
		.off('click.toggleMovement', '.toggle-movement')
		.on('click.toggleMovement', '.toggle-movement', function (e) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var $btn   = $(this);
			var $clicked = $btn;
			var moveId = getMoveId($btn);

			if (moveId) {
				if (lock(moveId)) return false;
			}

			var divId = $btn.closest('div.rowsContainerClass').attr('id');
			var url   = $btn.attr('data-url');
			if (!url) {
				if (moveId) unlock(moveId);
				return false;
			}

			// <-- ТУК е ключът: добавяме ги в URL, за да ги виждаш в сървърните логове
			var rid = tmRandId();
			var ts  = String(Date.now());
			var tab = tmGetTabId();

			if (moveId) {
				tmPendingMoves[moveId] = true;
				$btn = $btn.add($('.toggle-movement[data-moveid="' + moveId + '"]'));
			}
			$btn.prop('disabled', true).addClass('is-busy');

			getEO().isWaitingResponse = true;
			getEfae().waitPeriodicAjaxCall = 7;

			console.log('Call: ' + url, 'moveId=' + moveId, '_rid=' + rid, '_ts=' + ts, '_tab=' + tab);

			// Асинхронно, за да може бутонът да върти и да се покаже грешка при бавен сървър
			var request;
			try {
				request = getEfae().process({ url: url }, { _rid: rid, _ts: ts, _tab: tab, divId: divId });
			} catch (err) {
				tmReleaseBtn(moveId, $btn);
				throw err;
			}
			if (!request) {
				tmReleaseBtn(moveId, $btn);
				return false;
			}

			var $slowStatus;
			var slowTimer = setTimeout(function () {
				// Информацията за изчакване не трябва да потиска предупрежденията при HTTP 503.
				$slowStatus = tmShowStatus('Сървърът се бави - изчакайте, движението се обработва', 'notice');
			}, TM_SLOW_MS);

			var abortTimer = setTimeout(function () {
				request.abort();
			}, TM_TIMEOUT_MS);

			request.always(function () {
				clearTimeout(slowTimer);
				clearTimeout(abortTimer);
				if ($slowStatus) {
					$slowStatus.closest('.toast-item-wrapper').add($slowStatus).stop(true, true).remove();
				}
			}).done(function (res) {
				if (tmHasRenderFunc(res, 'movementDone')) {
					// Действието е записано - отметка, а бутоните остават неактивни до новите редове
					$btn.removeClass('is-busy');
					$clicked.addClass('is-done');
					tmRefreshAfterDone(moveId, $clicked, 1);
				} else {
					tmRefreshRows(function () {
						tmReleaseBtn(moveId, $btn);
					});
				}
			}).fail(function (xhr, textStatus) {
				tmReleaseBtn(moveId, $btn);

				// Заявката е прекъсната от презареждане на страницата
				if (getEO().isReloading) return;

				// При 503 общият обработчик показва съобщението на сървъра
				if (xhr.status != 503) {
					var text = (textStatus == 'abort') ? 'Няма отговор от сървъра' : 'Грешка при обработката на движението';
					if (xhr.status) {
						text += ' (' + xhr.status + ')';
					}
					tmShowStatus(text + '. Проверете състоянието му преди да опитате отново!', 'error');
				}
			});

			return false;
		});
}


// Има ли в AJAX отговора извикване на дадената render функция
function tmHasRenderFunc(res, func) {
	if (!res || typeof res.length == 'undefined') return false;

	for (var i = 0; i < res.length; i++) {
		if (res[i] && res[i].func == func) return true;
	}

	return false;
}


// Обновява веднага редовете на терминала, без да чака следващото периодично обновяване
function tmRefreshRows(onDone) {
	var name = 'rack_Zones_RefreshRows';
	var efae = getEfae();
	var sub = efae.subscribedArr ? efae.subscribedArr[name] : null;
	var request = null;

	if (sub && sub['url']) {
		var subscribedObj = {};
		subscribedObj[name] = sub['url'];
		efae.lastTimeArr[name] = new Date();
		request = efae.process(subscribedObj);
	}

	if (!request) {
		onDone();
		return;
	}

	var abortTimer = setTimeout(function () {
		request.abort();
	}, TM_REFRESH_TIMEOUT_MS);

	request.always(function () {
		clearTimeout(abortTimer);
		onDone();
	});
}


// След успешно действие: бутонът се отключва едва когато новите редове са на екрана
function tmRefreshAfterDone(moveId, $btn, attempt) {
	tmRefreshRows(function () {
		// Редовете са подменени (от тази или от периодичната заявка) - старият бутон вече го няма
		if (!$.contains(document.documentElement, $btn[0])) {
			tmReleaseBtn(moveId, $());
			return;
		}

		if (attempt < TM_REFRESH_TRIES) {
			setTimeout(function () {
				tmRefreshAfterDone(moveId, $btn, attempt + 1);
			}, 2000);
			return;
		}

		// Старият бутон остава неактивен, но движението се освобождава за редовете, които ще дойдат по-късно
		if (moveId) {
			delete tmPendingMoves[moveId];
			unlock(moveId);
		}
		tmShowStatus('Движението е записано, но екранът не се обнови. Обновете страницата!', 'warning');
	});
}


// Освобождава и бутоните, подменени междувременно от AJAX обновяване.
function tmReleaseBtn(moveId, $btn) {
	if (moveId) {
		delete tmPendingMoves[moveId];
		unlock(moveId);
		$btn = $btn.add($('.toggle-movement[data-moveid="' + moveId + '"]'));
	}
	$btn.prop('disabled', false).removeClass('is-busy');
}


// Показва съобщение веднага; общият обработчик на AJAX грешки не добавя второ, ако вече има такова
function tmShowStatus(text, type) {
	if ($().toastmessage) {
		return $().toastmessage('showToast', {
			text: text, sticky: type == 'error', stayTime: 6000, type: type,
			inEffectDuration: 800, position: 'bottom-right'
		});
	}
	return $('<div>').addClass('statuses-message statuses-' + type + ' connection-' + type + '-status')
		.text(text).appendTo('#statuses');
}


// ===== AJAX callback =====

// Сървърът потвърждава успешно действие; бутонът се обработва в обработчика на заявката
function render_movementDone(data) {
}


function render_enableBtn(data){
	var moveId = (data && data.moveId != null) ? String(data.moveId) : null;

	if (moveId) {
		// Активната заявка освобождава движението в своя always обработчик.
		if (!tmPendingMoves[moveId]) tmReleaseBtn(moveId, $());
	} else {
		console.log('render_enableBtn without moveId (no unlock)');
	}
}
