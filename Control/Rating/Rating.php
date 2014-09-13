<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\Rating;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use phpManufaktur\CommandCollection\Data\Rating\Rating as RatingData;
use phpManufaktur\CommandCollection\Data\Rating\RatingIdentifier;
use Carbon\Carbon;
use phpManufaktur\flexContent\Control\Command\Tools as flexContentTools;
use phpManufaktur\flexContent\Data\Content\Content as flexContentData;


class Rating extends Basic
{
    protected $RatingData = null;
    protected $RatingIdentifier = null;
    protected static $allowed_modes = array('IP', 'EMAIL');
    protected static $allowed_sizes = array('big', 'small');
    protected static $stars = null;
    protected static $maximum_rate = null;
    protected static $step = null;

    /**
     * (non-PHPdoc)
     *
     * @see \phpManufaktur\Basic\Control\kitCommand\Basic::initParameters()
     */
    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app, $parameter_id);

        $this->RatingData = new RatingData($app);
        $this->RatingIdentifier = new RatingIdentifier($app);
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

        // execute Rating within the iFrame
        return $this->createIFrame('/collection/rating/exec');
    }

    /**
     * Controller for the kitCommand Rating
     *
     * @param Application $app
     * @throws \Exception
     * @return string Rating Stars
     */
    public function controllerView(Application $app)
    {
        $this->initParameters($app);

        $param = $this->getCommandParameters();

        if (!isset($param['type']) || (strtoupper($param['type']) == 'PAGE')) {
            // use the PAGE_ID as identifier
            $type = 'PAGE';
            $id = $this->getCMSpageID();
        }
        elseif (isset($param['type']) && !empty($param['type']) && isset($param['id']) && is_numeric($param['id'])) {
            // use the given name and id
            $type = strtoupper($param['type']);
            $id = (int) $param['id'];
        }
        else {
            // logical problem
            throw new \Exception("Set parameter `type` and `id`, where `type` describe your application (string) and `id` is an identifier (integer).");
        }

        if (null === ($identifier = $this->RatingIdentifier->selectByTypeID($type, $id))) {
            // create new record
            $data = array(
                'identifier_type_name' => $type,
                'identifier_type_id' => $id,
                'identifier_mode' => (isset($param['mode']) && (in_array(strtoupper($param['mode']), self::$allowed_modes))) ? strtoupper($param['mode']) : 'IP'
            );
            $identifier_id = -1;
            $this->RatingIdentifier->insert($data, $identifier_id);
            $identifier = $this->RatingIdentifier->select($identifier_id);
        }

        $average = $this->RatingData->getAverage($identifier['identifier_id']);

        $is_disabled = false;

        $checksum = md5($_SERVER['REMOTE_ADDR']);
        if (false !== ($check = $this->RatingData->selectByChecksum($identifier['identifier_id'], $checksum))) {
            $Carbon = new Carbon($check[0]['rating_confirmation']);
            if ($Carbon->diffInHours() <= 24) {
                // this IP has rated within the last 24 hours, so we lock it.
                $is_disabled = true;
            }
        }

        self::$stars = (isset($param['stars'])) ? (int) $param['stars'] : 5;
        self::$maximum_rate = (isset($param['maximum_rate'])) ? (int) $param['maximum_rate'] : self::$stars;
        self::$step = (isset($param['step']) && (($param['step'] == 0) || (strtolower($param['step']) == 'false'))) ? false : true;

        // no addition to the iFrame!
        $this->setFrameAdd(0);

        // never track the rating iFrame!
        $this->disableTracking();

        // we want to grant the "fold in" if the frame url is executed outside the CMS
        if (!isset($param['url'])) {
            $url = $this->getCMSpageURL();
            if (empty($url) && !is_null($this->app['session']->get('FLEXCONTENT_EDIT_CONTENT_ID')) &&
                !is_null($this->app['session']->get('FLEXCONTENT_EDIT_CONTENT_LANGUAGE'))) {
                    // this is a flexContent article!
                    $fcTools = new flexContentTools($this->app);
                    $base_url = $fcTools->getPermalinkBaseURL($this->app['session']->get('FLEXCONTENT_EDIT_CONTENT_LANGUAGE'));
                    $fcData = new flexContentData($this->app);
                    $data = $fcData->selectPermaLinkByContentID($this->app['session']->get('FLEXCONTENT_EDIT_CONTENT_ID'));
                    if (isset($data['permalink'])) {
                        $url = $base_url.'/'.$data['permalink'];
                    }
                    $this->setCMSpageURL($url);
                }
                $param['url'] = $url;
                $this->createParameterID($param);
        }
        else {
            $this->setCMSpageURL($param['url']);
        }
        $this->setRedirectActive(true);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Rating',
            "rating.twig",
            $this->getPreferredTemplateStyle()),
            array(
                'basic' => $this->getBasicSettings(),
                'average' => isset($average['average']) ? $average['average'] : 0,
                'count' => isset($average['count']) ? $average['count'] : 0,
                'identifier_id' => $identifier['identifier_id'],
                'is_disabled' => $is_disabled,
                'guid' => $app['utils']->createGUID(),
                'size' => (isset($param['size']) && in_array(strtolower($param['size']), self::$allowed_sizes)) ? $param['size'] : 'big',
                'stars' => self::$stars,
                'maximum_rate' => self::$maximum_rate,
                'step' => self::$step
            ));
    }
}
