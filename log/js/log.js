var grep = "";
var invert = 0;
var documentHeight = 0;
var scrollPosition = 0;
var scroll = true;
var loaded = false;
var count = 0;
var odd = true;
var classFilters = [];
var stringFilter = "";
$("head").append("<style id='dynamicStylesheet'></style>");
var styles = $("#dynamicStylesheet");
var textFilter = $('#textFilter');

var myStyles = (function() {
    // Use the first style sheet for convenience
    var sheet = $('#dynamicStylesheet')[0].sheet;

    // Delete a rule from sheet based on the selector
    function deleteRule(selector) {
        // Get rules

        var rules = sheet.rules || sheet.cssRules; // Cover W3C and IE models
        // Search for rule and delete if found
        $.each(rules, function(key) {
            if (selector === rules[key].selectorText) {
                sheet.deleteRule(key);
            }
        });
    }

    // Add a rule to sheet given a selector and CSS text
    function addRule(selector, text) {
        console.log("SHEETS: ", document.styleSheets);

        console.log("Adding rule for selector " + selector);
        // First delete the rule if it exists
        deleteRule(selector);
        // Then add it
        sheet.insertRule(selector + text);
        console.log("SHEET: ", sheet);
    }

    // Return object with methods
    return {
        'addRule': addRule,
        'deleteRule': deleteRule
    };
}());

$(document).ready(function() {
    buildList();
    $(document).on('click', '.jsonParse',function(e) {
        e.preventDefault();
        console.log("Form submitted.");
        var data = $(this).data('json');
        console.log("Data: ",data);
        data = decodeURIComponent(data);
        data = data.replace(/\+/g, ' ');
        loadJson("http://jsonselector.com/process",{'rawjson':data});
    });

    $(document).on('dblclick', 'td',function() {
        var data = $(this).html();
        console.log("Data: ",data);
        var dummy = document.createElement("input");
        document.body.appendChild(dummy);
        dummy.setAttribute("id", "dummy_id");
        document.getElementById("dummy_id").value=JSON.stringify(data);
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
    });

    $(document).on('click', '.selectAll', function(){
        $(this).siblings('select').find('option').prop('selected', true);
        updateFilters();
    });

    $(document).on('click', '#filterLog', function(){filterLines()});

    $(document).on('click', '#clearFilter', function() {
        textFilter.val("");
        textFilter.text("");
        filterLines();
    });

    $(document).on('change', '.tableFilter', function() {
        updateFilters();
    });

    $(document).on('keypress', textFilter, function(e){
        if(e.keyCode===13) filterLines();
    });

    //If window is resized should we scroll to the bottom?
    $(window).resize(function() {
        if (scroll) {
        scrollToBottom();
    }
    });
    //Handle if the window should be scrolled down or not
    $(window).scroll(function() {
        documentHeight = $(document).height();
        scrollPosition = $(window).height() + $(window).scrollTop();
        if (documentHeight <= scrollPosition) {
            scroll = true;
        } else {
            scroll = false;
        }
    });

    updateLog();
    scrollToBottom();
    setInterval(function(){
        if (loaded) updateLog();
    },1000);
});

//This function scrolls to the bottom
function scrollToBottom() {
    $("html, body").animate({scrollTop: $(document).height()}, "fast");
}

//This function queries the server for updates.
function updateLog() {
    var url = '?fetch=true&apiToken=foo';
    var results = $('#results');
    var lines = [];
    if (loaded) url += "&refresh=true";
    $.getJSON(url, function(data) {
        if (data != null) {
            $.each(data, function (key, value) {
                var line = formatLine(value);
                if (loaded) {
                    results.append(line);
                } else {
                    lines.push(line);
                }
            });
        }
        if (!loaded) {
            $('.load-div').hide();
            results.append(lines);
            console.log("DATA: ",data);
            loaded = true;
        }

        if (scroll) {
            scrollToBottom();
        }
    });
}

function filterLines() {
    stringFilter = $('#textFilter').val();
    $('.textFilter').removeClass('textFilter');
    if (stringFilter !== "") {
        $('tr.line:not(:contains('+ stringFilter +'))').addClass('textFilter');
    }
}

