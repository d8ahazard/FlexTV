<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . "/webApp.php";
require_once dirname(__FILE__) . '/util.php';

require_once dirname(__FILE__) . "/digitalhigh/widget/src/widget.php";

use digitalhigh\widget\widget;

scriptDefaults();
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
    <link rel="stylesheet" href="../css/loadingAnimation.css">
    <!-- Material Kit/Bootstrap4 CSS, Material Icons-->
    <link href="../css/material-kit_custom.css?v=2.0.4" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!--Flex TV CSS Files-->
    <link href="../css/main.css" rel="stylesheet">
    <link href="../css/darkTheme.css" rel="stylesheet">
    <link rel="stylesheet" media="(max-width: 576px)" href="../css/main_max_576.css">
    <link rel="stylesheet" media="(max-width: 768px)" href="../css/main_max_768.css">
    <link rel="stylesheet" media="(min-width: 768px)" href="../css/main_min_768.css">
    <link rel="stylesheet" media="(min-width: 992px)" href="../css/main_min_992.css">
    <link rel="stylesheet" media="(max-width: 992px)" href="../css/main_max_992.css">
    <link rel="stylesheet" media="(min-width: 1200px)" href="../css/main_min_1200.css">
    <link rel="stylesheet" href="../css/homeBase.css" id="deferred">
    <link href="../css/lib/snackbar.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha256-rByPlHULObEjJ6XQxW/flG2r+22R5dKiAoef+aXWfik=" crossorigin="anonymous" />
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/gridstack.js/0.4.0/gridstack.min.css" />
    <link rel="stylesheet" href="../css/font/font-muximux.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,700" rel="stylesheet">
    <link href="../css/lib/bootstrap-iconpicker.min.css" rel="stylesheet">

</head>

<body style="background-color:black">
    <div class="backgrounds">
        <div id="bgwrap">

        </div>
    </div>
    <div id="widgetDemo" class="grid-stack">
	    <?php echo widget::getMarkup('HTML'); ?>
    </div>

    <!-- The root of all evil-->
    <script type="text/javascript" src="../js/lib/jquery-3.3.1.min.js"></script>
    <!-- material kit stuff -->
    <script src="../js/lib/popper.min.js" type="text/javascript"></script>
    <script src="../js/lib/bootstrap-material-design.min.js" type="text/javascript"></script>
    <script src="../js/lib/moment.min.js"></script>
    <script src="../js/lib/nouislider.min.js" type="text/javascript"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../js/lib/material-kit.js?v=2.0.4" type="text/javascript"></script>

    <script type="text/javascript">
        <?php echo widget::getMarkup('JS'); ?>
    </script>

    <!-- Utility scripts -->
    <script type="text/javascript" src="../js/lib/lazyload.min.js"></script>
    <script defer type="text/javascript" src="../js/lib/run_prettify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha256-KM512VNnjElC30ehFwehXjx1YCHPiQkOPmqnrWtpccM=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-ui-touch-punch@0.2.3/jquery.ui.touch-punch.min.js" integrity="sha256-AAhU14J4Gv8bFupUUcHaPQfvrdNauRHMt+S4UVcaJb0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.11/lodash.min.js" integrity="sha256-7/yoZS3548fXSRXqc/xYzjsmuW3sFKzuvOCHd06Pmps=" crossorigin="anonymous"></script>

    <script type="text/javascript" src='//cdnjs.cloudflare.com/ajax/libs/gridstack.js/0.4.0/gridstack.min.js'></script>
    <script type="text/javascript" src='//cdnjs.cloudflare.com/ajax/libs/gridstack.js/0.4.0/gridstack.jQueryUI.min.js'></script>
    <script defer type="text/javascript" src="../js/lib/jquery.simpleWeather.min.js"></script>

    <!-- Icon picker -->
    <script defer type="text/javascript" src="../js/lib/iconset_muximux.js"></script>
    <script defer type="text/javascript" src="../js/lib/bootstrap-iconpicker.min.js"></script>

    <!-- Snakbar, sort table, swipe to close, cache pfill for service worker -->
    <script defer type="text/javascript" src="../js/lib/snackbar.min.js"></script>
    <script defer type="text/javascript" src="../js/lib/swiped.min.js"></script>
    <script src="https://rubaxa.github.io/Sortable/Sortable.js"></script>
    <script type="text/javascript" src="../js/lib/cache-polyfill.js"></script>

    <script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js" integrity="sha384-3LK/3kTpDE/Pkp8gTNp2gR/2gOiwQ6QaO7Td0zV76UFJVhqLl4Vl3KL1We6q6wR9" crossorigin="anonymous"></script>

    <!-- These are some early-stage functions that we want available right away,
    even if the DOM doesn't fully load for some reason. -->

    <script type="text/javascript">
       setBackground();
       $(window).on("load", function() {

           var options = {
               alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
               cellHeight: 70,
               acceptWidgets: true,
               animate: true,
               float: true,
               height: 10
           };

           $(function () {
               $('.grid-stack').gridstack(options);
           });
           $('.editItem').show();
       });

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


</body>
</html>