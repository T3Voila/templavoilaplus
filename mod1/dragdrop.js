var sortable_currentItem;
// Needs also:
// sortable_linkParameters = mod1/index.php -- $this->link_getParameters()

function sortable_unhideRecord(it, command) {
	jumpToUrl(command);
}

function sortable_hideRecord(it, command) {
	if (!sortable_removeHidden)
		return jumpToUrl(command);

	while (it.className != 'sortableItem')
		it = it.parentNode;

	new Ajax.Request(command);
	new Effect.Fade(it,
		{ duration: 0.5,
		  afterFinish: sortable_hideRecordCallBack });
}

function sortable_hideRecordCallBack(obj) {
	var el = obj.element;

	while (el.lastChild)
		el.removeChild(el.lastChild);
}

function sortable_unlinkRecordCallBack(obj) {
	var el = obj.element;
	var pn = el.parentNode;
	pn.removeChild(el);
	sortable_update(pn);
}

function sortable_unlinkRecord(id) {
	new Ajax.Request("index.php?" + sortable_linkParameters + "&ajaxUnlinkRecord="+escape(id)); /* xxx */
	new Effect.Fade(id,
		{ duration: 0.5,
		afterFinish: sortable_unlinkRecordCallBack });
}

function sortable_updateItemButtons(el, position, pID) {
	var p	= new Array();	var p1 = new Array();
	var href = "";	var i=0;
	var newPos = escape(pID + position);
	var childs = el.childElements();
	var buttons = childs[0].childElements()[0].childElements()[0].childElements()[1].childNodes;
	for (i = 0; i < buttons.length ;i++) {
		if (buttons[i].nodeType != 1) continue;
		href = buttons[i].href;
		//alert(href);
		if (href.charAt(href.length - 1) == "#") continue;
		if ((p = href.split("unlinkRecord")).length == 2) {
			buttons[i].href = p[0] + "unlinkRecord(\'" + newPos + "\');";
		} else if ((p = href.split("deleteRecord")).length == 2) {
			buttons[i].href = p[0] + "deleteRecord=" + newPos;
		} else if((p = href.split("CB[el][tt_content")).length == 2) {
			p1 = p[1].split("=");
			buttons[i].href = p[0] + "CB[el][tt_content" + p1[0]+ "="  + newPos;
		} else if ((p = href.split("&parentRecord=")).length == 2) {
			buttons[i].href = p[0] + "&parentRecord=" + newPos;
		} else if ((p = href.split("&destination=")).length == 2) {
			buttons[i].href = p[0] + "&destination=" + newPos;
		}
	}

	if ((p = childs[1].href.split("&parentRecord=")).length == 2)
			childs[1].href = p[0] + "&parentRecord=" + newPos;

	buttons = childs[2].childElements()[0];
	//alert(buttons.nodeName);
	if (buttons && (p = buttons.href.split("&destination=")).length == 2)
			buttons.href = p[0] + "&destination=" + newPos;

}

function sortable_updatePasteButtons(oldPos, newPos) {
	var i = 0; var p = new Array; var href = "";
	var buttons = document.getElementsByClassName("sortablePaste");
	if (buttons[i].firstChild && buttons[i].firstChild.href.indexOf("&source="+escape(oldPos)) != -1) {
		for (i = 0; i < buttons.length; i++) {
			if (buttons[i].firstChild) {
				href = buttons[i].firstChild.href;
				if ((p = href.split("&source="+escape(oldPos))).length == 2) {
					buttons[i].firstChild.href = p[0] + "&source=" + escape(newPos) + p[1];
				}
			}
		}
	}
}

function sortable_update(el) {
	var node = el.firstChild;
	var i = 1;
	while (node != null) {
		if (node.className == "sortableItem") {
			if (sortable_currentItem && node.id == sortable_currentItem.id ) {
				var url = "index.php?" + sortable_linkParameters + "&ajaxPasteRecord=cut&source=" + sortable_items[sortable_currentItem.id] + "&destination=" + sortable_items[el.id] + (i-1); /* xxx */
				new Ajax.Request(url);
				sortable_updatePasteButtons(sortable_items[node.id], sortable_items[el.id] + i);
				sortable_currentItem = false;
			}
			sortable_updateItemButtons(node, i, sortable_items[el.id]);
			sortable_items[node.id] = sortable_items[el.id] + i;
			i++;
		}
		node	= node.nextSibling;
	}
}

function sortable_change(el) {
	sortable_currentItem=el;
}

function tv_createSortable(s, containment) {
	Sortable.create(s,{
		tag:"div",
		ghosting:false,
		format: /(.*)/,
		handle:"sortable_handle",
		dropOnEmpty:true,
		constraint:false,
		containment: containment,
		onChange:sortable_change,
		onUpdate:sortable_update});
}
