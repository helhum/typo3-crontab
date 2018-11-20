#
# Table structure for table 'tx_crontab_scheduled'
#
CREATE TABLE tx_crontab_scheduled (
	identifier VARCHAR(255) NOT NULL,
	next_execution int(11) unsigned DEFAULT '0' NOT NULL,
	UNIQUE `identifier_key`(`identifier`)
);

#
# Table structure for table 'tx_crontab_running'
#
CREATE TABLE tx_crontab_running (
	identifier VARCHAR(255) NOT NULL,
	process_id int(11) unsigned DEFAULT '0' NOT NULL,
	UNIQUE `identifier_process`(`identifier`,`process_id`)
);
