/**
 * Javascript Library for Tak-Me CMS
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.1
 */

Node.prototype.childOf = function(element) {
    var i = -1;
    var parent = this.parentNode;
    while (parent) {
        ++i;
        if (parent == element) {
            return i;
        }
        parent = parent.parentNode;
    }
    return -1;
};
Node.prototype.findParent = function(key) {
    var attr = 'nodeName';
    switch (key.substr(0,1)) {
        case '.' :
            attr = 'className';
            break;
        case '#' :
            attr = 'id';
            break;
    }
    key = (attr !== 'nodeName') ? key.substr(1) : key.toUpperCase();
    var parent = this.parentNode;
    while (parent) {
        if (attr !== 'className' && parent[attr] === key) {
            return parent;
        }
        else if (attr === 'className' && parent.classList && parent.classList.contains(key)) {
            return parent;
        }
        parent = parent.parentNode;
    }
};
String.prototype.translate = function() {
    let str = '';
    for (let i = 0; i < this.length; i++) {
        str += this[i];
    }

    if (typeof DICTIONARY === 'object' && DICTIONARY[str]) {
        return DICTIONARY[str];
    }

    return str;
}

/**
 * A common set of functions
 *
 * version: 1.0.0
 */
var TM_Common = function() {
    this.debug = 1;
    this.onLoad(this, 'init');
};

TM_Common.__FILE__ = (document.currentScript) ? document.currentScript.src : (function(){
    var script = document.getElementsByTagName('script');
    return script[script.length-1].src;
})();

TM_Common.__DIR__ = (function() {
    var a = document.createElement('a');
    a.href = TM_Common.__FILE__;
    var pathname = a.pathname;

    // 
    if (!pathname.match(/^\/.*$/)) {
        pathname = '/' + pathname;
    }

    return pathname.replace(/\/?[^\/]+$/, '');
})();

TM_Common.prototype.init = function(evn) {
    if (document.body.dataset.loadmessage) {
        setTimeout(this.showMessage, 300);
    }
};

TM_Common.prototype.showMessage = function() {
    if (document.body.dataset.loadmessage) {
        alert(decodeURIComponent(document.body.dataset.loadmessage));
    }
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
    if (!evn.message) {
        return;
    }
    evn.preventDefault();
    var filename = evn.filename.replace(/^.*?\/\/[^\/]+/, '');
    var msg = evn.message + ' at ' + filename + ':' + evn.lineno + ',' + evn.colno;
    console.error(msg);
};

TM_Common.prototype.onLoad = function(scope, func) {
    addEventListener('error', this.errorHandler, false);
    addEventListener('load', function(evn){ scope[func](evn); }, false);
};

TM_Common.prototype.loadModule = function(scripts) {
    var me = document.getElementsByTagName('script')[0];
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        var element = document.createElement('script');
        element.src = TM_Common.__DIR__ + '/TM/' + script.src + '.js';
        if (script.async !== '') {
            element.async = true;
        }
        if (script.defer !== '') {
            element.defer = true;
        }
        me.parentNode.insertBefore(element, me);
    }
};

TM_Common.prototype.dynamicLoadModule = function(scripts) {
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        var element = document.createElement('script');
        element.src = TM_Common.__DIR__ + '/TM/' + script.src + '.js';
        document.head.appendChild(element);
    }
};

TM_Common.prototype.loadStyle = function(styles) {
    var scriptElement = document.querySelector('script');
    for (var i = 0; i < styles.length; i++) {
        var style = styles[i];
        var element = document.createElement('link');
        element.rel = style.rel;
        element.href = TM_Common.__DIR__ + '/TM/' + style.href + '.css';
        scriptElement.parentNode.insertBefore(element, scriptElement);
    }
};

TM_Common.prototype.initModule = function() {
    var args = (arguments.length === 1 ? [arguments[0]] : Array.apply(null, arguments));
    var func = args.shift();
    var module = args.shift();
    var state = args.shift();
    var eventType = (state === 'interactive') ? 'DOMContentLoaded' : 'load';

    if (   document.readyState === state
        || (state === 'interactive' && document.readyState === 'complete')
    ) {
        func.apply(module, args);
    }
    else {
        window.addEventListener(
            eventType,
            function() {
                func.apply(module, args);
            },
            false
        );
    }
};


