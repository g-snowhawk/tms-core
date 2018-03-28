/**
 * Javascript Library for Tak-Me CMS
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */

var TM_Editor = function() {
    this.ta = undefined;
    this.lstr =  '';
    this.mstr =  '';
    this.rstr =  '';
    this.checkDblClick = 0;
    this.activeClassName = 'editor-image-active';
    this.onLoad(this, 'init');
};
TM_Editor.prototype.init = function(ev) {
    var a = document.getElementsByTagName('a');
    for(var i = 0; i < a.length; i++){
        var el = a[i];
        if(el.dataset.insert){
            el.addEventListener('click', this.onClick, false);
        }
    }
};
TM_Editor.prototype.onClick = function(ev) {
    ev.preventDefault();
    var cls = TM.editor;
    var el = ev.target;

    if(el.name == 'editor-insert'){
        return cls.insert(el);
    }else if(el.name == 'editor-cancel'){
        return cls.clearWindow(ev);
    }

    var id = el.href.substr(el.href.indexOf('#'));
    cls.ta = document.querySelector(id);
    cls.ta.focus();
    var val = cls.ta.value;
    var s = cls.ta.selectionStart;
    var e = cls.ta.selectionEnd;
    cls.lstr = val.slice(0, s);
    cls.rstr = val.slice(e);
    cls.mstr = (s != e) ? val.slice(s, e) : '';

    switch(el.dataset.insert){
        case 'link':
            cls.createLinkForm(cls.maskWindow(), el);
            break;
        case 'image':
            cls.createImageForm(cls.maskWindow(), el);
            break;
    }
};
TM_Editor.prototype.insert = function(el) {
    var url, text, imark;
    switch(el.dataset.type){
        case 'image':
            var span = document.getElementsByClassName(this.activeClassName);
            if(span.length == 0){
                alert('画像が選択されていません');
                return;
            }
            var img = span[0].getElementsByTagName('img');
            url = img[0].src;
            text = img[0].alt;
            imark = '!';
            break;
        case 'link':
            var fm = el.form;
            url = fm['editor-url'].value;
            text = fm['editor-text'].value;
            imark = '';
            break;
    }

    if(url != ''){
        var lStr = this.lstr;
        var mStr = (text == '') ? this.mstr : text;
        var rStr = this.rstr;
        this.ta.value = lStr + imark + '[' + mStr + '](' + url + ')' + rStr;
    }
    this.lstr = '';
    this.mstr = '';
    this.rstr = '';
    this.ta = undefined;
    this.clearWindow();
};
TM_Editor.prototype.clearWindow = function(ev) {
    var mask = document.getElementById('mask');
    mask.parentNode.removeChild(mask);
};
TM_Editor.prototype.maskWindow = function() {
    var mask = document.body.appendChild(document.createElement('div'));
    mask.id = 'mask';
    return mask;
};
TM_Editor.prototype.loadTemplate = function(href) {
    var tmplDir = location.pathname.replace(/[^\/]+$/, '');
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open('GET', tmplDir + href, false);
    xmlhttp.send(null);
    return xmlhttp.responseText;
};
TM_Editor.prototype.createLinkForm = function(mask, el) {
    mask.innerHTML = this.loadTemplate('script/editor/link.html');

    var bg = mask.getElementsByClassName('back')[0];
    bg.addEventListener('click', TM.editor.clearWindow);

    var ipt = mask.getElementsByTagName('input');
    for(var i = 0; i < ipt.length; i++){
        if(ipt[i].type == 'button'){
            ipt[i].addEventListener('click', TM.editor.onClick, false);
            continue;
        }
        switch(ipt[i].name){
            case 'editor-url':
                ipt[i].focus();
                break;
            case 'editor-text':
                ipt[i].value = this.mstr;
                break;
        }
    }

    var fm = mask.getElementsByTagName('form')[0];
    fm.action = location.pathname;
    fm.style.height = fm.clientHeight + 'px';
    fm.className = 'link';
};
TM_Editor.prototype.createImageForm = function(mask, el) {
    mask.innerHTML = this.loadTemplate('script/editor/image.html');

    var bg = mask.getElementsByClassName('back')[0];
    bg.addEventListener('click', TM.editor.clearWindow);

    var fm = mask.getElementsByTagName('form')[0];
    fm.action = location.pathname;

    var i;
    var ipt = mask.getElementsByTagName('input');
    for(i = 0; i < ipt.length; i++){
        if(ipt[i].type == 'file'){
            ipt[i].addEventListener('change', this.imageSelected, false);
        } else if(ipt[i].type == 'button'){
            ipt[i].addEventListener('click', this.onClick, false);
        }
    }

    var xhr = new XMLHttpRequest();
    xhr.onload = function(evn){
        if(xhr.status == 200){
            var obj = JSON.parse(xhr.responseText);
            TM.editor.createThumbnail(obj);
        } else {
            alert(xhr.responseText);
        }
    };
    var loc = fm.action + '?mode=cms.entry.response:ajaxImageList';
    xhr.open('GET', loc, true);
    xhr.send(null);
};
TM_Editor.prototype.createThumbnail = function(obj) {
    var origin = document.getElementById('image-addnew');
    for(var i = 0; i < obj.length; i++){
        var id = 'thumbnail-' + obj[i].id;
        var cln = document.getElementById(id);
        if(cln){
            var img = cln.getElementsByTagName('img')[0];
            img.src = obj[i].data;
            img.draggable = 'false';
            continue;
        }
        cln = origin.cloneNode(true);
        cln.id = 'thumbnail-' + obj[i].id;

        var label = cln.getElementsByTagName('label')[0];
        var dum = label.parentNode.insertBefore(document.createElement('span'), label);
        dum.innerHTML = label.innerHTML;
        dum.className = 'selected';
        dum.addEventListener('mousedown', this.thumbClick, false);
        label.parentNode.removeChild(label);

        var check = dum.appendChild(document.createElement('span'));
        check.className = 'checked';

        var span = cln.getElementsByClassName('thumbnail')[0];

        var img = span.appendChild(document.createElement('img'));
        img.src = obj[i].data;

        var input = cln.getElementsByTagName('input')[0];
        input.name = 'file[id_' + obj[i].id + ']';
        input.addEventListener('change', this.imageSelected, false);

        var a = cln.appendChild(document.createElement('a'));
        a.href = '#delete:' + obj[i].id;
        a.className = 'mark';
        a.addEventListener('click', this.delete, false);

        origin.parentNode.insertBefore(cln, origin);
    }
};
TM_Editor.prototype.thumbClick = function(ev) {
    ev.preventDefault();
    var cls = TM.editor;

    if(cls.checkDblClick != 1){
        cls.checkDblClick = 1;
        setTimeout(function(){
            if(cls.checkDblClick != 2){
                cls.selectThumbnail(ev.target);
            }
            cls.checkDblClick = 0;
        }, 300);
    } else {
        cls.checkDblClick = 2;
        return cls.thumbDblClick(ev.currentTarget);
    }
};
TM_Editor.prototype.selectThumbnail = function(el) {
    var i;
    var selected = document.getElementsByClassName(this.activeClassName);
    for(i = 0; i < selected.length; i++){
        selected[i].classList.remove(this.activeClassName);
    }
    var prn = TM.getParentNode(el, '.selected');
    prn.classList.add(this.activeClassName);
};
TM_Editor.prototype.thumbDblClick = function(el) {
    var input = el.getElementsByTagName('input')[0];
    var evn = document.createEvent('MouseEvents');
    evn.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    input.dispatchEvent(evn);
};
TM_Editor.prototype.delete = function(ev) {
    if(confirm('この画像を削除します。よろしいですか？')){
        var el = ev.target;
        var hash = el.href.substr(el.href.indexOf('#'));
        var id = hash.substr(hash.indexOf(':') + 1);

        var token = document.forms[0].stab.value;
        var action = document.forms[0].action;

        var data = new FormData();
        data.append('mode', 'cms.entry.receive:ajaxDeleteImage');
        data.append('stab', token);
        data.append('id', id);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', action, true);
        xhr.onload = function(evn){
            if(xhr.status == 200){
                var obj = JSON.parse(xhr.responseText);
                var id = 'thumbnail-' + obj.id;
                var el = document.getElementById(id);
                if(el){
                    el.parentNode.removeChild(el);
                }
            } else {
                alert(xhr.responseText);
            }
        };
        xhr.send(data);
    }
};
TM_Editor.prototype.imageSelected = function(ev) {
    if(ev.target.value == ''){
        ev.preventDefault();
        return;
    }
    var fm = ev.target.form;
    var token;
    var action;
    for(i = 0; i < document.forms.length; i++){
        var form = document.forms[i];
        if(form == fm || !form.stab) continue;
        token = form.stab.value;
        action = form.action;
        break;
    }

    var data = new FormData(fm);
    data.append('mode', 'cms.entry.receive:ajaxUploadImage');
    data.append('stab', token);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', action, true);
    xhr.onload = function(evn){
        if(xhr.status == 200){
            var obj = JSON.parse(xhr.responseText);
            TM.editor.createThumbnail(obj);
        } else {
            alert(xhr.responseText);
        }
    };
    xhr.send(data);
};
TM_Editor.prototype.onLoad = function(scope, func) {
    window.addEventListener(
        'load',
        function(ev){
            scope[func](ev);
        },
        false
    );
};
// Create instance
if (!window.TM) window.TM = new TM_Common();
TM.editor = new TM_Editor();
