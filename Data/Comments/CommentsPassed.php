<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2014 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Comments;

use Silex\Application;

class CommentsPassed
{
    protected $app = null;
    protected static $table_name = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = FRAMEWORK_TABLE_PREFIX.'collection_comments_passed';
    }

    /**
     * Create the Comments table
     *
     * @throws \Exception
     */
    public function createTable()
    {
        $table = self::$table_name;

        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier_type_id` INT(11) NOT NULL DEFAULT '-1',
        `identifier_type_name` VARCHAR(255) NOT NULL DEFAULT 'PAGE',
        `pass_to_identifier_id` INT(11) NOT NULL DEFAULT '-1',
        `timestamp` TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX (`identifier_type_id`, `identifier_type_name`, `pass_to_identifier_id`)
        )
    COMMENT='Reference passed identifier IDs'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table 'collection_comments_passed'", array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Drop the table
     *
     * @throws \Exception
     */
    public function dropTable()
    {
        $this->app['db.utils']->dropTable(self::$table_name);
    }

    /**
     * Select the passed to ID if possible
     *
     * @param string $identifier_type_name
     * @param integer $identifier_type_id
     * @throws \Exception
     * @return Ambigous <boolean, unknown>
     */
    public function selectPassTo($identifier_type_name, $identifier_type_id)
    {
        try {
            $SQL = "SELECT `pass_to_identifier_id` FROM `".self::$table_name."` WHERE ".
                "`identifier_type_name`='$identifier_type_name' AND `identifier_type_id`='$identifier_type_id'";
            $pass_to = $this->app['db']->fetchColumn($SQL);
            return ($pass_to > 0) ? $pass_to : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new PASS TO reference
     *
     * @param string $identifier_type_name
     * @param integer $identifier_type_id
     * @param integer $pass_to_identifier_id
     * @throws \Exception
     */
    public function insertPassTo($identifier_type_name, $identifier_type_id, $pass_to_identifier_id)
    {
        try {
            $this->app['db']->insert(self::$table_name, array(
                'identifier_type_name' => $identifier_type_name,
                'identifier_type_id' => $identifier_type_id,
                'pass_to_identifier_id' => $pass_to_identifier_id
            ));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete a PASS TO reference
     *
     * @param string $identifier_type_name
     * @param integer $identifier_type_id
     * @param integer $pass_to_identifier_id
     * @throws \Exception
     */
    public function deletePassTo($identifier_type_name, $identifier_type_id, $pass_to_identifier_id)
    {
        try {
            $this->app['db']->delete(self::$table_name, array(
                'identifier_type_name' => $identifier_type_name,
                'identifier_type_id' => $identifier_type_id,
                'pass_to_identifier_id' => $pass_to_identifier_id
            ));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

}