function updateFilters() {
    var filters = [];
    var levelFilters = $('#levelSelect').find('option:not(:selected)');
    var docFilters = $('#docSelect').find('option:not(:selected)');
    $.each(levelFilters, function(){
        filters.push(".line." + $(this).val().toLowerCase());
    });
    $.each(docFilters, function(){
        filters.push(".line." + $(this).val());
    });
    console.log("FILTERS: ", filters);
    // Filters cleared
    if (JSON.stringify(filters) !== JSON.stringify(classFilters)) {
        classFilters = filters;
        setRules();
    }
}


function formatLine(line) {
    console.log("FORMATTING: ", line);
    var doc = titleCase(line.doc.replace("_", " "));
    doc = doc.replace(".log", "");
    doc = doc.replace(".php", "");
    var numSpan = '<th scope="row" class=lineNo>' + line.line + '</th>';
    var stamp = line.stamp;
    var userName = line.user;
    var functionName = line.func;
    var stampSpan = '<td class="stamp stampCol">' + stamp + '</td>';
    if (~line.doc.indexOf("Error")) {
        line.level = "ERROR";
    }
    var lineClass = line.doc.replace(" ", "_").toLowerCase();
    var classes = ["line", lineClass, doc.replace(" ", "_")].join(" ");
    var docSpan = "<td><span class='doc'>" + doc + '</span></td>';
    var levelSpan = "<td><span class='badge level-badge " + line.level + "'>" + line.level + '</span></td>';
    var paramString = "<td class='userCol'>" + userName + "</td><td class='funcCol'>" + functionName + "</td>";
    var body = line.body;
    if (line.url) {
        console.log("Line has a url.");
        body = body.replace("[URL]",'<a href="' + line.url + '" target="_blank">' + line.url + '</a>');
    }

    if (line.json) {
        console.log("Line has json.");
        var jsonString = JSON.stringify(line.json);
        var jsonLink = '<a href="" class="jsonParse" title="' + htmlentities(jsonString) + '" data-json="' + urlencode(jsonString) + '">[JSON]</a>';
        body = body.replace("[JSON]",jsonLink);
    }
    var bodySpan = "<td class='bodySpan'>" + body + "</td>";

    odd = !odd;
    return "<tr class='" + classes + "'>" + numSpan + docSpan + levelSpan + stampSpan  + paramString + bodySpan + "</tr>";
}

function loadJson(path, params, method) {
    //Null check
    method = method || "post"; // Set method to post by default if not specified.

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);
    form.setAttribute("target","_blank");

    //Fill the hidden form
    if (typeof params === 'string') {
    var hiddenField = document.createElement("input");
    hiddenField.setAttribute("type", "hidden");
    hiddenField.setAttribute("name", 'data');
    hiddenField.setAttribute("value", params);
    form.appendChild(hiddenField);
}
    else {
    for (var key in params) {
    if (params.hasOwnProperty(key)) {
    var hiddenField = document.createElement("input");
    hiddenField.setAttribute("type", "hidden");
    hiddenField.setAttribute("name", key);
    if(typeof params[key] === 'object'){
    hiddenField.setAttribute("value", JSON.stringify(params[key]));
}
    else{
    hiddenField.setAttribute("value", params[key]);
}
    form.appendChild(hiddenField);
}
}
}

    document.body.appendChild(form);
    form.submit();
}

function htmlentities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function urlencode(str) {
    str = (str + '');

    // Tilde should be allowed unescaped in future versions of PHP (as reflected below),
    // but if you want to reflect current
    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
    return encodeURIComponent(str)
        .replace(/!/g, '%21')
        .replace(/'/g, '%27')
        .replace(/\(/g, '%28')
        .replace(/\)/g, '%29')
        .replace(/\*/g, '%2A')
        .replace(/%20/g, '+')
}

function buildList() {
    var options = "";
    $.each(logData, function(key) {
        key = titleCase(key.replace("_", " "));
        key = key.replace(".log", "");
        key = key.replace(".php", "");
        var value = key.replace(" ", "_");
        options += '<option value="'+value+'" selected>'+key+'</option>'
    });
    $('#docSelect').html(options);
}

function setRules() {
    var sheet = $('#dynamicStylesheet')[0].sheet;
    var rules = sheet.rules || sheet.cssRules; // Cover W3C and IE models

    $.each(rules, function(key) {
        try {
            sheet.deleteRule(key);
        } catch (DOMException) {

        }
    });

    $.each(classFilters, function(key, selector){
        sheet.insertRule(selector + " { display: none }");
    });

}

function titleCase(str) {
    return str.split(' ').map(function(word) {
        return (word.charAt(0).toUpperCase() + word.slice(1));
    }).join(' ');
}