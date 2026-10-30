/** Runs with real canvas elements and no device or weather service calls. */
async function testSkycons() {
    var checks = 0, icons = new Skycons({color: '#489fc0'}), host = document.getElementById('qa-icons');
    function check(ok, label) { if (!ok) throw new Error(label); checks++; }
    function nonempty(canvas) {
        var pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        for (var i = 3; i < pixels.length; i += 4) if(pixels[i] !== 0) return true;
        return false;
    }
    if (!host) throw new Error('Missing #qa-icons test container');
    host.textContent = '';
    try {
        icons.add(undefined, Skycons.RAIN);
        check(icons.list.length === 0, 'Undefined canvas ignored');
        icons.set(undefined, Skycons.RAIN);
        check(icons.list.length === 0, 'Undefined canvas in set ignored');
        icons.add(null, Skycons.RAIN); icons.add('qa-missing-canvas', Skycons.RAIN);
        check(icons.list.length === 0, 'Null and missing ID ignored');
        var names = ['clear-day','clear-night','partly-cloudy-day','partly-cloudy-night','cloudy','rain','sleet','snow','wind','fog'];
        names.forEach(function(name, index) {
            var box = document.createElement('div'), canvas = document.createElement('canvas'), label = document.createElement('p');
            canvas.width = 128; canvas.height = 128; canvas.id = 'qa-sky-' + index;
            label.textContent = name; box.appendChild(canvas); box.appendChild(label); host.appendChild(box);
            icons.add(index % 2 ? canvas.id : canvas, name);
            check(nonempty(canvas), 'Visible pixels ' + name);
        });
        check(icons.list.length === 10, 'Ten drawings registered');
        var first = document.getElementById('qa-sky-0'), baseline = first.toDataURL();
        icons.set(first, Skycons.SNOW);
        check(first.toDataURL() !== baseline && icons.list.length === 10, 'Replace drawing without duplicate');
        icons.set(first, Skycons.CLEAR_DAY);
        check(first.toDataURL() === baseline, 'Deterministic keyframe');
        icons.play();
        check(icons.interval !== null, 'Animation started');
        await new Promise(function(resolve) { setTimeout(resolve, 150); });
        icons.pause();
        check(icons.interval === null, 'Animation stopped');
        check(first.toDataURL() !== baseline, 'Animated frame changed');
        var stopped = first.toDataURL();
        await new Promise(function(resolve) { setTimeout(resolve, 80); });
        check(first.toDataURL() === stopped, 'Stopped frame stays unchanged');
        icons.play(); icons.play(); icons.pause();
        check(icons.interval === null, 'Restart/pause cycle');
        icons.remove(first); icons.remove('qa-sky-1');
        check(icons.list.length === 8, 'Remove by DOM and ID');
        return {checks: checks};
    } finally { icons.pause(); }
}
