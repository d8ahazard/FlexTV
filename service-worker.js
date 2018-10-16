importScripts('./js/lib/support/12_cache-polyfill.js');
self.addEventListener('install', function (e) {
	e.waitUntil(
		caches.open('phlex').then(function (cache) {
			console.log("opening caches?");
			return cache.addAll([
                './js/cssrelpreload.js',
                './js/homeBase/homebase.js',
                './js/homeBase/utilities.js',
                './js/lib/support/00_run_prettify.js',
                './js/lib/support/01_jquery-ui.min.js',
                './js/lib/support/02_clipboard.min.js',
                './js/lib/support/03_jquery.simpleWeather.min.js',
                './js/lib/support/04_snackbar.min.js',
                './js/lib/support/06_arrive.min.js',
                './js/lib/support/08_ripples.min.js',
                './js/lib/support/10_swiped.min.js',
                './js/lib/support/12_cache-polyfill.js',
                './js/lib/ui/00_jquery-3.3.1.min.js',
                './js/lib/ui/01_tether.min.js',
                './js/lib/ui/02_bootstrap.min.js',
                './js/lib/ui/03_html5shiv.min.js',
                './js/lib/ui/04_lazyload.min.js',
                './js/lib/ui/07_material.min.js',
                './js/login.js',
                './js/main.js',
                './css/dark.css',
                './css/font/MaterialIcons.woff2',
                './css/font/Roboto-Black.ttf',
                './css/font/Roboto-BlackItalic.ttf',
                './css/font/Roboto-Bold.ttf',
                './css/font/Roboto-BoldItalic.ttf',
                './css/font/Roboto-Italic.ttf',
                './css/font/Roboto-Light.ttf',
                './css/font/Roboto-LightItalic.ttf',
                './css/font/Roboto-Medium.ttf',
                './css/font/Roboto-MediumItalic.ttf',
                './css/font/Roboto-Regular.ttf',
                './css/font/Roboto-Thin.ttf',
                './css/font/Roboto-ThinItalic.ttf',
                './css/font/muximuxfonts.eot',
                './css/font/muximuxfonts.svg',
                './css/font/muximuxfonts.ttf',
                './css/font/muximuxfonts.woff',
                './css/font-muximux.css',
                './css/homeBase.css',
                './css/lib/00_bootstrap.min.css',
                './css/lib/01_bootstrap-grid.min.css',
                './css/lib/02_material.css',
                './css/lib/03_snackbar.min.css',
                './css/lib/04_bootstrap-material-design.min.css',
                './css/lib/05_bootstrap-dialog.css',
                './css/lib/06_ripples.min.css',
                './css/lib/07_jquery-ui.min.css',
                './css/lib/08_bootstrap-slider.min.css',
                './css/lib/MaterialIcons.woff2',
                './css/loader_main.css',
                './css/main.css',
                './css/main_max_576.css',
                './css/main_max_768.css',
                './css/main_min_1200.css',
                './css/main_min_768.css',
                './css/main_min_992.css'
			]);
		})
	);
});

self.addEventListener('fetch', function (event) {
	console.log(event.request.url);
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