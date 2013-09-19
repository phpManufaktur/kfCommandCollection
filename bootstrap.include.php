<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

global $app;

// scan the /Locale directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/CommandCollection/Data/Locale');
// scan the /Locale/Custom directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/CommandCollection/Data/Locale/Custom');

// use $collection for all CommandCollection routes
$collection = $app['controllers_factory'];

// Setup, Upgrade and Uninstall for the CommandCollection
$admin->get('/collection/setup',
    'phpManufaktur\CommandCollection\Data\Setup\Setup::exec');
$admin->get('/collection/upgrade',
    'phpManufaktur\CommandCollection\Data\Setup\Upgrade::exec');
$admin->get('/collection/uninstall',
    'phpManufaktur\CommandCollection\Data\Setup\Uninstall::exec');

// Lorem Ipsum
$command->post('/loremipsum',
    'phpManufaktur\CommandCollection\Control\LoremIpsum\LoremIpsum::exec')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.loremipsum.json');

// Excel Read
$command->post('/excelread',
    'phpManufaktur\CommandCollection\Control\ExcelRead\ExcelRead::initFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.excelread.json');
$collection->get('/excelread/exec',
    'phpManufaktur\CommandCollection\Control\ExcelRead\ExcelRead::exec');

// Rating
$command->post('/rating',
    'phpManufaktur\CommandCollection\Control\Rating\Rating::initFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.rating.json');
$collection->get('/rating/exec',
    'phpManufaktur\CommandCollection\Control\Rating\Rating::exec');
$collection->post('/rating/response',
    'phpManufaktur\CommandCollection\Control\Rating\Response::exec');

// Comments
$command->post('/comments',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerInitFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.comments.json');
$collection->get('/comments/view',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerView');
$collection->post('/comments/submit',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerSubmit');
$collection->get('/comments/contact/confirm/{guid}',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerConfirmContact');
$collection->get('/comments/admin/confirm/{guid}',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerConfirmComment');
$collection->get('/comments/admin/reject/{guid}',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerRejectComment');
$collection->get('/comments/admin/lock/{guid}',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerLockContact');

// mount the controller factories
$app->mount('/collection', $collection);
