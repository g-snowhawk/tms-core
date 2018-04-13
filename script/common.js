/**
 * Javascript Library for Tak-Me CMS
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */

var TM_Resizer = function() {
    this.handle    = undefined;
    this.resizebox = undefined;
    this.resizable = false;
    this.savewidth = null;
};
TM_Resizer.prototype.init = function() {
    var i, hdl, pr;
    var es = document.getElementsByClassName('resizable');
    if(es) {
        for(i = 0; i < es.length; i++){
            pr = es[i];
            if(!pr.id) pr.id = 'TMResizeBox' + i;

            var w = TM.getCookie(pr.id);
            if(w) pr.style.width = w;

            var style = document.defaultView.getComputedStyle(pr, '');
            var rel = style.position.match(/relative|absolute|fixed/i);
            if(!rel) pr.style.position = 'relative';
            hdl = pr.appendChild(document.createElement('div'));
            hdl.className = 'TM-resizehandler';
            hdl.addEventListener('mousedown', TM.resizer.start, false);
        }
    }
};
TM_Resizer.prototype.start = function(evn) {
    evn.preventDefault();
    TM.resizer.resizebox = evn.target.parentNode;
    document.addEventListener('mouseup', TM.resizer.stop, false);
    document.addEventListener('mousemove', TM.resizer.move, true);
};
TM_Resizer.prototype.stop = function(evn) {
    TM.setCookie(TM.resizer.resizebox.id, TM.resizer.resizebox.style.width);
    document.removeEventListener('mouseup', TM.resizer.stop, false);
    document.removeEventListener('mousemove', TM.resizer.move, true);
};
TM_Resizer.prototype.move = function(evn) {
    var cls = TM.resizer;
    var pos = evn.pageX;
    var mw = parseInt(cls.resizebox.dataset.minwidth);
    if(pos < mw) pos = cls.mw;
    cls.resizebox.style.width = pos + 'px';
};

/**
 * function on global navigation
 * 
 * version: 1.0.0
 */
var TM_Menu = function() {
    this.classname = 'navroot';
    this.levelTag  = 'UL';
    this.container = undefined;
    this.opener    = [];
    this.isopen    = [];
};
TM_Menu.prototype.openPullDown = function(evn, mo) {
    var i, j, u;
    var cls = TM.menu;
    var elm = evn.target;
    var prn = elm.parentNode;
    var cn = prn.childNodes;
    var lv = cls.getLevel(elm);
    evn.preventDefault();
    for(i = 0; i < cn.length; i++){
        u = cn[i];
        if(u.nodeName === cls.levelTag) {
            if(u.style.display !== 'block'){
                if(evn.type === 'mouseover'){
                    if(cls.isopen[lv] !== true) return;
                    for(j in cls.isopen){
                        if(j > lv) cls.isopen[j] = false;
                    }
                }
                if(mo !== true) cls.closePullDown(evn);
                u.style.display = 'block';
                cls.isopen[lv] = true;
                cls.opener.push(u);
                window.addEventListener('click', cls.closePullDown, false);
                evn.stopPropagation();
            } else {
                if(mo !== true) cls.closePullDown(evn);
            }
        }
    }
};
TM_Menu.prototype.closePullDown = function(evn) {
    var i, j, u;
    var cls = TM.menu;
    var el = evn.target;
    var lv = cls.getLevel(el);
    var tmp = [];
    while(u = cls.opener.shift()){
        if(evn.type === 'mouseover' && u.parentNode === el.parentNode){
            tmp.push(u);
            continue;
        }
        if(cls.isMyParent(el, u) === true){
            tmp.push(u);
            continue;
        }
        u.style.display = 'none';
        if(evn.type !== 'mouseover') cls.isopen[lv] = false;
    }
    var op = (evn.type === 'mouseover') ? cls.isopen[lv] : false;
    cls.opener = tmp;
    var ext = cls.checkExternalClick(el);
    if(cls.opener.length === 0 && evn.type !== 'mouseover'){
        window.removeEventListener('click', cls.closePullDown, false);
        cls.isopen = [];
    }
    if(op) cls.openPullDown(evn, true);
};
TM_Menu.prototype.checkExternalClick = function(el) {
    var cls = TM.menu;
    var prn = el.parentNode;
    while(prn && prn.className === cls.classname){
        if(chk === prn) return false;
        prn = prn.parentNode;
    }
    return true;
};
TM_Menu.prototype.isMyParent = function(elm, chk) {
    var cls = TM.menu;
    var prn = elm.parentNode;
    while(prn && prn.className !== cls.classname){
        if(chk === prn) return true;
        prn = prn.parentNode;
    }
};
TM_Menu.prototype.getLevel = function(el) {
    var lv = 0;
    var cls = TM.menu;
    var prn = el;
    while(prn && prn.className !== cls.classname){
        prn = prn.parentNode;
        if(prn && prn.nodeName === cls.levelTag) ++lv;
    }
    return lv;
};
TM_Menu.prototype.confirmSignOut = function(evn) {
    if(!confirm('サインアウトします。よろしいですか？')) {
        evn.preventDefault();
    }
};
TM_Menu.prototype.init = function(id) {
    var i, j, l, o;
    var r = document.getElementsByClassName(this.classname);
    for(o = 0; o < r.length; o++){

        // Style hack for shit browsers
        if(navigator.userAgent.match(/(Trident|MSIE)/)) {
            r[o].style.fontSize = 'inherit';
        }

        l = r[o].getElementsByTagName('li');
        for(i = 0; i < l.length; i++){
            var sub = 0;
            var li = l[i];
            o = li.getElementsByTagName('ul');
            if(o){
                for(j = 0; j < o.length; j++){
                    o[j].style.display = 'none';
                    ++sub;
                }
            }
            var cn = li.childNodes;
            for(j = 0; j < cn.length; j++){
                if(cn[j].nodeName === 'A') {
                    if(sub > 0) cn[j].addEventListener('click', this.openPullDown, true);
                    cn[j].addEventListener('mouseover', this.closePullDown, true);
                }
            }
        }
    }

    l = document.getElementById('signout');
    if(l) l.addEventListener('click', this.confirmSignOut, false);
};

