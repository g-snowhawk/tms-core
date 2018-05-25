/**
 * Javascript Library for Tak-Me CMS
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */

var TM_Upload = function() {
    this.onLoad(this, 'init');
    this.ta = undefined;
    this.lstr = '';
    this.mstr = '';
    this.rstr = '';
    this.popupwindow = undefined;
};
TM_Upload.prototype.init = function(ev) {
    var els = document.getElementsByClassName('image-selector');
    for(var i = 0; i < els.length; i++){
        els[i].addEventListener('change', this.onChange, false);
        var prn = TM.getParentNode(els[i], '.file-set');
        var a = prn.getElementsByTagName('a');
        for(var j = 0; j < a.length; j++){
          a[j].addEventListener('click', this.onClick, false);
        }
    }

    //window.addEventListener('drop', this.onDrop, false);

    TM.upload.ta = document.getElementById('body');
    if (!TM.upload.ta) {
        return;
    }
    TM.upload.ta.addEventListener('dragover', this.onDragCancel, false);
    TM.upload.ta.addEventListener('dragenter', this.onDragCancel, false);
    TM.upload.ta.addEventListener('drop', this.onDrop, false);

    var els = document.getElementsByTagName('img');
    for(var i = 0; i < els.length; i++){
        els[i].addEventListener('dragstart', this.onDragStart, false);
    }
};
TM_Upload.prototype.onClick = function(ev) {
    ev.preventDefault();
    TM.upload.delete(ev.currentTarget);
};
TM_Upload.prototype.onChange = function(ev) {
    if(ev.currentTarget.value == ''){
        ev.preventDefault();
        return;
    }
    TM.upload.thumbnail(ev.currentTarget);
};
TM_Upload.prototype.onDragCancel = function(ev) {
    ev.preventDefault();
};
TM_Upload.prototype.onDragStart = function(ev) {
    var el = ev.currentTarget;
    ev.dataTransfer.setData('src', el.src);
};
TM_Upload.prototype.onDrop = function(ev) {
    ev.preventDefault();
    ev.stopPropagation();
    var el = ev.currentTarget;
    if(el.id != 'body'){
        return;
    }

    el = TM.upload.ta;
    var val = el.value;
    var s = el.selectionStart;
    var e = el.selectionEnd;
    TM.upload.lstr = val.slice(0, s);
    TM.upload.rstr = val.slice(e);
    TM.upload.mstr = (s != e) ? val.slice(s, e) : '';

    TM.upload.ta.value = TM.upload.lstr + '![' + TM.upload.mstr + '](' + ev.dataTransfer.getData('src') + ')' + TM.upload.rstr;
};
TM_Upload.prototype.delete = function(el) {
    var i, ipt;
    var prn = TM.getParentNode(el, '.file-set');
    if (el.href == '#cancel') {
        prn.parentNode(prn);
        return;
    }

    if (el.className == 'marked') {
        el.className = 'mark';
        el.style.opacity = null;

        ipt = el.getElementsByTagName('input');
        for(i = 0; i < ipt.length; i++){
            el.removeChild(ipt[i]);
        }

        ipt = prn.getElementsByTagName('input');
        for(i = 0; i < ipt.length; i++){
            if(ipt[i].name == 'donothing[]'){
                ipt[i].name = 'file[]';
            }
        }

    } else {
        ipt = prn.getElementsByTagName('input');
        var id = ipt[0].name.match(/^file\[(.*?)([0-9]+)\]/);
        if (id) {
            var nm = id[1] + id[2];
            var hdn = document.createElement('input');
            hdn.type = 'hidden';
            hdn.name = 'delete[' + nm + ']';
            hdn.value = id[2];
            el.appendChild(hdn);
        } else {
            ipt[0].name = 'donothing[]';
        }
        el.className = 'marked';
        el.style.opacity = '1';
    }
};
TM_Upload.prototype.thumbnail = function(el) {
    var prn = TM.getParentNode(el, '.file-set');
    var span = prn.getElementsByClassName('thumbnail');
    if(span.length < 1) return;
    span = span[0];
    span.innerHTML = '';

    if(el.files.length < 1){
        return;
    }

    var file = el.files[0];
    if(!file.type.match('(image.*|.*pdf.*)')){
        return;
    }
    var render = new FileReader();
    render.onload = function(){
        if(el.parentNode.className !== 'selected'){
            var cln = prn.parentNode.appendChild(prn.cloneNode(false));
            cln.innerHTML = prn.innerHTML;
            var ipt = cln.getElementsByTagName('input');
            for(i = 0; i < ipt.length; i++){
                ipt[i].addEventListener('change', TM.upload.onChange, false);
            }
            el.parentNode.className = 'selected';

            // Relational TM_attachments
            if (TM.attachments) {
                prn.id = null;
                TM.attachments.setListener(prn);
                cln.id = 'attachment-origin';
                TM.attachments.setListener(cln);
            }
        }
        var img = span.appendChild(document.createElement('img'));
        var fileType;
        if(this.result.match('^data:image/(jpeg|png|gif)')){
            img.src = this.result;
            fileType = 'image';
        } else {
            var dir = location.pathname.replace(/[^\/]+$/, '');
            img.src = dir + 'style/icon_pdf.svg';

            var spn = span.appendChild(document.createElement('span'));
            spn.className = "filename";
            spn.innerHTML = TM.basename(el.value);
            fileType = 'pdf';
        }
        img.title = el.value;
        img.draggable = false;
        img.addEventListener('dragstart', TM.upload.onDragStart, false);

        var a = prn.appendChild(document.createElement('a'));
        a.href = '#delete';
        a.className = 'mark';
        a.addEventListener('click', TM.upload.onClick, false);

        var template = document.getElementById('popup-note');
        var popup = template.content.querySelector('.popup');
        if (fileType !== 'pdf') {
            var select = popup.querySelector('select');
            if (select) {
                select.parentNode.removeChild(select);
            }
        }
        prn.appendChild(popup.cloneNode(true));
        prn.addEventListener('contextmenu', this.onContextMenu, false);
    }
    render.readAsDataURL(file);
};
TM_Upload.prototype.onLoad = function(scope, func) {
    addEventListener('load', function(ev){ scope[func](ev); }, false);
};

// Create instance
if (!window.TM) window.TM = new TM_Common();
TM.upload = new TM_Upload();
