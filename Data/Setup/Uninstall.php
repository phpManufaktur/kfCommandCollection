<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2012 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Setup;

use Silex\Application;
use phpManufaktur\CommandCollection\Data\Rating\Rating;
use phpManufaktur\CommandCollection\Data\Rating\RatingIdentifier;

class Uninstall
{

    public function exec(Application $app)
    {
        $RatingIdentifier = new RatingIdentifier($app);
        $RatingIdentifier->dropTable();

        $Rating = new Rating($app);
        $Rating->dropTable();

        return 'The Uninstall for the CommandCollection was successfull';
    }
}
