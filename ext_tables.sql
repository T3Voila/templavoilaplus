# TYPO3 CVS ID: $Id$

#
# Table structure for table 'tx_templavoila_tmplobj'
#
CREATE TABLE tx_templavoila_tmplobj (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title varchar(60) DEFAULT '' NOT NULL,
	datastructure varchar(100) DEFAULT '' NOT NULL,
	fileref tinytext NOT NULL,
	templatemapping mediumtext NOT NULL,
	previewicon tinytext NOT NULL,
	description tinytext NOT NULL,
	rendertype varchar(10) DEFAULT '' NOT NULL,
	sys_language_uid int(11) unsigned DEFAULT '0' NOT NULL,
	parent int(11) unsigned DEFAULT '0' NOT NULL,
	localprocessing text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_templavoila_datastructure'
#
CREATE TABLE tx_templavoila_datastructure (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title varchar(60) DEFAULT '' NOT NULL,
	dataprot mediumtext NOT NULL,
	scope tinyint(4) unsigned DEFAULT '0' NOT NULL,
	previewicon tinytext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_templavoila_ds varchar(100) DEFAULT '' NOT NULL,
	tx_templavoila_to int(11) DEFAULT '0' NOT NULL,
    tx_templavoila_flex mediumtext NOT NULL,
    tx_templavoila_pito int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_templavoila_ds varchar(100) DEFAULT '' NOT NULL,
	tx_templavoila_to int(11) DEFAULT '0' NOT NULL,
	tx_templavoila_next_ds varchar(100) DEFAULT '' NOT NULL,
	tx_templavoila_next_to int(11) DEFAULT '0' NOT NULL,
    tx_templavoila_flex mediumtext NOT NULL,

);

