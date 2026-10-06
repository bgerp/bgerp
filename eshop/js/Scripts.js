function copyValToPlaceholder()
{
	$('.updateonchange').bind('keyup', function() {
		var changeVal = $(this).attr("data-updateonchange");
		
		$placeholder = $(this).val();
		
		var element = $("input[name="+ changeVal +"]");
		if (element.length <= 0) return;
		
		element.attr("placeholder", $placeholder);
	});
	
	$('select[name=deliveryCountry]').trigger('change');
	
	$('select[name=deliveryCountry]').bind('change', function() {
		var changeVal = $(this).attr("data-updateonchange");
		
		var $placeholder = $('select[name=deliveryCountry] option:selected').text();
		
		var element = $("select[name="+ changeVal +"");
		if (element.length <= 0) return;
		
		element.attr("data-placeholder", $placeholder);
		element.select2();
	});
	
	$('.updateonchange').trigger('keyup');
}

function refreshInvoiceFields()
{
	//Триене на символи от формата за търсене
	$(document.body).on('change', 'select[name=makeInvoice]', function(e){
			var changeVal = $(this).attr("data-updateonchange");
		
		var $placeholder = $('select[name=deliveryCountry] option:selected').text();
		
		var element = $("select[name="+ changeVal +"");
		if (element.length <= 0) return;
		
		element.attr("data-placeholder", $placeholder);
		element.select2();
	});
}

/**
 * Динамична ширина на полето за количество
 */
function changeInputWidth()
{
	$('.option-quantity-input').each(function () {
		$(this).css( "width", 12 + $(this).val().length * 10);
	});
}


function render_changeInputWidth()
{
	changeInputWidth();
}

function productGallery() {
	const $gallery = $(".product-gallery");
	$(".main-image a").removeClass("fancybox").removeAttr('href');

	$gallery.each(function () {
		const $thumbs = $(this).find(".thumbnails img");
		const $mainImage = $(this).find(".main-image img");
		const $thumbsContainer = $(this).find(".thumbnails");

		// Ако няма снимки → нищо не правим
		if ($thumbs.length === 0) return;

		// Вземаме първата снимка
		const firstSrc = $thumbs.first().attr("src");

		// Слагаме active и задаваме main image
		$thumbs.first().addClass("active");
		$mainImage.attr("src", firstSrc);

		$thumbs.on("mouseover", function (e) {
			e.stopPropagation();

			const newSrc = $(this).attr("src");
			$mainImage.attr("srcset", newSrc);

			$thumbs.removeClass("active");
			$(this).addClass("active");
		});
	});
}

