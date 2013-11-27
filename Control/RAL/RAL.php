<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\RAL;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use phpManufaktur\CommandCollection\Data\RAL\RAL as dataRAL;

class RAL
{
    protected $app = null;
    protected static $cms = null;
    protected static $parameter = null;
    protected $dataRAL = null;

    protected function promptMessage($message, $params=array())
    {
        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/RAL',
            'message.twig', self::$parameter['template']),
            array(
                'parameter' => self::$parameter,
                'message' => $this->app['translator']->trans($message, $params)
            ));
    }

    /**
     * Calculate the contrast color to the given one
     *
     * @param string $hexcolor
     * @return string
     * @see http://24ways.org/2010/calculating-color-contrast/
     */
    protected function getContrastYIQ($hexcolor)
    {
        $r = hexdec(substr($hexcolor,0,2));
        $g = hexdec(substr($hexcolor,2,2));
        $b = hexdec(substr($hexcolor,4,2));
        $yiq = (($r*299)+($g*587)+($b*114))/1000;
        return ($yiq <= 128) ? 'black' : 'white';
    }

    /**
     * Calculate the contrast color to the given one
     *
     * @param string $hexcolor
     * @return string
     * @see http://24ways.org/2010/calculating-color-contrast/
     */
    protected function getContrast50($hexcolor){
        return (hexdec($hexcolor) > 0xffffff/2) ? 'black':'white';
    }

    /**
     * Return the RAL color(s)
     *
     * @param array $color
     */
    protected function getColor($color)
    {
        $colors = array();
        if (!empty($color)) {
            if (strpos($color, ',')) {
                $items = explode(',', $color);
                foreach ($items as $item) {
                    $colors[] = trim($item);
                }
            }
            else {
                $colors[] = $color;
            }
        }
        if (false === ($items = $this->dataRAL->selectRALcolors($colors))) {
            return $this->promptMessage('No hits for the color(s) %colors%!', array('%colors%' => implode(',', $colors)));
        }

        if (!isset(self::$parameter['width']) ||
            (false === ($width = filter_var(self::$parameter['width'], FILTER_VALIDATE_INT)))) {
            $width = null;
        }

        if (!isset(self::$parameter['height']) ||
            (false === ($height = filter_var(self::$parameter['height'], FILTER_VALIDATE_INT)))) {
            $height = null;
        }

        $link = null;
        if (isset(self::$parameter['link'])) {
            $link = self::$parameter['link'];
            $link .= (strpos($link, '?')) ? '&ral=' : '?ral=';
        }

        $colors = array();
        foreach ($items as $item) {
            $colors[] = array(
                'number' => $item['ral'],
                'hex' => $item['hex'],
                'rgb' => str_replace('-', ',', $item['rgb']),
                'contrast' => $this->getContrast50($item['hex']),
                'name' => array(
                    'de' => $item['de'],
                    'en' => $item['en'],
                    'es' => $item['es'],
                    'fr' => $item['fr'],
                    'it' => $item['it'],
                    'nl' => $item['nl']
                ),
                'link' => (!is_null($link)) ? $link . $item['ral'] : null
            );
        }

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/RAL',
            'color.twig', self::$parameter['template']),
            array(
                'parameter' => self::$parameter,
                'colors' => $colors,
                'size' => array(
                    'width' => $width,
                    'height' => $height
                )
            ));
    }

    /**
     * Controller for the RAL kitCommand
     *
     * @param Application $app
     */
    public function controllerRAL(Application $app)
    {
        $this->app = $app;

        // get the CMS settings
        self::$cms = $app['request']->request->get('cms');

        // set the locale from the CMS
        $this->app['translator']->setLocale(self::$cms['locale']);

        // get the parameters
        self::$parameter = $app['request']->request->get('parameter');

        if (isset(self::$parameter['css']) &&
            ((strtolower(self::$parameter['css']) == 'false') || (self::$parameter['css'] == 0))) {
            self::$parameter['css'] = false;
        }
        else {
            self::$parameter['css'] = true;
        }

        if (!isset(self::$parameter['template'])) {
            self::$parameter['template'] = FRAMEWORK_TEMPLATE_PREFERRED;
        }

        $this->dataRAL = new dataRAL($app);

        if (isset(self::$parameter['colors']) || isset(self::$parameter['color'])) {
            $colors = isset(self::$parameter['color']) ? self::$parameter['color'] : self::$parameter['colors'];
            return $this->getColor($colors);
        }
        else {
            // don't know what to do - show help
            $subRequest = Request::create('/command/help?command=ral', 'POST');
            return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }
    }
}
