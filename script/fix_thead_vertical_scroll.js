/**
 * Fix a table header on vertical scroll
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive. 
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function FixTheadOnVerticalScroll() {
    this.inited = false;
    this.cnTable = 'ftv-table';
    this.cnHorizontalScroller = 'ftv-h-scroller';
    this.cnVerticalScroller = 'ftv-v-scroller';
    this.cnFlexColumn = 'ftv-flex-column';
    this.cnFixedHeader = 'ftv-fixed-header';
    this.onLoad(this, 'init');
}

FixTheadOnVerticalScroll.prototype.init = function(ev) {
    if (this.inited) {
        return;
    }
    var i, max;
    var elements = document.getElementsByClassName(this.cnTable);
    for (i = 0, max = elements.length; i < max; i++) {
        this.fix(elements[i]);
    }
    window.addEventListener('resize', this.onResize, false);
    this.inited = true;
};

FixTheadOnVerticalScroll.prototype.fix = function(table) {

    var horizontalScroller = document.createElement('div');
    horizontalScroller.className = this.cnHorizontalScroller;

    var flexColumn = horizontalScroller.appendChild(document.createElement('div'));
    flexColumn.className = this.cnFlexColumn;

    var fixedHeader = horizontalScroller.appendChild(document.createElement('table'));
    fixedHeader.className = this.cnFixedHeader;
    flexColumn.appendChild(fixedHeader);

    var verticalScroller = flexColumn.appendChild(document.createElement('div'));
    verticalScroller.className = this.cnVerticalScroller;

    var thead = table.querySelector('thead');
    fixedHeader.appendChild(thead);

    table.parentNode.insertBefore(horizontalScroller, table);
    verticalScroller.appendChild(table);

    this.align(horizontalScroller);
};

FixTheadOnVerticalScroll.prototype.align = function(horizontalScroller) {
    var tableRowFixed = horizontalScroller.querySelector('.' + this.cnFixedHeader).querySelector('tr');
    var tableRowOrigin = horizontalScroller.querySelector('.' + this.cnVerticalScroller).querySelector('tr');

    var tableCellsFixed = tableRowFixed.getElementsByTagName('td');
    var tableCellsOrigin = tableRowOrigin.getElementsByTagName('td');

    if (tableCellsFixed.length != tableCellsOrigin.length) {
        /* TODO: align width colspan */
        return;
    }

    var i, max;
    for (i = 0, max = tableCellsOrigin.length; i < max; i++) {
        tableCellsFixed[i].style.width = tableCellsOrigin[i].offsetWidth + 'px';
    }

    // overflow for vertical scroll
    var flexColumn = horizontalScroller.querySelector('.' + this.cnFlexColumn);
    var table = horizontalScroller.querySelector('.' + this.cnTable);
    if (flexColumn.clientWidth < table.clientWidth) {
        flexColumn.style.width = table.clientWidth + 'px';
    }
};

FixTheadOnVerticalScroll.prototype.onResize = function(ev) {
    var instance = window.fixTheadOnVerticalScroll;
    var elements = document.getElementsByClassName(instance.cnHorizontalScroller);
    for (i = 0, max = elements.length; i < max; i++) {
        instance.align(elements[i]);
    }
};

FixTheadOnVerticalScroll.prototype.onLoad = function(scope, func) {
    window.addEventListener(
        'DOMContentLoaded',
        function(ev) {
            scope[func](ev);
        },
        false
    );
};

window.fixTheadOnVerticalScroll = new FixTheadOnVerticalScroll();
