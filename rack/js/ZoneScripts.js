// ===== SessionStorage lock + cooldown (само за текущия TAB) =====
var TM_LOCK_PREFIX = 'tm_lock_move_';
var TM_COOL_PREFIX = 'tm_cool_move_';

var TM_LOCK_TTL_MS      = 15000;
var TM_COOLDOWN_MS      = 700;

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

			getEO().isReloading = true;
			getEO().isWaitingResponse = true;
			getEfae().waitPeriodicAjaxCall = 7;

			console.log('Call: ' + url, 'moveId=' + moveId, '_rid=' + rid, '_ts=' + ts, '_tab=' + tab);

			// data контекстът може да си остане само divId (не ти трябва за логовете)
			getEfae().process({ url: url }, { _rid: rid, _ts: ts, _tab: tab, divId: divId }, false);
			return false;
		});
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
