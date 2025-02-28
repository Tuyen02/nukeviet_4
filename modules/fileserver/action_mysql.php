<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_FILE_MODULES')) {
    exit('Stop!!!');
}

$sql_drop_module = [];

$sql_drop_module[] = 'DROP TABLE IF EXISTS ' . $db_config['prefix'] . '_' . $lang . '_' . $module_data . '_files';
$sql_drop_module[] = 'DROP TABLE IF EXISTS ' . $db_config['prefix'] . '_' . $lang . '_' . $module_data . '_permissions';
$sql_drop_module[] = 'DROP TABLE IF EXISTS ' . $db_config['prefix'] . '_' . $lang . '_' . $module_data . '_logs';

$sql_create_module = $sql_drop_module;

$sql_create_module[] = 'CREATE TABLE ' . $db_config['prefix'] . '_' . $lang . '_' . $module_data . '_files (
  file_id INT(11) NOT NULL AUTO_INCREMENT,
  file_name VARCHAR(255) NOT NULL,
  alias varchar(250) NOT NULL DEFAULT \'\',
  file_path VARCHAR(255) NOT NULL,
  file_size BIGINT(20) DEFAULT 0,
  uploaded_by INT(11) DEFAULT 0,
  created_at INT(11) NOT NULL DEFAULT 0,
  updated_at INT(11) NOT NULL DEFAULT 0,
  is_folder TINYINT(2) NOT NULL DEFAULT 1,
  status TINYINT(4) NOT NULL DEFAULT 1,
  lev TINYINT(4) NOT NULL DEFAULT 0,
  view INT(11) NOT NULL DEFAULT 0,
  share TINYINT(3) NOT NULL DEFAULT 0,
  compressed VARCHAR(50) NOT NULL DEFAULT 0,
  PRIMARY KEY (file_id),
   UNIQUE KEY alias (alias)
)ENGINE=MyISAM';

$sql_create_module[] = 'CREATE TABLE ' . $db_config['prefix'] . '_' . $lang . '_' . $module_data . '_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    file_id INT NOT NULL,
    p_group TINYINT(3) DEFAULT 1,
    p_other TINYINT(3) DEFAULT 1,
    updated_at INT(11) NOT NULL DEFAULT 0
)ENGINE=MyISAM';

$sql_create_module[] = 'CREATE TABLE ' . $db_config['prefix'] . '_' . $lang . '_' . $module_data . '_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    lev INT NOT NULL,
    total_files INT NOT NULL,
    total_folders INT NOT NULL,
    total_size INT NOT NULL,
    log_time INT(11) NOT NULL DEFAULT 0,
    UNIQUE KEY lev_unique (lev)
)ENGINE=MyISAM';

$sql_create_module[] = 'INSERT INTO ' . NV_CONFIG_GLOBALTABLE . " (lang, module, config_name, config_value) VALUES ('" . $lang . "', '" . $module_name . "', 'group_admin_fileserver', '1,2,3')";

