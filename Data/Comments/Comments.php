<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Comments;

use Silex\Application;

class Comments
{
    protected $app = null;
    protected static $table_name = null;

    public function __construct(Application $app)
    {
       $this->app = $app;
       self::$table_name = FRAMEWORK_TABLE_PREFIX.'collection_comments';
    }

    /**
     * Create the Comments table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $table_identifier = FRAMEWORK_TABLE_PREFIX.'collection_comments_identifier';
        $table_contact = FRAMEWORK_TABLE_PREFIX.'contact_contact';

        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier_id` INT(11) NOT NULL DEFAULT '-1',
        `comment_parent` INT(11) NOT NULL DEFAULT '0',
        `comment_title` VARCHAR(64) NOT NULL DEFAULT '',
        `comment_content` TEXT NOT NULL DEFAULT '',
        `comment_status` ENUM ('CONFIRMED', 'PENDING') NOT NULL DEFAULT 'PENDING',
        `comment_guid` VARCHAR(128) NOT NULL DEFAULT '',
        `comment_confirmation` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `contact_id` INT(11) NOT NULL DEFAULT '-1',
        `contact_nickname` VARCHAR(64) NOT NULL DEFAULT 'Anonymous',
        `contact_email` VARCHAR(128) NOT NULL DEFAULT '',
        `contact_homepage` VARCHAR(255) NOT NULL DEFAULT '',
        `comment_timestamp` TIMESTAMP,
        PRIMARY KEY (`comment_id`, `contact_id`),
        INDEX (`comment_parent`, `identifier_id`, `contact_id`),
        CONSTRAINT
            FOREIGN KEY (`identifier_id`)
            REFERENCES `$table_identifier` (`identifier_id`)
            ON DELETE CASCADE,
        CONSTRAINT
            FOREIGN KEY (`contact_id`)
            REFERENCES `$table_contact` (`contact_id`)
            ON DELETE CASCADE
        )
    COMMENT='The comments table'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'comments'", array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete table - switching check for foreign keys off before executing
     *
     * @throws \Exception
     */
    public function dropTable()
    {
        try {
            $table = self::$table_name;
            $SQL = <<<EOD
    SET foreign_key_checks = 0;
    DROP TABLE IF EXISTS `$table`;
    SET foreign_key_checks = 1;
EOD;
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Drop table 'comments'", array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function select($parent=0)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` a LEFT JOIN `".self::$table_name."` ".
                "b ON a.comment_id = b.comment_id WHERE a.comment_parent = '$parent' ORDER BY a.comment_timestamp ASC";
            $result = $this->app['db']->fetchAll($SQL);
            return $result;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function getThread()
    {
        $threads = $this->select(0);

        $result = array();
        foreach ($threads as $thread) {
            $sub = $this->select($thread['comment_id']);
            $result[] = array(
                'main' => $thread,
                'sub' => $sub
            );
        }
        return $result;
    }
}
