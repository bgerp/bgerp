// ===== SessionStorage lock + cooldown (само за текущия TAB) =====
var TM_LOCK_PREFIX = 'tm_lock_move_';
var TM_COOL_PREFIX = 'tm_cool_move_';

var TM_LOCK_TTL_MS      = 15000;
var TM_COOLDOWN_MS      = 700;

// След колко време да се каже, че сървърът се бави, и кога заявката се прекъсва
var TM_SLOW_MS          = 8000;
var TM_TIMEOUT_MS       = 60000;

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

			$btn.addClass('is-busy');
		});

	$(document.body)
		.off('click.toggleMovement', '.toggle-movement')
		.on('click.toggleMovement', '.toggle-movement', function (e) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var $btn   = $(this);
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

			$btn.prop('disabled', true).addClass('is-busy');

			getEO().isWaitingResponse = true;
			getEfae().waitPeriodicAjaxCall = 7;

			console.log('Call: ' + url, 'moveId=' + moveId, '_rid=' + rid, '_ts=' + ts, '_tab=' + tab);

			// Асинхронно, за да може бутонът да върти и да се покаже грешка при бавен сървър
			var request = getEfae().process({ url: url }, { _rid: rid, _ts: ts, _tab: tab, divId: divId });
			if (!request) {
				tmReleaseBtn(moveId, $btn);
				return false;
			}

			var slowTimer = setTimeout(function () {
				tmShowStatus('Сървърът се бави - изчакайте, движението се обработва', 'warning');
			}, TM_SLOW_MS);

			var abortTimer = setTimeout(function () {
				request.abort();
			}, TM_TIMEOUT_MS);

			request.done(function () {
				// Сървърът е приключил; не всеки отговор съдържа enableBtn (напр. повторно започване)
				tmReleaseBtn(moveId, $btn);
			}).fail(function (xhr, textStatus) {
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
				tmReleaseBtn(moveId, $btn);
			}).always(function () {
				clearTimeout(slowTimer);
				clearTimeout(abortTimer);
			});

			return false;
		});
}


// Отключва бутона, когато отговор от сървъра няма да дойде
function tmReleaseBtn(moveId, $btn) {
	if (moveId) unlock(moveId);
	$btn.prop('disabled', false).removeClass('is-busy');
}


// Показва съобщение веднага; общият обработчик на AJAX грешки не добавя второ, ако вече има такова
function tmShowStatus(text, type) {
	if (typeof showToast != 'undefined' && $().toastmessage) {
		showToast({ timeOut: 1, text: text, isSticky: type == 'error', stayTime: 6000, type: type });
	} else {
		render_html({ id: 'statuses', html: "<div class='statuses-message statuses-" + type + " connection-" + type + "-status'>" + text + "</div>", replace: false });
	}
}


// ===== AJAX callback =====
function render_enableBtn(data){
	var moveId = (data && data.moveId != null) ? String(data.moveId) : null;

	if (moveId) {
		unlock(moveId);

		$('.toggle-movement[data-moveid="' + moveId + '"]')
			.prop('disabled', false)
			.removeClass('is-busy');
	} else {
		console.log('render_enableBtn without moveId (no unlock)');
	}
}
