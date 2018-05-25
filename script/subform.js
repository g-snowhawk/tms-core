/**
 * This file is part of Tak-Me System.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive. 
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */

/**
 * Subform
 */
function Subform() {
    this.inited = false;
    this.sealedID = 'subform-sealed';
    this.cancelButtonID = 'cancel-subform-button';
    this.containerID = 'subform-container';
    this.cnTransition = 'trans-left';
    this.subformWidth = undefined;
    this.pollingTimer = undefined;
    this.startPollingDelay = 1000;
    this.pollingInterval = 10000;
    this.finally = undefined;
    this.onLoad(this, 'init');
}

Subform.prototype.show = function(container) {
    var instance = TM.subform;
    container.addEventListener('transitionend', instance.listener, false);
    container.classList.add(instance.cnTransition);
    container.style.left = '0';
};

Subform.prototype.endOpenTransition = function(container) {
    var instance = TM.subform;
    if (container.offsetLeft < 0) {
        container.parentNode.removeChild(container);
        var sealed = document.getElementById(this.sealedID);
        sealed.parentNode.removeChild(sealed);

        if (instance.finally) {
            TM.apply(instance.finally.function, instance.finally.arguments);
            instance.finally = undefined;
        }
    }
    else {
        instance.setListenerButtons();
    }
};

Subform.prototype.setListenerButtons = function() {
    var cancelSubformButton = document.getElementById(this.cancelButtonID);
    cancelSubformButton.addEventListener('click', this.listener, false);
};

Subform.prototype.setListenerForms = function(container) {
    var forms = container.getElementsByTagName('form');
    for (i = 0, max = forms.length; i < max; i++) {
        forms[i].addEventListener('submit', this.listener, false);
    }
};

Subform.prototype.create = function(json) {
    var sealed = document.body.appendChild(document.createElement('div'));
    sealed.id = this.sealedID;

    var container = document.body.appendChild(document.createElement('div'));
    container.id = this.containerID;
    container.innerHTML = json.response;

    this.setListenerForms(container);

    this.width = container.offsetWidth;

    container.style.left = '-' + this.width + 'px';
    setTimeout(this.show, 0, container);
};

Subform.prototype.close = function(container) {
    container.style.left = '-' + this.width + 'px';
    var sealed = document.getElementById(this.sealedID);
    sealed.classList.add('fadeout');
};

Subform.prototype.open = function(element) {
    var instance = TM.subform;
    TM.xhr.init('GET', element.href, true, function(event){
        if(this.status == 200){
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.create(json);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
        }
    });
    TM.xhr.send(null);
};

Subform.prototype.posted = function(json, form) {

    if (json.message) {
        alert(json.message);
    }

    if (json.status === 0) {
        this.close(document.getElementById(this.containerID));
    }
    else if (json.status === 45) {
        if (json.arguments && json.arguments.polling_id) {
            this.pollingTimer = setTimeout(this.polling, this.startPollingDelay, json.arguments.polling_address + "&polling_id=" + json.arguments.polling_id);
        }
        return;
    }

    var i, max;
    switch (json.response.type) {
        case 'redirect':
            location.href = json.response.source;
            break;
        case 'callback':
            var args = json.arguments ? json.arguments : [];
            TM.apply(json.response.source, args);
            break;
        default:
            var template = document.createElement('template');
            if (template.content) {
                template.innerHTML = json.response.source;
                document.head.appendChild(template);
                var nodeList = template.content.childNodes;
                for (i = 0, max = nodeList.length; i < max; i++) {
                    var element = nodeList[i];
                    if (element.nodeType != Node.ELEMENT_NODE || !element.id) {
                        continue;
                    }
                    var origin = document.getElementById(element.id);
                    if (origin) {
                        origin.parentNode.replaceChild(element.cloneNode(true), origin);
                    }
                }
                document.head.removeChild(template);

                this.setListenerForms(document.getElementById(this.containerID));
                this.setListenerButtons();
            }
            break;
    }

    if (json.finally) {
        this.finally = json.finally;
    }

    var buttons = form.querySelectorAll("button[type=submit], input[type=submit]");
    for (i = 0, max = buttons.length; i < max; i++) {
        buttons[i].disabled = false;
    }
};

