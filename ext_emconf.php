<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'TemplaVoila!',
	'description' => 'Point-and-click, popular and easy template engine for TYPO3. Public free support is provided only through TYPO3 mailing lists! Contact by e-mail for commercial support.',
	'category' => 'misc',
	'version' => '2.0.4',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_templavoila/',
	'clearcacheonload' => 1,
	'author' => 'Benjamin Mack',
	'author_email' => 'benni@typo3.org',
	'author_company' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.5.0-5.6.99',
			'typo3' => '7.0.0-7.9.99',
			'static_info_tables' => '',
		),
		'conflicts' => array(
			'kb_tv_clipboard' => '-0.1.0',
			'templavoila_cw' => '-0.1.0',
			'eu_tradvoila' => '-0.0.2',
			'me_templavoilalayout' => '',
			'me_templavoilalayout2' => '',
		),
		'suggests' => array(),
	),
	'_md5_values_when_last_written' => '',
);
