<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);

	// Adding the two plugins TypoScript:
t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_templavoila_pi1.php','_pi1','CType',1);
$tvSetup = array('plugin.tx_templavoila_pi1.disableExplosivePreview = 1');
if (!$_EXTCONF['enable.']['renderFCEHeader']) {
	$tvSetup[] = 'tt_content.templavoila_pi1.10 >';
}

		//sectionIndex replacement
$tvSetup[] = 'tt_content.menu.20.3 = USER
	tt_content.menu.20.3.userFunc = tx_templavoila_pi1->tvSectionIndex
	tt_content.menu.20.3.select.where >
	tt_content.menu.20.3.indexField.data = register:tx_templavoila_pi1.current_field
';


t3lib_extMgm::addTypoScript($_EXTKEY,'setup',implode(PHP_EOL, $tvSetup), 43);

	// Use templavoila's wizard instead the default create new page wizard
t3lib_extMgm::addPageTSConfig('
    mod.web_list.newPageWiz.overrideWithExtension = templavoila
	mod.web_list.newContentWiz.overrideWithExtension = templavoila
	mod.web_txtemplavoilaM2.templatePath = templates,default/templates
	mod.web_txtemplavoilaM1.enableDeleteIconForLocalElements = 0
	mod.web_txtemplavoilaM1.enableContentAccessWarning = 1
	mod.web_txtemplavoilaM1.enableLocalizationLinkForFCEs = 0
	mod.web_txtemplavoilaM1.useLiveWorkspaceForReferenceListUpdates = 1
	mod.web_txtemplavoilaM1.adminOnlyPageStructureInheritance = fallback
');

 	// Use templavoila instead of the default page module
 t3lib_extMgm::addUserTSConfig('
 	options.overridePageModule = web_txtemplavoilaM1
	mod.web_txtemplavoilaM1.sideBarEnable = 1
 ');

	// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'].=',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to';

	// Register our classes at a the hooks:
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['templavoila'] = 'EXT:templavoila/class.tx_templavoila_tcemain.php:tx_templavoila_tcemain';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['templavoila'] = 'EXT:templavoila/class.tx_templavoila_tcemain.php:tx_templavoila_tcemain';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['templavoila'] = 'EXT:templavoila/class.tx_templavoila_tcemain.php:tx_templavoila_tcemain';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals']['templavoila'] = 'EXT:templavoila/class.tx_templavoila_access.php:&tx_templavoila_access->recordEditAccessInternals';

$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = array('EXT:templavoila/class.tx_templavoila_unusedce.php:tx_templavoila_unusedce');
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = array('EXT:templavoila/class.tx_templavoila_usedce.php:tx_templavoila_usedCE');


	// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['default']           = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_default.php:&tx_templavoila_preview_default';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['text']              = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_text.php:&tx_templavoila_preview_type_text';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['table']             = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_text.php:&tx_templavoila_preview_type_text';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['mailform']          = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_text.php:&tx_templavoila_preview_type_text';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['header']            = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_header.php:&tx_templavoila_preview_type_header';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['multimedia']        = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_multimedia.php:&tx_templavoila_preview_type_multimedia';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['media']             = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_media.php:&tx_templavoila_preview_type_media';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['uploads']           = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_uploads.php:&tx_templavoila_preview_type_uploads';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['textpic']           = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_textpic.php:&tx_templavoila_preview_type_textpic';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['splash']            = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_textpic.php:&tx_templavoila_preview_type_textpic';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['image']             = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_image.php:&tx_templavoila_preview_type_image';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['bullets']           = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_bullets.php:&tx_templavoila_preview_type_bullets';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['html']              = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_html.php:&tx_templavoila_preview_type_html';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['menu']              = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_menu.php:&tx_templavoila_preview_type_menu';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['list']              = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_list.php:&tx_templavoila_preview_type_list';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['search']            = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_null.php:&tx_templavoila_preview_type_null';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['login']             = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_null.php:&tx_templavoila_preview_type_null';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['shortcut']          = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_null.php:&tx_templavoila_preview_type_null';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['div']               = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_null.php:&tx_templavoila_preview_type_null';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['templavoila_pi1']   = 'EXT:templavoila/classes/preview/class.tx_templavoila_preview_type_null.php:&tx_templavoila_preview_type_null';

// configuration for new content element wizard
t3lib_extMgm::addPageTSConfig('
templavoila.wizards.newContentElement.wizardItems {
	common.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common
	common.elements {
		text {
			icon = gfx/c_wiz/regular_text.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_regularText_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_regularText_description
			tt_content_defValues {
				CType = text
			}
		}
		textpic {
			icon = gfx/c_wiz/text_image_right.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_textImage_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_textImage_description
			tt_content_defValues {
				CType = textpic
				imageorient = 17
			}
		}
		image {
			icon = gfx/c_wiz/images_only.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_imagesOnly_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_imagesOnly_description
			tt_content_defValues {
				CType = image
				imagecols = 2
			}
		}
		bullets {
			icon = gfx/c_wiz/bullet_list.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_bulletList_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_bulletList_description
			tt_content_defValues {
				CType = bullets
			}
		}
		table {
			icon = gfx/c_wiz/table.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_table_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_table_description
			tt_content_defValues {
				CType = table
			}
		}

	}
	common.show := addToList(text,textpic,image,bullets,table)

	special.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special
	special.elements {
		uploads {
			icon = gfx/c_wiz/filelinks.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_filelinks_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_filelinks_description
			tt_content_defValues {
				CType = uploads
			}
		}
		multimedia {
			icon = gfx/c_wiz/multimedia.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_multimedia_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_multimedia_description
			tt_content_defValues {
				CType = multimedia
			}
		}
		menu {
			icon = gfx/c_wiz/sitemap2.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_sitemap_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_sitemap_description
			tt_content_defValues {
				CType = menu
				menu_type = 2
			}
		}
		html {
			icon = gfx/c_wiz/html.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_plainHTML_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_plainHTML_description
			tt_content_defValues {
				CType = html
			}
		}
		div {
		 	icon = gfx/c_wiz/div.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_divider_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_divider_description
			tt_content_defValues {
				CType = div
			}
		}

	}
	special.show := addToList(uploads,multimedia,menu,html,div)

	forms.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms
	forms.elements {
		mailform {
			icon = gfx/c_wiz/mailform.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_mail_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_mail_description
			tt_content_defValues {
				CType = mailform
				bodytext (
# Example content:
Name: | *name = input,40 | Enter your name here
Email: | *email=input,40 |
Address: | address=textarea,40,5 |
Contact me: | tv=check | 1

|formtype_mail = submit | Send form!
|html_enabled=hidden | 1
|subject=hidden| This is the subject
				)
			}
		}
		search {
			icon = gfx/c_wiz/searchform.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_search_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_search_description
			tt_content_defValues {
				CType = search
			}
		}
		login {
			icon = gfx/c_wiz/login_form.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_login_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_login_description
			tt_content_defValues {
				CType = login
			}
		}

	}
	forms.show := addToList(mailform,search,login)

	fce.header = LLL:EXT:templavoila/mod1/locallang_db_new_content_el.xml:fce
	fce.elements  {

	}
	fce.show = *

	plugins.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins
	plugins.elements {
		general {
			icon = gfx/c_wiz/user_defined.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins_general_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins_general_description
			tt_content_defValues.CType = list
		}
	}
	plugins.show = *
}
# set to tabs for tab rendering
templavoila.wizards.newContentElement.renderMode =

');

if (t3lib_div::compat_version('4.3')) {
	t3lib_extMgm::addPageTSConfig('
templavoila.wizards.newContentElement.wizardItems.special.elements.media {
	icon = gfx/c_wiz/multimedia.gif
	title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_media_title
	description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_media_description
	tt_content_defValues {
		CType = media
	}
}
templavoila.wizards.newContentElement.wizardItems.special.show = uploads,media,menu,html,div
');
}

$TYPO3_CONF_VARS['BE']['AJAX']['tx_templavoila_mod1_ajax::moveRecord'] =
	'EXT:templavoila/mod1/class.tx_templavoila_mod1_ajax.php:tx_templavoila_mod1_ajax->moveRecord';

$TYPO3_CONF_VARS['BE']['AJAX']['tx_templavoila_cm1_ajax::getDisplayFileContent'] =
	'EXT:templavoila/cm1/class.tx_templavoila_cm1_ajax.php:tx_templavoila_cm1_ajax->getDisplayFileContent';

?>
