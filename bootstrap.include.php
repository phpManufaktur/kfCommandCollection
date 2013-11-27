<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// not needed - use only for syntax check!
global $app, $admin, $command, $collection;

// scan the /Locale directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/CommandCollection/Data/Locale');
// scan the /Locale/Custom directory and add all available languages
$app['utils']->addLanguageFiles(MANUFAKTUR_PATH.'/CommandCollection/Data/Locale/Custom');

// use $collection for all CommandCollection routes
$collection = $app['controllers_factory'];

// Setup, Update and Uninstall for the CommandCollection
$admin->get('/collection/setup',
    'phpManufaktur\CommandCollection\Data\Setup\Setup::exec');
$admin->get('/collection/update',
    'phpManufaktur\CommandCollection\Data\Setup\Update::exec');
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
$app->post('/search/command/excelread',
    'phpManufaktur\CommandCollection\Control\ExcelRead\Search::controllerSearch');

// Rating
$command->post('/rating',
    'phpManufaktur\CommandCollection\Control\Rating\Rating::initFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.rating.json');
$collection->get('/rating/exec',
    'phpManufaktur\CommandCollection\Control\Rating\Rating::controllerView');
$collection->post('/rating/response',
    'phpManufaktur\CommandCollection\Control\Rating\Response::exec');

// Import Comments from FeedbackModule
$admin->get('/comments/import/feedbackmodule',
    'phpManufaktur\CommandCollection\Control\Comments\Import\FeedbackModule::controllerStart');
$admin->get('/comments/import/feedbackmodule/execute',
    'phpManufaktur\CommandCollection\Control\Comments\Import\FeedbackModule::controllerExecute');

// Comments
$command->post('/comments',
    // kitCommand Comments
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerInitFrame')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.comments.json');

$collection->get('/comments/view',
    // default view for the comments
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerView');
$collection->get('/comments/reply/id/{comment_id}',
    // reply to an existing comment
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerReply');
$collection->post('/comments/submit',
    // new comment posted
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerSubmit');

$collection->get('/comments/contact/confirm/{guid}',
    // contact confirm the email address
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerContactConfirmContact');
$collection->get('/comments/comment/confirm/{guid}',
    // contact confirm the comment
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerContactConfirmComment');
$collection->get('/comments/unsubscribe/{guid}',
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerContactUnsubscribeThread');

$collection->get('/comments/admin/confirm/{guid}',
    // admin confirm the comment
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerAdminConfirmComment');
$collection->get('/comments/admin/reject/{guid}',
    // admin reject the comment
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerAdminRejectComment');
$collection->get('/comments/admin/lock/{guid}',
    // admin reject the comment and lock the contact
    'phpManufaktur\CommandCollection\Control\Comments\Comments::controllerAdminLockContact');

// RAL colors
$command->post('/ral',
    'phpManufaktur\CommandCollection\Control\RAL\RAL::controllerRAL')
    ->setOption('info', MANUFAKTUR_PATH.'/CommandCollection/command.ral.json');

// mount the controller factories
$app->mount('/collection', $collection);
