// Options:
// initWidget, updateWidget (the widget ID to intialize or update)

// initListeners, initTemplates, initGrid - params = [
//  main: The "display" grid users add and remove widgets from
//  templates: A hidden div filled with the contents of widget()::getMarkup("HTML") in php
//  list: The active drawer users will select new widgets from. Populated from 'templates'
//  delete: (optional) If specified, a grid that items can be dragged to to be deleted
//  save: (function(data){}) The function to execute when saving widget data to the server.
//        Data will be serialzied from the data tags of the current "main" grid.
//        Data will be a JSON-serializable array of widget objects.
//
// ]



!function($) {
    $.flexWidget = function(data, param) {
        if (data === 'addWidget' || data === 'initWidget' || data === 'updateWidget') {
                if (data === 'initWidget') {
                    initWidget(param);
                } else if (data === 'updateWidget') {
                    updateWidget(param);
                } else {
                    addWidget(param);
                }
        } else if (data === 'initListeners') {
            initListeners(param);
        } else if (data === 'refreshWidgets') {
            console.log("Refreshing widgets.");
            refreshWidgets();
        } else if (typeof data === "object") {
            initGrid(data);
            initListeners(data);
            var returnFunc = data['save'];
            window['return_func'] = returnFunc;
            returnFunc(serialize());
        }


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
                height: 10
            };

            if (data.hasOwnProperty('main')) {
                if (data.hasOwnProperty('delete')) {
                    options['removable'] = data['delete'];
                }
                var mainTarget = data['main'];
                var wl = $(mainTarget);

                wl.gridstack(options);

                wl.on('dragstop', function () {
                    console.log('Drag stop');
                    returnFunc(serialize());
                });

                wl.on('change', function () {
                    console.log('Change');
                    returnFunc(serialize());
                });
                widgetList = wl.data('gridstack');
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
                    acceptWidgets: false
                };

                wt.gridstack(addOptions);

                widgetDrawer.on('removed', function() {
                    initGrid({'templates': templateTarget, 'drawer': drawerTarget})
                });
            }
        }

        function initListeners(data) {
            console.log("Initializing listeners.");

            $(document).on('click', '.widgetEdit', function() {
                console.log("Widget edit button clicked.");
                var parent = $(this).closest('.widgetCard');
                parent.find('.card-settings').slideToggle();
                parent.toggleClass('editCard');
                var noResize = parent.hasClass('editCard');
                parent.attr('data-gs-no-resize', noResize);
                //parent.toggleClass('editCard ui-resizable-autohide');
                if (! parent.hasClass('editCard')) {
                    $('.clickJack').show();
                }

            });

            $(window).on('click', '.widgetRefresh', function() {
                var parent = $(this).closest('.widgetCard');
            });

            $(window).on('click', '.widgetDelete', function() {
                var parent = $(this).closest('.widgetCard');
            });

            // Set the value of the setting thinger to the widget data, refresh
            $(window).on('change', '.widgetSetting', function() {
                var parent = $(this).closest('.widgetCard');
                console.log("Got a change for ", parent);
            });

            if (data.hasOwnProperty('main')) {
                $(data['main']).on('added', function(evt, items) {
                    for (var i = 0; i < items.length; i++) {
                        var item = $(items[i]['el'][0]);
                        initWidget(item);
                    }
                });
            }

            if (data.hasOwnProperty('delete')) {
                $(data['delete']).on('added', function() {
                    $(this).html("");
                });
            }

            if (data.hasOwnProperty('templates')) {
                var drawer = data['templates'];
                if (data.hasOwnProperty('list')) {
                    $(document).on('click', data['list'], function () {
                        $(drawer).slideUp();
                    });
                }

                if (data.hasOwnProperty('main')) {
                    $(document).on('click', data['main'], function () {
                        $(drawer).slideUp();
                    });
                }
            }
        }

        function serialize() {
            var widgets = $('#widgetList').find('.widgetCard');
            var widgetData = [];
            $.each(widgets, function() {
                var elemData = $(this).info();
                var id = false;
                if (!elemData.hasOwnProperty('gs-id') && !elemData.hasOwnProperty('id')) {
                    id = Math.floor((Math.random() * 100000) + 1000);
                    elemData['gs-id'] = id;
                }
                widgetData.push(elemData);
            });
            return widgetData;
        }

        // Build a widget into the UI using previously stored data
        function addWidget(widget) {
            console.log("Adding widget: ", widget);
            var result = false;
            // It should be safe to call this from the DOM, as we've declared this name within widgets.js
            var addAppList = $('#widgetAddList');
            if (widget.hasOwnProperty('type')) {
                var type = widget['type'];
                var source = addAppList.find('[data-type="'+type+'"]');
                if (source.length) {
                    var clone = source.clone();
                    for (var key in widget) if (widget.hasOwnProperty(key)) {
                        clone.attr('data-' + key, widget[key]);
                        var inputItem = '.' + type + key + "Input";
                        var inputTarget = clone.find(inputItem);
                        if (inputTarget.length) inputTarget.val(widget[key]);

                    }
                    clone.attr('data-gs-auto-position', 0);
                    clone.attr('data-gs-no-resize', 0);

                    result = true;
                    var id = false;
                    if (widget.hasOwnProperty('gs-id')) id = widget['gs-id'];
                    if (id === false) id = Math.floor((Math.random() * 100000) + 1000);
                    clone.attr('id',id);
                    clone.attr('data-gs-id',widget['gs-id']);
                    clone.appendTo($('#widgetList'));
                    widgetList.makeWidget(clone);
                    initWidget($(id));
                }
            } else {
                console.error("Widget data doesn't have a type...");
            }
            return result;
        }

        // Update a widget in the UI using fetched data
        function updateWidget(widgetData) {
            var widgetId = widgetData['gs-id'];
            var target = $('#widget' + widgetId);
            target.bind("DOMSubtreeModified", function () {
                console.log("Element readY??");
            });
            var checkCount = 0;
            console.log("Targeting widget with ID of " + widgetId, target);
            while(!target.length && checkCount < 20) {

                console.log("NO ELEMENT, Re-checking...");
                target = $('#widget' + widgetId);
                checkCount++;
            }
            var type = widgetData['type'];


            switch(type) {
                case 'generic':
                    console.log('No update function defined for generic');
                    break;

                case 'nowPlaying':
                    console.log('No update function defined for nowPlaying');
                    break;

                case 'systemMonitor':
                    var devOutput = "";
                    if (window.hasOwnProperty(devices)) {
                        console.log("We have a device list.");
                        var deviceList = JSON.parse(window['devices']);
                        if (deviceList.hasOwnProperty('Server')) {
                            var serverList = deviceList['Server'];
                            var i = 0;
                            var widgetTarget = false;
                            if (widgetData.hasOwnProperty('target')) {
                                widgetTarget = widgetData['target'];
                            }
                            $.each(serverList, function (key, device) {
                                var selected = "";
                                if (widgetTarget) {
                                    if (widgetTarget = device['Id']) {
                                        selected = " selected";
                                    }
                                } else {
                                    if (i = 0) {
                                        selected = " selected";
                                    }
                                }
                                var id = device["Id"];
                                var name = device["Name"];
                                if (device['HasPlugin']) {
                                    devOutput += "<option data-type='Server' value='" + id + "'" + selected + ">" + name + "</option>";
                                    if (selected !== "") {
                                        widgetData['target'] = id;
                                        widgetData['uri'] = device['Uri'];
                                        widgetData['token'] = device['Token'];
                                    }
                                }
                            });
                        }
                    }
                    var list = target.find('.serverList');
                    console.log("Setting serverList to " + devOutput, list);
                    list.html(devOutput);

                    if (widgetData.hasOwnProperty('stats')) {
                        var statData = widgetData['stats'];
                        console.log("We have statData: ", statData);
                    }
                    break;

                case 'serverStatus':
                    console.log('No update function defined for serverStatus');
                    break;

                case 'statusMonitor':
                    var targetId = widgetData['target'];
                    var targetDiv = $('#AppzDrawer').find('#' + targetId + 'Btn');
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
                    if (widgetData['service-status'] === "online") {
                        console.log("OI: ", target.find('.offline-indicator'));
                        target.find('.offline-indicator').hide();
                        target.find('.online-indicator').show();
                    } else {
                        target.find('.offline-indicator').show();
                        target.find('.online-indicator').hide();
                    }
                    var color2 = shadeColor(widgetData['color'], -30);
                    var colString = "background: linear-gradient(60deg, "+widgetData['color']+", "+color2+");";
                    var ss = target.find(".card.m-0.service-status");
                    ss.attr('style', colString);
                    break;

                case 'userTest':
                    console.log('No update function defined for userTest');
                    break;

                default:
                    return false;
            }
        }

        // Initialize a widget added to the grid via drag/drop
        function initWidget(widget) {
            console.log("ItemEL: ", widget);
            var type = widget.data('type');
            var targetId = widget.data('target');
            widget.attr('data-gs-auto-position', 0);
            console.log("Type is " + type, "target is " + targetId);
            var id = Math.floor((Math.random() * 100000) + 1000);
            widget.attr('id', "widget" + id);
            widget.attr('data-gs-id',id);

            switch(type) {
                case 'generic':
                    console.log('No init function defined for generic');
                    break;

                case 'nowPlaying':
                    widget.find('#currentActivity').removeClass('list-group-item-danger');
                    break;

                case 'serverStatus':

                    console.log("Trying to add server status widget...");
                    if (window.hasOwnProperty('plexServerId')) {
                        console.log("We have a server ID.");
                        widget.attr('data-target', window['plexServerId']);
                    } else {
                        console.log("We need that ID.");
                    }
                    break;

                case 'systemMonitor':
                    var devOutput = "";
                    if (window.hasOwnProperty(devices)) {
                        console.log("We have a device list.");
                        var deviceList = JSON.parse(window['devices']);
                        if (deviceList.hasOwnProperty('Server')) {
                            var serverList = deviceList['Server'];
                            var i = 0;
                            $.each(serverList, function (key, device) {
                                var selected = "";
                                    if (i = 0) {
                                        selected = " selected";
                                    }
                                var id = device["Id"];
                                var name = device["Name"];
                                if (device['HasPlugin']) {
                                    devOutput += "<option data-type='Server' value='" + id + "'" + selected + ">" + name + "</option>";
                                    if (selected !== "") {
                                        widget.attr('data-target', id);
                                        widget.attr('data-uri', device['Uri']);
                                        widget.attr('data-token', device['Token']);
                                    }
                                }
                                i++;
                            });
                        }
                    }
                    var list = widget.find('.serverList');
                    console.log("Setting serverList to " + devOutput, list);
                    list.html(devOutput);
                    break;

                case 'statusMonitor':
                    if (widget.hasOwnProperty('data-target')) {
                        console.log("Data is set for widget??");
                    } else {
                        console.log("NO TARGET");
                    }
                    if (widget.data('target') === undefined || widget.data('target') === 0) {
                        var drawer = $('#AppzDrawer');
                        var drawerItems = drawer.find('.drawer-item');
                        if (drawerItems.length) {
                            id = drawer.find('.drawer-item').attr('id').replace('Btn','');
                            console.log('No defined target, using' + id);
                        } else {
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
                        var color2 = shadeColor(color, -30);
                        var colString = 'background: linear-gradient(60deg, '+color+', '+color2+');';
                        widget.attr('data-target', id);
                        widget.attr('data-icon', icon);
                        widget.attr('data-label', label);
                        widget.attr('data-color', color);
                        widget.attr('data-url', url);

                        widget.find('.service-icon').attr('class', 'service-icon ' + icon);
                        widget.find('.statTitle').text(label);
                        widget.attr('style', colString);
                    }
                    if (widget.attr('data-service-status') !== undefined) {
                        if (widget.attr('data-service-status') === "online") {
                            widget.find('.offline-indicator').hide();
                            widget.find('.online-indicator').show();
                        }
                    } else {
                        widget.attr('data-service-status', "offline");
                        widget.find('.offline-indicator').show();
                        widget.find('.online-indicator').hide();

                    }


                    $(document).on('change', '.serviceList', function() {
                        console.log("Service list changed, we need to do some magic...");
                        var target = $(this).closest('.widgetCard');
                        var selection = $(this).find(":selected").val();
                        target.data('target', selection);
                        console.log("Current value " + selection, target);
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

        function refreshWidgets() {
            var widgetList = serialize();
            $.each(widgetList, function(widget) {
                updateWidget(widget);
            });
        }
    }
}( jQuery );