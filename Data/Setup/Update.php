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

class Update
{
    protected $app = null;

    /**
     * Release 0.16
     */
    protected function release_016()
    {
        // the helpfile graphics are moved to the 'PublicGraphics' repository
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/CommandCollection/Data/Help');
    }

    public function exec(Application $app)
    {
        $this->app = $app;

        // Release 0.16
        $this->release_016();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'CommandCollection'));
    }
}
