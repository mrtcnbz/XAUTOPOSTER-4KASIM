<?php
namespace XAutoPoster\Models;

class Queue {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'xautoposter_queue';
    }
    
    public function createTable() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function addToQueue($postId) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_name,
            [
                'post_id' => $postId,
                'status' => 'pending'
            ],
            ['%d', '%s']
        );
    }
    
    public function getPendingPosts($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                AND attempts < 3 
                ORDER BY created_at ASC 
                LIMIT %d",
                $limit
            )
        );
    }
    
    public function markAsShared($postId) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'completed',
                'updated_at' => current_time('mysql')
            ],
            ['post_id' => $postId],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    public function incrementAttempts($postId) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} 
                SET attempts = attempts + 1 
                WHERE post_id = %d",
                $postId
            )
        );
    }
    
    public function cleanOldRecords($days = 30) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE status = 'completed' 
                AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
}