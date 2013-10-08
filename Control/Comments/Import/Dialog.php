<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/contact
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\Comments\Import;

use Silex\Application;

class Dialog {

    protected $app = null;
    protected static $message = '';

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app=null)
    {
        if (!is_null($app)) {
            $this->initialize($app);
        }
    }

    protected function initialize(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return the $message
     */
    public function getMessage()
    {
        return self::$message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message, $params=array())
    {
        self::$message .= $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Comments', 'message.twig'),
            array('message' => $this->app['translator']->trans($message, $params)));
    }

    /**
     * Check if a message is active
     *
     * @return boolean
     */
    public function isMessage()
    {
        return !empty(self::$message);
    }


}
