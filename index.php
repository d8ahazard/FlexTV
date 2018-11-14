<?php
require_once dirname(__FILE__) . '/php/vendor/autoload.php';
require_once dirname(__FILE__) . "/php/webApp.php";
require_once dirname(__FILE__) . '/php/util.php';
require_once dirname(__FILE__) . "/api.php";

require_once dirname(__FILE__) . "/php/digitalhigh/widget/src/widget.php";

use digitalhigh\widget\widget;

write_log("-------NEW REQUEST RECEIVED-------", "ALERT");
scriptDefaults();

$defaults = checkDefaults();
if ($defaults['migrated'] ?? false) header("Refresh:0");
$forceSSL = $defaults['forceSSL'] ?? false;
write_log("ForceSSL is ".($forceSSL ? "Enabled" : "Disabled"));
if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") && $forceSSL) {
	$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	write_log("Force is on, redirecting to: $redirect","ERROR");
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

header('Cache-Control: max-age=86400');

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
$bodyData = "";
if ($code || $apiToken || $getToken) {
	$GLOBALS['login'] = false;
	$result = false;
	if (!$apiToken) $result = plexSignIn($code);
	if ($getToken) $user = verifyApiToken($_GET['apiToken']);
	if ($user) $token = $user['apiToken'] ?? false;
	if ($token) $apiToken = $token;
	if ($result || $apiToken) {
		if ($result == "Not allowed.") {
			$bodyData = showError();
		} else {
			define('LOGGED_IN', true);
			require_once dirname(__FILE__) . '/php/body.php';
			write_log("Making body!");
			$defaults['token'] = $token;
			$bodyData = mainBody($defaults);
			$body = $bodyData[0];
			$_SESSION['darkTheme'] = $bodyData[1];
			$bodyData = $body;
		}
	}
} else {
	$bodyData = showLogin();
}
$execution_time = (microtime(true) - $GLOBALS['time']);


function showLogin() {
	$GLOBALS['login'] = true;
	$loginString = '

							<div class="loginBox">
								<div class="login-box">
									<div class="card loginCard">
									<div class="card-body">
									    <h4 class="card-title loginLabel">Welcome to Flex TV!</h4>
										<img class="loginLogo" src="./img/phlex-med.png" alt="Card image">
										<div class="card-subtitle">Please log in below to begin.</div>
										<div id="loginForm">
											<button class="btn btn-primary btn-link btn-wd" id="plexAuth">DO IT!</button>
											<br><br>
											<a href="http://phlexchat.com/Privacy.html" class="card-link">Privacy Policy</a>
										</div>
									</div>
								</div>
							</div>
							' .
		headerhtml();
	return $loginString;
}

function showError() {
	write_log("A new user tried to sign in, but new users are not allowed!","ERROR");
	$GLOBALS['login'] = true;
	$errorString = '
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
	return $errorString;
}

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
    <link rel="manifest" href="./manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="./img/apple-icon.png">
	<link rel="icon" type="image/png" href="./img/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="./img/favicon-16x16.png" sizes="16x16">
	<link rel="mask-icon" href="./img/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="./img/favicon.ico">
    <link rel="stylesheet" href="css/loadingAnimation.css">
    <!-- Material Kit/Bootstrap4 CSS, Material Icons-->
    <!-- <link href="css/material-kit_custom.css?v=2.0.4" rel="stylesheet" /> -->
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-material-design@4.1.1/dist/css/bootstrap-material-design.min.css" integrity="sha384-wXznGJNEXNG1NFsbm0ugrLFMQPWswR3lds2VeinahP8N0zJw9VWSopbjv2x7WCvX" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!--Flex TV CSS Files-->
    <link href="./css/main.css" rel="stylesheet">
    <?php if ($_SESSION['darkTheme'] ?? false) echo '<link href="css/darkTheme.css" rel="stylesheet">' .PHP_EOL ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js" integrity="sha256-EPrkNjGEmCWyazb3A/Epj+W7Qm2pB9vnfXw+X6LImPM=" crossorigin="anonymous"></script>
    <style type="text/css">
        .pace {
            -webkit-pointer-events: none;
            pointer-events: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }
        .pace-inactive {
            display: none;
        }
        .pace .pace-progress {
            background: var(--theme-accent);
            position: fixed;
            z-index: 2000;
            top: 0;
            right: 100%;
            width: 100%;
            height: 3px;
        }
    </style>
    <style>
        <?php echo widget::getMarkup('CSS') ?>
    </style>

</head>

