<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Adding the two plugins TypoScript:
t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_templavoila_pi1.php','_pi1','CType',1);
t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_templavoila_pi2.php','_pi2','CType',1);

	// Use templavoila's wizard instead the default create new page wizard
t3lib_extMgm::addPageTSConfig('
    mod.web_list.newPageWiz.overrideWithExtension = templavoila
	mod.web_list.newContentWiz.overrideWithExtension = templavoila
');
	// Use templavoila instead of the default page module
t3lib_extMgm::addUserTSConfig('
	options.overridePageModule = web_txtemplavoilaM1
');

	// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'].=',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to';

?>