<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Adding the two plugins TypoScript:
t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_templavoila_pi1.php','_pi1','CType',1);
t3lib_extMgm::addPItoST43($_EXTKEY,'pi2/class.tx_templavoila_pi2.php','_pi2','CType',1);

	// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'].=',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to';

?>