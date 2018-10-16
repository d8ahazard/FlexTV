<?php
require_once dirname(__FILE__) . '/php/vendor/autoload.php';
require_once dirname(__FILE__) . "/php/webApp.php";
require_once dirname(__FILE__) . '/php/util.php';
require_once dirname(__FILE__) . "/api.php";
write_log("-------NEW REQUEST RECEIVED-------", "ALERT");
scriptDefaults();
$defaults = checkDefaults();
if ($defaults['migrated'] ?? false) header("Refresh:0");
$forceSSL = $defaults['forceSSL'] ?? false;
if ($forceSSL === "false") $forceSSL = false;
write_log("ForceSSL is ".($forceSSL ? "Enabled" : "Disabled"));
if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") && $forceSSL) {
	$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	write_log("Force is on, redirecting to: $redirect","ERROR");
	if (isDomainAvailable($redirect)) {
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $redirect);
		exit();
	}
}

if (!session_started()) {
	session_start();
}

writeSessionArray($defaults);
$GLOBALS['time'] = microtime(true);
if (substr_count($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") && hasGzip()) ob_start("ob_gzhandler"); else ob_start();

$messages = checkFiles();
if (isset($_GET['logout'])) {
	clearSession();
	$url = fetchUrl();
	echo "<script language='javascript'>
                    document.location.href='$url';
                    </script>";
}
checkUpdate();
?>
<!doctype html>
<html>
<head>
	<title>Flex TV</title>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Plex Voice Automation for Google Assistant">
	<meta name="msapplication-config" content="./img/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	<meta name="apple-mobile-web-app-capable" content="yes">

	<link rel="apple-touch-icon" sizes="180x180" href="./img/apple-icon.png">
	<link rel="icon" type="image/png" href="./img/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="./img/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="./manifest.json">
	<link rel="mask-icon" href="./img/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="./img/favicon.ico">

	<style>
		.fade-in{
			-webkit-animation: fade-in 2s ease;
			-moz-animation: fade-in ease-in-out 2s both;
			-o-animation: fade-in ease-in-out 2s both;
			animation: fade-in 2s ease;
			visibility: visible;
			-webkit-backface-visibility: hidden;
		}

		@-webkit-keyframes fade-in{0%{opacity:0;} 100%{opacity:1;}}
		@-moz-keyframes fade-in{0%{opacity:0} 100%{opacity:1}}
		@-o-keyframes fade-in{0%{opacity:0} 100%{opacity:1}}
		@keyframes fade-in{0%{opacity:0} 100%{opacity:1}}

	</style>

    <link rel="stylesheet" href="css/loader_main.css">
    <link rel="stylesheet" href="css/font-muximux.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="./css/lib/000_fonts.css" rel="stylesheet">
    <link href="./css/lib/01_bootstrap-grid.min.css" rel="stylesheet">
    <link href="./css/lib/03_snackbar.min.css" rel="stylesheet">
    <link href="./css/lib/04_bootstrap-material-design.min.css" rel="stylesheet">
    <link href="./css/lib/05_bootstrap-dialog.css" rel="stylesheet">
    <link href="./css/lib/06_ripples.min.css" rel="stylesheet">
    <link href="./css/lib/07_jquery-ui.min.css" rel="stylesheet">
    <link href="./css/lib/08_bootstrap-slider.min.css" rel="stylesheet">
    <link href="./css/main.css" rel="stylesheet">
    <?php if ($_SESSION['darkTheme']) echo '<link href="./css/dark.css" rel="stylesheet">'.PHP_EOL?>
    <link rel="stylesheet" media="(max-width: 576px)" href="css/main_max_576.css">
    <link rel="stylesheet" media="(max-width: 768px)" href="css/main_max_768.css">
    <link rel="stylesheet" media="(min-width: 768px)" href="css/main_min_768.css">
    <link rel="stylesheet" media="(min-width: 992px)" href="css/main_min_992.css">
    <link rel="stylesheet" media="(min-width: 1200px)" href="css/main_min_1200.css">
    <link rel="stylesheet" href="./css/homeBase.css">

</head>

<body style="background-color:black">
	<div class="backgrounds">
        <div id="bgwrap">

        </div>
        <div id="weatherDiv">
            <div id="tempDiv" class="meta"></div>
            <div id="weatherStatus" class="row justify-content-end meta">
                <div id="city" class="meta col"></div>
                <div id="weatherIcon" class="meta col-1"> </div>
            </div>
            <div id="timeDiv" class="meta"></div>
            <div id="revision" class="meta"><?php echo checkRevision(true) ?></div>
        </div>
    </div>

    <?php

		$code = false;
		foreach ($_GET as $key => $value) {
			//write_log("hey, got a key named $key with a value of $value.");
			if ($key == "pinID") {
				write_log("We have a PIN: $value");
				$code = $value;
			}
		}
		$apiToken = $_SESSION['apiToken'] ?? false;
		$getToken =  $_GET['apiToken'] ?? false;
		$user = $token = false;
		if ($code || $apiToken || $getToken) {
			$GLOBALS['login'] = false;
			$result = false;
			if (!$apiToken) $result = plexSignIn($code);
			if ($getToken) $user = verifyApiToken($_GET['apiToken']);
			if ($user) $token = $user['apiToken'] ?? false;
			if ($token) $apiToken = $token;
			if ($result || $apiToken) {
				if ($result == "Not allowed.") {
					showError();
				} else {
					define('LOGGED_IN', true);
					require_once dirname(__FILE__) . '/php/body.php';
					write_log("Making body!");
					$defaults['token'] = $token;
					$bodyData = makeBody($defaults);
					$body = $bodyData[0];
					$_SESSION['darkTheme'] = $bodyData[1];
					echo $body;
				}
			}
		} else {
			showLogin();
	}
	$execution_time = (microtime(true) - $GLOBALS['time']);


	function showLogin() {
		$GLOBALS['login'] = true;
		echo '
							<div class="loginBox">
								<div class="login-box">
									<div class="card loginCard">
									<div class="card-block">
										<img class="loginLogo" src="./img/phlex-med.png" alt="Card image">
										<b><h3 class="loginLabel card-title">Welcome to Flex TV!</h3></b>
										<h6 class="loginLabel cardSub" id="loginTag">Please log in below to begin.</h6>
									</div>
									<div class="card-block">
										<div id="loginForm">
											<button class="btn btn-raised btn-primary" id="plexAuth">DO IT!</button>
											<br><br>
											<a href="http://phlexchat.com/Privacy.html" class="card-link">Privacy Policy</a>
										</div>
									</div>
								</div>
							</div>' .
			headerhtml();
	}

	function showError() {
		write_log("A new user tried to sign in, but new users are not allowed!","ERROR");
		$GLOBALS['login'] = true;
		echo '
							<div class="loginBox">
								<div class="login-box">
									<div class="card loginCard">
									<div class="card-block">
										<b><h3 class="loginLabel card-title">NOT ALLOWED!</h3></b>
										<img class="loginLogo" src="./img/phlex-med.png" alt="Card image">
										<h6 class="loginLabel card-subtitle text-muted">Sorry, the administrator has disabled new logins.</h6>
									</div>
								</div>
							</div>' .
			headerhtml();
	}
	?>

    <div class="modals">
        <div class="modal fade" id="alertModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="alertTitle">Modal title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="alertBody">
                        <p>Modal body text goes here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loader-wrapper">
        <div id="loader"></div>
        <div class="loader-section section-left"></div>
        <div class="loader-section section-right"></div>
    </div>

    <meta id="messages" data-array="<?php if (count($messages)) echo urlencode(json_encode($messages)); ?>"/>

    <script type="text/javascript" src="./js/lib/ui/00_jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="./js/lib/ui/01_tether.min.js"></script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js" integrity="sha384-pjaaA8dDz/5BgdFUPX6M/9SUZv4d12SUPF0axWc+VRZkx5xU3daN+lYb49+Ax+Tl" crossorigin="anonymous"></script>
    <script type="text/javascript" src="./js/lib/ui/07_material.min.js"></script>

    <script type="text/javascript" src="./js/lib/ui/03_html5shiv.min.js"></script>
    <script type="text/javascript" src="./js/lib/ui/04_lazyload.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/00_run_prettify.js"></script>
    <script type="text/javascript" src="./js/lib/support/01_jquery-ui.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/02_clipboard.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/03_jquery.simpleWeather.min.js"></script>

    <script type="text/javascript" src="./js/lib/support/04_snackbar.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/06_arrive.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/08_ripples.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/06_arrive.min.js"></script>
    <script type="text/javascript" src="./js/lib/support/10_swiped.min.js"></script>

    <script type="text/javascript" src="./js/lib/support/12_cache-polyfill.js"></script>


    <script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js" integrity="sha384-3LK/3kTpDE/Pkp8gTNp2gR/2gOiwQ6QaO7Td0zV76UFJVhqLl4Vl3KL1We6q6wR9" crossorigin="anonymous"></script>

    <?php
	if ($GLOBALS['login']) {
		echo '<script type="text/javascript" src="./js/login.js" async></script>';
	} else {
		echo '<script type="text/javascript" src="./js/homebase.js" async></script>';
		echo '<script src="./js/utilities.js"></script>';
        echo '<script src="./js/main.js"></script>';
	}
	?>


	<script>
		<?php echo fetchBackground();?>
		var noWorker = true;
		if ('serviceWorker' in navigator) {
			navigator.serviceWorker.register('service-worker.js').then(function (registration) {
				console.log("Service worker registered.");
				noWorker = false;
			}).catch(function (err) {
				console.log('ServiceWorker registration failed: ', err);
			});
		}

		if (typeof window.history.pushState === 'function') {
			window.history.pushState({}, "Hide", '<?php echo $_SERVER['PHP_SELF'];?>');
		}

		var messageBox = [];
		// We call this inside the login window if necessary, or main.js. Ignore lint warnings.
		function loopMessages(messages) {
			console.log("Function fired.");
			var messageArray = messages;
			messageBox = messages;
			$.each(messageArray, function () {
				if (messageArray[0] === undefined) return false;
				var keepLooping = showMessage(messageArray[0].title, messageArray[0].message, messageArray[0].url);
				messageBox.splice(0, 1);
				if (!keepLooping) return false;
			})
		}

		function showMessage(title, message, url) {
			if (Notification.permission === 'granted') {
				var notification = new Notification(title, {
					icon: './img/avatar.png',
					body: message
				});
				if (url) {
					notification.onclick = function () {
						window.open(url);
					};
				}
				return true;

			} else {
                if (url !== "") {
                    message = "<a href='" + url + "'>"+message+"</a>";
                } else {
                    message = "<p>" + message + "</p>";
                }
				if (Notification.permission !== 'denied') {
					Notification.requestPermission().then(function (result) {
						if ((result === 'denied') || (result === 'default')) {
						    $('#alertTitle').text(title);
							$('#alertBody').html(message);
							$('#alertModal').modal('show');
						}
					});
				} else {
					$('#alertTitle').text(title);
					$('#alertBody').html(message);
					$('#alertModal').modal('show');
				}
				return false;
			}
		}
	</script>


</body>
</html>