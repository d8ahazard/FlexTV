function FlexWidget(data) {
    var cols = 12;
    var rows = 12;
    var vmargin = 20;
    var hmargin = 20;

    var width = 0;
    var height = 0;
    var lastHeight = 0;
    var gridObj = 0;
    var returnFunc = data['save'];


    function initGrid(data) {
        var returns = [];
        var returnFunc = data['save'];
        console.log("Initializing grid.");
        var options = {
            alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
            cellHeight: 70,
            acceptWidgets: true,
            animate: true,
            float: true,
            height: 10,
            handle: '.dragHandle'
        };

        var gridOptions = {
            width: cols,
            height: 10,
            float: true,
            animate: true,
            disableResize: false,
            disableDrag: false,
            cellHeight: 70,
            removable: false,
            removeTimeout: 100,
            verticalMargin: vmargin,
            horizontalMargin: hmargin,
            acceptWidgets: '.grid-stack-item',
            handle: '.dragHandle',
            resizable: {handles: 'se, sw'},
            alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        };

        if (data.hasOwnProperty('main')) {
            var dl = false;
            var toggleDeleteList = false;
            if (data.hasOwnProperty('delete')) {
                console.log("Setting remove target to " + data['delete']);
                dl = $(data['delete']);
                dl.gridstack(options);
                toggleDeleteList = true;
                dl.on('added', function () {
                    console.log("Item added.");
                    flipFab(false, dl);
                    $(this).html("");
                });
                //gridOptions['removable'] = data['delete'];
            }
            var mainTarget = data['main'];
            var wl = $(mainTarget);

            wl.gridstack(gridOptions);

            wl.on('added', function (event, items) {
                for (var i = 0; i < items.length; i++) {
                    console.log('item added');
                    console.log(items[i]);
                    //initWidget($(items[i].el).info());
                }
            });

            wl.on('dragstart', function () {
                console.log("Drag start.");
                if (toggleDeleteList) {
                    flipFab(true, dl);
                }
            });

            wl.on('dragstop', function () {
                console.log('Drag stop');
                if (toggleDeleteList) {
                    flipFab(false, dl);
                }
                returnFunc(serialize());
            });

            wl.on('change', function () {
                console.log('Change');
                returnFunc(serialize());
            });
            widgetList = wl.data('gridstack');
            widgetList._updateHeightsOnResize = function () {
                resize();
            };
            widgetList.onResizeHandler();
            returns['list'] = widgetList;
        }

        if (data.hasOwnProperty('templates') && data.hasOwnProperty('drawer')) {
            var templateTarget = data['templates'];
            var drawerTarget = data['drawer'];
            console.log("Initializing templates, targeting " + drawerTarget + " with data from " + templateTarget);
            var tt = $(templateTarget);
            var widgetDrawer = $(drawerTarget);
            var wt = tt.clone().prop('id', 'widgetAddList');
            wt.css('display', 'block');
            widgetDrawer.html("");
            wt.appendTo(widgetDrawer);

            var addOptions = {
                cellHeight: 70,
                acceptWidgets: false,
                handle: '.dragHandle'
            };

            wt.gridstack(addOptions);

            widgetDrawer.on('removed', function () {
                initGrid({'templates': templateTarget, 'drawer': drawerTarget})
            });
        }
    }

    function initListeners(data) {
        console.log("Initializing listeners.");

        $(document).on('click', '.widgetEdit', function () {
            console.log("Widget edit button clicked.");
            var parent = $(this).closest('.widgetCard');
            var cs = parent.find('.card-settings');
            cs.slideToggle();

            parent.find('.slideContent').slideToggle();
            parent.toggleClass('editCard');
            if (!parent.hasClass('editCard')) {
                $('.clickJack').show();
            }
        });

        $(document).on('click','.carousel-indicator', function () {
            var parent = $(this).closest('.widgetCard');
            var carousel = parent.find('.carousel');
            if (!$(this).hasClass('active')) carousel.carousel($(this).data('slide-to'));
        });

        $(window).on('click', '.widgetRefresh', function () {
            var parent = $(this).closest('.widgetCard');
        });

        $(window).on('click', '.widgetDelete', function () {
            var parent = $(this).closest('.widgetCard');
            parent.remove();
        });

        // Set the value of the setting thinger to the widget data, refresh
        $(window).on('change', '.widgetSetting', function () {
            var parent = $(this).closest('.widgetCard');
            console.log("Got a change for ", parent);
        });

        if (data.hasOwnProperty('main')) {
            $(data['main']).on('added', function (evt, items) {
                for (var i = 0; i < items.length; i++) {
                    var item = $(items[i]['el'][0]);
                    initWidget(item, false);
                }
            });
        }

        if (data.hasOwnProperty('delete')) {
            $(data['delete']).on('added', function () {
                $(this).html("");
            });
        }

        if (data.hasOwnProperty('drawer')) {
            var drawer = $(data['drawer']);
            // $('#widgetList').on('click', function() {
            //     // Check for left button
            //     console.log("DOC CLICK.");
            //     if (drawer.is(':visible')) {
            //         console.log("Hiding");
            //         drawer.slideUp();
            //     }
            // });
        }
    }

    function flipFab(red, dl) {
        var wf = $('#widgetFab');
        if (wf.length) {
            console.log("Widget fab found to flip, foo.");
            var icon = wf.find('i');
            if (red) {
                wf.addClass("delete");
                icon.removeClass('addIcon');
                icon.text("delete");
                dl.show();
            } else {
                wf.removeClass("delete");
                icon.addClass('addIcon');
                icon.text("add");
                dl.hide();
            }
        }
    }

    function serialize() {
        console.log("Serializing data...");
        var wl = $('#widgetList');
        var widgets = wl.find('.widgetCard');
        console.log("Widgets: ", widgets);
        var widgetData = [];
        $.each(widgets, function () {
            var elemData = $(this).info();
            console.log("Element data: ", elemData);
            var id = false;
            if (!elemData.hasOwnProperty('gs-id') && !elemData.hasOwnProperty('id')) {
                id = Math.floor((Math.random() * 100000) + 1000);
                elemData['gs-id'] = id;
            }
            widgetData.push(elemData);
        });
        console.log("Returning widget data: ", widgetData);
        return widgetData;
    }

    // Add a widget from drag drop
    function addWidget(widgetData) {
        console.log("Adding widget: ", widgetData);
        var result = false;
        // It should be safe to call this from the DOM, as we've declared this name within widgets.js
        var addAppList = $('#widgetAddList');
        if (widgetData.hasOwnProperty('type')) {
            var type = widgetData['type'];
            var source = addAppList.find('[data-type="' + type + '"]');
            if (source.length) {
                var clone = source.clone();
                for (var key in widgetData) if (widgetData.hasOwnProperty(key)) {
                    clone.attr('data-' + key, widgetData[key]);
                    var inputItem = '.' + type + key + "Input";
                    var inputTarget = clone.find(inputItem);
                    if (inputTarget.length) inputTarget.val(widgetData[key]);
                }

                result = true;
                var id = (widgetData.hasOwnProperty('gs-id')) ? widgetData['gs-id'] : Math.floor((Math.random() * 100000) + 1000);
                clone.attr('id', 'widget' + id);
                clone.attr('data-gs-id', id);
                clone.attr('data-gs-auto-position', "0");
                widgetList.addWidget(clone, widgetData['gs-x'], widgetData['gs-y'], widgetData['gs-width'], widgetData['gs-height'], 0, widgetData['gs-min-width'], widgetData['gs-max-width'], widgetData['gs-min-height'], widgetData['gs-max-height'], widgetData['gs-id']);

                console.log("About to init widget here: ", widgetData);
                var widgetId = widgetData['gs-id'];
                var widget = $('#widget' + widgetId);

                switch (type) {
                    case 'Generic':
                        console.log('No init function defined for generic');
                        break;
                    case 'NowPlaying':
                        console.log("Nowplaying init.");
                        widget.find('#currentActivity').removeClass('list-group-item-danger');
                        loadWidgetTarget(widget, widgetData, false, true);

                        break;
                    case 'URL':
                        widget.find('.card-header').hide();
                        $(document).on('change', '.linkInput', function () {
                            console.log("Link input changed.");
                            var target = $(this).closest('.widgetCard');
                            var linkVal = $(this).val();
                            var attr = $(this).data('for');
                            target.attr('data-' + attr, linkVal);
                            target.data(attr, linkVal);
                            var updElem = target.find('.url' + ucFirst(attr));
                            if (attr === "title" || attr === "subtitle") {
                                updElem.text(linkVal);
                            }
                            if (attr === 'img') updElem.attr('src', linkVal);
                            if (attr === 'url') updElem.attr('href', linkVal);
                        });
                        var widgetUrl = "";
                        var img = "";
                        var title = "";
                        var subtitle = "";
                        if (widgetData.hasOwnProperty('url')) widgetUrl = widgetData.url;
                        if (widgetData.hasOwnProperty('img')) img = widgetData.img;
                        if (widgetData.hasOwnProperty('title')) title = widgetData.title;
                        if (widgetData.hasOwnProperty('subtitle')) subtitle = widgetData.subtitle;
                        console.log("Updating data: ", widgetData);
                        widget.find('.urlLink').attr('href', widgetUrl);
                        widget.find('.urlImg').attr('src', img);
                        widget.find('.urlTitle').text(title);
                        widget.find('.urlSubtitle').text(subtitle);
                        widget.find('.imgInput').val(img);
                        widget.find('.titleInput').val(title);
                        widget.find('.urlInput').val(widgetUrl);
                        widget.find('.subtitleInput').val(subtitle);
                        break;
                    case 'SystemMonitor':
                        loadWidgetTarget(widget, widgetData, true, true);

                        var bars = widget.find('.serverOverviewBars');
                        var chartData = buildChart('systemMonitor', widgetData['stats']);
                        console.log("Chart data from widgetData", widgetData, chartData);
                        var seriesData = chartData[0];
                        var drillDownData = chartData[1];

                        var chartOpts = {
                            chart: {
                                type: 'bar'
                            },
                            title: {
                                text: null
                            },
                            legend: {
                                enabled: false
                            },
                            tooltip: {
                                outside: true
                            },
                            xAxis: {
                                type: 'category',
                                title: {
                                    text: null
                                }
                            },
                            yAxis: {
                                min: 0,
                                max: 100,
                                title: {
                                    text: null
                                },
                                labels: {
                                    formatter: function () {
                                        return Math.abs(this.value) + '%';
                                    }
                                }
                            },
                            plotOptions: {
                                series: {
                                    borderWidth: 0
                                },
                                bar: {
                                    dataLabels: {
                                        enabled: true,
                                        format: '{point.percent}%'
                                    }
                                }
                            },
                            series: seriesData,
                            drilldown: drillDownData
                        };
                        console.log("Chart options: ", chartOpts);
                        var serverOverviewBars = Highcharts.chart(bars[0], chartOpts);

                        $(document).on('gsresizestop', widget, function () {
                            console.log("REFLOW TRIGGERED.");
                            serverOverviewBars.reflow();
                        });
                        break;

                    case 'StatusMonitor':
                        console.log("Adding status monitor.");
                        if (widget.hasOwnProperty('data-target')) {
                            console.log("Data is set for widget??");
                        } else {
                            console.log("NO TARGET");
                        }
                        if (widget.data('target') === undefined || widget.data('target') === 0) {
                            console.log("No target, setting from drawer...");
                            var drawer = $('#AppzDrawer');
                            var drawerItems = drawer.find('.drawer-item');
                            if (drawerItems.length) {
                                id = drawer.find('.drawer-item').attr('id').replace('Btn', '');
                                console.log('No defined target, using' + id);
                            } else {
                                console.log("No drawer Item!!");
                                id = false;
                            }
                        } else {
                            id = widget.data('target');
                            console.log('using target ID of ' + id);
                        }
                        console.log('Target id is  ' + id, widget);

                        var targetBtn = $('#' + id + 'Btn');
                        var dataSet = targetBtn.data();
                        console.log('Dataset: ', dataSet);
                        if (dataSet !== undefined) {
                            console.log("Setting status monitor attributes.");
                            var icon = dataSet['icon'];
                            var label = dataSet['label'];
                            var url = dataSet['url'];
                            var color = dataSet['color'];
                            widget.attr('data-target', id);
                            widget.attr('data-icon', icon);
                            widget.attr('data-label', label);
                            widget.attr('data-color', color);
                            widget.attr('data-url', url);
                            widget.find('.service-icon').attr('class', 'service-icon ' + icon);
                            widget.find('.statTitle').text(label);
                        }
                        var shade = -100;
                        if (widget.attr('data-service-status') === "online") {
                            widget.find('.offline-indicator').hide();
                            widget.find('.online-indicator').show();
                        } else {
                            widget.attr('data-service-status', "offline");
                            widget.find('.offline-indicator').show();
                            widget.find('.online-indicator').hide();
                            shade = 100;
                        }
                        var color2 = shadeColor(color, shade);
                        var colString = 'background: linear-gradient(60deg, ' + color + ', ' + color2 + ');';

                        widget.attr('style', colString);

                        $(document).on('change', '.serviceList', function () {
                            console.log("Service list changed, we need to do some magic...");
                            var target = $(this).closest('.widgetCard');
                            var selection = $(this).find(":selected").val();
                            target.attr('data-target', selection);
                            target.data('target', selection);
                            console.log("Current value " + selection, target);
                            var targetDiv = $('#AppzDrawer').find('#' + selection + 'Btn');
                            var dataSet = targetDiv.info();
                            if (dataSet !== undefined) {
                                widgetData['icon'] = dataSet['icon'];
                                widgetData['label'] = dataSet['label'];
                                widgetData['color'] = dataSet['color'];
                                widgetData['url'] = dataSet['url'];
                            }
                            target.find('.service-icon').attr('class', 'service-icon ' + widgetData['icon']);
                            target.find('.statTitle').text(widgetData['label']);
                            console.log("Widget service status is " + widgetData['service-status']);
                            var shade = -100;
                            if (widgetData['service-status'] === "online") {
                                console.log("OI: ", widget.find('.offline-indicator'));
                                target.find('.offline-indicator').hide();
                                target.find('.online-indicator').show();
                            } else {
                                target.find('.offline-indicator').show();
                                target.find('.online-indicator').hide();
                                shade = 100;
                            }
                            var color2 = shadeColor(widgetData['color'], shade);
                            var colString = "background: linear-gradient(60deg, " + widgetData['color'] + ", " + color2 + ");";
                            var ss = target.find(".card.m-0.service-status");
                            ss.attr('style', colString);
                            target.attr('style', colString);
                            initWidget(target);
                            returnFunc(serialize());
                        });
                        break;

                    case 'userTest':
                        console.log('No init function defined for userTest');
                        break;

                    default:
                        return false;
                }
            }
        } else {
            console.error("Widget data doesn't have a type...");
        }
        return result;
    }

    // Update a widget in the UI using fetched data
    function updateWidget(widgetData) {
        console.log("Widget update fired for " + type);
        var widgetId = widgetData['gs-id'];
        var widget = $('#widget' + widgetId);
        var type = widgetData['type'];


        switch (type) {
            case 'Generic':
                console.log('No update function defined for generic');
                break;

            case 'NowPlaying':
                console.log('No update function defined for nowPlaying');
                loadWidgetTarget(widget, widgetData, false, false);
                if (widgetData.hasOwnProperty('sessions')) {
                    var sessions = widgetData.sessions;
                    var carousel = widget.find('.carousel-inner');
                    var indicatorContainer = widget.find('.carousel-indicators');
                    var indicators = widget.find('.carousel.indicator');
                    var currentWidgets = carousel.find('.carousel-item');
                    var npTemplate = widget.find('.carousel-template-item');
                    var empty = true;
                    var sessionIds = [];
                    var indicatorCount = indicators.length;
                    console.log("We have " + indicatorContainer + " indicators.");
                    $.each(sessions, function (key, session) {
                        var sessionId = session['id'];
                        sessionIds.push(sessionId);
                        var targetSession = false;
                        var data = session;
                        $.each(currentWidgets, function () {
                            console.log("Current widget: ", $(this));
                            if ($(this).data('sessionid') === sessionId) {
                                console.log("This session is already set up, update it.");
                                targetSession = $(this);
                                empty = false;
                                return false;
                            }
                        });

                        if (!targetSession) {
                            console.log("Specified session doesn't exist, appending.");
                            targetSession = npTemplate.clone();
                            targetSession.attr('id', "currentActivity" + sessionId);
                            var targetIndicator = $('<li class="carousel-indicator" id="indicator' + sessionId + '" data-target="#currentActivity' + sessionId + '" data-slide-to="' + indicatorCount + '"></li>');
                            carousel.append(targetSession);
                            indicatorContainer.append(targetIndicator);
                            indicatorCount++;
                            targetSession.removeClass('carousel-template-item');
                            if (empty) {
                                targetSession.addClass('active');
                                targetIndicator.addClass('active');
                            }
                            targetSession.attr('data-sessionid', sessionId);
                            targetSession.data('sessionid', sessionId);
                            empty = false;
                        }

                        console.log("Okay, now we should be updating values within " + targetSession);
                        var streamLink = ' <a data-toggle="collapse" href="#streamInfo' + targetSession + '" role="button" aria-expanded="false" aria-controls="streamInfo1">+</a>';
                        var titleText = "";
                        if (data['type'] === 'episode') {
                            titleText += data['grandparentTitle'] + "<br>";
                            titleText += "S" + data['parentIndex'] + "E" + data['index'] + " - " + data['title'];
                        }

                        if (data['type'] === 'track') {
                            titleText += data['grandparentTitle'] + "<br>";
                            titleText += data['title'] + " " + data['parentTitle'];
                        }

                        if (data['type'] === "movie") titleText = data['title'] + " (" + data['year'] + ")";

                        targetSession.find('.card-body').css('background-image', 'url(' + data['art'] + ')');
                        targetSession.find('.media-image').css('background-image', 'url(' + data['poster'] + ')');
                        targetSession.find('.npStatusText').text(data['state']);
                        targetSession.find('.npStatusQuality').text(data['quality']);
                        targetSession.find('.npStatusBandwidth').text(data['bandwidth']);
                        targetSession.find('.npStreamType').html(data['direct'] + streamLink);
                        targetSession.find('.npPlayerName').text(data['player']);
                        targetSession.find('.npUserName').text(data['user']);
                        targetSession.find('.npMediaTitle').html(titleText);
                        targetSession.find('.progress-bar').text(data['percent'] + "%");
                        targetSession.find('.progress-bar').attr('aria-valuenow', data['percent']);
                        targetSession.find('.progress-bar').css('width', data['percent'] + "%");


                    });

                    $.each(currentWidgets, function () {
                        var sessId = $(this).data('sessionid');
                        var remove = true;
                        $.each(sessionIds, function (key, value) {
                            console.log("Comparing ", value, sessId);
                            if (value === sessId) remove = false;
                        });
                        if (remove) {
                            console.log("This session card doesn't exist, deleting...", $.inArray(sessId, sessionIds));
                            $(this).remove();
                            widget.find('#indicator' + sessId).remove();
                        }
                    });
                }
                break;

            case 'URL':
                console.log("URL Widget.");
                var url = "";
                var img = "";
                var title = "";
                var subtitle = "";
                if (widgetData.hasOwnProperty('url')) url = widgetData.url;
                if (widgetData.hasOwnProperty('img')) img = widgetData.img;
                if (widgetData.hasOwnProperty('title')) title = widgetData.title;
                if (widgetData.hasOwnProperty('subtitle')) subtitle = widgetData.subtitle;
                console.log("Updating data: ", widgetData);
                widget.find('.urlLink').attr('href', url);
                widget.find('.urlImg').attr('src', img);
                widget.find('.urlTitle').text(title);
                widget.find('.urlSubtitle').text(subtitle);
                widget.find('.imgInput').val(img);
                widget.find('.titleInput').val(title);
                widget.find('.urlInput').val(url);
                widget.find('.subtitleInput').val(subtitle);
                break;

            case 'SystemMonitor':
                loadWidgetTarget(widget, widgetData, true);

                if (widgetData.hasOwnProperty('stats')) {
                    var statData = widgetData['stats'];
                    console.log("We have statData: ", statData);
                    var barObj = widget.find('.serverOverviewBars');
                    console.log("BarObj: ", barObj);
                    var bars = barObj.highcharts();
                    var chartData = buildChart('systemMonitor', statData);
                    var seriesData = chartData[0];
                    var drillDownData = chartData[1];
                    var currentData = bars.options;
                    console.log("Current data before replace: ", currentData);
                    console.log("Setting series data: ", seriesData);
                    bars.series[0].setData(seriesData[0]['data']);
                    console.log("Setting drilldown data: ", drillDownData);
                    bars.options.drilldown = drillDownData;
                    currentData = bars.options;
                    console.log("New options: ", currentData);
                }
                break;

            case 'serverStatus':
                console.log('No update function defined for serverStatus');
                break;

            case 'StatusMonitor':
                console.log("Initializing status monitor??");
                var targetId = widgetData['target'];
                var targetDiv = $('#AppzDrawer').find('#' + targetId + 'Btn');
                var dataSet = targetDiv.info();
                if (dataSet !== undefined) {
                    widgetData['icon'] = dataSet['icon'];
                    widgetData['label'] = dataSet['label'];
                    widgetData['color'] = dataSet['color'];
                    widgetData['url'] = dataSet['url'];
                }
                widget.find('.service-icon').attr('class', 'service-icon ' + widgetData['icon']);
                widget.find('.statTitle').text(widgetData['label']);
                console.log("Widget service status is " + widgetData['service-status']);
                var shade = -100;
                if (widgetData['service-status'] === "online") {
                    console.log("OI: ", widget.find('.offline-indicator'));
                    widget.find('.offline-indicator').hide();
                    widget.find('.online-indicator').show();
                } else {
                    widget.find('.offline-indicator').show();
                    widget.find('.online-indicator').hide();
                    shade = 100;
                }
                var color2 = shadeColor(widgetData['color'], shade);
                var colString = "background: linear-gradient(60deg, " + widgetData['color'] + ", " + color2 + ");";
                var ss = widget.find(".card.m-0.service-status");
                ss.attr('style', colString);
                break;

            case 'userTest':
                console.log('No update function defined for userTest');
                break;

            default:
                return false;
        }
    }

    function removeWidget(widget) {

    }

    function resize() {
        width = $(id).parent().width();
        height = $(id).parent().height() - ((rows - 1) * vmargin);

        if (0 >= height) {
            setTimeout(gridObj.onResizeHandler, 1000);
            return;
        }

        if (lastHeight === height)
            return;

        lastHeight = height;
        gridObj.cellHeight(parseInt(height / rows) + 'px');
    }

    function loadWidgetTarget(widget, widgetData, usePlugin, setListener) {
        var devOutput = "";
        console.log("Loading widget target for widget: ", widget);
        var deviceList = devices;
        if (deviceList.hasOwnProperty('Server')) {
            var serverList = deviceList['Server'];
            console.log("We have a server list: ", serverList);

            var widgetTarget = (widgetData.hasOwnProperty('target') ? widgetData['target'] : false);
            var i = 0;

            $.each(serverList, function (key, device) {
                var selected = "";
                if (widgetTarget) {
                    if (widgetTarget === device['Id']) {
                        selected = " selected";
                    }
                } else {
                    if (i === 0) {
                        selected = " selected";
                    }
                }
                var id = device["Id"];
                var name = device["Name"];
                if ((device['HasPlugin'] && usePlugin) || !usePlugin) {
                    devOutput += "<option data-type='Server' value='" + id + "'" + selected + ">" + name + "</option>";
                    if (selected !== "") {
                        widgetData['target'] = id;
                        widgetData['uri'] = device['Uri'];
                        widgetData['token'] = device['Token'];
                    }
                }
                i++;
            });
        }

        var list = widget.find('.serverList');
        console.log("Setting serverList to " + devOutput, list);
        list.html(devOutput);

        if (setListener) {
            var selVal = "";
            list.on('click change', function () {
                selVal = $(this).val();
                var parent = widget;
                console.log("Selected: " + selVal);
                if (devices.hasOwnProperty('Server')) {
                    console.log("We've got a server list");
                    var serverList = devices['Server'];
                    var i = 0;
                    $.each(serverList, function (key, device) {
                        var devId = device["Id"];
                        console.log("Comparing " + selVal + " to " + devId);

                        if (devId === selVal) {
                            console.log("Okay, device updated.");
                            var uri = device['Uri'];
                            var token = device['Token'];
                            parent.attr('data-target', selVal);
                            parent.attr('data-uri', uri);
                            parent.attr('data-token', token);
                            parent.data('target, selVal');
                            parent.data('token', token);
                            parent.data('uri', uri);
                            return false;
                        }
                    });
                }
            });
        }
    }

    function buildChart(chartType, widgetData) {
        var seriesData = [];
        var drillDownData = [];
        if (chartType === 'systemMonitor') {
            var cpuPct = 0;
            var memPct = 0;
            var netTx = 0;
            var netRx = 0;
            var hddPct = 0;
            var nicTxArray = [];
            var nicRxArray = [];
            var nicName = "";
            var hddName = "";
            var hddArray = [];
            if (widgetData && widgetData.hasOwnProperty('Cpu') && widgetData.hasOwnProperty('Mem') && widgetData.hasOwnProperty('Net') && widgetData.hasOwnProperty('Hdd')) {
                cpuPct = widgetData['Cpu'][0]['cpu_pct_used'];
                memPct = widgetData["Mem"][0]['mem_pct_used'];
                netTx = widgetData['Net'][0]['Interface'][0]['net_tx'];
                netRx = widgetData['Net'][0]['Interface'][0]['net_rx'];
                var steps = [
                    "KB/s",
                    "MB/s",
                    "GB/s",
                    "TB/s"
                ];
                var tag = "B/s";
                $.each(steps, function (i) {
                    if (netRx < 1024) {
                        tag = steps[i];
                        return false;
                    }
                    netRx /= 1024;
                    netTx /= 1024;
                });
                nicName = widgetData['Net'][0]['Interface'][0]['nic_name'];
                hddPct = widgetData['Hdd'][0]['Disk'][0]['hdd_pct_used'];
                hddName = widgetData['Hdd'][0]['Disk'][0]['hdd_path'];
                var hddData = widgetData['Hdd'][0]['Disk'];
                $.each(hddData, function (key, data) {
                    hddArray.push([data['hdd_name'], data['hdd_pct_used']]);
                });
                var netData = widgetData['Net'][0]['Interface'];
                $.each(netData, function (key, data) {
                    nicRxArray.push([data['nic_name'], data['nix_rx']]);
                    nicTxArray.push([data['nic_name'], data['nic_tx']]);
                });
            }

            var seriesSet = [
                {
                    name: 'CPU',
                    y: cpuPct,
                    percent: cpuPct,
                    color: '#3E9A99',
                    drilldown: null
                },
                {
                    name: 'Memory',
                    y: memPct,
                    percent: memPct,
                    color: '#83D973',
                    drilldown: null
                },
                {
                    name: 'Tx - ' + nicName,
                    y: netTx,
                    percent: netTx,
                    value: netRx + tag,
                    color: '#DE5353',
                    drilldown: 'net_tx'
                },
                {
                    name: 'Rx - ' + nicName,
                    y: netRx,
                    percent: netRx,
                    value: netRx + tag,
                    color: '#DE5353',
                    drilldown: 'net_tx'
                },
                {
                    name: hddName,
                    y: hddPct,
                    percent: hddPct,
                    color: '#FFE066',
                    drilldown: "hdd"
                }
            ];

            var drillDownSet = [
                {
                    id: 'net_rx',
                    name: "Rx",
                    data: nicRxArray
                },
                {
                    id: 'net_tx',
                    name: "Tx",
                    data: nicTxArray
                },
                {
                    id: 'hdd',
                    name: "Storage",
                    data: hddArray
                }
            ];

            seriesData = [
                {
                    name: 'Used',
                    data: seriesSet
                }
            ];

            drillDownData = {
                series: drillDownSet
            };
        }
        return [seriesData, drillDownData]
    }

    initGrid(data);
    initListeners(data);
    this.addWidget = addWidget;
    this.updateWidget = updateWidget;
    this.removeWidget = removeWidget;
    this.serialize = serialize;

}