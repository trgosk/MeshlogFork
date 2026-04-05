<?php

class Migration_007 extends Migration {
    public function __construct() {
        parent::__construct(6, 7);
    }

    function migrate($pdo) {
        // add last_heard_at col
        
        $pdo->exec("ALTER TABLE `advertisements` ADD `hash_size` TINYINT NOT NULL DEFAULT '1' AFTER `flags`;");
        $pdo->exec("ALTER TABLE `direct_messages` ADD `hash_size` TINYINT NOT NULL DEFAULT '1' AFTER `message`;");
        $pdo->exec("ALTER TABLE `channel_messages` ADD `hash_size` TINYINT NOT NULL DEFAULT '1' AFTER `message`;");
        $pdo->exec("ALTER TABLE `raw_packets` ADD `hash_size` TINYINT NOT NULL DEFAULT '1' AFTER `path`;");
        $pdo->exec("ALTER TABLE `contacts` ADD `hash_size` TINYINT NOT NULL DEFAULT '1' AFTER `enabled`;");

        return array(
            'success' => true,
            'message' => ''
        );
    }
}

?>