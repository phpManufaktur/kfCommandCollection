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

use Silex\Application;
use phpManufaktur\CommandCollection\Data\Rating\RatingIdentifier;
use phpManufaktur\CommandCollection\Data\Rating\Rating;
use Carbon\Carbon;

class Response
{
    public function exec(Application $app)
    {
        if ((null === ($action = $app['request']->get('action', null))) || ($action != 'rating')) {
            $app['monolog']->addError("[Rating] The POST parameter 'action' is missing or contains a invalid value.", array(__METHOD__, __LINE__));
            return 'error';
        }

        if ((null === ($identifier_id = $app['request']->get('idBox', null))) || !is_numeric($identifier_id)) {
            $app['monolog']->addError("[Rating] The POST paramteter 'idBox' is missing or not a numeric value.", array(__METHOD__, __LINE__));
            exit();
        }

        if ((null === ($rating_value = $app['request']->get('rate', null))) || !is_numeric($rating_value)) {
            $app['monolog']->addError("[Rating] The POST paramteter 'rate' is missing or not a numeric value.", array(__METHOD__, __LINE__));
            return 'error';
        }

        $RatingIdentifier = new RatingIdentifier($app);
        if (null === ($identifier = $RatingIdentifier->select($identifier_id))) {
            $app['monolog']->addError("[Rating] The identifier ID $identifier_id does not exists!", array(__METHOD__, __LINE__));
            return 'error';
        }

        if ($identifier['identifier_mode'] != 'IP') {
            $app['monolog']->addError("[Rating] The Rating Response actual supports only the IP mode, Sorry!", array(__METHOD__, __LINE__));
            return 'error';
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $checksum = md5($ip);
        $app['monolog']->addInfo("IP: $ip - Checksum: $checksum");

        $RatingData = new Rating($app);
        if (false === ($rating = $RatingData->selectByChecksum($identifier_id, $checksum))) {
            // insert the record
            $data = array(
                'identifier_id' => $identifier_id,
                'rating_value' => $rating_value,
                'rating_checksum' => $checksum,
                'rating_status' => 'CONFIRMED',
                'rating_guid' => $app['utils']->createGUID(),
                'rating_confirmation' => date('Y-m-d H:i:s')
            );
            $rating_id = -1;
            $RatingData->insert($data, $rating_id);
            $app['monolog']->addInfo("[Rating] Insert the rating $rating_value for rating identifier ID $identifier_id.", array(__METHOD__, __LINE__));
        }
        else {
            $app['monolog']->addInfo("DT: {$rating[0]['rating_confirmation']}");
            $Carbon = new Carbon($rating[0]['rating_confirmation']);
            $diff_hours = $Carbon->diffInHours();
            if ($diff_hours <= 24) {
                $app['monolog']->addInfo("[Rating] Rejected voting, IP was last used before $diff_hours hours.", array(__METHOD__, __LINE__));
                return 'error';
            }
            else {
                $app['monolog']->addInfo("[Rating] IP was already used before $diff_hours, accept voting.", array(__METHOD__, __LINE__));
                $data = array(
                    'identifier_id' => $identifier_id,
                    'rating_value' => $rating_value,
                    'rating_checksum' => $checksum,
                    'rating_status' => 'CONFIRMED',
                    'rating_guid' => $app['utils']->createGUID(),
                    'rating_confirmation' => date('Y-m-d H:i:s')
                );
                $rating_id = -1;
                $RatingData->insert($data, $rating_id);
                $app['monolog']->addInfo("[Rating] Insert the rating $rating_value for rating identifier ID $identifier_id.", array(__METHOD__, __LINE__));
            }
        }
        return 'ok';
    }
}
