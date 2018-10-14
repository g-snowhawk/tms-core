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
    this.textarea = undefined;
    this.lstr =  '';
    this.mstr =  '';
    this.rstr =  '';
    this.checkDblClick = 0;
    this.cnActiveImage = 'editor-image-active';
    TM.initModule(this.init, this);
};

TM_Editor.prototype.init = function(event) {
    var elements = document.getElementsByTagName('a');
    for (var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if (element.dataset.insert) {
            element.addEventListener('click', this.onClick, false);
        }
    }
};

TM_Editor.prototype.onClick = function(event) {
    event.preventDefault();
    var instance = TM.editor;
    var element = event.currentTarget;

    if (element.name === 'editor-insert') {
        return instance.insert(element);
    }
    else if (element.name === 'editor-cancel') {
        return instance.clearWindow(event);
    }

    var id = element.hash;
    instance.textarea = document.querySelector(id);
    instance.textarea.focus();
    var value = instance.textarea.value;
    var start = instance.textarea.selectionStart;
    var end = instance.textarea.selectionEnd;
    instance.lstr = value.slice(0, start);
    instance.rstr = value.slice(end);
    instance.mstr = (start != end) ? value.slice(start, end) : '';

    switch(element.dataset.insert) {
        case 'link':
            instance.createLinkForm(instance.maskWindow(), element);
            break;
        case 'image':
            instance.createImageForm(instance.maskWindow(), element);
            break;
    }
};

TM_Editor.prototype.insert = function(element) {
    var url, text, imark;
    switch(element.dataset.type) {
        case 'image':
            var span = document.getElementsByClassName(this.cnActiveImage);
            if(span.length == 0) {
                alert('画像が選択されていません');
                return;
            }
            var img = span[0].getElementsByTagName('img');
            url = img[0].src;
            text = img[0].alt;
            imark = '!';
            break;
        case 'link':
            var form = element.form;
            url = form['editor-url'].value;
            text = form['editor-text'].value;
            imark = '';
            break;
    }

    if(url != '') {
        var lStr = this.lstr;
        var mStr = (text === '') ? this.mstr : text;
        var rStr = this.rstr;
        this.textarea.value = lStr + imark + '[' + mStr + '](' + url + ')' + rStr;
    }
    this.lstr = '';
    this.mstr = '';
    this.rstr = '';
    this.textarea = undefined;
    this.clearWindow();
};

TM_Editor.prototype.clearWindow = function(event) {
    var mask = document.getElementById('mask');
    if (mask) {
        mask.parentNode.removeChild(mask);
    }
};

TM_Editor.prototype.maskWindow = function() {
    var mask = document.body.appendChild(document.createElement('div'));
    mask.id = 'mask';
    return mask;
};

TM_Editor.prototype.createLinkForm = function(mask, element) {
    TM.xhr.init('GET', TM_Common.__DIR__ + '/TM/editor/link.html', true, function(event) {
        if (this.status === 200) {
            mask.innerHTML = this.responseText;
            TM.editor.linkForm(mask, element);
        }
    });
    TM.xhr.send(null);
};

TM_Editor.prototype.linkForm = function(mask, element) {
    var backGround = mask.getElementsByClassName('back')[0];
    backGround.addEventListener('click', TM.editor.clearWindow);

    var elements = mask.getElementsByTagName('input');
    for(var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if(element.type == 'button') {
            element.addEventListener('click', TM.editor.onClick, false);
            continue;
        }
        switch(element.name) {
            case 'editor-url':
                element.focus();
                break;
            case 'editor-text':
                element.value = this.mstr;
                break;
        }
    }

    var form = mask.getElementsByTagName('form')[0];
    form.action = location.pathname;
    form.style.height = form.clientHeight + 'px';
    form.className = 'link';
};

TM_Editor.prototype.createImageForm = function(mask, element) {
    TM.xhr.init('GET', TM_Common.__DIR__ + '/TM/editor/image.html', true, function(event) {
        if (this.status === 200) {
            mask.innerHTML = this.responseText;
            TM.editor.imageForm(mask, element);
        }
    });
    TM.xhr.send(null);
};

TM_Editor.prototype.imageForm = function(mask, element) {
    var stub = document.querySelector('[name=stub]');
    if (!stub) {
        return;
    }

    var backGround = mask.getElementsByClassName('back')[0];
    backGround.addEventListener('click', TM.editor.clearWindow);

    var form = mask.getElementsByTagName('form')[0];
    form.action = location.pathname;
    form.stub.value = stub.value;
    form.dataset.upload = element.dataset.upload;
    form.dataset.delete = element.dataset.delete;
    form.dataset.list = element.dataset.list;
    form.dataset.confirm = element.dataset.confirm;

    var elements = mask.getElementsByTagName('input');
    for (var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if (element.type === 'file') {
            element.addEventListener('change', this.imageSelected, false);
        } else if (element.type === 'button') {
            element.addEventListener('click', this.onClick, false);
        }
    }

    TM.xhr.init('GET', form.action + '?mode=' + form.dataset.list, true, function(event) {
        if(this.status == 200) {
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            TM.editor.createThumbnail(json);
        } else {
            console.error(this.responseText);
        }
    });
    TM.xhr.send(null);
};

