<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de/CommandCollection
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\LoremIpsum;

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;

include_once MANUFAKTUR_PATH.'/CommandCollection/Control/LoremIpsum/LoremPHPsum/lorem-phpsum.php';

class LoremIpsum extends Basic
{
    public function exec(Application $app)
    {
        // initialize the Basic class
        $this->initParameters($app);
        // get the command parameters
        $parameter = $this->getCommandParameters();

        $minWords = 100;
        $maxWords = null;
        $minParagraphs = (isset($parameter['paragraph'])) ? $parameter['paragraph'] : 3;
        $maxParagraphs = null;

        $args = array(
            'duplicateParagraphs' => 'false',
            'lorem' => 'true',
            'periods' => 'true',
            'caps' => 'true',
            'html' => (isset($parameter['html']) && ((strtolower($parameter['html']) == 'false') || ($parameter['html']) == '0')) ? 'false' : 'true',
            'nums' => 'false',
            'specialChars' => 'false',
            'vowelSense' => 'true',
            'doubleSpace' => 'false',
            'minCharsInWords' => 2,
            'maxCharsInWords' => 8,
            'minWordsInSentences' => 4,
            'maxWordsInSentences' => 12
        );

        return phpsum($minWords, $maxWords, $minParagraphs, $maxParagraphs, $args);
    }
}