function eshopActions() {
	changeInputWidth();
	if ($(".product-gallery").length) productGallery();
	eshopParamFilter();


	// Добавяне/махане на артикул от любими
	$(document.body).on("click", '.favouritesBtn', function(event){

		var url = $(this).attr("data-url");
		if(!url) return;

		resObj = new Object();
		resObj['url'] = url;

		getEfae().process(resObj);
	});

	// Изтриване на ред от кошницата
	$(document.body).on("click", '.remove-from-cart', function(event){
		
		var url = $(this).attr("data-url");
	    if(!url) return;
	    
	    var cartId = $(this).attr("data-cart");
	    var data = {cartId:cartId};
	   
	    resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
	
	// Добавяне на артикул в кошницата
	$(document.body).on("click", '.eshop-btn', function(event){
		if ($(event.target).hasClass('order-btn')) {
			$(event.target).attr('disabled', 'disabled');
			$(event.target).css('opacity', 0.8);
		}
		
		var url = $(this).attr("data-url");
	    if(!url) return;
	    
	    var eshopProductId = $(this).attr("data-eshopproductpd");
	    var productId = $(this).attr("data-productid");
	    var packagingId = $(this).attr("data-packagingid");
	    var packQuantity = $("input[name=product" + productId + "-" + packagingId +"]").val();
	    
	    if(!packQuantity){
	    	packQuantity = 1;
	    }
	    
	    if(!$.isNumeric(packQuantity) || packQuantity < 1){
	    	$(this).addClass('inputError');
	    	return;
	    }
	    
	    var data = {eshopProductId:eshopProductId,productId:productId,packQuantity:packQuantity,packagingId:packagingId};
	    
	    resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	});
	
	// Време за изчакване
	var timeout1 = [];
	disableBtns();
	// Ъпдейт на кошницата след промяна на к-то
	$(document.body).on('keyup', ".option-quantity-input", function(e){
		$(this).removeClass('inputError');
		var packQuantity = $(this).val();
		
		var max = parseFloat($(this).attr("data-maxquantity"));
		var aboveMax = max && parseFloat(packQuantity) > parseFloat(max);

		var idProd = $(this).attr('name');

		if(aboveMax){
			var maxReachedText = $(this).attr("data-maxquantity-reached-text");
			clearTimeout(timeout1[idProd]);

			timeout1[idProd] = setTimeout(function(){
				render_showToast({timeOut: 100, text: maxReachedText, isSticky: true, stayTime: 8000, type: 'error'});
			}, 2000);
		}
		disableBtns();
		if(packQuantity && (!$.isNumeric(packQuantity) || packQuantity < 1 || aboveMax)){
			$(this).addClass('inputError');
			
		} else {
			$(this).removeClass('inputError');
			changeInputWidth();
			var url = $(this).attr("data-url");
		    if(!url) return;
		    var data = {packQuantity:packQuantity};

		    // След всяко натискане на бутон изчистваме времето на изчакване
			clearTimeout(timeout1[idProd]);

			// Правим Ajax заявката като изтече време за изчакване
			timeout1[idProd] = setTimeout(function(){
				resObj = new Object();
				resObj['url'] = url;
				getEfae().process(resObj, data);
			}, 2000);

		}
	});
	
	// Оцветяване на инпута, ако има грешка
	$(document.body).on('keyup', ".eshop-product-option", function(e){
		$(this).removeClass('inputError');
		
		var packQuantity = $(this).val();
		
		if(packQuantity && (!$.isNumeric(packQuantity) || packQuantity < 0)){
			$(this).addClass('inputError');
		}
	});

	// Бутоните за +/- да променят количеството
	$(document.body).on('click tap', ".btnUp, .btnDown",  function(){
		var data = {type:'error'};
		render_clearStatuses(data);
		
		var input = $(this).siblings('.option-quantity-input');

		var max = parseFloat(input.attr("data-maxquantity"));
		var val = parseFloat($(input).val());
		var step = $(this).hasClass('btnUp') ? 1 : -1;
		var valNew = parseFloat(val) + parseFloat(step);
        var update = $(input).hasClass('autoUpdate');

		if (valNew > 0 && (!max || step == -1 || (max && val + step <= max))) {
			$(input).val(valNew);
			disableBtns();
            if(update) {
			    $(input).css( "color", "green");
                $("#cart-view-table").css("cursor", "progress");
            }
			changeInputWidth();
		}
        
        if(update) {
            // Ръчно инвоукване на ивент на инпут полето
            input.keyup();
        }
	});




	$('.eshop-product .eshop-btn, .eshop-product-list .eshop-btn').on('click', function () {
		if ($(document.body).hasClass('commerce-theme')) return;
		if($('.eshop-product-option').hasClass('inputError')) return;
		var cart = $('#cart-external-status');
		if($('.eshop-product-list').length) {
			var imgtodrag = $(this).closest('.eshop-product-list').find('.eshop-product-image');
		} else {
			var imgtodrag = $('.product-image').eq(0);
		}
		var imgWidth = imgtodrag ? parseInt($(imgtodrag).css('width')) : 150;
		var imgHeight = imgtodrag ? parseInt($(imgtodrag).css('height')) : 150;
		if (imgtodrag) {
			var imgclone = imgtodrag.clone()
				.offset({
					top: imgtodrag.offset().top,
					left: imgtodrag.offset().left
				})
				.css({
					'opacity': '0.5',
					'position': 'absolute',
					'height': imgHeight,
					'width': imgWidth,
					'z-index': '100'
				})
				.appendTo($('body'))
				.animate({
					'top': cart.offset().top,
					'left': cart.offset().left,
					'width': imgWidth/2,
					'height': imgHeight/2,
				}, 1000, 'easeInOutExpo');

			imgclone.animate({
				'width': 0,
				'height': 0
			}, function () {
				$(this).detach()
			});
		}
	});
}

/**
 * Забраняване на бутоните от кошницата според количеството
 */
function disableBtns() {
	$(".option-quantity-input").each(function(){
		if ($(this).attr('data-maxquantity') && $(this).val() >= parseFloat($(this).attr('data-maxquantity'))) {
			$(this).siblings('.btnUp').addClass('quiet');
			$(this).siblings('.btnUp').css("pointer-events", "none");
		} else {
			$(this).siblings('.btnDown').removeClass('quiet');
			$(this).siblings('.btnDown').css("pointer-events", "auto");
		}
		if($(this).val() == 1) {
			$(this).siblings('.btnDown').addClass('quiet');
			$(this).siblings('.btnDown').css("pointer-events", "none");
		} else {
			$(this).siblings('.btnDown').removeClass('quiet');
			$(this).siblings('.btnDown').css("pointer-events", "auto");
		}
	});
}

/**
 * Забраняване на бутоните от кошницата според количеството
 */
function render_disableBtns(data)
{
	disableBtns();
}


/**
 * Добавяне на клас към елемент
 */
function render_addClass(data)
{
	var id = data.id;
    var cls = data.class;
	
	var element = $("#" + id);
	element.addClass(cls);
}

/**
 * Да скролира да данните за доставка
 */
function scrollToDetail(){
	if(sessionStorage.getItem('editedForm') == 1) {
		$(window).scrollTop($('.narrow #cart-view-order-info').offset().top - 12);
		sessionStorage.setItem('editedForm', 0);
	}
}


function afterSubmitDetails(){
	$(document.body).on('click', ".submitBtn", function(e){
		sessionStorage.setItem('editedForm', 1);
	});
}


/**
 * Филтър по параметри: изборите се натрупват и страницата се зарежда след кратка пауза
 */
function eshopParamFilter() {
	var timer = null;
	var refreshTimer = null;
	var refreshNum = 0;
	var pendingUrl = null;
	var initial = null;

	// Зарежда 2 s след последното действие във филтъра
	var schedule = function() {
		clearTimeout(timer);
		if (!pendingUrl) return;
		timer = setTimeout(function() {
			document.location = pendingUrl;
		}, 2000);
	};

	// Бройките и сивите стойности за натрупания избор - от същата страница, без да се презарежда
	var refresh = function(url) {
		clearTimeout(refreshTimer);
		var num = ++refreshNum;
		refreshTimer = setTimeout(function() {
			$.get(url, function(html) {
				if (num != refreshNum) return;

				// В отделен документ, за да не се теглят картинките от страницата
				var node = new DOMParser().parseFromString(html, 'text/html').querySelector('.eshop-param-filter');
				var box = $('.eshop-param-filter').first();
				if (!node || !box.length) return;
				var fresh = $(document.importNode(node, true));

				// Отворените и затворените секции остават, както ги е оставил посетителят
				box.find('details[data-filter-section]').each(function() {
					fresh.find('details[data-filter-section="' + $(this).attr('data-filter-section') + '"]').prop('open', this.open);
				});
				box.replaceWith(fresh);
			});
		}, 300);
	};

	// На фазата на прихващане, за да не стигне кликът до onclick-а на връзката, който зарежда веднага
	document.addEventListener('click', function(event) {
		if (!event.target.closest || !window.URL) return;
		var link = event.target.closest('.eshop-param-filter [data-filter-val]');
		if (!link) {

			// Отварянето на параметър или „още“ удължава чакането, за да се стигне до следващия избор
			if (event.target.closest('.eshop-param-filter')) schedule();

			return;
		}

		event.preventDefault();
		event.stopPropagation();

		// Кутията се подменя при опресняването, затова изборът се чете наново от нея
		var box = link.closest('.eshop-param-filter');
		var state = eshopParamFilterParse(box.getAttribute('data-pf'), box.getAttribute('data-pc'));
		if (initial === null) {

			// Началният избор в същия ред, в който се сглобява, за да се разпознае връщането към него
			initial = eshopParamFilterBuildPf(state) + '&' + eshopParamFilterBuildPc(state);
		}
		eshopParamFilterToggle(state, link.getAttribute('data-filter-var'), link.getAttribute('data-filter-key'), link.getAttribute('data-filter-val'));
		$(link).toggleClass('checked');

		var pf = eshopParamFilterBuildPf(state);
		var pc = eshopParamFilterBuildPc(state);
		box.setAttribute('data-pf', pf);
		box.setAttribute('data-pc', pc);

		var url = new URL(box.getAttribute('data-url'), document.location.href);
		if (pf.length) url.searchParams.set('pf', pf);
		if (pc.length) url.searchParams.set('pc', pc);

		var changed = (pf + '&' + pc != initial);
		$('#cmsNavigation').parent().toggleClass('eshop-param-filter-pending', changed);
		pendingUrl = changed ? url.toString() : null;
		schedule();
		refresh(url.toString());
	}, true);

	// При връщане назад от кеша на браузъра страницата не трябва да остане замъглена
	window.addEventListener('pageshow', function(event) {
		if (event.persisted && $('.eshop-param-filter-pending').length) document.location.reload();
	});
}


/**
 * Текущият избор от pf=tsvyat-p5.cherven-1a9634_dalzhina-p15.10 и pc=slug-4.slug-3
 */
function eshopParamFilterParse(pf, pc) {
	var state = {params: {}, groups: {}};
	(pf || '').split('_').forEach(function(part) {
		var slugs = part.split('.');
		var key = slugs.shift();
		var m = key.match(/(?:^|-)p(\d+)$/);
		if (!m) return;
		state.params[m[1]] = {key: key, vals: slugs.filter(function(v) { return v.length; })};
	});
	(pc || '').split('.').forEach(function(part) {
		var m = part.match(/(?:^|-)(\d+)$/);
		if (m) state.groups[m[1]] = part;
	});

	return state;
}


/**
 * Добавя или маха стойност от избора
 */
function eshopParamFilterToggle(state, urlVar, key, val) {
	var m;
	if (urlVar == 'pc') {
		m = val.match(/(?:^|-)(\d+)$/);
		if (!m) return;
		if (state.groups[m[1]]) {
			delete state.groups[m[1]];
		} else {
			state.groups[m[1]] = val;
		}

		return;
	}

	m = (key || '').match(/(?:^|-)p(\d+)$/);
	if (!m) return;
	var param = state.params[m[1]] || (state.params[m[1]] = {key: key, vals: []});
	var pos = param.vals.indexOf(val);
	if (pos >= 0) {
		param.vals.splice(pos, 1);
	} else {
		param.vals.push(val);
	}
}


/**
 * Изборът по параметри за URL-то - параметрите по ид, както в cat_products_ParamFilter::buildUrlValue()
 */
function eshopParamFilterBuildPf(state) {
	var ids = Object.keys(state.params).sort(function(a, b) { return a - b; });
	var parts = [];
	ids.forEach(function(id) {
		var param = state.params[id];
		if (param.vals.length) parts.push(param.key + '.' + param.vals.join('.'));
	});

	return parts.join('_');
}


/**
 * Избраните категории за URL-то
 */
function eshopParamFilterBuildPc(state) {
	return Object.keys(state.groups).map(function(id) { return state.groups[id]; }).join('.');
}