TM_Editor.prototype.createThumbnail = function(json) {
    var origin = document.getElementById('image-addnew');
    for(var i = 0; i < json.length; i++) {
        var item = json[i];
        var id = 'thumbnail-' + item.id;
        var thumbnail = document.getElementById(id);
        if(thumbnail) {
            var img = thumbnail.getElementsByTagName('img')[0];
            img.src = item.data;
            img.draggable = 'false';
            continue;
        }

        thumbnail = origin.cloneNode(true);
        thumbnail.id = 'thumbnail-' + item.id;

        var label = thumbnail.getElementsByTagName('label')[0];
        var dummy = label.parentNode.insertBefore(document.createElement('span'), label);
        dummy.innerHTML = label.innerHTML;
        dummy.className = 'selected';
        dummy.addEventListener('mousedown', this.thumbClick, false);
        label.parentNode.removeChild(label);

        var check = dummy.appendChild(document.createElement('span'));
        check.className = 'checked';

        var span = thumbnail.getElementsByClassName('thumbnail')[0];

        var img = span.appendChild(document.createElement('img'));
        img.src = item.data;

        var input = thumbnail.getElementsByTagName('input')[0];
        input.name = 'file[id_' + item.id + ']';
        input.addEventListener('change', this.imageSelected, false);

        var a = thumbnail.appendChild(document.createElement('a'));
        a.href = '#delete:' + item.id;
        a.className = 'mark';
        a.addEventListener('click', this.delete, false);

        origin.parentNode.insertBefore(thumbnail, origin);
    }
};

TM_Editor.prototype.thumbClick = function(event) {
    event.preventDefault();
    var instance = TM.editor;

    if(instance.checkDblClick != 1) {
        instance.checkDblClick = 1;
        setTimeout(function() {
            if(instance.checkDblClick != 2) {
                instance.selectThumbnail(event.target);
            }
            instance.checkDblClick = 0;
        }, 300);
    } else {
        instance.checkDblClick = 2;
        return instance.thumbDblClick(event.currentTarget);
    }
};

TM_Editor.prototype.selectThumbnail = function(element) {
    var selected = document.getElementsByClassName(this.cnActiveImage);
    for (var i = 0; i < selected.length; i++) {
        selected[i].classList.remove(this.cnActiveImage);
    }
    var parent = TM.getParentNode(element, '.selected');
    if (parent) {
        parent.classList.add(this.cnActiveImage);
    }
};

TM_Editor.prototype.thumbDblClick = function(element) {
    var input = element.getElementsByTagName('input')[0];
    var event = document.createEvent('MouseEvents');
    event.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    input.dispatchEvent(event);
};

TM_Editor.prototype.delete = function(event) {
    var element = event.target;
    var form = TM.getParentNode(element, 'form');

    if (confirm(form.dataset.confirm)) {
        var hash = element.hash;
        var id = hash.substr(hash.indexOf(':') + 1);

        var data = new FormData();
        data.append('mode', form.dataset.delete);
        data.append('id', id);
        data.append('stub', form.stub.value);

        TM.xhr.init('POST', form.action, true, function(event) {
            if (this.status == 200) {
                try {
                    var json = JSON.parse(this.responseText);
                } catch (exceptionObject) {
                    console.error(exceptionObject.message);
                    console.log(this.responseText);
                    return;
                }

                var id = 'thumbnail-' + json.id;
                var element = document.getElementById(id);
                if (element) {
                    element.parentNode.removeChild(element);
                }

            } else {
                // TODO: add error handling
                console.log(this.responseText);
            }
        });
        TM.xhr.send(data);
    }
};

TM_Editor.prototype.imageSelected = function(event) {
    var element = event.currentTarget;
    if (event.currentTarget.value === '') {
        event.preventDefault();
        return;
    }
    var form = element.form;
    var data = new FormData(form);
    data.append('mode', form.dataset.upload);


    /*
     * Fix to Safari
     *
     * delete empty file selector
     */
    var empties = [];
    for (item of data) {
        if (typeof(item[1]) === 'object') {
            if (item[0] !== element.name) {
                empties.push(item[0]);
            }
        }
    }
    for (item of empties) data.delete(item);


    TM.xhr.init('POST', form.action, true, function(event) {
        if (this.status == 200) {
            try {
                var json = JSON.parse(this.responseText);
                TM.editor.createThumbnail(json);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
            }
        } else {
            console.error(this.responseText);
        }
    });
    TM.xhr.send(data);
};

TM.editor = new TM_Editor();
