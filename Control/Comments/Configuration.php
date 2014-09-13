<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\Comments;

use Silex\Application;

class Configuration
{
    protected $app = null;
    private static $configuration = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->readConfiguration();
    }

    /**
     * Create the configuration file with default values
     *
     */
    protected function createConfiguration()
    {
        self::$configuration = array(
            'administrator' => array(
                'email' => SERVER_EMAIL_ADDRESS,
                'name' => SERVER_EMAIL_NAME
            ),
            'contact' => array(
                'confirmation' => array(
                    'double_opt_in' => true
                ),
                'information' => array(
                    'pending_confirmation' => true,
                    'published_comment' => true,
                    'rejected_comment' => true,
                    'locked_contact' => true
                )
            ),
            'comments' => array(
                'length' => array(
                    'minimum' => 5,
                    'maximum' => 1024
                ),
                'confirmation' => array(
                    'double_opt_in' => false,
                    'administrator' => true
                )
            ),
            'gravatar' => array(
                'enabled' => true,
                'size' => 40,
                'max_rating' => 'pg',
                'default_image' => 'mm',
                'use_ssl' => false,
                'comment' => array(
                    'main' => array(
                        'enabled' => true,
                        'size' => 40
                    ),
                    'reply' => array(
                        'enabled' => true,
                        'size' => 30
                    )
                )
            ),
            'rating' => array(
                'enabled' => true,
                'type' => 'small',
                'length' => 5,
                'step' => true,
                'rate_max' => 5,
                'show_rate_info' => false,
                'comment' => array(
                    'main' => array(
                        'enabled' => true
                    ),
                    'reply' => array(
                        'enabled' => true
                    )
                )
            )
        );
        file_put_contents(MANUFAKTUR_PATH.'/CommandCollection/config.comments.json',
            $this->app['utils']->JSONFormat(self::$configuration));
    }

    /**
     * Read the configuration file
     */
    protected function readConfiguration()
    {
       if (!file_exists(MANUFAKTUR_PATH.'/CommandCollection/config.comments.json')) {
           $this->createConfiguration();
       }
       self::$configuration = $this->app['utils']->readConfiguration(MANUFAKTUR_PATH.'/CommandCollection/config.comments.json');
    }

    /**
     * Get the configuration settings
     *
     * @return array configuration
     */
    public function getConfiguration()
    {
        return self::$configuration;
    }

    /**
     * Set the configuration with the given values
     *
     * @param array $configuration
     */
    public function setConfiguration($configuration)
    {
        self::$configuration = $configuration;
    }
}
