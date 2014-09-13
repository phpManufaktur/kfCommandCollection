<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Rating;

use Silex\Application;

class Rating
{
    protected $app = null;
    protected static $table_name = null;

    public function __construct(Application $app)
    {
       $this->app = $app;
       self::$table_name = FRAMEWORK_TABLE_PREFIX.'collection_rating';
    }

    /**
     * Create the Rating table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;
        $table_identifier = FRAMEWORK_TABLE_PREFIX.'collection_rating_identifier';

        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `rating_id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier_id` INT(11) NOT NULL DEFAULT '-1',
        `rating_value` INT(11) NOT NULL DEFAULT '0',
        `rating_checksum` VARCHAR(128) NOT NULL DEFAULT '',
        `rating_status` ENUM('CONFIRMED', 'PENDING') NOT NULL DEFAULT 'PENDING',
        `rating_guid` VARCHAR(128) NOT NULL DEFAULT '',
        `rating_confirmation` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        `rating_timestamp` TIMESTAMP,
        PRIMARY KEY (`rating_id`),
        INDEX (`identifier_id`, `rating_checksum`),
        UNIQUE (`rating_guid`),
        CONSTRAINT
            FOREIGN KEY (`identifier_id` )
            REFERENCES `$table_identifier` (`identifier_id` )
            ON DELETE CASCADE
        )
    COMMENT='The contact address table'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'collection_rating'", array(__METHOD__, __LINE__));
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
            $this->app['monolog']->addInfo("Drop table 'collection_rating'", array(__METHOD__, __LINE__));
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
            'rating_id' => -1,
            'identifier_id' => -1,
            'rating_value' => 0,
            'rating_checksum' => '',
            'rating_status' => 'PENDING',
            'rating_guid' => $this->app['utils']->createGUID(),
            'rating_confirmation' => '0000-00-00 00:00:00',
            'rating_timestamp' => '0000-00-00 00:00:00'
        );
    }

    public function getAverage($identifier_id, $status='CONFIRMED')
    {
        try {
            $SQL = "SELECT AVG(`rating_value`) AS average, COUNT(`rating_value`) as count FROM `".self::$table_name.
                "` WHERE (`identifier_id`='$identifier_id' AND `rating_status`='$status')";
            return $this->app['db']->fetchAssoc($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function selectByChecksum($identifier_id, $rating_checksum)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE (`identifier_id`='$identifier_id' AND `rating_checksum`='$rating_checksum') ORDER BY `rating_confirmation` DESC";
            $result = $this->app['db']->fetchAll($SQL);
            return (isset($result[0]['identifier_id'])) ? $result : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    public function insert($data, &$rating_id)
    {
        try {
            $this->app['db']->insert(self::$table_name, $data);
            $rating_id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
