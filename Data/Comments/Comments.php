<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Comments;

use Silex\Application;
use phpManufaktur\CommandCollection\Data\Rating\Rating as RatingData;
use phpManufaktur\CommandCollection\Data\Rating\RatingIdentifier;
use Carbon\Carbon;

class Comments
{
    protected $app = null;
    protected static $table_name = null;
    protected $RatingIdentifier = null;
    protected $RatingData = null;

    public function __construct(Application $app)
    {
       $this->app = $app;
       self::$table_name = FRAMEWORK_TABLE_PREFIX.'collection_comments';
       $this->RatingData = new RatingData($app);
       $this->RatingIdentifier = new RatingIdentifier($app);
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
        `comment_content` TEXT NOT NULL,
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

    protected function getRatingData($comment_id)
    {
        if (null === ($identifier = $this->RatingIdentifier->selectByTypeID('COMMENTS', $comment_id))) {
            // create new record
            $data = array(
                'identifier_type_name' => 'COMMENTS',
                'identifier_type_id' => $comment_id,
                'identifier_mode' => 'IP'
            );
            $identifier_id = -1;
            $this->RatingIdentifier->insert($data, $identifier_id);
            $identifier = $this->RatingIdentifier->select($identifier_id);
        }

        $average = $this->RatingData->getAverage($identifier['identifier_id']);

        $is_disabled = false;

        $checksum = md5($_SERVER['REMOTE_ADDR']);
        if (false !== ($check = $this->RatingData->selectByChecksum($identifier['identifier_id'], $checksum))) {
            $Carbon = new Carbon($check[0]['rating_confirmation']);
            if ($Carbon->diffInHours() <= 24) {
                // this IP has rated within the last 24 hours, so we lock it.
                $is_disabled = true;
            }
        }

        return array(
            'identifier_id' => $identifier['identifier_id'],
            'is_disabled' => $is_disabled,
            'average' => isset($average['average']) ? $average['average'] : 0,
            'count' => isset($average['count']) ? $average['count'] : 0
        );
    }

    /**
     * Select the comments for the thread with the given identifier ID.
     * By default select comments with no parent (0)
     *
     * @param integer $identifier_id
     * @param integer $parent
     * @throws \Exception
     * @return array comments
     */
    public function selectComments($identifier_id, $parent=0, $gravatar=null, $rating=true)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` a LEFT JOIN `".self::$table_name."` ".
                "b ON a.comment_id = b.comment_id WHERE a.comment_parent = '$parent'".
                "AND a.identifier_id='$identifier_id' AND a.comment_status='CONFIRMED' ORDER BY a.comment_timestamp ASC";
            $results = $this->app['db']->fetchAll($SQL);
            $comments = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    $item[$key] = (is_string($value)) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                // get the URL for a Gravatar fitting to the email address
                $item['gravatar'] = (!is_null($gravatar)) ? $gravatar->buildGravatarURL($item['contact_email']) : null;

                // rating is enabled, get the data
                $item['rating'] = ($rating) ? $this->getRatingData($item['comment_id']) : null;

                $comments[] = $item;
            }
            return $comments;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get the complete comment thread with all levels for the given identifier ID
     *
     * @return array comments
     */
    public function getThread($identifier_id, $gravatar=null, $rating=true)
    {
        $threads = $this->selectComments($identifier_id, 0, $gravatar, $rating);

        $result = array();
        foreach ($threads as $thread) {
            $sub = $this->selectComments($identifier_id, $thread['comment_id'], $gravatar, $rating);
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
            return $comment_id;
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
     * Check if the given Comment ID exists
     *
     * @param integer $comment_id
     * @throws \Exception
     * @return boolean
     */
    public function existsCommentID($comment_id)
    {
        try {
            $SQL = "SELECT `comment_id` FROM `".self::$table_name."` WHERE `comment_id`=$comment_id";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result === $comment_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Remove, physically delete the given Comment ID
     *
     * @param integer $comment_id
     * @throws \Exception
     */
    public function removeCommentID($comment_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array('comment_id' => $comment_id));
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
            $this->app['db']->update(self::$table_name, $update, array('comment_id' => $comment_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select the subscribers of the thread with the given identifier
     *
     * @param integer $identifier_id
     * @throws \Exception
     */
    public function selectSubscribers($identifier_id)
    {
        try {
            $SQL = "SELECT DISTINCT `contact_email`, `contact_id`, `contact_nick_name`, `comment_guid` FROM `".
                self::$table_name."` WHERE `identifier_id`='$identifier_id' AND `comment_status`='CONFIRMED' AND `comment_update_info`='1' GROUP BY `contact_email`";
            return $this->app['db']->fetchAll($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Unsubscribe the given contact ID from the thread
     *
     * @param integer $identifier_id
     * @param integer $contact_id
     * @throws \Exception
     */
    public function unsubscribeContactID($identifier_id, $contact_id)
    {
        try {
            $this->app['db']->update(self::$table_name, array('comment_update_info' => '0'),
                array('identifier_id' => $identifier_id, 'contact_id' => $contact_id));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check with identifier ID, contact ID and date of comment confirmation if a comment already exists
     *
     * @param integer $identifier_id
     * @param integer $contact_id
     * @param date $comment_confirmation
     * @throws \Exception
     * @return boolean
     */
    public function commentAlreadyExists($identifier_id, $contact_id, $comment_confirmation)
    {
        try {
            $SQL = "SELECT `contact_id` FROM `".self::$table_name."` WHERE `identifier_id`='$identifier_id' ".
                "AND `contact_id`='$contact_id' AND `comment_confirmation`='$comment_confirmation'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $contact_id);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count the CONFIRMED comments for the given identifier type_name and type_id
     *
     * @param string $type_name
     * @param integer $type_id
     * @throws \Exception
     * @return integer
     */
    public function countComments($type_name, $type_id)
    {
        try {
            $comments_tbl = self::$table_name;
            $comments_idf = FRAMEWORK_TABLE_PREFIX.'collection_comments_identifier';
            $SQL = "SELECT COUNT(comment_id) FROM `$comments_tbl` ".
                "LEFT JOIN `$comments_idf` ON `$comments_idf`.`identifier_id`=`$comments_tbl`.`identifier_id` ".
                "WHERE `identifier_type_name`='$type_name' AND `identifier_type_id`='$type_id' AND ".
                "`comment_status`='CONFIRMED'";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
