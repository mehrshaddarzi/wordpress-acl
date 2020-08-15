<?php

namespace WordPress_ACL;

class install
{

    /*
     * install Plugin Method
     */
    public static function run_install()
    {
        global $wpdb;

        // Create Base Table in mysql
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create User code [For login code Auth]
        $table_name = $wpdb->prefix . 'users_code';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `ID` BIGINT(48) NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(120) NOT NULL,
            `mobile` VARCHAR(100) NOT NULL,
            `code` INT(20) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `user_id` BIGINT(48) NOT NULL DEFAULT '0',
            PRIMARY KEY (ID)
            ) {$charset_collate};";
        dbDelta($sql);
    }
}
