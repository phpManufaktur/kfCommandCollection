<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2012 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

global $app;

// scan the /Locale directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/CommandCollection/Data/Locale');
// scan the /Locale/Custom directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/CommandCollection/Data/Locale/Custom');


// Lorem Ipsum
$app->post('/command/loremipsum',
    'phpManufaktur\CommandCollection\Control\LoremIpsum\LoremIpsum::exec')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.loremipsum.json');

// Excel Read
$app->post('/command/excelread',
    'phpManufaktur\CommandCollection\Control\ExcelRead\ExcelRead::InitFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.excelread.json');
$app->get('/excelread/exec',
    'phpManufaktur\CommandCollection\Control\ExcelRead\ExcelRead::Exec');
