<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\RAL;

use Silex\Application;

class RAL
{
    protected $app = null;
    protected static $table_name = null;

    public function __construct(Application $app)
    {
       $this->app = $app;
       self::$table_name = FRAMEWORK_TABLE_PREFIX.'collection_ral_colors';
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
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `ral` VARCHAR(16) NOT NULL DEFAULT '',
        `rgb` VARCHAR(16) NOT NULL DEFAULT '',
        `hex` VARCHAR(16) NOT NULL DEFAULT '',
        `de` VARCHAR(64) NOT NULL DEFAULT '',
        `en` VARCHAR(64) NOT NULL DEFAULT '',
        `fr` VARCHAR(64) NOT NULL DEFAULT '',
        `es` VARCHAR(64) NOT NULL DEFAULT '',
        `it` VARCHAR(64) NOT NULL DEFAULT '',
        `nl` VARCHAR(64) NOT NULL DEFAULT '',
        PRIMARY KEY (`id`)
        )
    COMMENT='RAL color table'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table '".self::$table_name."'", array(__METHOD__, __LINE__));
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
            $this->app['monolog']->addInfo("Drop table '".self::$table_name."'", array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Import RAL colors from the given CSV table
     *
     * @param string $csv_path
     * @throws \Exception
     */
    public function importCSV($csv_path)
    {
        try {
            if (!file_exists($csv_path)) {
                throw new \Exception("The CSV file $csv_path does not exists!");
            }

            $valid_fields = array('ral','rgb','hex','de','en','fr','es','it','nl');

            $row = 1;
            $header = array();

            if (false !== ($handle = fopen($csv_path, 'r'))) {
                while (false !== ($data = fgetcsv($handle, 1000, ','))) {
                    $num = count($data);
                    $insert = array();
                    for ($c=0; $c < $num; $c++) {
                        if ($row == 1) {
                            // expect the column names in the first line!
                            $header[$c] = strtolower(trim($data[$c]));
                        }
                        elseif (in_array($header[$c], $valid_fields)) {
                            $value = trim($data[$c]);
                            if ($header[$c] == 'ral') {
                                // remove the leading 'RAL' from the color name
                                $value = trim(str_ireplace('RAL', '', $value));
                            }
                            $insert[$header[$c]] = $value;
                        }
                    }
                    if (isset($insert['ral']) && !$this->existsRAL($insert['ral'])) {
                        // insert the color only if not exists
                        $this->insert($insert);
                    }
                    $row++;
                }
                fclose($handle);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new RAL color value
     *
     * @param array $data
     * @param integer reference $id
     * @throws \Exception
     * @return integer new inserted ID
     */
    public function insert($data, &$id=-1)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value) {
                if ($key == 'id') continue;
                $insert[$key] = (is_string($value)) ? $this->app['utils']->sanitizeText($value) : $value;
            }
            $this->app['db']->insert(self::$table_name, $insert);
            $id = $this->app['db']->lastInsertId();
            return $id;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Check if a RAL number already exists in the table
     *
     * @param string $ral_number
     * @throws \Exception
     * @return boolean
     */
    public function existsRAL($ral_number)
    {
        try {
            $SQL = "SELECT `ral` FROM `".self::$table_name."` WHERE `ral`='$ral_number'";
            $result = $this->app['db']->fetchColumn($SQL);
            return ($result == $ral_number);
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select a RAL record by the given RAL number
     *
     * @param integer $ral_number
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectRAL($ral_number)
    {
        try {
            $SQL = "SELECT * FROM `".self::$table_name."` WHERE `ral`='$ral_number'";
            $result = $this->app['db']->fetchAssoc($SQL);
            $data = array();
            foreach ($result as $key => $value) {
                $data[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
            }
            return (isset($data['ral'])) ? $data : false;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Select all given RAL $colors
     *
     * @param array $colors
     * @throws \Exception
     * @return Ambigous <boolean, array>
     */
    public function selectRALcolors($colors=array())
    {
        try {
            if (!is_array($colors)) {
                throw new \Exception('Parameter $colors must be an array!');
            }
            if (empty($colors)) {
                $SQL = "SELECT * FROM `".self::$table_name."` ORDER BY `ral` ASC";
            }
            else {
                $SQL = "SELECT * FROM `".self::$table_name."` WHERE ";
                $start = true;
                foreach ($colors as $color) {
                    if (!$start) {
                        $SQL .= ' OR ';
                    }
                    $SQL .= "`ral` LIKE '$color%'";
                    $start = false;
                }
                $SQL .= " ORDER BY `ral` ASC";
            }
            $results = $this->app['db']->fetchAll($SQL);
            $items = array();
            foreach ($results as $result) {
                $item = array();
                foreach ($result as $key => $value) {
                    $item[$key] = is_string($value) ? $this->app['utils']->unsanitizeText($value) : $value;
                }
                $items[] = $item;
            }
            return (empty($items)) ? false : $items;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }
}
