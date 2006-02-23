<?php

########################################################################
# Extension Manager/Repository config file for ext: "templavoila"
#
# Auto generated 22-01-2006 19:21
#
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'TemplaVoila!',
	'description' => 'An alternative template engine for TYPO3. Features include a mapping tool for creating templates, a new page module, the ability to create flexible content elements and an API for developers.',
	'category' => 'misc',
	'shy' => 0,
	'dependencies' => 'cms,lang,static_info_tables',
	'conflicts' => 'kb_tv_clipboard,templavoila_cw,eu_tradvoila',
	'constraints' => array(
		'depends' => array(
			'php' => '4.3.0-',
			'typo3' => '3.9.9-',
			'static_info_tables' => '',
			'cms' => '',
			'lang' => ''
		),
		'conflicts' => array(
			'kb_tv_clipboard' => '-0.1.0',
			'templavoila_cw' => '-0.1.0',
			'eu_tradvoila' => '-0.1.0'
		),
		'suggests' => array(
			'doc_tut_ftb1',
			'rlmp_tvnotes'
		),
	),
	'priority' => '',
	'loadOrder' => '',
	'module' => 'cm1,cm2,mod1,mod2',
	'state' => 'beta',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_templavoila/',
	'modify_tables' => 'pages,tt_content',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Kasper Skårhøj / Robert Lemke',
	'author_email' => 'kasper@typo3.com / robert@typo3.org',
	'author_company' => 'TYPO3',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'private' => 0,
	'download_password' => '',
	'version' => '0.6.0',
	'_md5_values_when_last_written' => 'a:174:{s:10:".cvsignore";s:4:"44ff";s:8:".project";s:4:"db53";s:9:"ChangeLog";s:4:"3276";s:28:"class.tx_templavoila_api.php";s:4:"cc20";s:28:"class.tx_templavoila_cm1.php";s:4:"91fc";s:51:"class.tx_templavoila_handlestaticdatastructures.php";s:4:"ace0";s:35:"class.tx_templavoila_htmlmarkup.php";s:4:"5dc7";s:30:"class.tx_templavoila_rules.php";s:4:"5ea4";s:32:"class.tx_templavoila_tcemain.php";s:4:"0f3a";s:35:"class.tx_templavoila_xmlrelhndl.php";s:4:"c6f9";s:21:"ext_conf_template.txt";s:4:"0be8";s:12:"ext_icon.gif";s:4:"cb59";s:17:"ext_localconf.php";s:4:"26e7";s:15:"ext_php_api.dat";s:4:"212e";s:14:"ext_tables.php";s:4:"9015";s:14:"ext_tables.sql";s:4:"fe0f";s:11:"icon_ds.gif";s:4:"0b15";s:14:"icon_ds__x.gif";s:4:"9991";s:11:"icon_to.gif";s:4:"de63";s:14:"icon_to__x.gif";s:4:"46d4";s:13:"locallang.xml";s:4:"392d";s:20:"locallang_csh_ds.xml";s:4:"82b9";s:23:"locallang_csh_intro.xml";s:4:"7859";s:24:"locallang_csh_module.xml";s:4:"bd35";s:23:"locallang_csh_pages.xml";s:4:"04de";s:20:"locallang_csh_pm.xml";s:4:"23a6";s:20:"locallang_csh_to.xml";s:4:"f268";s:21:"locallang_csh_ttc.xml";s:4:"91e5";s:16:"locallang_db.xml";s:4:"47d8";s:7:"tca.php";s:4:"d133";s:42:".settings/org.eclipse.core.resources.prefs";s:4:"4d8e";s:13:"cm1/clear.gif";s:4:"cc11";s:15:"cm1/cm_icon.gif";s:4:"cb59";s:24:"cm1/cm_icon_activate.gif";s:4:"cb59";s:12:"cm1/conf.php";s:4:"66ed";s:13:"cm1/index.php";s:4:"1e18";s:15:"cm1/item_at.gif";s:4:"8362";s:15:"cm1/item_co.gif";s:4:"3a12";s:15:"cm1/item_el.gif";s:4:"f8d6";s:15:"cm1/item_sc.gif";s:4:"e42d";s:17:"cm1/locallang.xml";s:4:"9f79";s:15:"cm1/CVS/Entries";s:4:"4419";s:18:"cm1/CVS/Repository";s:4:"d3bc";s:12:"cm1/CVS/Root";s:4:"8fee";s:13:"cm2/clear.gif";s:4:"cc11";s:15:"cm2/cm_icon.gif";s:4:"cb59";s:12:"cm2/conf.php";s:4:"2f07";s:13:"cm2/index.php";s:4:"2c6b";s:17:"cm2/locallang.xml";s:4:"084c";s:15:"cm2/CVS/Entries";s:4:"05ad";s:18:"cm2/CVS/Repository";s:4:"5b95";s:12:"cm2/CVS/Root";s:4:"8fee";s:19:"cshimages/intro.png";s:4:"018d";s:21:"cshimages/CVS/Entries";s:4:"a33c";s:24:"cshimages/CVS/Repository";s:4:"dd38";s:18:"cshimages/CVS/Root";s:4:"8fee";s:11:"CVS/Entries";s:4:"0a7e";s:14:"CVS/Repository";s:4:"e2d6";s:8:"CVS/Root";s:4:"8fee";s:12:"doc/TODO.txt";s:4:"691d";s:14:"doc/manual.sxw";s:4:"92d4";s:15:"doc/CVS/Entries";s:4:"50fc";s:18:"doc/CVS/Repository";s:4:"7fa5";s:12:"doc/CVS/Root";s:4:"8fee";s:15:"html_tags/a.gif";s:4:"afe4";s:18:"html_tags/area.gif";s:4:"18d0";s:15:"html_tags/b.gif";s:4:"b123";s:24:"html_tags/blockquote.gif";s:4:"99f0";s:18:"html_tags/body.gif";s:4:"87ff";s:16:"html_tags/br.gif";s:4:"e0b0";s:16:"html_tags/dd.gif";s:4:"0939";s:17:"html_tags/div.gif";s:4:"139d";s:16:"html_tags/dl.gif";s:4:"ff7a";s:16:"html_tags/dt.gif";s:4:"2dbd";s:16:"html_tags/em.gif";s:4:"c543";s:19:"html_tags/embed.gif";s:4:"e128";s:22:"html_tags/fieldset.gif";s:4:"0335";s:18:"html_tags/font.gif";s:4:"28bf";s:18:"html_tags/form.gif";s:4:"4da0";s:16:"html_tags/h1.gif";s:4:"e1e4";s:16:"html_tags/h2.gif";s:4:"23b7";s:16:"html_tags/h3.gif";s:4:"ccc3";s:16:"html_tags/h4.gif";s:4:"fadd";s:16:"html_tags/h5.gif";s:4:"3cae";s:16:"html_tags/h6.gif";s:4:"72d9";s:18:"html_tags/head.gif";s:4:"9048";s:16:"html_tags/hr.gif";s:4:"543e";s:15:"html_tags/i.gif";s:4:"89dc";s:20:"html_tags/iframe.gif";s:4:"e8b6";s:17:"html_tags/img.gif";s:4:"7a1b";s:19:"html_tags/input.gif";s:4:"3952";s:19:"html_tags/label.gif";s:4:"fe96";s:20:"html_tags/legend.gif";s:4:"ed85";s:16:"html_tags/li.gif";s:4:"b411";s:18:"html_tags/link.gif";s:4:"a93c";s:17:"html_tags/map.gif";s:4:"8aac";s:18:"html_tags/meta.gif";s:4:"7382";s:16:"html_tags/ol.gif";s:4:"173a";s:20:"html_tags/option.gif";s:4:"cd86";s:15:"html_tags/p.gif";s:4:"1261";s:17:"html_tags/pre.gif";s:4:"44bc";s:23:"html_tags/prototype.psd";s:4:"f154";s:20:"html_tags/script.gif";s:4:"b861";s:20:"html_tags/select.gif";s:4:"85b4";s:18:"html_tags/span.gif";s:4:"9291";s:20:"html_tags/strong.gif";s:4:"e4d8";s:19:"html_tags/style.gif";s:4:"253e";s:19:"html_tags/table.gif";s:4:"cf2c";s:19:"html_tags/tbody.gif";s:4:"7058";s:16:"html_tags/td.gif";s:4:"90ab";s:22:"html_tags/textarea.gif";s:4:"b575";s:16:"html_tags/th.gif";s:4:"6fd4";s:19:"html_tags/thead.gif";s:4:"5321";s:19:"html_tags/title.gif";s:4:"b867";s:16:"html_tags/tr.gif";s:4:"a0a7";s:15:"html_tags/u.gif";s:4:"7b3c";s:16:"html_tags/ul.gif";s:4:"a73a";s:21:"html_tags/CVS/Entries";s:4:"bbd6";s:24:"html_tags/CVS/Repository";s:4:"af5f";s:18:"html_tags/CVS/Root";s:4:"8fee";s:21:"mod1/.#index.php.1.66";s:4:"fc8d";s:44:"mod1/class.tx_templavoila_mod1_clipboard.php";s:4:"ce4c";s:47:"mod1/class.tx_templavoila_mod1_localization.php";s:4:"bded";s:42:"mod1/class.tx_templavoila_mod1_sidebar.php";s:4:"1ede";s:50:"mod1/class.tx_templavoila_mod1_specialdoktypes.php";s:4:"8f65";s:42:"mod1/class.tx_templavoila_mod1_wizards.php";s:4:"9362";s:14:"mod1/clear.gif";s:4:"cc11";s:25:"mod1/clip_pastesubref.gif";s:4:"9260";s:17:"mod1/clip_ref.gif";s:4:"6812";s:19:"mod1/clip_ref_h.gif";s:4:"ac5e";s:13:"mod1/conf.php";s:4:"6cb9";s:26:"mod1/db_new_content_el.php";s:4:"55e8";s:17:"mod1/greenled.gif";s:4:"3431";s:14:"mod1/index.php";s:4:"d530";s:18:"mod1/locallang.xml";s:4:"8ad7";s:36:"mod1/locallang_db_new_content_el.xml";s:4:"ba14";s:22:"mod1/locallang_mod.xml";s:4:"8332";s:22:"mod1/makelocalcopy.gif";s:4:"ce99";s:19:"mod1/moduleicon.gif";s:4:"9620";s:15:"mod1/redled.gif";s:4:"9933";s:16:"mod1/CVS/Entries";s:4:"bcdd";s:19:"mod1/CVS/Repository";s:4:"0536";s:13:"mod1/CVS/Root";s:4:"8fee";s:14:"mod2/clear.gif";s:4:"cc11";s:13:"mod2/conf.php";s:4:"d1c9";s:14:"mod2/index.php";s:4:"531d";s:18:"mod2/locallang.xml";s:4:"3c9c";s:22:"mod2/locallang_mod.xml";s:4:"c255";s:26:"mod2/mapbody_animation.gif";s:4:"f085";s:26:"mod2/maphead_animation.gif";s:4:"2208";s:19:"mod2/moduleicon.gif";s:4:"2f0c";s:20:"mod2/new_tv_site.xml";s:4:"8eb5";s:16:"mod2/CVS/Entries";s:4:"ff3e";s:19:"mod2/CVS/Repository";s:4:"d987";s:13:"mod2/CVS/Root";s:4:"8fee";s:32:"pi1/class.tx_templavoila_pi1.php";s:4:"d524";s:15:"pi1/CVS/Entries";s:4:"db75";s:18:"pi1/CVS/Repository";s:4:"3340";s:12:"pi1/CVS/Root";s:4:"8fee";s:28:"res1/default_previewicon.gif";s:4:"edf8";s:16:"res1/CVS/Entries";s:4:"300e";s:19:"res1/CVS/Repository";s:4:"5212";s:13:"res1/CVS/Root";s:4:"8fee";s:37:"tests/tx_templavoila_api_testcase.php";s:4:"3fe4";s:17:"tests/CVS/Entries";s:4:"9983";s:20:"tests/CVS/Repository";s:4:"6f8b";s:14:"tests/CVS/Root";s:4:"8fee";s:37:"tests/fixtures/page_datastructure.xml";s:4:"5de5";s:33:"tests/fixtures/page_template.html";s:4:"769f";s:38:"tests/fixtures/page_templateobject.dat";s:4:"4c2a";s:43:"tests/fixtures/t3d-import-multilanguage.xml";s:4:"e44d";s:26:"tests/fixtures/CVS/Entries";s:4:"1f9f";s:29:"tests/fixtures/CVS/Repository";s:4:"5829";s:23:"tests/fixtures/CVS/Root";s:4:"8fee";}',
);

?>