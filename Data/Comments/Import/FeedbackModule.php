<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Comments\Import;

use Silex\Application;

class FeedbackModule
{
    protected $app = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Check if the given $table exists
     *
     * @param string $table
     * @throws \Exception
     * @return boolean
     */
    protected function tableExists($table)
    {
        try {
            $query = $this->app['db']->query("SHOW TABLES LIKE '$table'");
            return (false !== ($row = $query->fetch())) ? true : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if a table for the FeedbackModule exists
     *
     * @return boolean
     */
    public function existsFeedbackModule()
    {
        return $this->tableExists(CMS_TABLE_PREFIX.'mod_feedback');
    }

    /**
     * Get all active records from the FeedbackModule
     *
     * @throws \Exception
     * @return array
     */
    public function getRecords()
    {
        try {
            $SQL = "SELECT * FROM `".CMS_TABLE_PREFIX."mod_feedback` WHERE `page_id` > '0' AND `active`='1'";
            $results = $this->app['db']->fetchAll($SQL);
            return $results;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Count all active records
     *
     * @throws \Exception
     * @return integer
     */
    public function countRecords()
    {
        try {
            $SQL = "SELECT COUNT(`id`) FROM `".CMS_TABLE_PREFIX."mod_feedback` WHERE `page_id` > '0' AND `active`='1'";
            return $this->app['db']->fetchColumn($SQL);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
