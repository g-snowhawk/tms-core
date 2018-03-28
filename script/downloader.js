/**
 * Javascript Library for Tak-Me System
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */

var TM_Downloader = function() {
    this.timer = undefined;
    this.startCookie = 'download-start';
    this.endCookie = 'download-end';
    this.onLoad(this, 'init');
};
TM_Downloader.prototype.click = function(ev) {
    var cls = TM.downloader;
    ev.preventDefault();
    cls.starter = ev.target;
    var formElement = cls.starter.form;
    cls.start = new Date().getTime();
    TM.setCookie(cls.startCookie, cls.start);
    cls.appendHidden(formElement, 'start_cookie', cls.startCookie);
    cls.appendHidden(formElement, 'end_cookie', cls.endCookie);
    formElement.submit();
    cls.timer = setInterval(cls.poling, 1000);
};
TM_Downloader.prototype.appendHidden = function(formElement, name, value) {
    var node = document.createElement('input');
    node.setAttribute('type', 'hidden');
    node.setAttribute('name', name);
    node.setAttribute('value', value);
    formElement.appendChild(node);
};
TM_Downloader.prototype.poling = function() {
    var cls = TM.downloader;
    var result = TM.getCookie(cls.endCookie);
    if (TM.downloader.start === result) {
        clearInterval(cls.timer);
        if (cls.starter.dataset.redirect) {
            location.href = cls.starter.dataset.redirect;
        } else {
            location.reload();
        }
    }
};
TM_Downloader.prototype.init = function(ev) {
    var i, element;
    var elements = document.getElementsByTagName('input');
    for (i = 0; i < elements.length; i++) {
        element = elements[i];
        if (element.dataset.request === 'TM.downloader') {
            element.addEventListener('click', this.click, false);
        }
    }
};
TM_Downloader.prototype.onLoad = function(scope, func) {
    addEventListener('load', function(ev){ scope[func](ev); }, false);
};

// Create instance
if (!window.TM) window.TM = new TM_Common();
TM.downloader = new TM_Downloader();
