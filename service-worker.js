importScripts('./js/lib/cache-polyfill.js');
self.addEventListener('install', function (e) {
	e.waitUntil(
		caches.open('phlex').then(function (cache) {
			console.log("opening caches?");
			return cache.addAll([
                './js/homebase.js',
                './js/lib/bootstrap-iconpicker.min.js',
                './js/lib/bootstrap-material-design.min.js',
                './js/lib/cache-polyfill.js',
                './js/lib/cssrelpreload.js',
                './js/lib/hammer.min.js',
                './js/lib/iconset_muximux.js',
                './js/lib/jquery-3.3.1.min.js',
                './js/lib/jquery-ui.min.js',
                './js/lib/jquery.sharrre.js',
                './js/lib/jquery.simpleWeather.min.js',
                './js/lib/lazyload.min.js',
                './js/lib/material-kit.js',
                './js/lib/material-kit.js.map',
                './js/lib/material-kit.min.js',
                './js/lib/moment.min.js',
                './js/lib/muuri.min.js',
                './js/lib/nouislider.min.js',
                './js/lib/popper.min.js',
                './js/lib/run_prettify.js',
                './js/lib/snackbar.min.js',
                './js/lib/swiped.min.js',
                './js/login.js',
                './js/utilities.js',
                './css/darkTheme.css',
                './css/font/font-muximux.css',
                './css/font/muximuxfonts.eot',
                './css/font/muximuxfonts.svg',
                './css/font/muximuxfonts.ttf',
                './css/font/muximuxfonts.woff',
                './css/homeBase.css',
                './css/lib/bootstrap-iconpicker.min.css',
                './css/lib/jquery-ui.min.css',
                './css/lib/snackbar.min.css',
                './css/loadingAnimation.css',
                './css/main_max_576.css',
                './css/main_max_768.css',
                './css/main_min_1200.css',
                './css/main_min_768.css',
                './css/main_min_992.css',
                './css/material-kit_custom.css',
                './css/material-kit_custom.css.map',
                './css/material-kit_custom.min.css'
			]);
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