!function($) {
    $.flexWidget = function(action, id) {
        var target = $('#widget' + id);
        var type = target.data('type');
        if (action === 'init') {
            console.log('Initializing target from id ' + id, target);
            init(type, target);
        } else if (action === 'update') {
            console.log('Updating target from id ' + id, target);
            update(type, target);
        } else {
            initTemplates();
            initGrid();
            console.log('Initializing the grid.');
        }

        function initGrid() {
            console.log("Max rows?");
            var options = {
                alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
                cellHeight: 70,
                acceptWidgets: true,
                animate: true,
                float: true,
                height: 10
            };

            var wl = $('#widgetList');

            wl.gridstack(options);

            $('#widgetDeleteList').gridstack(options);
            widgetList = wl.data('gridstack');

            wl.on('dragstop', function(event, ui) {
                console.log('Drag stop');
                //saveWidgetContainers();
            });

            wl.on('change', function() {
                console.log('Change');
                saveWidgetContainers();
            });

        }

        function initTemplates() {
            console.log("INITIALIZING TEMPLATES.");
            var wt = $('#widgetTemplates').clone().prop('id', 'widgetAddList');
            wt.css('display', 'block');
            var wd = $('#widgetDrawer');
            wd.html("");
            wt.appendTo(wd);

            var addOptions = {
                cellHeight: 70,
                acceptWidgets: false
            };

            var wal = $('#widgetAddList');

            wal.gridstack(addOptions);

            wal.on('removed', function() {
                initTemplates();
            });

        }

        function init(type, target) {
            console.log("ItemEL: ", target);
            var targetId = target.data('target');
            console.log("Type is " + type, "target is " + targetId);
            switch(type) {

                case 'generic':
                    console.log('No init function defined for generic');
                    break;

                case 'serverStatus':

                    console.log("Trying to add server status widget...");
                    if (window.hasOwnProperty('plexServerId')) {
                        console.log("We have a server ID.");
                        target.attr('data-target', window['plexServerId']);
                    } else {
                        console.log("We need that ID.");
                    }

                    break;

                case 'statusMonitor':

                    if (target.data('target') === undefined || target.data('target' === 0) {
                        id = $('#AppzDrawer').find('.drawer-item').attr('id').replace('Btn','');
                        console.log('No defined target, using' + id);
                    } else {
                        id = target.data('target');
                        console.log('using target ID of ' + id);
                    }
                    console.log('Target id is  ' + id, target);

                    var targetBtn = $('#' + id + 'Btn');
                    var dataSet = targetBtn.data();
                    console.log('Dataset: ', dataSet);
                    if (dataSet !== undefined) {
                        var icon = dataSet['icon'];
                        var label = dataSet['label'];
                        var url = dataSet['url'];
                        var color = dataSet['color'];
                        var color2 = shadeColor(color, -30);
                        var colString = 'background: linear-gradient(60deg, '+color+', '+color2+');';
                        console.log('Motherfucker...');
                        target.attr('data-target', id);
                        target.attr('data-icon', icon);
                        target.attr('data-label', label);
                        target.attr('data-color', color);
                        target.attr('data-url', url);

                        target.find('.service-icon').attr('class', 'service-icon ' + icon);
                        target.find('.statTitle').text(label);
                        target.find('.offline-indicator').show();
                        target.find('.online-indicator').hide();
                        target.find('.card-background').attr('style', colString);
                    }
                    break;

                case 'userTest':
                    console.log('No init function defined for userTest');
                    break;

                default:
                    return false;
            }
        }

        function update(type, target) {
            switch(type) {

                case 'generic':
                    console.log('No update function defined for generic');
                    break;

                case 'serverStatus':
                    console.log('No update function defined for serverStatus');
                    break;

                case 'statusMonitor':
                    console.log('No update function defined for statusMonitor');
                    break;

                case 'userTest':
                    console.log('No update function defined for userTest');
                    break;

                default:
                    return false;
            }
        }
    }
}( jQuery );