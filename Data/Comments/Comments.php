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
        `comment_url` TEXT NOT NULL,
        `comment_headline` VARCHAR(64) NOT NULL DEFAULT '',
        `comment_content` TEXT NOT NULL DEFAULT '',
        `comment_status` ENUM ('CONFIRMED', 'PENDING', 'REJECTED') NOT NULL DEFAULT 'PENDING',
        `comment_guid` VARCHAR(128) NOT NULL DEFAULT '',
        `comment_guid_2` VARCHAR(128) NOT NULL DEFAULT '',
        `comment_confirmation` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `comment_update_info` TINYINT NOT NULL DEFAULT '0',
        `contact_id` INT(11) NOT NULL DEFAULT '-1',
        `contact_nick_name` VARCHAR(64) NOT NULL DEFAULT 'Anonymous',
        `contact_email` VARCHAR(128) NOT NULL DEFAULT '',
        `contact_url` TEXT NOT NULL,
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

    /**
     * Select a comment by the given PARENT
     *
     * @param integer $parent
     * @throws \Exception
     * @return array
     */
    public function selectParent($parent=0)
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

    /**
     * Get the complete comment thread
     *
     * @return array
     */
    public function getThread()
    {
        $threads = $this->selectParent(0);

        $result = array();
        foreach ($threads as $thread) {
            $sub = $this->selectParent($thread['comment_id']);
            $result[] = array(
                'main' => $thread,
                'sub' => $sub
            );
        }
        return $result;
    }

    /**
     * Insert a new comment record
     *
     * @param array $data
     * @param integer reference $comment_id
     * @throws \Exception
     */
    public function insert($data, &$comment_id=-1)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value) {
                if (($key == 'comment_id') || ($key == 'comment_timestamp')) continue;
                $insert[$key] = (is_string($value)) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $comment_id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the comment by the given comment ID
     *
     * @param integer $comment_id
     * @throws \Exception
     * @return boolean|multitype:unknown
     */
    public function select($comment_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `comment_id`='$comment_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            if (!isset($result['comment_id'])) {
                return false;
            }
            $comment = array();
            foreach ($result as $key => $value) {
                $comment[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
            }
            return $comment;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a comment by the given GUID
     *
     * @param string $guid
     * @throws \Exception
     * @return boolean|multitype:unknown
     */
    public function selectGUID($guid)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `comment_guid`='$guid'";
            $result = $this->app['db']->fetchAssoc($SQL);
            if (!isset($result['comment_id'])) {
                return false;
            }
            $comment = array();
            foreach ($result as $key => $value) {
                $comment[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
            }
            return $comment;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a comment by the given Administrator GUID
     *
     * @param string $guid
     * @throws \Exception
     * @return boolean|multitype:unknown
     */
    public function selectAdminGUID($guid)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `comment_guid_2`='$guid'";
            $result = $this->app['db']->fetchAssoc($SQL);
            if (!isset($result['comment_id'])) {
                return false;
            }
            $comment = array();
            foreach ($result as $key => $value) {
                $comment[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
            }
            return $comment;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update the comment record
     *
     * @param array $data
     * @param integer $comment_id
     * @throws \Exception
     */
    public function update($data, $comment_id)
    {
        try {
            $update = array();
            foreach ($data as $key => $value) {
                if (($key == 'comment_id') || ($key == 'comment_timestamp')) continue;
                $update[$key] = (is_string($value)) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['monolog']->addInfo('update', $update);
            $this->app['db']->update(self::$table_name, $update, array('comment_id' => $comment_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
