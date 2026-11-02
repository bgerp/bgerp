/**
 * ZTM browser checks. Requires a QA page blocking XHR/fetch and mocking $.ajax
 * before dashboard initialization; never run against a connected device.
 * Copyright 2026 Experta EOOD. License GPL 3.
 */
async function ztmDashboardCheck(expectedVersion) {
    if (!window.ztmQaNetworkBlocked || !Array.isArray(window.qaRegisters)) throw new Error('Blocked QA network required');
    var checks = 0;
    function check(ok,message) { if(!ok) throw new Error(message); checks++; }
    function delay(ms) { return new Promise(function(resolve){setTimeout(resolve,ms);}); }
    async function waitForCollapse() {
        var deadline = Date.now() + 5000;
        while (modal.querySelector('.collapsing') && Date.now() < deadline) await delay(50);
    }
    check(jQuery.fn.modal.Constructor.VERSION===expectedVersion,'Selected Bootstrap');
    check(jQuery.fn.jquery==='3.1.1','Preserved standalone jQuery');
    check(document.querySelectorAll('script[src*="jquery-"]').length===1,'One jQuery');
    check(document.querySelectorAll('script[src*="/bootstrap.min"],script[src*="/bootstrap.bundle.min"]').length===1,'One Bootstrap');
    check(document.querySelectorAll('script[src*="/popper.min"]').length===(expectedVersion==='5.3.8'?0:1),'Matching Popper');
    check(document.querySelectorAll('.rangeslider').length===4,'Four initialized range controls');
    check(document.querySelectorAll('#grid > .cont').length===6,'Six dashboard panels');
    check(document.querySelectorAll('#grid img').length===5,'Settings and four panel icons');
    check(document.getElementById('currentTime').textContent.includes(':'),'Clock initialized');
    check(document.getElementById('temperature').textContent==='24.5','Fractional display');
    var modal = document.getElementById('toolsModal');
    var open = document.querySelector('.optionsBtn');
    var close = modal.querySelector('[data-dismiss="modal"],[data-bs-dismiss="modal"]');
    check(open.getBoundingClientRect().width>=24 && open.getBoundingClientRect().height>=24,'Visible settings button');
    if(modal.classList.contains('show')) {close.click();await delay(30);}
    for(var cycle=0;cycle<3;cycle++) {
        open.click();await delay(40);
        check(modal.classList.contains('show'),'Modal opens '+cycle);
        check(document.querySelectorAll('.modal-backdrop').length===1,'One backdrop '+cycle);
        check(getComputedStyle(modal.querySelector('.modal-content')).color==='rgb(219, 223, 229)','Readable dark modal '+cycle);
        close.click();await delay(40);
        check(!modal.classList.contains('show'),'Modal closes '+cycle);
        check(document.querySelectorAll('.modal-backdrop').length===0,'Backdrop removed '+cycle);
    }
    open.click();await delay(40);
    for(var id of ['Two','Three','One']) {
        document.querySelector('a[href="#collapse'+id+'"]').click();await waitForCollapse();
        check(document.getElementById('collapse'+id).classList.contains('show'),'Expanded '+id);
        check(modal.querySelectorAll('.collapse.show').length===1,'Only one section '+id);
    }
    close.click();await delay(40);
    check(!document.body.classList.contains('modal-open'),'Scrolling restored');
    var inputs=Array.from(document.querySelectorAll('[data-rangeslider]'));
    var values=[25.5,75,80,35], keys=['hvac.adjust_temp_1','vent.op_setpoint_1','light.target_illum','blinds.position'], wire=[25,75,8000,35];
    qaRegisters.length=0;
    inputs.forEach(function(input,i){
        jQuery(input).val(values[i]).trigger('input').trigger('change');
        check(input.value===String(values[i]),'Range value '+i);
        check(input.parentElement.querySelector('output').textContent===String(values[i]),'Visible output '+i);
        check(qaRegisters.some(function(r){return r[keys[i]]===wire[i];}),'Preserved serialized value '+i);
    });
    check(document.documentElement.style.getPropertyValue('--rotation-speed')==='1.2s','Ventilation animation');
    inputs[1].disabled=true;jQuery(inputs[1]).rangeslider('update');
    check(inputs[1].parentElement.querySelector('.rangeslider--disabled')!==null,'Disabled slider');
    inputs[1].disabled=false;jQuery(inputs[1]).rangeslider('update');
    check(inputs[1].parentElement.querySelector('.rangeslider--disabled')===null,'Re-enabled slider');
    check(window.qaErrors.length===0,'No script errors: '+JSON.stringify(window.qaErrors));
    return {version:expectedVersion,width:window.innerWidth,checks:checks,deviceRequestsSent:0};
}
