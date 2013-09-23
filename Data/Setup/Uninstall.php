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
use phpManufaktur\CommandCollection\Data\Comments\CommentsIdentifier;

class Uninstall
{

    public function exec(Application $app)
    {
        $RatingIdentifier = new RatingIdentifier($app);
        $RatingIdentifier->dropTable();

        $Rating = new Rating($app);
        $Rating->dropTable();

        $CommentsIdentifier = new CommentsIdentifier($app);
        $CommentsIdentifier->dropTable();

        $Comments = new Comments($app);
        $Comments->dropTable();

        return $app['translator']->trans('Successfull uninstalled the extension %extension%.',
            array('%extension%' => 'CommandCollection'));
    }
}
