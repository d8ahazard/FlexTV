var grep = "";
var invert = 0;
var documentHeight = 0;
var scrollPosition = 0;
var scroll = true;
var loaded = false;
var count = 0;
var odd = true;
var filtering = false;

$(document).ready(function() {

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

    // Setup the settings dialog
    $("#settings").dialog({
        modal : true,
        resizable : false,
        draggable : false,
        autoOpen : false,
        width : 590,
        height : 270,
        buttons : {
            Close : function() {
                $(this).dialog("close");
            }
        },
        open : function(event, ui) {
            scrollToBottom();
        },
        close : function(event, ui) {
            grep = $("#grep").val();
            invert = $('#invert input:radio:checked').val();
            $("#results").text("");
            lastSize = 0;
            $("#grepspan").html("Grep keyword: \"" + grep + "\"");
            $("#invertspan").html("Inverted: " + (invert == 1 ? 'true' : 'false'));
        }
    });
    //Close the settings dialog after a user hits enter in the textarea
    var grepBtn = $('#grep');
    grepBtn.keyup(function(e) {
        if (e.keyCode == 13) {
            $("#settings").dialog('close');
        }
    });
    $('#filterLog').on('click', function(){filterLog()});
    $('#clearFilter').on('click', function() {showAll()});
    $('#filterText').keypress(function(e){
        if(e.keyCode===13)
            filterLog();
    });
    //Focus on the textarea
    grepBtn.focus();
    //Settings button into a nice looking button with a theme
    //Settings button opens the settings dialog
    $("#grepKeyword").click(function() {
        $("#settings").dialog('open');
        $("#grepKeyword").removeClass('ui-state-focus');
    });


    //Some window scroll event to keep the menu at the top
    $(window).scroll(function(e) {
        if ($(window).scrollTop() > 0) {
            $('.float').css({
                position : 'fixed',
                top : '0',
                left : 'auto'
            });
        } else {
            $('.float').css({
                position : 'static'
            });
        }
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
scrollToBottom();
updateLog();
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
    if (loaded) url += "&refresh=true";
    $.getJSON(url, function(data) {
        if (!loaded) {
            $('.load-div').hide();
            console.log("DATA: ",data);
            loaded = true;
        }
        if (data != null) {
            var levels = [];
            var docs = [];
            $.each(data, function (key, value) {
                $("#results").append(formatLine(value));
            });

            if (filtering) filterLog();
        }
        if (scroll) {
            scrollToBottom();
        }
    });
}

function filterLog() {
    var search = $('#filterText').val();
    if (search === "") {
        showAll();
    } else {
        console.log("Filtering for " + search);
        filtering = search;
        $("tr").not(':first').hide();
        $("tr:contains(" + search + ")").show();
    }
}

function showAll() {
    filtering = false;
    $("tr").show();
    $("#filter").val("");
    $("#filter").text("");
}

function formatLine(line) {
    console.log("FORMATTING: ", line);
    var numSpan = '<th scope="row" class=lineNo>' + line.line + '</th>';
    var stamp = line.params.shift();
    if (stamp === undefined) stamp = "";
    var stampSpan = '<td class="stamp">' + stamp + '</td>';
    var rowClass = "";
    if (~line.doc.indexOf("Error")) {
        rowClass = " error";
        line.level = "ERROR";
    }
    var docSpan = "<td><span class='doc'>" + line.doc + '</span></td>';
    var levelSpan = "<td><span class='badge level-badge " + line.level + "'>" + line.level + '</span></td>';
    var paramString = "";
    $.each(line.params, function(key, param){
        paramString += "<td class='param'>" + param + "</td>";
    });
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
    return "<tr class='line" + rowClass + "'>" + numSpan + docSpan + levelSpan + stampSpan  + bodySpan + "</tr>";
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