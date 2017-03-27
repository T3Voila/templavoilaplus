var browserPos = null;

function setFormValueOpenBrowser(url, mode, params) {
    var url = url + "&mode=" + mode + "&bparams=" + params;

    browserWin = window.open(url, "templavoilareferencebrowser", "height=350,width=" + (mode == "db" ? 650 : 600) + ",status=0,menubar=0,resizable=1,scrollbars=1");
    browserWin.focus();
}

function setFormValueFromBrowseWin(fName, value, label, exclusiveValues) {
    if (value) {
        var ret = value.split('_');
        var rid = ret.pop();
        ret = ret.join('_');
        browserPos.href = browserPos.rel.replace('%23%23%23', ret + ':' + rid);
        jumpToUrl(browserPos.href);
    }
}

function jumpToUrl(URL) {
    window.location.href = URL;
    return false;
}

function setHighlight(id) {
    top.fsMod.recentIds["web"] = id;
    top.fsMod.navFrameHighlightedID["web"] = "pages" + id + "_" + top.fsMod.currentBank;	// For highlighting

    if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
        top.content.nav_frame.refresh_nav();
    }
}

function editList(table, idList) {
    var list = "";

    // Checking how many is checked, how many is not
    var pointer = 0;
    var pos = idList.indexOf(",");
    while (pos != -1) {
        if (cbValue(table + "|" + idList.substr(pointer, pos - pointer))) {
            list += idList.substr(pointer, pos - pointer) + ",";
        }
        pointer = pos + 1;
        pos = idList.indexOf(",", pointer);
    }
    if (cbValue(table + "|" + idList.substr(pointer))) {
        list += idList.substr(pointer) + ",";
    }

    return list ? list : idList;
}

// --- drag & drop ----

var sortable_currentItem;
// Needs also:
// sortable_linkParameters = mod1/index.php -- $this->link_getParameters()

function sortable_unhideRecord(it, command) {
    jumpToUrl(command);
}

function sortable_hideRecord(it, command) {
    if (!sortable_removeHidden) {
        return jumpToUrl(command);
    }

    while ((typeof it.className == "undefined") || (it.className.search(/tpm-element(?!-)/) == -1)) {
        it = it.parentNode;
    }
    new Ajax.Request(command);
    new Effect.Fade(it,
        { duration: 0.5,
            afterFinish: sortable_hideRecordCallBack });
}

function sortable_hideRecordCallBack(obj) {
    var el = obj.element;

    while (el.lastChild) {
        el.removeChild(el.lastChild);
    }
}

function sortable_unlinkRecordCallBack(obj)
{
    $parentSortable = TYPO3.jQuery(obj).parents('.ui-sortable');
    obj.remove();
    sortable_updateItemButtons($parentSortable[0], $parentSortable.sortable('toArray'))
}

function sortable_unlinkRecord(pointer, id, elementPointer) {
    new TYPO3.jQuery.ajax({
        url: TYPO3.settings.ajaxUrls['Ppi\\TemplaVoilaPlus\\Module\\Mod1\\Ajax::unlinkRecord'],
        type: 'post',
        cache: false,
        data: {
            'unlink': pointer
        }
    }).done(function(data) {
        // @TODO insert unlinked element into sidebar, so it is viewable without reloading?
        // This was functional in older TV releases.

        // Fade out unlinked element
        new TYPO3.jQuery('#' + id).fadeTo('fast', 0.0, function() {
            sortable_unlinkRecordCallBack(TYPO3.jQuery(this))
        });
    });
}

function sortable_unlinkRecordSidebarCallBack(pointer) {
    var childNodes = $('tx_templavoilaplus_mod1_sidebar-bar').childElements();
    var innerHeight = 0;
    for (var i = 0; i < childNodes.length; i++) {
        innerHeight += childNodes[i].getHeight();
    }
    $('tx_templavoilaplus_mod1_sidebar-bar').morph(
        { height: innerHeight + 'px'},
        {
            duration: 0.1,
            afterFinish: function() {
                $('tx_templavoilaplus_mod1_sidebar-bar').setStyle({height: 'auto'});
                if (pointer && $(pointer)) {
                    $(pointer).highlight();
                }
            }
        }
    );
}