/**
 * Anchor on document
 *
 * vertion: 1.0.0
 */
var TM_Anchor = function() {
};
TM_Anchor.prototype.setConfirm = function() {
    var i, el;
    var a = document.getElementsByTagName('a');
    for(i = 0; i < a.length; i++){
        el = a[i];
        if(el.dataset.confirm){
            el.addEventListener('click', TM.anchor.confirm, false);
        }
    }
};
TM_Anchor.prototype.confirm = function(evn) {
    var msg = '';
    switch(evn.target.dataset.confirm){
        case 'delete':
            msg = 'データを削除します。取消はできません\nよろしいですか？';
            break;
    }
    if(!confirm(msg)) evn.preventDefault();
};

/**
 * functions on forms
 *
 * vertion: 1.0.0
 */
var TM_Form = function() {
};
TM_Form.prototype.focusInFirstChild = function() {
    if(document.forms[0]){
        var i;
        var e = document.forms[0].elements;
        if(!e) return;
        for(i = 0; i < e.length; i++){
            var el = e[i];
            if(TM.isTextbox(el)) {
                el.focus();
                break;
            }
        }
    }
};
TM_Form.prototype.onSubmit = function(evn) {
    var i, pr, el;
    var fm = evn.target;
    var invalid = 0;
    for(i = 0; i < fm.elements.length; i++){
        el = fm.elements[i];
        if(!el.dataset.validate) continue;
        if(el.dataset.validate === 'necessary' && el.value === ''){
            pr = TM.getParentNode(el, '.fieldset');
            if(pr) pr.classList.add('invalid');
            ++invalid;
        }
    }
    if(invalid > 0){
        evn.preventDefault();
        alert('未入力項目があります');
        return;
    }
    if(fm.dataset.confirm){
        if(!confirm(decodeURIComponent(fm.dataset.confirm))){
            evn.preventDefault();
        } else {
            if(evn.type === 'externalsubmit') fm.submit();
        }
    }
};
TM_Form.prototype.changeStyle = function(evn) {
    var element = evn.target;
    var parent = TM.getParentNode(element, '.fieldset');
    if (parent) {
        var func = (evn.type === 'focus') ? 'add' : 'remove';
        parent.classList[func]('active');
    }
};
TM_Form.prototype.setDeleteConfirm = function() {
    var i;
    var e = document.getElementsByName('delete');
    if(!e) return;
    for(i = 0; i < e.length; i++){
        e[i].addEventListener('click', TM.form.sendDelete, false);
    }
};
TM_Form.prototype.sendDelete = function(evn) {
    var el = evn.target;

    if(el.checked){
        var fm = el.form;

        if(fm.dataset.confirm){
            var confirmation = fm.dataset.confirm;
            if (TM.category) TM.category.changeConfirm(el);
            if(confirm(decodeURIComponent(fm.dataset.confirm))) fm.submit();
            fm.dataset.confirm = confirmation;
        }
    }
};
TM_Form.prototype.cancelEnterKey = function(ev) {
    if(ev.which === 13){
        ev.preventDefault();
    }
};
TM_Form.prototype.init = function() {
    var i, j, el;
    for(i = 0; i < document.forms.length; i++){
        var fm = document.forms[i];
        fm.addEventListener('submit', this.onSubmit, false);
        fm.addEventListener('externalsubmit', this.onSubmit, false);

        for(j = 0; j < fm.elements.length; j++){
            el = fm.elements[j];
            if(TM.isTextbox(el)){
                el.addEventListener('focus', this.changeStyle, false);
                el.addEventListener('blur', this.changeStyle, false);
            }
        }
    }

    var ipt = document.getElementsByTagName('input');
    for(i = 0; i < ipt.length; i++){
        el = ipt[i];
        if(el.type === 'submit' || el.type === 'button') continue;
        el.addEventListener('keydown', this.cancelEnterKey, false);
    }
};

