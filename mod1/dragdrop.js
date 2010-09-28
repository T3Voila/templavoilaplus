	var browserPos = null;

	function setFormValueOpenBrowser(mode,params) {	//
		var url = T3_TV_MOD1_BACKPATH + "browser.php?mode="+mode+"&bparams="+params;

		browserWin = window.open(url,"templavoilareferencebrowser","height=350,width="+(mode=="db"?650:600)+",status=0,menubar=0,resizable=1,scrollbars=1");
		browserWin.focus();
	}
	function setFormValueFromBrowseWin(fName,value,label,exclusiveValues){
		if (value) {
			var ret = value.split('_');
			var rid = ret.pop();
			ret = ret.join('_');
			browserPos.href = browserPos.rel.replace('%23%23%23', ret+':'+rid);
			jumpToUrl(browserPos.href);
		}
	}

	function jumpToUrl(URL)	{	//
		window.location.href = URL;
		return false;
	}
	function jumpExt(URL,anchor)	{	//
		var anc = anchor?anchor:"";
		window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
		return false;
	}
	function jumpSelf(URL)	{	//
		window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
		return false;
	}

	function setHighlight(id)	{	//
		top.fsMod.recentIds["web"]=id;
		top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

		if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
			top.content.nav_frame.refresh_nav();
		}
	}

	function editRecords(table,idList,addParams,CBflag)	{	//
		window.location.href=T3_TV_MOD1_BACKPATH + "alt_doc.php?returnUrl=" + T3_TV_MOD1_RETURNURL + "&edit["+table+"]["+idList+"]=edit"+addParams;
	}
	function editList(table,idList)	{	//
		var list="";

			// Checking how many is checked, how many is not
		var pointer=0;
		var pos = idList.indexOf(",");
		while (pos!=-1)	{
			if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
				list+=idList.substr(pointer,pos-pointer)+",";
			}
			pointer=pos+1;
			pos = idList.indexOf(",",pointer);
		}
		if (cbValue(table+"|"+idList.substr(pointer))) {
			list+=idList.substr(pointer)+",";
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
	if (!sortable_removeHidden)
		return jumpToUrl(command);

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

	while (el.lastChild)
		el.removeChild(el.lastChild);
}

function sortable_unlinkRecordCallBack(obj) {
	var el = obj.element;
	var pn = el.parentNode;
	pn.removeChild(el);
	sortable_update(pn);
}

function sortable_unlinkRecord(pointer, id, elementPointer) {
	new Ajax.Request("index.php?" + sortable_linkParameters + "&ajaxUnlinkRecord="+escape(pointer), {
		onSuccess: function(response) {
			var node = Builder.build(response.responseText);
			$('tx_templavoila_mod1_sidebar-bar').setStyle({height : $('tx_templavoila_mod1_sidebar-bar').getHeight() + 'px'});
			$('tx_templavoila_mod1_sidebar-bar').innerHTML = node.innerHTML;
				//we can't do that instantanious because the browser won't calculate the hights properly
			setTimeout(function(){sortable_unlinkRecordSidebarCallBack(elementPointer);}, 100);
		}
	});
	new Effect.Fade(id, {
		duration: 0.5,
		afterFinish: sortable_unlinkRecordCallBack
	});
}

function sortable_unlinkRecordSidebarCallBack(pointer) {
	var childNodes = $('tx_templavoila_mod1_sidebar-bar').childElements();
	var innerHeight = 0;
	for (var i = 0; i < childNodes.length; i++) {
		innerHeight += childNodes[i].getHeight();
	}
	$('tx_templavoila_mod1_sidebar-bar').morph(
		{ height: innerHeight + 'px'},
		{
			duration: 0.1,
			afterFinish: function() {
				$('tx_templavoila_mod1_sidebar-bar').setStyle({height : 'auto'});
				if (pointer && $(pointer)) {
					$(pointer).highlight();
				}
			}
		}
	);
}

function sortable_updateItemButtons(el, position, pID) {
	var p = [], p1 = [];
	var newPos = escape(pID + position);
	el.childElements().each(function(node){
		if (node.nodeName == 'A' && node.href) {
			switch (node.className) {
				case 'tpm-new':
					node.href = node.href.replace(/&parentRecord=[^&]+/,"&parentRecord=" + newPos);
					break;
				case 'tpm-browse':
					if (node.rel) {
						node.rel = node.rel.replace(/&destination=[^&]+/,"&destination=" + newPos);
					}
					break;
				case 'tpm-delete':
					node.href = node.href.replace(/&deleteRecord=[^&]+/,"&deleteRecord=" + newPos);
					break;
				case 'tpm-unlink':
					node.href = node.href.replace(/unlinkRecord\('[^']+'/,"unlinkRecord(\'" + newPos + "\'");
					break;
				case 'tpm-cut':
				case 'tpm-copy':
				case 'tpm-ref':
					node.href = node.href.replace(/CB\[el\]\[([^\]]+)\]=[^&]+/, "CB[el][$1]=" +  newPos);
					break;
				case 'tpm-pasteAfter':
				case 'tpm-pasteSubRef':
					node.href = node.href.replace(/&destination=[^&]+/,"&destination=" + newPos);
					break;
				case 'tpm-makeLocal':
					node.href = node.href.replace(/&makeLocalRecord=[^&]+/,"&makeLocalRecord=" + newPos);
					break;
			}
		} else if(node.childElements() && node.className != 'tpm-subelement-table') {
				// recursion within current container to "find" all pointers
				// we don't want to update nested containers since their inner references didn't change
			sortable_updateItemButtons(node, position, pID);
		}
	});
}

function sortable_update(el) {
	var node = el.firstChild;
	var i = 1;
	while (node != null) {
		if (!(typeof node.className == "undefined") && node.className.search(/tpm-element(?!-)/) > -1) {
			if (sortable_currentItem && node.id == sortable_currentItem.id ) {
				var url = T3_TV_MOD1_BACKPATH + "ajax.php?ajaxID=tx_templavoila_mod1_ajax::moveRecord&source=" + all_items[sortable_currentItem.id] + "&destination=" + all_items[el.id] + (i-1); /* xxx */
				new Ajax.Request(url);
				sortable_currentItem = false;
			}
			sortable_updateItemButtons(node, i, all_items[el.id]);
			all_items[node.id] = all_items[el.id] + i;
			i++;
		}

		node	= node.nextSibling;
	}
}

function sortable_change(el) {
	sortable_currentItem=el;
}

function tv_createSortable(s, containment) {
	Position.includeScrollOffsets = true;
	Position.prepare();
	Sortable.create(s,{
		tag:"div",
		ghosting:false,
		format: /(.*)/,
		handle:"sortable_handle",
		scroll: "typo3-docbody",
		scrollSpeed: 30,
		dropOnEmpty:true,
		constraint:false,
		containment: containment,
		onChange:sortable_change,
		onUpdate:sortable_update});
}
