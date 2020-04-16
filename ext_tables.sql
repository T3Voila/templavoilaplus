#
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
