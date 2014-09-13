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

class CommentsIdentifier
{
    protected $app = null;
    protected static $table_name = null;

    public function __construct(Application $app)
    {
       $this->app = $app;
       self::$table_name = FRAMEWORK_TABLE_PREFIX.'collection_comments_identifier';
    }

    /**
     * Create the RatingIdentifier table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `identifier_id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier_type_name` VARCHAR(255) NOT NULL DEFAULT 'PAGE',
        `identifier_type_id` INT(11) NOT NULL DEFAULT '-1',
        `identifier_mode` ENUM('EMAIL') NOT NULL DEFAULT 'EMAIL',
        `identifier_publish` ENUM('IMMEDIATE','CONFIRM_EMAIL','CONFIRM_ADMIN','CONFIRM_EMAIL_ADMIN') NOT NULL DEFAULT 'CONFIRM_EMAIL',
        `identifier_contact_tag` VARCHAR(32) NOT NULL DEFAULT '',
        `identifier_comments_type` ENUM('HTML', 'TEXT') NOT NULL DEFAULT 'HTML',
        `identifier_timestamp` TIMESTAMP,
        PRIMARY KEY (`identifier_id`),
        INDEX (`identifier_type_name`, `identifier_type_id`)
        )
    COMMENT='The CommentsIdentifier table'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'collection_comments_identifier'", array(__METHOD__, __LINE__));
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
            $this->app['monolog']->addInfo("Drop table 'collection_comments_identifier'", array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get a default Rating record
     *
     * @return array
     */
    public function getDefaultRecord()
    {
        return array(
            'identifier_id' => -1,
            'identifier_type_name' => 'PAGE',
            'identifier_type_id' => -1,
            'identifier_mode' => 'IP',
            'identifier_timestamp' => '0000-00-00 00:00:00'
        );
    }

    /**
     * Select a comments identifier by the given type name and id
     *
     * @param string $type_name
     * @param integer $type_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectByTypeID($type_name, $type_id) {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `identifier_type_name`='$type_name' AND `identifier_type_id`='$type_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            return (isset($result['identifier_id'])) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Inser a new comments identifier
     *
     * @param array $data
     * @param integer $identifier_id
     * @throws \Exception
     */
    public function insert($data, &$identifier_id=-1)
    {
        try {
            $this->app['db']->insert(self::$table_name, $data);
            $identifier_id = $this->app['db']->lastInsertId();
            return $identifier_id;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a comments identifier by the given identifier ID
     *
     * @param integer $identifier_id
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function select($identifier_id)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `identifier_id`='$identifier_id'";
            $result = $this->app['db']->fetchAssoc($SQL);
            return (isset($result['identifier_id'])) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

}
