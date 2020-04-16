#
<<<<<<< HEAD
# Table structure for table 'tx_templavoilaplus_tmplobj'
#
CREATE TABLE tx_templavoilaplus_tmplobj (
    uid int(11) DEFAULT '0' NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    t3ver_oid int(11) DEFAULT '0' NOT NULL,
    t3ver_id int(11) DEFAULT '0' NOT NULL,
    t3ver_wsid int(11) DEFAULT '0' NOT NULL,
    t3ver_label varchar(30) DEFAULT '' NOT NULL,
    t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_count int(11) DEFAULT '0' NOT NULL,
    t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
    t3ver_move_id int(11) DEFAULT '0' NOT NULL,
    t3_origuid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
    fileref_mtime int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    datastructure tinytext DEFAULT '' NOT NULL,
    fileref tinytext,
    templatemapping mediumblob,
    previewicon tinytext,
    description tinytext,
    rendertype varchar(32) DEFAULT '' NOT NULL,
    sys_language_uid int(11) unsigned DEFAULT '0' NOT NULL,
    parent int(11) unsigned DEFAULT '0' NOT NULL,
    rendertype_ref int(11) unsigned DEFAULT '0' NOT NULL,
    localprocessing text,
    fileref_md5 varchar(32) DEFAULT '' NOT NULL,
    belayout tinytext,

    PRIMARY KEY (uid),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid),
    KEY parent (pid),
    KEY rendering (parent,rendertype)
);

#
# Table structure for table 'tx_templavoilaplus_datastructure'
#
CREATE TABLE tx_templavoilaplus_datastructure (
    uid int(11) DEFAULT '0' NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    t3ver_oid int(11) DEFAULT '0' NOT NULL,
    t3ver_id int(11) DEFAULT '0' NOT NULL,
    t3ver_wsid int(11) DEFAULT '0' NOT NULL,
    t3ver_label varchar(30) DEFAULT '' NOT NULL,
    t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_count int(11) DEFAULT '0' NOT NULL,
    t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
    t3ver_move_id int(11) DEFAULT '0' NOT NULL,
    t3_origuid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    dataprot mediumtext,
    scope tinyint(4) unsigned DEFAULT '0' NOT NULL,
    previewicon tinytext,
    belayout tinytext,

    PRIMARY KEY (uid),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid),
    KEY parent (pid)
);

#
=======
>>>>>>> a256f3e... [TASK] Remove obsolate code for staticDataStructures as we use places now
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
    tx_templavoilaplus_map tinytext,
    tx_templavoilaplus_flex mediumtext,
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
    tx_templavoilaplus_map tinytext,
    tx_templavoilaplus_next_map tinytext,
    tx_templavoilaplus_flex mediumtext,
);

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
    tx_templavoilaplus_access text,
);
