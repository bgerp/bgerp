/** Browser fixtures for the optional Hotkeys/Wheelzoom copies. GPL 3, 2026 Experta EOOD. */
async function checkOptionalWidgets(modern, regressions) {
    var checks=0;
    function check(ok,message){if(!ok)throw Error(message);checks++;}
    function key(target,type,name,code,keyCode,extra){target.dispatchEvent(new KeyboardEvent(type,Object.assign({key:name,code:code,keyCode:keyCode,which:keyCode,bubbles:true,cancelable:true},extra)));}
    function press(target,name,code,keyCode,extra){key(target,'keydown',name,code,keyCode,extra);key(target,'keyup',name,code,keyCode,extra);}
    var target=document.getElementById('hotkeys-target');
    hotkeys.unbind();hotkeys.setScope('all');var hits=0;
    hotkeys('q',function(){hits++;});
    press(target,'q','KeyQ',81);check(hits===1,'Plain shortcut');
    ['hotkeys-input','hotkeys-area','hotkeys-edit'].forEach(function(id){press(document.getElementById(id),'q','KeyQ',81);check(hits===1,'Editable field filter '+id);});
    hotkeys('ctrl+shift+k',function(){hits+=10;});
    press(target,'Control','ControlLeft',17,{ctrlKey:true});
    key(target,'keydown','Control','ControlLeft',17,{ctrlKey:true});
    key(target,'keydown','Shift','ShiftLeft',16,{ctrlKey:true,shiftKey:true});
    press(target,'K','KeyK',75,{ctrlKey:true,shiftKey:true});
    key(target,'keyup','Shift','ShiftLeft',16,{ctrlKey:true});key(target,'keyup','Control','ControlLeft',17,{});
    check(hits===11,'Modifier combination');
    hotkeys('x','qa',function(){hits+=100;});press(target,'x','KeyX',88);check(hits===11,'Inactive scope');
    hotkeys.setScope('qa');press(target,'x','KeyX',88);check(hits===111,'Active scope');
    hotkeys.deleteScope('qa');hotkeys.setScope('all');press(target,'x','KeyX',88);check(hits===111,'Scope removed');
    hotkeys.unbind('q');press(target,'q','KeyQ',81);check(hits===111,'Unbound shortcut');
    var up=0;hotkeys('z',{keyup:true,keydown:false},function(){up++;});press(target,'z','KeyZ',90);check(up===1,'Keyup option');
    hotkeys.unbind();var manual=0;hotkeys('q',function(){document.getElementById('hotkeys-count').textContent=String(++manual);});
    check(document.getElementById('hotkeys-input').value==='Кирилица','Cyrillic input preserved');
    var src='data:image/svg+xml;base64,'+btoa('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="240"><rect width="400" height="240" fill="#08a"/><circle cx="200" cy="120" r="90" fill="#fea"/></svg>');
    var holder=document.getElementById('widget-images');holder.innerHTML='';
    async function makeImage(){var img=new Image();img.style.width='200px';img.style.height='120px';img.src=src;holder.appendChild(img);await img.decode();return img;}
    function wheel(img,delta){var r=img.getBoundingClientRect();var e=new WheelEvent('wheel',{deltaY:delta,clientX:r.left+100,clientY:r.top+60,bubbles:true,cancelable:true});img.dispatchEvent(e);return e;}
    var img=await makeImage();var original=img.src;
    wheelzoom(img,{zoom:0.5,maxZoom:2});check(img.style.backgroundSize==='200px 120px','Initial dimensions');
    check(wheel(img,-1).defaultPrevented,'Wheel handled');check(img.style.backgroundSize==='300px 180px','Custom zoom');
    wheel(img,-1);check(img.style.backgroundSize==='400px 240px','Maximum zoom');
    for(var i=0;i<6;i++)wheel(img,1);check(img.style.backgroundSize==='200px 120px','Lower zoom bound');
    wheel(img,-1);img.dispatchEvent(new Event('wheelzoom.reset'));check(img.style.backgroundSize==='200px 120px','Reset');
    img.dispatchEvent(new Event('wheelzoom.destroy'));check(img.src===original,'Source restored');check(!wheel(img,-1).defaultPrevented,'Wheel listener removed');
    wheelzoom(img);wheel(img,-1);check(img.style.backgroundSize==='220px 132px','Reinitialize');img.dispatchEvent(new Event('wheelzoom.destroy'));
    if(modern){
        wheelzoom(img,{initialZoom:2,initialX:0,initialY:1});check(img.style.backgroundSize==='400px 240px','Initial zoom');check(img.style.backgroundPosition==='0px -120px','Initial position');img.dispatchEvent(new Event('wheelzoom.destroy'));
    }
    if(regressions){
        var img2=await makeImage();wheelzoom(holder.querySelectorAll('img'),{zoom:0.5,maxZoom:2});wheel(img,-1);wheel(img2,-1);
        check(img.style.backgroundSize==='300px 180px' && img2.style.backgroundSize==='300px 180px','NodeList receives options for every image');
        var r=img.getBoundingClientRect();img.dispatchEvent(new MouseEvent('mousedown',{clientX:r.left+100,clientY:r.top+60,bubbles:true,cancelable:true}));
        img.dispatchEvent(new Event('wheelzoom.destroy'));var before=img.style.backgroundPosition;
        document.dispatchEvent(new MouseEvent('mousemove',{clientX:r.left+130,clientY:r.top+80,bubbles:true,cancelable:true}));
        check(img.style.backgroundPosition===before,'Destroy during drag removes document listeners');
        document.dispatchEvent(new MouseEvent('mouseup',{bubbles:true}));img2.dispatchEvent(new Event('wheelzoom.destroy'));
    }
    return {modern:modern,checks:checks};
}

/** Regression fixed upstream in Hotkeys 4.0.9: single with a custom separator. */
function checkHotkeysSingle() {
    var calls = [];
    var options = {splitKey: '-', single: true, scope: 'qa-single'};
    hotkeys('ctrl-a', options, function () { calls.push('previous'); });
    hotkeys('ctrl-a', options, function () { calls.push('replacement'); });
    hotkeys.setScope('qa-single');
    try {
        var target = document.getElementById('hotkeys-target');
        target.dispatchEvent(new KeyboardEvent('keydown', {
            key: 'a', code: 'KeyA', keyCode: 65, which: 65, ctrlKey: true, bubbles: true
        }));
        target.dispatchEvent(new KeyboardEvent('keyup', {
            key: 'a', code: 'KeyA', keyCode: 65, which: 65, bubbles: true
        }));
        if (JSON.stringify(calls) !== '["replacement"]') {
            throw Error('single + splitKey invoked: ' + JSON.stringify(calls));
        }
        return {checks: 1, calls: calls};
    } finally {
        hotkeys.unbind();
        hotkeys.deleteScope('qa-single');
        hotkeys.setScope('all');
    }
}
