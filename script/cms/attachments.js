/**
 * Javascript Library for Tak-Me CMS
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */

var TM_Attachments = function() {
    this.target = undefined;
    this.onLoad(this, 'init');
};

TM_Attachments.prototype.onDragStart = function(ev) {
    var element = TM.attachments.getElement(ev)
    TM.attachments.target = element;
};

TM_Attachments.prototype.onDragLeave = function(ev) {
    var element = TM.attachments.getElement(ev)
    element.classList.remove('lockon');
};

TM_Attachments.prototype.onDragEnter = function(ev) {
    ev.preventDefault();
    var element = TM.attachments.getElement(ev)
    element.classList.add('lockon');
};

TM_Attachments.prototype.onDrop = function(ev) {
    ev.preventDefault();
    var element = TM.attachments.getElement(ev)
    element.parentNode.insertBefore(TM.attachments.target, element);
    element.classList.remove('lockon');
};

TM_Attachments.prototype.getElement = function(ev) {
    var element = ev.target;
    if (!element.classList.contains('file-set')) {
        element= TM.getParentNode(element, '.file-set');
    }
    return element;
};

TM_Attachments.prototype.setListener = function(element) {
    if (!element.classList.contains('file-set')) {
        return;
    }
    element.draggable = element.id !== 'attachment-origin';
    element.addEventListener('dragleave', this.onDragLeave, false);
    element.addEventListener('dragstart', this.onDragStart, false);
    element.addEventListener('dragover',  this.onDragEnter, false);
    element.addEventListener('drop',      this.onDrop, false);
};

TM_Attachments.prototype.init = function(ev) {
    var i, j, element;
    this.block = document.getElementById('file-uploader');
    if (!this.block) {
        return;
    }
    var elements = this.block.getElementsByTagName('div');
    for (i = 0; i < elements.length; i++) {
        this.setListener(elements[i]);
    }
};

TM_Attachments.prototype.onLoad = function(scope, func) {
    addEventListener('load', function(ev){ scope[func](ev); }, false);
};

// Create instance
if(!window.TM) window.TM = new TM_Common();
TM.attachments = new TM_Attachments();
