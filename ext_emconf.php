<?php

########################################################################
# Extension Manager/Repository config file for ext: 'templavoila'
#
# Auto generated 02-02-2004 15:40
#
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'TemplaVoila!',
	'description' => 'A cool tool ...',
	'category' => 'module',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'cm1,cm2,mod1,mod2',
	'state' => 'alpha',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_templavoila/',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj / Robert Lemke',
	'author_email' => 'kasper@typo3.com / rl@robertlemke.de',
	'author_company' => 'TYPO3',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'private' => 0,
	'download_password' => '',
	'version' => '0.2.0',	// Don't modify this! Managed automatically during upload to repository.
	'_md5_values_when_last_written' => 'a:124:{s:9:"ChangeLog";s:4:"e422";s:28:"class.tx_templavoila_cm1.php";s:4:"8d3a";s:51:"class.tx_templavoila_handlestaticdatastructures.php";s:4:"da3b";s:35:"class.tx_templavoila_htmlmarkup.php";s:4:"4bc2";s:30:"class.tx_templavoila_rules.php";s:4:"6579";s:21:"ext_conf_template.txt";s:4:"8025";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"730d";s:15:"ext_php_api.dat";s:4:"33c4";s:14:"ext_tables.php";s:4:"48a9";s:14:"ext_tables.sql";s:4:"f044";s:11:"icon_ds.gif";s:4:"0b15";s:14:"icon_ds__x.gif";s:4:"9991";s:11:"icon_to.gif";s:4:"de63";s:14:"icon_to__x.gif";s:4:"46d4";s:13:"locallang.php";s:4:"ba9d";s:23:"locallang_csh_pages.php";s:4:"237e";s:16:"locallang_db.php";s:4:"a6ff";s:7:"tca.php";s:4:"21c0";s:11:"CVS/Entries";s:4:"f474";s:14:"CVS/Repository";s:4:"ac05";s:8:"CVS/Root";s:4:"be87";s:13:"cm1/clear.gif";s:4:"cc11";s:15:"cm1/cm_icon.gif";s:4:"8074";s:12:"cm1/conf.php";s:4:"2fbb";s:13:"cm1/index.php";s:4:"be78";s:15:"cm1/item_at.gif";s:4:"8362";s:15:"cm1/item_co.gif";s:4:"3a12";s:15:"cm1/item_el.gif";s:4:"f8d6";s:15:"cm1/item_sc.gif";s:4:"e42d";s:17:"cm1/locallang.php";s:4:"0017";s:15:"cm1/CVS/Entries";s:4:"498d";s:18:"cm1/CVS/Repository";s:4:"f4da";s:12:"cm1/CVS/Root";s:4:"be87";s:12:"doc/TODO.txt";s:4:"2b64";s:14:"doc/manual.sxw";s:4:"6131";s:15:"doc/CVS/Entries";s:4:"c7a5";s:18:"doc/CVS/Repository";s:4:"ead5";s:12:"doc/CVS/Root";s:4:"be87";s:15:"html_tags/a.gif";s:4:"afe4";s:18:"html_tags/area.gif";s:4:"18d0";s:15:"html_tags/b.gif";s:4:"b123";s:24:"html_tags/blockquote.gif";s:4:"99f0";s:18:"html_tags/body.gif";s:4:"87ff";s:16:"html_tags/br.gif";s:4:"e0b0";s:17:"html_tags/div.gif";s:4:"139d";s:16:"html_tags/em.gif";s:4:"c543";s:19:"html_tags/embed.gif";s:4:"e128";s:18:"html_tags/font.gif";s:4:"28bf";s:18:"html_tags/form.gif";s:4:"4da0";s:16:"html_tags/h1.gif";s:4:"e1e4";s:16:"html_tags/h2.gif";s:4:"23b7";s:16:"html_tags/h3.gif";s:4:"ccc3";s:16:"html_tags/h4.gif";s:4:"fadd";s:16:"html_tags/h5.gif";s:4:"3cae";s:16:"html_tags/h6.gif";s:4:"72d9";s:18:"html_tags/head.gif";s:4:"9048";s:16:"html_tags/hr.gif";s:4:"543e";s:15:"html_tags/i.gif";s:4:"89dc";s:20:"html_tags/iframe.gif";s:4:"e8b6";s:17:"html_tags/img.gif";s:4:"7a1b";s:19:"html_tags/input.gif";s:4:"3952";s:16:"html_tags/li.gif";s:4:"b411";s:18:"html_tags/link.gif";s:4:"a93c";s:17:"html_tags/map.gif";s:4:"8aac";s:18:"html_tags/meta.gif";s:4:"7382";s:16:"html_tags/ol.gif";s:4:"173a";s:20:"html_tags/option.gif";s:4:"cd86";s:15:"html_tags/p.gif";s:4:"1261";s:17:"html_tags/pre.gif";s:4:"44bc";s:23:"html_tags/prototype.psd";s:4:"f154";s:20:"html_tags/script.gif";s:4:"b861";s:20:"html_tags/select.gif";s:4:"85b4";s:18:"html_tags/span.gif";s:4:"9291";s:20:"html_tags/strong.gif";s:4:"e4d8";s:19:"html_tags/style.gif";s:4:"253e";s:19:"html_tags/table.gif";s:4:"cf2c";s:19:"html_tags/tbody.gif";s:4:"7058";s:16:"html_tags/td.gif";s:4:"90ab";s:22:"html_tags/textarea.gif";s:4:"b575";s:19:"html_tags/thead.gif";s:4:"5321";s:19:"html_tags/title.gif";s:4:"b867";s:16:"html_tags/tr.gif";s:4:"a0a7";s:15:"html_tags/u.gif";s:4:"7b3c";s:16:"html_tags/ul.gif";s:4:"a73a";s:21:"html_tags/CVS/Entries";s:4:"0b53";s:24:"html_tags/CVS/Repository";s:4:"966f";s:18:"html_tags/CVS/Root";s:4:"be87";s:14:"mod1/clear.gif";s:4:"cc11";s:17:"mod1/clip_ref.gif";s:4:"6812";s:19:"mod1/clip_ref_h.gif";s:4:"ac5e";s:13:"mod1/conf.php";s:4:"7434";s:26:"mod1/db_new_content_el.php";s:4:"3154";s:17:"mod1/greenled.gif";s:4:"3431";s:14:"mod1/index.php";s:4:"0a4f";s:18:"mod1/locallang.php";s:4:"7eef";s:36:"mod1/locallang_db_new_content_el.php";s:4:"02eb";s:22:"mod1/locallang_mod.php";s:4:"7e3b";s:19:"mod1/moduleicon.gif";s:4:"46b6";s:15:"mod1/redled.gif";s:4:"9933";s:16:"mod1/CVS/Entries";s:4:"7624";s:19:"mod1/CVS/Repository";s:4:"a061";s:13:"mod1/CVS/Root";s:4:"be87";s:14:"mod2/clear.gif";s:4:"cc11";s:13:"mod2/conf.php";s:4:"0996";s:14:"mod2/index.php";s:4:"8753";s:18:"mod2/locallang.php";s:4:"f717";s:22:"mod2/locallang_mod.php";s:4:"898f";s:19:"mod2/moduleicon.gif";s:4:"a421";s:16:"mod2/CVS/Entries";s:4:"a7bd";s:19:"mod2/CVS/Repository";s:4:"4f79";s:13:"mod2/CVS/Root";s:4:"be87";s:32:"pi1/class.tx_templavoila_pi1.php";s:4:"2247";s:15:"pi1/CVS/Entries";s:4:"684c";s:18:"pi1/CVS/Repository";s:4:"15fe";s:12:"pi1/CVS/Root";s:4:"be87";s:32:"pi2/class.tx_templavoila_pi2.php";s:4:"e70c";s:15:"pi2/CVS/Entries";s:4:"cca3";s:18:"pi2/CVS/Repository";s:4:"70bf";s:12:"pi2/CVS/Root";s:4:"be87";s:28:"res1/default_previewicon.gif";s:4:"edf8";s:16:"res1/CVS/Entries";s:4:"415c";s:19:"res1/CVS/Repository";s:4:"a5e1";s:13:"res1/CVS/Root";s:4:"be87";}',
);

?>