Subform.prototype.submit = function(form) {
    if (form.normal_request && form.normal_request.value === '1') {
        form.submit();
        return;
    }

    var instance = TM.subform;
    TM.xhr.init('POST', form.action, true, function(event){
        if(this.status == 200){
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.posted(json, form);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
        }
    });

    if (form.polling_id && form.polling_id.value !== '') {
        this.pollingInterval = parseInt(form.polling_id.dataset.interval);
    }

    var buttons = form.querySelectorAll("button[type=submit], input[type=submit]");
    for (i = 0, max = buttons.length; i < max; i++) {
        buttons[i].disabled = true;
    }

    var formData = new FormData(form);
    formData.append('returntype', 'json');
    TM.xhr.send(formData);
};

Subform.prototype.download = function(mode) {
    location.href = mode;
};

Subform.prototype.progress = function(args) {
    var progressbar = document.getElementById('progressbar');
    if (!progressbar) {
        var template = document.getElementById('polling-parts');
        if (template.content) {
            progressbar = template.content.getElementById('progressbar');
            progressbar = document.body.appendChild(progressbar.cloneNode(true));
        }
        var bottom = document.getElementById('cancel-subform');
        bottom.parentNode.insertBefore(progressbar, bottom);
    }
    progressbar.querySelector('.description').innerHTML = args;

    var counter = args.split(' / ');
    var percent = Math.round(parseInt(counter[0]) / parseInt(counter[1]) * 100);
    progressbar.querySelector('.bar').style.width = percent + '%';
};

Subform.prototype.ended = function(args) {
    console.log(args);
};

Subform.prototype.showLog = function(polling_id, mode) {
    var template = document.getElementById('polling-parts');
    if (template.content) {
        var panel = template.content.getElementById('log-block');
        panel = document.body.appendChild(panel.cloneNode(true));

        var iframe = panel.querySelector('iframe');
        iframe.src = '?mode=' + mode + '&polling_id=' + polling_id;

        var button = panel.querySelector('button');
        button.addEventListener('click', TM.subform.hideLog, false);
    }
};

Subform.prototype.hideLog = function() {
    var instance = TM.subform;
    var panel = document.getElementById('log-block');
    if (panel) {
        panel.parentNode.removeChild(panel);
        instance.close(document.getElementById(instance.containerID));
    }
};

Subform.prototype.polling = function(url) {
    var instance = TM.subform;
    var xhr = new TM_XMLHttpRequest();
    xhr.init('GET', url, true, function(event){
        if(this.status == 200){
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                return;
            }
            clearTimeout(instance.pollingTimer);
            if (json.status == 'running') {
                instance.pollingTimer = setTimeout(instance.polling, instance.pollingInterval, url);
            }

            if (json.finally) {
                instance.finally = json.finally;
            }
            TM.apply(json.response.source, json.arguments);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
        }
    });
    xhr.send(null);
};

Subform.prototype.listener = function(event) {
    var instance = TM.subform;
    var caller = event.target;
    switch (event.type) {
        case 'click':
            event.preventDefault();
            event.stopPropagation();

            var container = document.getElementById(instance.containerID);

            if (!container) {
                instance.open(caller);
            }
            else if (caller.id === 'cancel-subform-button') {
                instance.close(container);
            }
            break;
        case 'submit':
            event.preventDefault();
            event.stopPropagation();

            instance.submit(caller);
            break;
        case 'transitionend':
            if (caller.id === instance.containerID) {
                instance.endOpenTransition(caller);
            }
            break;
    }
};

Subform.prototype.init = function(event) {
    if (this.inited) {
        return;
    }

    var elements = document.body.getElementsByClassName('subform-opener');
    for (i = 0, max = elements.length; i < max; i++) {
        elements[i].addEventListener('click', this.listener, false);
    }

    this.inited = true;
};

Subform.prototype.onLoad = function(scope, func) {
    window.addEventListener(
        'DOMContentLoaded',
        function(event) {
            scope[func](event);
        },
        false
    );
};

/**
 * Create instance
 */
window.TM = window.TM || new TM_Common();
TM.subform = new Subform();