<body style="background-color:black">
    <noscript id="deferred-styles">
        <link href="css/lib/snackbar.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha256-rByPlHULObEjJ6XQxW/flG2r+22R5dKiAoef+aXWfik=" crossorigin="anonymous" />
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/gridstack.js/0.4.0/gridstack.min.css" />
        <link rel="stylesheet" href="css/font/font-muximux.css">
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700" rel="stylesheet">
        <link href="css/lib/bootstrap-iconpicker.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.3/css/bootstrap-select.min.css" integrity="sha256-q+3CS1i6GuPNz/JQk7dapdWXvii85xf7vecZx2QqsFI=" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simplebar/2.6.1/simplebar.min.css" integrity="sha256-K165qkCFzUrAZsklS/OSoifAwNqxWxJ/PbYEWYUSh0I=" crossorigin="anonymous" />
    </noscript>

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
        // Body data goes here
        echo $bodyData;
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

    <!-- The root of all evil-->
    <script type="text/javascript" src="js/lib/jquery-3.3.1.min.js"></script>
    <!-- material kit stuff -->
    <script src="js/lib/popper.min.js" type="text/javascript"></script>
    <script src="js/lib/bootstrap-material-design.min.js" type="text/javascript"></script>
    <script src="js/lib/moment.min.js"></script>
    <script src="js/lib/nouislider.min.js" type="text/javascript"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="js/lib/material-kit.js?v=2.0.4" type="text/javascript"></script>

    <script src="js/widgets.js" type="text/javascript"></script>

    <!-- This is disabled until all the code works, then will be re-enabled
    <script type="text/javascript">
        <?php //echo widget::getMarkup('JS'); ?>
    </script>
    -->

    <!-- Utility scripts -->
    <script type="text/javascript" src="js/lib/lazyload.min.js"></script>
    <script defer type="text/javascript" src="js/lib/run_prettify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha256-KM512VNnjElC30ehFwehXjx1YCHPiQkOPmqnrWtpccM=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-ui-touch-punch@0.2.3/jquery.ui.touch-punch.min.js" integrity="sha256-AAhU14J4Gv8bFupUUcHaPQfvrdNauRHMt+S4UVcaJb0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.11/lodash.min.js" integrity="sha256-7/yoZS3548fXSRXqc/xYzjsmuW3sFKzuvOCHd06Pmps=" crossorigin="anonymous"></script>

    <script type="text/javascript" src='//cdnjs.cloudflare.com/ajax/libs/gridstack.js/0.4.0/gridstack.min.js'></script>
    <script type="text/javascript" src='//cdnjs.cloudflare.com/ajax/libs/gridstack.js/0.4.0/gridstack.jQueryUI.min.js'></script>
    <script defer type="text/javascript" src="js/lib/jquery.simpleWeather.min.js"></script>

    <!-- Icon picker -->
    <script defer type="text/javascript" src="js/lib/iconset_muximux.js"></script>
    <script defer type="text/javascript" src="js/lib/bootstrap-iconpicker.min.js"></script>

    <!-- Snakbar, sort table, swipe to close, cache pfill for service worker -->
    <script defer type="text/javascript" src="js/lib/snackbar.min.js"></script>
    <script defer type="text/javascript" src="js/lib/swiped.min.js"></script>
    <script src="https://rubaxa.github.io/Sortable/Sortable.js"></script>
    <script type="text/javascript" src="js/lib/cache-polyfill.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.3/js/bootstrap-select.min.js" integrity="sha256-APwNt/Yju1ZD+yBddFqLhJsk21FXdiNGUuISL4EkUs4=" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/simplebar/2.6.1/simplebar.min.js" integrity="sha256-bpLJbsEBcYeYFpadgZfuNyDGy9/IWoOEFzuVgM9QrtE=" crossorigin="anonymous"></script>

    <script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js" integrity="sha384-3LK/3kTpDE/Pkp8gTNp2gR/2gOiwQ6QaO7Td0zV76UFJVhqLl4Vl3KL1We6q6wR9" crossorigin="anonymous"></script>

    <!-- These are some early-stage functions that we want available right away,
    even if the DOM doesn't fully load for some reason. -->

    <script type="text/javascript">
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

        // Ignore the IDE when it says this is unused, it's lying
        function setBackground(last) {
            var cv="";
            $('#bgwrap').append("<div class='bg hidden'></div>");
            var imgUrl = "https://img.phlexchat.com?new=true"+(last ? "&last" : "")+"&height=" + $(window).height() + "&width=" + $(window).width() + "&v=" + (Math.floor(Math.random() * (1084))) + cv;

            var newDiv = $('.bg').last();

            var img = $('<img src="'+imgUrl+'" class="bgImg"/>').on('load', function(){

                $.when(newDiv.fadeIn(500)).done(function() {
                    newDiv.removeClass('hidden');
                    $("#bgwrap div:not(:last-child)").remove();
                })
            });

            newDiv.append(img);
        }

    </script>

    <?php
        if ($GLOBALS['login']) {
            echo '<script type="text/javascript" src="./js/login.js" async></script>';
        } else {
            echo '<script type="text/javascript" src="js/homebase.js" async></script>';
            echo '<script src="js/utilities.js"></script>';
            echo '<script src="./js/main.js"></script>';
        }
	?>


	<script>
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

        // Load CSS deferred, because we went down the Lighthouse Audit rabbithole again...
        var loadDeferredStyles = function() {
            var addStylesNode = document.getElementById("deferred-styles");
            var replacement = document.createElement("div");
            replacement.innerHTML = addStylesNode.textContent;
            var referenceNode = document.getElementById('deferred');
            referenceNode.parentNode.insertBefore(replacement, referenceNode.nextSibling);
            document.getElementById('deferred').appendChild(replacement);
            addStylesNode.parentElement.removeChild(addStylesNode);
        };
        var raf = window.requestAnimationFrame || window.mozRequestAnimationFrame ||
            window.webkitRequestAnimationFrame || window.msRequestAnimationFrame;
        if (raf) raf(function() { window.setTimeout(loadDeferredStyles, 0); });
        else window.addEventListener('load', loadDeferredStyles);

	</script>


</body>
</html>