function sortable_updateItemButtons(list, sortOrder)
{
    sortOrder.forEach(function(itemId, position) {
        var newPos = sortable_containers['#' + list.id] + (position + 1);
        TYPO3.jQuery('#' + itemId).find('a').each(function() {
            $this = TYPO3.jQuery(this);
            if ($this.hasClass('tpm-new')) {
                this.setAttribute('onclick', this.getAttribute('onclick').replace(/&parentRecord=[^&]+/, "&parentRecord=" + newPos));
            }
            if ($this.hasClass('tpm-browse')) {
                if (this.rel) {
                    this.rel = this.rel.replace(/&destination=[^&]+/, "&destination=" + newPos);
                }
            }
            if ($this.hasClass('tpm-delete')) {
                this.href = this.href.replace(/&deleteRecord=[^&]+/, "&deleteRecord=" + newPos);
            }
            if ($this.hasClass('tpm-unlink')) {
                this.href = this.href.replace(/unlinkRecord\('[^']+'/, "unlinkRecord(\'" + newPos + "\'");
            }
            if ($this.hasClass('tpm-cut') || $this.hasClass('tpm-copy') || $this.hasClass('tpm-ref') ) {
                this.href = this.href.replace(/CB\[el\]\[([^\]]+)\]=[^&]+/, "CB[el][$1]=" + newPos);
            }
            if ($this.hasClass('tpm-pasteAfter') || $this.hasClass('tpm-pasteSubRef')) {
                this.href = this.href.replace(/&destination=[^&]+/, "&destination=" + newPos);
            }
            if ($this.hasClass('tpm-makeLocal')) {
                this.href = this.href.replace(/&makeLocalRecord=[^&]+/, "&makeLocalRecord=" + newPos);
            }
        });
    });
}

function sortable_update(list, element, sortOrder)
{
    destinationIndex = sortOrder.indexOf(element.id);

    // If it wasn't found in sortOrder then it was removed from given list'
    if (destinationIndex != -1 && sortableSource != null) {
        var destination = sortable_containers['#' + list.id] + destinationIndex;
        new TYPO3.jQuery.ajax({
            url: TYPO3.settings.ajaxUrls['Ppi\\TemplaVoilaPlus\\Module\\Mod1\\Ajax::moveRecord'],
            type: 'post',
            cache: false,
            data: {
                'source': sortableSource,
                'destination': destination
            }
        }).done(function(data) {
        });
    }

    sortable_updateItemButtons(list, sortOrder);
}

function sortable_start(list, element, sortOrder)
{
    sortableSource = sortable_containers['#' + list.id] + (sortOrder.indexOf(element.id) + 1);
    sortableSourceList = '#' + list.id;
}

function sortable_stop()
{
    sortableSource = null;
    sortableSourceList = null;
}

function tv_createSortable(container, connectWith)
{
    var $sortingContainer = TYPO3.jQuery(container);
    $sortingContainer.sortable(
    {
        connectWith: connectWith,
        handle: '.sortable_handle',
        items: '.sortableItem',
        //zIndex: '4000',
        tolerance: 'pointer',
        opacity: 0.5,
        revert: true,
        start: function (event, ui) {
            sortable_start(TYPO3.jQuery(this)[0], ui.item[0], TYPO3.jQuery(this).sortable('toArray'));
        },
        update: function (event, ui) {
            sortable_update(TYPO3.jQuery(this)[0], ui.item[0], TYPO3.jQuery(this).sortable('toArray'));
        },
        stop: function (event, ui) {
            sortable_stop();
        },
        forcePlaceholderSize: true,
        placeholder: 'drag-placeholder'
    });
    $sortingContainer.disableSelection();
}
