<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\Comments;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use phpManufaktur\CommandCollection\Data\Comments\Comments as CommentsData;

class Comments extends Basic
{
    protected $CommentsData = null;

    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app, $parameter_id);

        $this->CommentsData = new CommentsData($app);
    }

    protected function getCommentForm($data=array())
    {

    }

    /**
     * Initialize the iFrame for ExcelRead
     * Return the Welcome page if no file is specified. otherwise show the Excel file
     *
     * @param Application $app
     */
    public function initFrame(Application $app)
    {
        // initialize only the Basic class, dont need additional initialisations
        parent::initParameters($app);

        // execute the Comments within the iFrame
        return $this->createIFrame('/collection/comments/view');
    }

    public function view(Application $app)
    {
        $this->initParameters($app);

        $result = $this->CommentsData->getThread();

        return $this->app['twig']->render($this->app['utils']->templateFile(
            '@phpManufaktur/CommandCollection/Template/Comments',
            "comments.twig",
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
            ));
    }

}
