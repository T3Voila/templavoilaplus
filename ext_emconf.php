<?php

########################################################################
# Extension Manager/Repository config file for ext "templavoila".
#
# Auto generated 01-12-2012 15:45
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
	'version' => '2.0.0-dev',
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
	'lockType' => '',
	'author' => 'Tolleiv Nietsch',
	'author_email' => 'tolleiv.nietsch@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '6.2.0-6.2.99',
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
	'_md5_values_when_last_written' => 'a:156:{s:9:"ChangeLog";s:4:"0aef";s:20:"class.ext_update.php";s:4:"301c";s:31:"class.tx_templavoila_access.php";s:4:"cae9";s:28:"class.tx_templavoila_api.php";s:4:"56bc";s:28:"class.tx_templavoila_cm1.php";s:4:"54a3";s:35:"class.tx_templavoila_extdeveval.php";s:4:"6890";s:51:"class.tx_templavoila_handlestaticdatastructures.php";s:4:"2d7f";s:35:"class.tx_templavoila_htmlmarkup.php";s:4:"4dd8";s:30:"class.tx_templavoila_rules.php";s:4:"9940";s:32:"class.tx_templavoila_tcemain.php";s:4:"dd1b";s:33:"class.tx_templavoila_unusedce.php";s:4:"e2c4";s:31:"class.tx_templavoila_usedce.php";s:4:"0c8e";s:35:"class.tx_templavoila_xmlrelhndl.php";s:4:"cf91";s:16:"ext_autoload.php";s:4:"a196";s:21:"ext_conf_template.txt";s:4:"c630";s:12:"ext_icon.gif";s:4:"cb59";s:17:"ext_localconf.php";s:4:"1979";s:14:"ext_tables.php";s:4:"6c10";s:14:"ext_tables.sql";s:4:"4af9";s:24:"ext_typoscript_setup.txt";s:4:"9995";s:11:"icon_ds.gif";s:4:"f741";s:14:"icon_ds__x.gif";s:4:"cfd9";s:15:"icon_fce_ce.png";s:4:"2347";s:21:"icon_pagetemplate.gif";s:4:"c731";s:11:"icon_to.gif";s:4:"6410";s:14:"icon_to__x.gif";s:4:"84a1";s:13:"locallang.xml";s:4:"ec5b";s:20:"locallang_access.xml";s:4:"7f32";s:22:"locallang_csh_begr.xml";s:4:"e42c";s:20:"locallang_csh_ds.xml";s:4:"7b87";s:23:"locallang_csh_intro.xml";s:4:"063d";s:24:"locallang_csh_module.xml";s:4:"ff5d";s:23:"locallang_csh_pages.xml";s:4:"f028";s:20:"locallang_csh_pm.xml";s:4:"9644";s:20:"locallang_csh_to.xml";s:4:"d648";s:21:"locallang_csh_ttc.xml";s:4:"fb6f";s:16:"locallang_db.xml";s:4:"ef4c";s:7:"tca.php";s:4:"8b30";s:46:"classes/class.tx_templavoila_datastructure.php";s:4:"f89d";s:53:"classes/class.tx_templavoila_datastructure_dbbase.php";s:4:"55dd";s:57:"classes/class.tx_templavoila_datastructure_staticbase.php";s:4:"a989";s:56:"classes/class.tx_templavoila_datastructureRepository.php";s:4:"d1c8";s:36:"classes/class.tx_templavoila_div.php";s:4:"fc38";s:37:"classes/class.tx_templavoila_file.php";s:4:"6e25";s:38:"classes/class.tx_templavoila_icons.php";s:4:"88e8";s:38:"classes/class.tx_templavoila_label.php";s:4:"fdf9";s:41:"classes/class.tx_templavoila_template.php";s:4:"5684";s:51:"classes/class.tx_templavoila_templateRepository.php";s:4:"327a";s:56:"classes/preview/class.tx_templavoila_preview_default.php";s:4:"0621";s:61:"classes/preview/class.tx_templavoila_preview_type_bullets.php";s:4:"2cf3";s:60:"classes/preview/class.tx_templavoila_preview_type_header.php";s:4:"4ca1";s:58:"classes/preview/class.tx_templavoila_preview_type_html.php";s:4:"7d83";s:59:"classes/preview/class.tx_templavoila_preview_type_image.php";s:4:"6a64";s:58:"classes/preview/class.tx_templavoila_preview_type_list.php";s:4:"a1ee";s:59:"classes/preview/class.tx_templavoila_preview_type_media.php";s:4:"7971";s:58:"classes/preview/class.tx_templavoila_preview_type_menu.php";s:4:"6624";s:64:"classes/preview/class.tx_templavoila_preview_type_multimedia.php";s:4:"5bb9";s:58:"classes/preview/class.tx_templavoila_preview_type_null.php";s:4:"655f";s:58:"classes/preview/class.tx_templavoila_preview_type_text.php";s:4:"3814";s:61:"classes/preview/class.tx_templavoila_preview_type_textpic.php";s:4:"6a56";s:61:"classes/preview/class.tx_templavoila_preview_type_uploads.php";s:4:"0ed1";s:56:"classes/staticds/class.tx_templavoila_staticds_check.php";s:4:"65f1";s:56:"classes/staticds/class.tx_templavoila_staticds_tools.php";s:4:"f796";s:57:"classes/staticds/class.tx_templavoila_staticds_wizard.php";s:4:"be87";s:37:"cm1/class.tx_templavoila_cm1_ajax.php";s:4:"7301";s:39:"cm1/class.tx_templavoila_cm1_dsedit.php";s:4:"5233";s:39:"cm1/class.tx_templavoila_cm1_etypes.php";s:4:"3fc5";s:13:"cm1/clear.gif";s:4:"cc11";s:15:"cm1/cm_icon.gif";s:4:"cb59";s:24:"cm1/cm_icon_activate.gif";s:4:"cb59";s:12:"cm1/conf.php";s:4:"2fbb";s:13:"cm1/index.php";s:4:"891e";s:15:"cm1/item_at.gif";s:4:"8362";s:15:"cm1/item_co.gif";s:4:"3a12";s:15:"cm1/item_el.gif";s:4:"f8d6";s:15:"cm1/item_no.gif";s:4:"6af0";s:15:"cm1/item_sc.gif";s:4:"e42d";s:17:"cm1/locallang.xml";s:4:"c29a";s:14:"cm1/styles.css";s:4:"26ab";s:13:"cm2/clear.gif";s:4:"cc11";s:15:"cm2/cm_icon.gif";s:4:"cb59";s:12:"cm2/conf.php";s:4:"5e88";s:13:"cm2/index.php";s:4:"e37c";s:17:"cm2/locallang.xml";s:4:"f859";s:19:"cshimages/intro.png";s:4:"018d";s:14:"doc/manual.sxw";s:4:"52af";s:12:"doc/TODO.txt";s:4:"6b9b";s:61:"func_wizards/class.tx_templavoila_referenceelementswizard.php";s:4:"6bea";s:65:"func_wizards/class.tx_templavoila_renamefieldinpageflexwizard.php";s:4:"09a4";s:39:"mod1/class.tx_templavoila_mod1_ajax.php";s:4:"d758";s:44:"mod1/class.tx_templavoila_mod1_clipboard.php";s:4:"f7fa";s:47:"mod1/class.tx_templavoila_mod1_localization.php";s:4:"cdc8";s:45:"mod1/class.tx_templavoila_mod1_recordlist.php";s:4:"fcc0";s:42:"mod1/class.tx_templavoila_mod1_records.php";s:4:"b1e4";s:42:"mod1/class.tx_templavoila_mod1_sidebar.php";s:4:"6a08";s:50:"mod1/class.tx_templavoila_mod1_specialdoktypes.php";s:4:"a17f";s:42:"mod1/class.tx_templavoila_mod1_wizards.php";s:4:"2b55";s:14:"mod1/clear.gif";s:4:"cc11";s:24:"mod1/clip_pasteafter.gif";s:4:"233a";s:25:"mod1/clip_pastesubref.gif";s:4:"cf77";s:17:"mod1/clip_ref.gif";s:4:"88b0";s:19:"mod1/clip_ref_h.gif";s:4:"2860";s:13:"mod1/conf.php";s:4:"7434";s:26:"mod1/db_new_content_el.php";s:4:"6658";s:20:"mod1/dragdrop-min.js";s:4:"89cf";s:16:"mod1/dragdrop.js";s:4:"8bbe";s:14:"mod1/index.php";s:4:"81af";s:18:"mod1/locallang.xml";s:4:"4d78";s:36:"mod1/locallang_db_new_content_el.xml";s:4:"d3a7";s:22:"mod1/locallang_mod.xml";s:4:"ff7e";s:22:"mod1/makelocalcopy.gif";s:4:"ff06";s:19:"mod1/moduleicon.gif";s:4:"4364";s:15:"mod1/unlink.png";s:4:"c57d";s:14:"mod2/clear.gif";s:4:"cc11";s:13:"mod2/conf.php";s:4:"0996";s:14:"mod2/index.php";s:4:"25e5";s:18:"mod2/locallang.xml";s:4:"198b";s:22:"mod2/locallang_mod.xml";s:4:"039e";s:26:"mod2/mapbody_animation.gif";s:4:"f085";s:26:"mod2/maphead_animation.gif";s:4:"2208";s:19:"mod2/moduleicon.gif";s:4:"0425";s:20:"mod2/new_tv_site.xml";s:4:"186e";s:15:"mod2/styles.css";s:4:"2105";s:32:"pi1/class.tx_templavoila_pi1.php";s:4:"bb6f";s:14:"res1/blank.gif";s:4:"9f3a";s:28:"res1/default_previewicon.gif";s:4:"edf8";s:13:"res1/join.gif";s:4:"86ea";s:19:"res1/joinbottom.gif";s:4:"3822";s:13:"res1/line.gif";s:4:"d3d7";s:31:"res1/language/template_conf.xml";s:4:"21c6";s:27:"resources/icons/html_go.png";s:4:"6081";s:29:"resources/styles/mod1_4.4.css";s:4:"159b";s:29:"resources/styles/mod1_4.5.css";s:4:"02fb";s:29:"resources/styles/mod1_4.6.css";s:4:"2299";s:29:"resources/styles/mod1_4.7.css";s:4:"2299";s:33:"resources/styles/mod1_default.css";s:4:"a978";s:36:"resources/templates/cm1_default.html";s:4:"529c";s:36:"resources/templates/cm2_default.html";s:4:"cb70";s:33:"resources/templates/mod1_4.4.html";s:4:"fc99";s:33:"resources/templates/mod1_4.5.html";s:4:"fc99";s:33:"resources/templates/mod1_4.6.html";s:4:"fc99";s:33:"resources/templates/mod1_4.7.html";s:4:"fc99";s:37:"resources/templates/mod1_default.html";s:4:"9433";s:41:"resources/templates/mod1_new_content.html";s:4:"529c";s:38:"resources/templates/mod1_noaccess.html";s:4:"a839";s:37:"resources/templates/mod2_default.html";s:4:"fc34";s:37:"tests/tx_templavoila_api_testcase.php";s:4:"7832";s:41:"tests/fixtures/fce_2col_datastructure.xml";s:4:"4751";s:37:"tests/fixtures/fce_2col_template.html";s:4:"febf";s:42:"tests/fixtures/fce_2col_templateobject.dat";s:4:"18ee";s:43:"tests/fixtures/main_typoscript_template.txt";s:4:"d737";s:47:"tests/fixtures/page_datastructure_onecolumn.xml";s:4:"7236";s:48:"tests/fixtures/page_datastructure_twocolumns.xml";s:4:"7c4a";s:33:"tests/fixtures/page_template.html";s:4:"1f16";s:48:"tests/fixtures/page_templateobject_onecolumn.dat";s:4:"4c2a";s:49:"tests/fixtures/page_templateobject_twocolumns.dat";s:4:"83a6";}',
	'suggests' => array(
	),
);

?>