<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);

// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi1/class.tx_templavoila_pi1.php', '_pi1', 'CType', 1);
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'setup', implode(PHP_EOL, $tvSetup), 43);

// Use templavoila's wizard instead the default create new page wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
 	options.overridePageModule = web_txtemplavoilaM1
	mod.web_txtemplavoilaM1.sideBarEnable = 1
 ');

// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to';

// Register our classes at a the hooks:
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['templavoila'] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:\Extension\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['templavoila'] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:\Extension\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['templavoila'] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:\Extension\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals']['templavoila'] = 'EXT:templavoila/Classes/Service/UserFunc/Access.php:&\Extension\Templavoila\Service\UserFunc\Access->recordEditAccessInternals';

$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = array('EXT:templavoila/Classes/Comand/UnusedContentElementComand.php:\Extension\Templavoila\Comand\UnusedContentElementComand');
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = array('EXT:templavoila/Classes/Service/UserFunc/UsedContentElement.php:\Extension\Templavoila\Service\UserFunc\UsedContentElement');


// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['default'] = 'EXT:templavoila/Classes/Controller/Preview/DefaultController.php:&\Extension\Templavoila\Controller\Preview\DefaultController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['text'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&\Extension\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['table'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&\Extension\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['mailform'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&\Extension\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['header'] = 'EXT:templavoila/Classes/Controller/Preview/HeaderController.php:&\Extension\Templavoila\Controller\Preview\HeaderController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['multimedia'] = 'EXT:templavoila/Classes/Controller/Preview/MultimediaController.php:&\Extension\Templavoila\Controller\Preview\MultimediaController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['media'] = 'EXT:templavoila/Classes/Controller/Preview/MediaController.php:&\Extension\Templavoila\Controller\Preview\MediaController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['uploads'] = 'EXT:templavoila/Classes/Controller/Preview/UploadsController.php:&\Extension\Templavoila\Controller\Preview\UploadsController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['textpic'] = 'EXT:templavoila/Classes/Controller/Preview/TextpicController.php:&\Extension\Templavoila\Controller\Preview\TextpicController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['splash'] = 'EXT:templavoila/Classes/Controller/Preview/TextpicController.php:&\Extension\Templavoila\Controller\Preview\TextpicController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['image'] = 'EXT:templavoila/Classes/Controller/Preview/ImageController.php:&\Extension\Templavoila\Controller\Preview\ImageController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['bullets'] = 'EXT:templavoila/Classes/Controller/Preview/BulletsController.php:&\Extension\Templavoila\Controller\Preview\BulletsController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['html'] = 'EXT:templavoila/Classes/Controller/Preview/HtmlController.php:&\Extension\Templavoila\Controller\Preview\HtmlController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['menu'] = 'EXT:templavoila/Classes/Controller/Preview/MenuController.php:&\Extension\Templavoila\Controller\Preview\MenuController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['list'] = 'EXT:templavoila/Classes/Controller/Preview/ListController.php:&\Extension\Templavoila\Controller\Preview\ListController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['search'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['login'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['shortcut'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['div'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['templavoila_pi1'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';

// configuration for new content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
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

if (\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('4.3')) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
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

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_templavoila_mod1_ajax::moveRecord'] =
	'EXT:templavoila/mod1/class.tx_templavoila_mod1_ajax.php:tx_templavoila_mod1_ajax->moveRecord';

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_templavoila_cm1_ajax::getDisplayFileContent'] =
	'EXT:templavoila/cm1/class.tx_templavoila_cm1_ajax.php:tx_templavoila_cm1_ajax->getDisplayFileContent';
