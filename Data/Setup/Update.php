<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Data\Setup;

use Silex\Application;
use phpManufaktur\CommandCollection\Data\RAL\RAL;
use phpManufaktur\CommandCollection\Data\Comments\CommentsPassed;

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

    /**
     * Release 0.27
     */
    protected function release_027()
    {
        // install the RAL table
        $RAL = new RAL($this->app);
        $RAL->createTable();
        $RAL->importCSV(MANUFAKTUR_PATH.'/CommandCollection/Data/RAL/csv/ral_standard.csv');
    }

    /**
     * Release 0.31
     */
    protected function release_031()
    {
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/CommandCollection/Template/Comments/white');
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/CommandCollection/Template/Comments/default/message.twig');
    }

    /**
     * Release 0.33
     */
    protected function release_033()
    {
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/CommandCollection/Control/Comments/Import/Dialog.php');
        $this->app['filesystem']->remove(MANUFAKTUR_PATH.'/CommandCollection/Template/Comments/default/import/message.twig');

        if (!$this->app['db.utils']->tableExists(FRAMEWORK_TABLE_PREFIX.'collection_comments_passed')) {
            // add the table for passed Identifiers
            $CommentsPassed = new CommentsPassed($this->app);
            $CommentsPassed->createTable();
        }
    }



    public function exec(Application $app)
    {
        $this->app = $app;

        // Release 0.16
        $this->release_016();
        // Release 0.27
        $this->release_027();
        // Release 0.31
        $this->release_031();
        // Release 0.33
        $this->release_033();

        return $app['translator']->trans('Successfull updated the extension %extension%.',
            array('%extension%' => 'CommandCollection'));
    }
}