class Dialog {
    constructor(title, type, callback) {
        this.callback = callback;
        this.type = type;
        this.createDialog(title, type);
    }

    createDialog(title, type) {
        this.container = document.body.appendChild(document.createElement('div'));
        this.setStyle(this.container, {
            alignItems : 'center',
            backgroundColor : 'rgba(0,0,0,0.5)',
            bottom : '0',
            display : 'flex',
            justifyContent : 'center',
            left : '0',
            position : 'absolute',
            right : '0',
            top : '0',
            zIndex : '2147483647',
        });

        const dialog = this.container.appendChild(document.createElement('div'));
        this.setStyle(dialog, {
            backgroundColor : '#FFF',
            borderRadius : '6px',
            minWidth : '380px',
            minHeight : '60px',
            overflow: 'hidden',
        });

        const message = dialog.appendChild(document.createElement('p'));
        message.innerHTML = title;
        this.setStyle(message, {
            fontSize: '11pt',
            margin: '1.2em 1em',
        });

        if (type === 'prompt' || type === 'secret') {
            const span = message.appendChild(document.createElement('span'));
            this.setStyle(span, {
                display: 'block',
                width: '100%',
            });
            this.input = span.appendChild(document.createElement('input'));
            this.input.name = 'answer';
            this.input.type = (type === 'secret') ? 'password' : 'text';
            this.setStyle(this.input, {
                fontSize: '9pt',
                width: '100%',
            });

            this.input.focus();
        }

        const line = dialog.appendChild(document.createElement('hr'));
        this.setStyle(line, {
            border : '0 none transparent',
            borderTop : '1px solid #ccc',
            height : '0',
            margin : '0',
        });

        const buttons = dialog.appendChild(document.createElement('p'));
        this.setStyle(buttons, {
            margin: '0 0.8em',
            textAlign : 'right',
        });

        if (type === 'confirm' || type === 'prompt' || type === 'secret') {
            const cancelButton = buttons.appendChild(document.createElement('button'));
            cancelButton.value = 'cancel';
            cancelButton.innerHTML = 'Cancel';
            this.setStyle(cancelButton, {
                background: 'transparent',
                borderRadius: '0',
                borderWidth: '0',
                color : 'blue',
                fontSize: '11pt',
                height: 'auto',
                lineHeight: '1',
                margin: '0.8em 0.5em',
                minHeight: 'auto',
                minWidth: 'auto',
                padding: '0',
                textAlign : 'center',
                textDecoration : 'none',
                width: 'auto',
            });

            cancelButton.addEventListener('click', this.close.bind(this));
        }

        const okButton = buttons.appendChild(document.createElement('button'));
        okButton.value = 'ok';
        okButton.innerHTML = 'OK';
        this.setStyle(okButton, {
            background: 'transparent',
            borderRadius: '0',
            borderWidth: '0',
            color : 'blue',
            fontSize: '11pt',
            height: 'auto',
            lineHeight: '1',
            margin: '0.8em 0.5em',
            minHeight: 'auto',
            minWidth: 'auto',
            padding: '0',
            textAlign : 'center',
            textDecoration : 'none',
            width: 'auto',
        });

        okButton.addEventListener('click', this.close.bind(this));
    }

    setStyle(element, styles) {
        for (let key in styles) {
            element.style[key] = styles[key];
        }
    }

    close(event) {
        event.preventDefault();
        const returnValue = event.target.value;
        this.container.parentNode.removeChild(this.container);
        if (typeof this.callback === 'function') {
            const func = function(click, input, type) {
                return (type === 'prompt' || type === 'secret')
                    ? input : click;
            }
            let answer = (returnValue === 'cancel') ? null : func(returnValue, this.input.value, this.type);
            this.callback(answer);
        }
    }
}
