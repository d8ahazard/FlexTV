importScripts('./js/lib/cache-polyfill.js');
var request = new XMLHttpRequest();

request.onload = addListeners;
request.open("get", "datas.json", true);
request.send();

function addListeners(json) {
    self.addEventListener('install', function (e) {
        e.waitUntil(
            caches.open('phlex').then(function (cache) {
                console.log("opening caches?");
                return cache.addAll(json);
            })
        );
    });

    self.addEventListener('fetch', function (event) {
        event.respondWith(
            caches.match(event.request).then(function (response) {
                if (event.request.cache === 'only-if-cached' && event.request.mode !== 'same-origin') return;
                return response || fetch(event.request);
            })
        );
    });

}
function reload() {
	fetchData(true);
}