var TM_Accordion = function() {
};
TM_Accordion.prototype.init = function() {
    var a = document.getElementsByClassName('accordion-switcher');
    for(var i = 0; i < a.length; i++){
        var el = a[i];
        if(el.nodeName !== 'A') continue;
        var tg = document.getElementById(TM.hash(el.href).substr(1));
        if(!tg) continue;
        tg.dataset.height = tg.clientHeight;
        tg.style.height = '0';
        tg.classList.add('close');
        el.addEventListener('click', this.onClick, false);
    }
};
TM_Accordion.prototype.onClick = function(ev) {
    ev.preventDefault();
    var el = ev.target;
    var tg = document.getElementById(TM.hash(el.href).substr(1));
    if (tg.classList.contains('close')) {
        tg.style.height = tg.dataset.height + 'px';
        tg.classList.remove('close');
    } else {
        tg.style.height = '0';
        tg.classList.add('close');
    }
    if(el.dataset.callback){
        var func = new Function('arg1', 'return ' + el.dataset.callback + '(arg1)');
        func(tg);
    }
};

/**
 * XMLHttpRequest class
 */
var TM_XMLHttpRequest = function() {
    this.xhr = undefined;
    this.callback = undefined;
    this.method = undefined;
    this.location = undefined;
    this.async = undefined;
    this.headers = {};
};
TM_XMLHttpRequest.prototype.init = function(method, location, async, callback) {
    this.headers = {};
    this.method = method;
    this.location = location;
    this.async = async;
    this.xhr = new XMLHttpRequest();
    this.xhr.addEventListener('loadend', callback, false);
};
TM_XMLHttpRequest.prototype.header = function(key, value) {
    this.headers[key] = value;
};
TM_XMLHttpRequest.prototype.send = function(data) {
    this.xhr.open(this.method, this.location, this.async);
    this.xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    for (var key in this.headers) {
        this.xhr.setRequestHeader(key, this.headers[key]);
    }
    this.xhr.send(data);
};

/**
 * A common set of functions
 *
 * version: 1.0.0
 */
