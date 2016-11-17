var websocket = {};
var userlist = {};
var client_id = 0;
var to_client_id = 0;
var GET = getRequest();    
   
function init() {    
    initWebSocket();   
}    

function initWebSocket() {
    var wsUri = wavechat.server;
    websocket = new WebSocket(wsUri);   
    websocket.onopen = function(evt) {   
        onOpen(evt)   
    };   
    websocket.onclose = function(evt) {
        layer.confirm('聊天服务器已关闭', {
            btn: ['确定'] //按钮
        }, function(){
            location.href = '/site/logout';
        });
    };   
    websocket.onmessage = function(evt) {   
        onMessage(evt)   
    };   
    websocket.onerror = function(evt) {   
        onError(evt)   
    };   
}


function getRequest() {
    var url = location.search; // 获取url中"?"符后的字串
    var theRequest = new Object();
    if (url.indexOf("?") != -1) {
        var str = url.substr(1);

        strs = str.split("&");
        for (var i = 0; i < strs.length; i++) {
            var decodeParam = decodeURIComponent(strs[i]);
            var param = decodeParam.split("=");
            theRequest[param[0]] = param[1];
        }

    }
    return theRequest;
}

function xssFilter(val) {
    val = val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\x22/g, '&quot;').replace(/\x27/g, '&#39;');
    return val;
}

