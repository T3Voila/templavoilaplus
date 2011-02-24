<?php

########################################################################
# Extension Manager/Repository config file for ext "templavoila".
#
# Auto generated 24-02-2011 15:36
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TemplaVoila!',
	'description' => 'Point-and-click, popular and easy template engine for TYPO3. Public free support is provided only through TYPO3 mailing lists! Contact by e-mail for commercial support.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '1.5.4',
	'dependencies' => 'static_info_tables,cms,lang',
	'conflicts' => 'kb_tv_clipboard,templavoila_cw,eu_tradvoila,me_templavoilalayout,me_templavoilalayout2',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'cm1,cm2,mod1,mod2',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_templavoila/',
	'modify_tables' => 'pages,tt_content,be_groups',
	'clearcacheonload' => 1,
	'lockType' => 'L',
	'author' => 'TemplaVoila! Team, Tolleiv Nietsch',
	'author_email' => 'templavoila@tolleiv.de',
	'author_company' => 'AOEmedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-0.0.0',
			'static_info_tables' => '',
			'cms' => '',
			'lang' => '',
		),
		'conflicts' => array(
			'kb_tv_clipboard' => '-0.1.0',
			'templavoila_cw' => '-0.1.0',
			'eu_tradvoila' => '-0.0.2',
			'me_templavoilalayout' => '',
			'me_templavoilalayout2' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:146:{s:9:"ChangeLog";s:4:"4bf5";s:31:"class.tx_templavoila_access.php";s:4:"2d5d";s:28:"class.tx_templavoila_api.php";s:4:"f785";s:28:"class.tx_templavoila_cm1.php";s:4:"b7a8";s:35:"class.tx_templavoila_extdeveval.php";s:4:"3680";s:51:"class.tx_templavoila_handlestaticdatastructures.php";s:4:"2cd5";s:35:"class.tx_templavoila_htmlmarkup.php";s:4:"c49a";s:30:"class.tx_templavoila_rules.php";s:4:"1fb2";s:32:"class.tx_templavoila_tcemain.php";s:4:"3a24";s:33:"class.tx_templavoila_unusedce.php";s:4:"c568";s:31:"class.tx_templavoila_usedce.php";s:4:"8953";s:35:"class.tx_templavoila_xmlrelhndl.php";s:4:"10cc";s:16:"ext_autoload.php";s:4:"c036";s:21:"ext_conf_template.txt";s:4:"e7d0";s:12:"ext_icon.gif";s:4:"cb59";s:17:"ext_localconf.php";s:4:"be12";s:14:"ext_tables.php";s:4:"d8bb";s:14:"ext_tables.sql";s:4:"4af9";s:24:"ext_typoscript_setup.txt";s:4:"9995";s:11:"icon_ds.gif";s:4:"f741";s:14:"icon_ds__x.gif";s:4:"cfd9";s:15:"icon_fce_ce.png";s:4:"2347";s:21:"icon_pagetemplate.gif";s:4:"c731";s:11:"icon_to.gif";s:4:"6410";s:14:"icon_to__x.gif";s:4:"84a1";s:13:"locallang.xml";s:4:"70bf";s:20:"locallang_access.xml";s:4:"7f32";s:22:"locallang_csh_begr.xml";s:4:"e42c";s:20:"locallang_csh_ds.xml";s:4:"7b87";s:23:"locallang_csh_intro.xml";s:4:"063d";s:24:"locallang_csh_module.xml";s:4:"ff5d";s:23:"locallang_csh_pages.xml";s:4:"f028";s:20:"locallang_csh_pm.xml";s:4:"9644";s:20:"locallang_csh_to.xml";s:4:"d648";s:21:"locallang_csh_ttc.xml";s:4:"fb6f";s:16:"locallang_db.xml";s:4:"ef4c";s:7:"tca.php";s:4:"22f8";s:8:"v1.patch";s:4:"22bc";s:46:"classes/class.tx_templavoila_datastructure.php";s:4:"3674";s:56:"classes/class.tx_templavoila_datastructureRepository.php";s:4:"5ad0";s:53:"classes/class.tx_templavoila_datastructure_dbbase.php";s:4:"085a";s:57:"classes/class.tx_templavoila_datastructure_staticbase.php";s:4:"66bf";s:36:"classes/class.tx_templavoila_div.php";s:4:"8a1a";s:38:"classes/class.tx_templavoila_icons.php";s:4:"62d2";s:38:"classes/class.tx_templavoila_label.php";s:4:"fdf9";s:46:"classes/class.tx_templavoila_staticdstools.php";s:4:"306e";s:41:"classes/class.tx_templavoila_template.php";s:4:"fcdc";s:51:"classes/class.tx_templavoila_templateRepository.php";s:4:"5db7";s:56:"classes/preview/class.tx_templavoila_preview_default.php";s:4:"e666";s:61:"classes/preview/class.tx_templavoila_preview_type_bullets.php";s:4:"8fa7";s:60:"classes/preview/class.tx_templavoila_preview_type_header.php";s:4:"f055";s:58:"classes/preview/class.tx_templavoila_preview_type_html.php";s:4:"87ec";s:59:"classes/preview/class.tx_templavoila_preview_type_image.php";s:4:"b983";s:58:"classes/preview/class.tx_templavoila_preview_type_list.php";s:4:"d220";s:58:"classes/preview/class.tx_templavoila_preview_type_menu.php";s:4:"e54b";s:64:"classes/preview/class.tx_templavoila_preview_type_multimedia.php";s:4:"5e11";s:58:"classes/preview/class.tx_templavoila_preview_type_null.php";s:4:"231f";s:58:"classes/preview/class.tx_templavoila_preview_type_text.php";s:4:"6412";s:61:"classes/preview/class.tx_templavoila_preview_type_textpic.php";s:4:"1bd5";s:61:"classes/preview/class.tx_templavoila_preview_type_uploads.php";s:4:"e60d";s:37:"cm1/class.tx_templavoila_cm1_ajax.php";s:4:"0f73";s:39:"cm1/class.tx_templavoila_cm1_dsedit.php";s:4:"1fa7";s:39:"cm1/class.tx_templavoila_cm1_etypes.php";s:4:"3fc5";s:13:"cm1/clear.gif";s:4:"cc11";s:15:"cm1/cm_icon.gif";s:4:"cb59";s:24:"cm1/cm_icon_activate.gif";s:4:"cb59";s:12:"cm1/conf.php";s:4:"2fbb";s:13:"cm1/index.php";s:4:"71d5";s:15:"cm1/item_at.gif";s:4:"8362";s:15:"cm1/item_co.gif";s:4:"3a12";s:15:"cm1/item_el.gif";s:4:"f8d6";s:15:"cm1/item_no.gif";s:4:"6af0";s:15:"cm1/item_sc.gif";s:4:"e42d";s:17:"cm1/locallang.xml";s:4:"280e";s:14:"cm1/styles.css";s:4:"26ab";s:13:"cm2/clear.gif";s:4:"cc11";s:15:"cm2/cm_icon.gif";s:4:"cb59";s:12:"cm2/conf.php";s:4:"5e88";s:13:"cm2/index.php";s:4:"7fd0";s:17:"cm2/locallang.xml";s:4:"f859";s:19:"cshimages/intro.png";s:4:"018d";s:12:"doc/TODO.txt";s:4:"6b9b";s:14:"doc/manual.sxw";s:4:"34cb";s:61:"func_wizards/class.tx_templavoila_referenceelementswizard.php";s:4:"3603";s:39:"mod1/class.tx_templavoila_mod1_ajax.php";s:4:"d758";s:44:"mod1/class.tx_templavoila_mod1_clipboard.php";s:4:"63d5";s:47:"mod1/class.tx_templavoila_mod1_localization.php";s:4:"f80e";s:45:"mod1/class.tx_templavoila_mod1_recordlist.php";s:4:"d093";s:42:"mod1/class.tx_templavoila_mod1_records.php";s:4:"da26";s:42:"mod1/class.tx_templavoila_mod1_sidebar.php";s:4:"4732";s:50:"mod1/class.tx_templavoila_mod1_specialdoktypes.php";s:4:"a457";s:42:"mod1/class.tx_templavoila_mod1_wizards.php";s:4:"505f";s:14:"mod1/clear.gif";s:4:"cc11";s:24:"mod1/clip_pasteafter.gif";s:4:"233a";s:25:"mod1/clip_pastesubref.gif";s:4:"cf77";s:17:"mod1/clip_ref.gif";s:4:"88b0";s:19:"mod1/clip_ref_h.gif";s:4:"2860";s:13:"mod1/conf.php";s:4:"7434";s:26:"mod1/db_new_content_el.php";s:4:"6252";s:20:"mod1/dragdrop-min.js";s:4:"89cf";s:16:"mod1/dragdrop.js";s:4:"8bbe";s:14:"mod1/index.php";s:4:"1d7c";s:18:"mod1/locallang.xml";s:4:"86ae";s:36:"mod1/locallang_db_new_content_el.xml";s:4:"d3a7";s:22:"mod1/locallang_mod.xml";s:4:"ff7e";s:22:"mod1/makelocalcopy.gif";s:4:"ff06";s:19:"mod1/moduleicon.gif";s:4:"4364";s:19:"mod1/pagemodule.css";s:4:"c419";s:23:"mod1/pagemodule_4.4.css";s:4:"159b";s:23:"mod1/pagemodule_4.5.css";s:4:"02fb";s:23:"mod1/pagemodule_4.6.css";s:4:"02fb";s:15:"mod1/unlink.png";s:4:"c57d";s:14:"mod2/clear.gif";s:4:"cc11";s:13:"mod2/conf.php";s:4:"0996";s:14:"mod2/index.php";s:4:"ee52";s:18:"mod2/locallang.xml";s:4:"ecf5";s:22:"mod2/locallang_mod.xml";s:4:"039e";s:26:"mod2/mapbody_animation.gif";s:4:"f085";s:26:"mod2/maphead_animation.gif";s:4:"2208";s:19:"mod2/moduleicon.gif";s:4:"0425";s:20:"mod2/new_tv_site.xml";s:4:"7c22";s:15:"mod2/styles.css";s:4:"2105";s:32:"pi1/class.tx_templavoila_pi1.php";s:4:"6a69";s:14:"res1/blank.gif";s:4:"9f3a";s:28:"res1/default_previewicon.gif";s:4:"edf8";s:13:"res1/join.gif";s:4:"86ea";s:19:"res1/joinbottom.gif";s:4:"3822";s:13:"res1/line.gif";s:4:"d3d7";s:31:"res1/language/template_conf.xml";s:4:"21c6";s:27:"resources/icons/html_go.png";s:4:"6081";s:36:"resources/templates/cm1_default.html";s:4:"529c";s:36:"resources/templates/cm2_default.html";s:4:"cb70";s:37:"resources/templates/mod1_default.html";s:4:"eed0";s:41:"resources/templates/mod1_new_content.html";s:4:"529c";s:38:"resources/templates/mod1_noaccess.html";s:4:"a839";s:37:"resources/templates/mod2_default.html";s:4:"fc34";s:37:"tests/tx_templavoila_api_testcase.php";s:4:"090a";s:41:"tests/fixtures/fce_2col_datastructure.xml";s:4:"4751";s:37:"tests/fixtures/fce_2col_template.html";s:4:"febf";s:42:"tests/fixtures/fce_2col_templateobject.dat";s:4:"18ee";s:43:"tests/fixtures/main_typoscript_template.txt";s:4:"d737";s:47:"tests/fixtures/page_datastructure_onecolumn.xml";s:4:"7236";s:48:"tests/fixtures/page_datastructure_twocolumns.xml";s:4:"7c4a";s:33:"tests/fixtures/page_template.html";s:4:"1f16";s:48:"tests/fixtures/page_templateobject_onecolumn.dat";s:4:"4c2a";s:49:"tests/fixtures/page_templateobject_twocolumns.dat";s:4:"83a6";}',
	'suggests' => array(
	),
);

?>