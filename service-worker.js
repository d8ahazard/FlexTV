importScripts('./js/lib/cache-polyfill.js');
importScripts('./cacheItems.js');

    self.addEventListener('install', function (e) {
        e.waitUntil(
            caches.open('flextv').then(function (cache) {
                console.log("opening caches?");
                return cache.addAll(cacheData);
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


function reload() {
	fetchData(true);
}