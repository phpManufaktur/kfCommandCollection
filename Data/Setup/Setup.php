<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Setup;

use Silex\Application;
use phpManufaktur\CommandCollection\Data\Rating\Rating;
use phpManufaktur\CommandCollection\Data\Rating\RatingIdentifier;
use phpManufaktur\CommandCollection\Data\Comments\Comments;

class Setup
{

    public function exec(Application $app)
    {
        try {
            $app['db']->beginTransaction();

            $RatingIdentifier = new RatingIdentifier($app);
            $RatingIdentifier->createTable();

            $Rating = new Rating($app);
            $Rating->createTable();

            $Comments = new Comments($app);
            $Comments->createTable();

            // COMMIT TRANSACTION
            $app['db']->commit();
        } catch (\Exception $e) {
            // ROLLBACK TRANSACTION
            $app['db']->rollback();
            throw $e;
        }
        return 'The Setup for the CommandCollection was successfull';
    }
}