var  TM_Common = function() {
    this.onLoad(this, 'init');
    this.debug = 1;
    this.form = new TM_Form();
    this.menu = new TM_Menu();
    this.accordion = new TM_Accordion();
    this.resizer = new TM_Resizer();
    this.xhr = new TM_XMLHttpRequest();
};
TM_Common.prototype.init = function(evn) {
    this.form.init();
    this.form.focusInFirstChild();
    this.form.setDeleteConfirm();
    this.menu.init('global-nav');
    this.accordion.init();
    this.resizer.init();
    if (document.body.dataset.loadmessage) {
        setTimeout(this.showMessage, 300);
    }
};
TM_Common.prototype.showMessage = function() {
    if (document.body.dataset.loadmessage) 
        alert(decodeURIComponent(document.body.dataset.loadmessage));
};
TM_Common.prototype.setCookie  = function() {
    var str, name, value, expires, path, domain, secure, expires = '';
    var arg = TM.setCookie.arguments;

    if (arg.length === 0) {
        return false;
    }

    name   = arg[0];
    value  = (typeof(arg[1]) !== 'undefined') ? arg[1] : '';
    expire = (typeof(arg[2]) !== 'undefined') ? arg[2] :  0;
    path   = (typeof(arg[3]) !== 'undefined') ? arg[3] : '';
    domain = (typeof(arg[4]) !== 'undefined') ? arg[4] : '';
    secure = (typeof(arg[5]) !== 'undefined') ? arg[5] :  0;
    path   = (path   !== '') ? 'Path=' + path + '; ' : '';
    domain = (domain !== '') ? 'Domain=' + domain + '; ' : '';
    secure = (secure  >  0) ? 'secure' : '';

    if (value === '') {
        expires = 'expires=Thu, 1-jan-1970 00:00:00 GMT' + '; ';
    } else if(expire !== 0){
        expires = 'expires=' + expire.toUTCString() + '; ';
    }

    str = name + '=' + encodeURIComponent(value) + "; "
        + expires + path + domain + secure;
    document.cookie = str;
    return TM.getCookie(name) !== value;
};
TM_Common.prototype.getCookie  = function(name) {
    var str = document.cookie + ';';
    var data = '';
    var fStart = str.indexOf(name), fEnd;
    name += '=';
    if (fStart !== -1) {
        fEnd = str.indexOf(';', fStart);
        data = decodeURIComponent(str.substring(fStart + name.length, fEnd));
    }
    return data;
};
TM_Common.prototype.getParentNode = function(el, key) {
    var attr = 'nodeName';
    switch(key.substr(0,1)) {
        case '.' :
            attr = 'className';
            break;
        case '#' :
            attr = 'id';
            break;
    }
    key = (attr !== 'nodeName') ? key.substr(1) : key.toUpperCase();
    var pn = el.parentNode;
    while (pn) {
        if(attr !== 'className' && pn[attr] === key) return pn;
        else if (attr === 'className' && pn.classList && pn.classList.contains(key)) return pn;
        pn = pn.parentNode;
    }
};
TM_Common.prototype.isTextbox = function(el) {
    return (el.type !== 'reset' && el.type !== 'checkbox' &&
            el.type !== 'radio' && el.type !== 'hidden' && 
            el.type !== 'image' && el.type !== 'button' && 
            el.type !== 'file') || el.nodeName === 'TEXTAREA';
};
TM_Common.prototype.basename = function(str) {
    var m = str.match(/([^\/\\]+)$/);
    return (m) ? m[1] : str;
};
TM_Common.prototype.parseQuery = function(str) {
    str = str.replace(/^\?/, '').replace(/&amp;/, '&');
    var i, query = {};
    var pairs = str.split('&');
    for (i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');
        query[pair[0]] = decodeURIComponent(pair[1]);
    }
    return query;
};
TM_Common.prototype.apply = function(command, args) {
    var names = command.split('.');
    var i, max;
    var obj = window;
    for (i = 0, max = names.length; i < max; i++) {
        var key = names[i];
        if (typeof(obj[key]) === 'undefined') {
            break;
        }
        else if (typeof(obj[key]) === 'function') {
            return obj[key].apply(obj, args);
        }
        obj = obj[key];
    }
};
TM_Common.prototype.hash = function(str) {
    return str.substr(str.indexOf('#'));
};
TM_Common.prototype.errorHandler = function(evn) {
    evn.preventDefault();
    var filename = evn.filename.replace(/^.*?\/\/[^\/]+/, '');
    var msg = evn.message + ' at ' + filename + ':' + evn.lineno + ',' + evn.colno;
    console.error(msg);
    //if(TM.debug === 1) alert(msg);
    //else console.log(msg);
};
TM_Common.prototype.onLoad = function(scope, func) {
    addEventListener('error', this.errorHandler, false);
    addEventListener('load', function(evn){ scope[func](evn); }, false);
};
// Create instance
if (!window.TM) window.TM = new TM_Common();