function parseXss(val) {
    val = val.replace(/#(\d*)/g, '<img src="/resouce/img/face/$1.gif" />');
    val = val.replace('&amp;', '&');
    return val;
}

function GetDateT(time_stamp) {
    var d;
    d = new Date();

    if (time_stamp) {
        d.setTime(time_stamp * 1000);
    }
    var h, i, s;
    h = d.getHours();
    i = d.getMinutes();
    s = d.getSeconds();

    h = ( h < 10 ) ? '0' + h : h;
    i = ( i < 10 ) ? '0' + i : i;
    s = ( s < 10 ) ? '0' + s : s;
    return h + ":" + i + ":" + s;
}

function onOpen(evt) {
    // 用户登录
    var msg = new Object();
    msg.cmd = 'login';
    msg.user_id = user_id;
    msg.username = username;
    msg.avatar = avatar;
    msg.chat_key = chat_key;
    doSend(msg);  
} 

function onMessage(evt) {   
    var message = $.evalJSON(evt.data);
    var cmd = message.cmd;
    if (cmd == 'login') {
        client_id = $.evalJSON(evt.data).fd;
        //获取在线列表
        doSend({cmd : 'getOnline'});
        //获取历史记录
        doSend({cmd : 'getHistory'});

    }else if (cmd == 'getOnline') {
        showOnlineList(message);
    }else if (cmd == 'getHistory') {
        showHistory(message);
    }else if (cmd == 'newUser') {
        showNewUser(message);
    }else if (cmd == 'fromMsg') {
        showNewMsg(message, 0);
    }else if (cmd == 'offline') {
        var cid = message.fd;
        showNewMsg(message, 0);
        delUser(cid);
    }else if (cmd == 'login_error') {
        layer.confirm(message.content, {
            btn: ['确定'] //按钮
        }, function(){
            location.href = '/site/logout';
        });
    }
}   

function onError(evt) {   
    writeToScreen('<span style="color: red;">ERROR:</span> '+ evt.data);   
}    

function doSend(msg) {
    websocket.send($.toJSON(msg));
}

/**
 * 在屏幕写内容
 */
function writeToScreen(message) {   
    var pre = document.createElement("p");   
    pre.style.wordWrap = "break-word";   
    pre.innerHTML = message;   
    output.appendChild(pre);   
}    

window.addEventListener("load", init, false);

function selectUser(cid, isGetHistory) {
    if (client_id == cid) {
        alert('不能与自己聊天！');
        return false;
    }
    if (userlist[cid]) {
        var username = userlist[cid].username;
        to_client_id = cid;

        if (isGetHistory == 1) {
            // 获取历史记录
            var msg = {cmd : 'getHistory', 'to': to_client_id, 'channal': 1};
            doSend(msg);
        }
        

        $("#chat-with").html('与'+username+'聊天');
    }else{
        alert('没有此用户');
    }
}

/**
 * 在线列表
 */
function showOnlineList(dataObj) {
    var txt = '';
    for (var i = dataObj.list.length - 1; i >= 0; i--) {
        userlist[dataObj.list[i].fd] = dataObj.list[i];
        txt += '<li class="list-group-item" id="user_'+dataObj.list[i].fd+'" onclick="selectUser('+dataObj.list[i].fd+', 1)">';
            txt += '<img src="'+dataObj.list[i].avatar+'" width="30px" height="30px"> ';
            txt += dataObj.list[i].username;
        txt += '</li>';
    }
    $("#user-list").html(txt);
}

/**
 * 显示历史记录
 */
function showHistory(dataObj) {
    // alert(JSON.stringify(dataObj));
    for (var i = 0; i < dataObj.history.length; i++) {
        showNewMsg(dataObj.history[i], 0);
    }
}

/**
 * 有新用户
 */
function showNewUser(dataObj) {
    // alert(JSON.stringify(dataObj));
    var txt = '';
    if (!userlist[dataObj.fd]) {
        userlist[dataObj.fd] = dataObj;
        txt += '<li class="list-group-item" id="user_'+dataObj.fd+'" onclick="selectUser('+dataObj.fd+', 1)">';
            txt += '<img src="'+dataObj.avatar+'" width="30px" height="30px"> ';
            txt += dataObj.username;
        txt += '</li>';
        $("#user-list").append(txt);
    }
}

/**
 * 显示新消息
 */
function showNewMsg(dataObj, isGetHistory) {
    // alert(JSON.stringify(dataObj));
    var content = xssFilter(dataObj.content);
    content = parseXss(content);

    if (dataObj.type == 'img') {
        content = '<a href="'+content+'" target="_blank"><img src="'+content+'" width="100%"></a>';
    }

    var fromId = dataObj.from;
    var channal = dataObj.channal;
    var time_str;
    if (dataObj.time) {
        time_str = GetDateT(dataObj.time)
    } else {
        time_str = GetDateT()
    }

    var txt = '';
    if (client_id == fromId) {
        txt += '<div class="direct-chat-msg right">';
            txt += '<div class="direct-chat-info clearfix">';
                txt += '<span class="direct-chat-name pull-right">'+userlist[fromId].username+'</span>';
                    txt += '<span class="direct-chat-timestamp pull-left">'+time_str+'</span>';
            txt += '</div>';
            txt += '<img class="direct-chat-img" src="'+userlist[fromId].avatar+'">';
            txt += '<div class="direct-chat-text">';
                txt += content;
            txt += '</div>';
        txt += '</div>';
    }else{
        txt += '<div class="direct-chat-msg">';
            txt += '<div class="direct-chat-info clearfix">';
                txt += '<span class="direct-chat-name pull-left">'+userlist[fromId].username+'</span>';
                txt += '<span class="direct-chat-timestamp pull-right">'+time_str+'</span>';
            txt += '</div>';
            txt += '<img class="direct-chat-img" src="'+userlist[fromId].avatar+'">';
            txt += '<div class="direct-chat-text">';
                txt += content;
            txt += '</div>';
        txt += '</div>';
    }
    if (channal == 0) {
        $("#group-chat").append(txt);
        $('#group-chat')[0].scrollTop = 1000000;
    }else{
        if (client_id != fromId) {
            selectUser(fromId, isGetHistory);
        }
        $("#single-chat").append(txt);
        $('#single-chat')[0].scrollTop = 1000000;
    }
}

/**
 * 删除用户
 */
function delUser(userid) {
    $('#user_' + userid).remove();
    to_client_id = 0;
    $("#single-chat").children('div').remove();
    $("#chat-with").html('单聊');
    delete(userlist[userid]);
}

/**
 * 退出
 */
function logout() {
    websocket.close();
    layer.load(1);
}

/**
 * 发消息 0-群发 1-单聊
 */
function sendMsg(channal)
{
    var content = $("#content"+channal).val();
    if (typeof content == "string") {
        content = content.replace(" ", "&nbsp;");
    }
    if (content == '') {
        alert('请输入内容！');
    }else{
        var msg = new Object();
        msg.cmd = 'message';
        msg.from = client_id;
        if (channal == 1) {
            if (to_client_id == 0) {
                msg.to = 0;
                alert('没有选择单聊用户！');
                return false;
            }else{
                msg.to = to_client_id;
            }
        }
        msg.channal = channal;
        msg.type = 'text';
        msg.content = content;
        doSend(msg);
        showNewMsg(msg, 0);
    }
    $("#content"+channal).val('');
}

/**
 * 发送图片 imgurl  channal 0-群发 1-单聊
 */
function sendImg(imgurl, channal)
{
    var content = imgurl;
    if (typeof content == "string") {
        content = content.replace(" ", "&nbsp;");
    }
    if (content == '') {
        alert('请输入内容！');
    }else{
        var msg = new Object();
        msg.cmd = 'message';
        msg.from = client_id;
        if (channal == 1) {
            if (to_client_id == 0) {
                msg.to = 0;
                alert('没有选择单聊用户！');
                return false;
            }else{
                msg.to = to_client_id;
            }
        }
        msg.channal = channal;
        msg.type = 'img';
        msg.content = content;
        doSend(msg);
        showNewMsg(msg, 0);
    }
}
