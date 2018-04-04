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
    this.containerID = 'subform-container';
    this.cnTransition = 'trans-left';
    this.subformWidth = undefined;
    this.onLoad(this, 'init');
}

Subform.prototype.show = function(container) {
    var instance = TM.subform;
    container.addEventListener('transitionend', instance.listener, false);
    container.classList.add(instance.cnTransition);
    container.style.left = '0';
};

Subform.prototype.endTransition = function(container) {
    if (container.offsetLeft < 0) {
        container.parentNode.removeChild(container);
    }
    else {
        var cancelSubformButton = document.getElementById('cancel-subform-button');
        cancelSubformButton.addEventListener('click', this.listener, false);
    }
};

Subform.prototype.create = function(json) {
    var container = document.body.appendChild(document.createElement('div'));
    container.id = this.containerID;
    container.innerHTML = json.response;

    var forms = container.getElementsByTagName('form');
    for (i = 0, max = forms.length; i < max; i++) {
        forms[i].addEventListener('submit', this.listener, false);
    }

    this.width = container.offsetWidth;

    container.style.left = '-' + this.width + 'px';
    setTimeout(this.show, 0, container);
};

Subform.prototype.close = function(container) {
    container.style.left = '-' + this.width + 'px';
};

Subform.prototype.open = function(element) {
    var instance = TM.subform;
    TM.xhr.init('GET', element.href, false, function(event){
        if(this.status == 200){
            try {
                var obj = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.create(obj);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
        }
    });
    TM.xhr.send(null);
};

Subform.prototype.posted = function(json) {
    alert(json.message);
    if (json.status === 0) {
        this.close(document.getElementById(this.containerID));
    }

    switch (json.response.type) {
        case 'redirect':
            location.href = json.response.source;
            break;
        default:
            var template = document.createElement('template');
            if (template.content) {
                template.innerHTML = json.response.source;
                document.head.appendChild(template);
                var nodeList = template.content.childNodes;
                var i, max;
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
            }
            break;
    }
};

Subform.prototype.submit = function(form) {
    var instance = TM.subform;
    TM.xhr.init('POST', form.action, false, function(event){
        if(this.status == 200){
            try {
                var obj = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.posted(obj);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
        }
    });

    var formData = new FormData(form);
    formData.append('returntype', 'json');
    TM.xhr.send(formData);
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
            instance.endTransition(caller);
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
