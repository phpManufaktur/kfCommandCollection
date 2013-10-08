<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\CommandCollection\Control\Comments\Import;

use Silex\Application;
use phpManufaktur\CommandCollection\Data\Comments\Import\FeedbackModule as FeedbackModuleData;
use phpManufaktur\Contact\Control\Contact;
use phpManufaktur\CommandCollection\Data\Comments\Comments;
use phpManufaktur\CommandCollection\Data\Comments\CommentsIdentifier;
use phpManufaktur\Basic\Data\CMS\Page;

class FeedbackModule extends Dialog
{
    protected static $import_is_possible = false;
    protected $FeedbackModuleData = null;
    protected $Contact = null;
    protected $Comments = null;
    protected $CommentsIdentifier = null;
    protected $Page = null;

    /**
     * (non-PHPdoc)
     * @see \phpManufaktur\CommandCollection\Control\Comments\Import\Dialog::initialize()
     */
    protected function initialize(Application $app)
    {
        parent::initialize($app);

        $this->FeedbackModuleData = new FeedbackModuleData($app);
        self::$import_is_possible = $this->FeedbackModuleData->existsFeedbackModule();

        $this->Contact = new Contact($app);
        $this->Comments = new Comments($app);
        $this->CommentsIdentifier = new CommentsIdentifier($app);
        $this->Page = new Page($app);
    }

    /**
     * Controller for the start dialog of the import
     *
     * @param Application $app
     */
    public function controllerStart(Application $app)
    {
        $this->initialize($app);

        $count = 0;
        if (!self::$import_is_possible) {
            // no import possible
            $this->setMessage('There exists no FeedbackModule table for import!');
        }
        else {
            $count = $this->FeedbackModuleData->countRecords();
        }

        return $this->app['twig']->render($this->app['utils']->getTemplateFile('@phpManufaktur/CommandCollection/Template/Comments', 'import/start.feedbackmodule.twig'),
            array(
                'message' => $this->getMessage(),
                'import_is_possible' => self::$import_is_possible,
                'count' => $count
            ));
    }

    /**
     * Add a new contact record
     *
     * @param string $email
     * @param string $nickname
     * @param integer $timestamp
     * @param integer reference $contact_id
     * @return boolean
     */
    protected function addContact($email, $nickname, $timestamp, &$contact_id=-1)
    {
        $data = array(
            'contact' => array(
                'contact_id' => -1,
                'contact_type' => 'PERSON',
                'contact_status' => 'ACTIVE',
                'contact_name' => $email,
                'contact_login' => $email,
                'contact_since' => date('Y-m-d H:i:s', $timestamp)
            ),
            'tag' => array(
                array(
                    'contact_id' => -1,
                    'tag_name' => 'COMMENTS'
                )
            ),
            'communication' => array(
                array(
                    'contact_id' => -1,
                    'communication_id' => -1,
                    'communication_type' => 'EMAIL',
                    'communication_usage' => 'PRIMARY',
                    'communication_value' => $email
                )
            ),
            'person' => array(
                array(
                    'contact_id' => -1,
                    'person_nick_name' => $nickname
                )
            )
        );
        // insert the contact data
        $contact_id = -1;
        if (!$this->Contact->insert($data, $contact_id)) {
            self::$message = $this->Contact->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Execute the import from the FeedbackModule
     *
     * @param Application $app
     * @throws \Exception
     */
    public function controllerExecute(Application $app)
    {
        $this->initialize($app);

        if (!self::$import_is_possible) {
            // no import possible
            throw new \Exception('There exists no FeedbackModule table for import!');
        }

        $imported_feedback = 0;
        $imported_comment = 0;

        $feedbacks = $this->FeedbackModuleData->getRecords();
        foreach ($feedbacks as $feedback) {
            // loop through the feedbacks
            if (false === ($contact_id = $this->Contact->existsLogin($feedback['email']))) {
                // add a new contact
                if (false === ($this->addContact(
                    $feedback['email'],
                    $feedback['name'],
                    $feedback['timestamp'],
                    $contact_id))) {
                    // on error break the loop
                    break;
                }
            }
            elseif (!$this->Contact->issetContactTag('COMMENTS', $contact_id)) {
                // set the COMMENTS tag for the already existing contact
                $this->Contact->setContactTag('COMMENTS', $contact_id);
            }

            // get the comments identifier for the page ID
            $identifier_id = -1;
            if (false === ($identifier = $this->CommentsIdentifier->selectByTypeID('PAGE', $feedback['page_id']))) {
                $this->CommentsIdentifier->insert(array(
                    'identifier_type_name' => 'PAGE',
                    'identifier_type_id' => $feedback['page_id'],
                    'identifier_mode' => 'EMAIL',
                    'identifier_publish' => 'CONFIRM_ADMIN',
                    'identifier_contact_tag' => 'COMMENTS',
                    'identifier_comments_type' => 'HTML'
                ), $identifier_id);
            }
            else {
                $identifier_id = $identifier['identifier_id'];
            }

            if (!$this->Comments->commentAlreadyExists($identifier_id, $contact_id, date('Y-m-d H:i:s', $feedback['activation_stamp']))) {
                // insert the comment
                $comment_id = -1;
                $this->Comments->insert(array(
                    'identifier_id' => $identifier_id,
                    'comment_parent' => 0,
                    'comment_url' => $this->Page->getURL($feedback['page_id']),
                    'comment_headline' => $feedback['header'],
                    'comment_content' => $feedback['feedback'],
                    'comment_status' => 'CONFIRMED',
                    'comment_guid' => $this->app['utils']->createGUID(),
                    'comment_guid_2' => $this->app['utils']->createGUID(),
                    'comment_confirmation' => date('Y-m-d H:i:s', $feedback['activation_stamp']),
                    'comment_update_info' => 0,
                    'contact_id' => $contact_id,
                    'contact_nick_name' => $feedback['name'],
                    'contact_email' => $feedback['email'],
                    'contact_url' => ''
                ), $comment_id);
                // counter
                $imported_feedback++;

                // now check for a comment to the feedback
                if (!empty($feedback['comment'])) {
                    // there exists a 'comment' (reply) to the feedback
                    if (false === ($contact_id = $this->Contact->existsLogin($feedback['comment_mail']))) {
                        // add a new contact
                        if (false === ($this->addContact(
                            $feedback['comment_mail'],
                            $feedback['comment_from'],
                            $feedback['comment_date'],
                            $contact_id))) {
                            // on error break the loop
                            break;
                        }
                    }
                    elseif (!$this->Contact->issetContactTag('COMMENTS', $contact_id)) {
                        // set the COMMENTS tag for the already existing contact
                        $this->Contact->setContactTag('COMMENTS', $contact_id);
                    }
                    if (!$this->Comments->commentAlreadyExists($identifier_id, $contact_id, date('Y-m-d H:i:s', $feedback['comment_date']))) {
                        $this->Comments->insert(array(
                            'identifier_id' => $identifier_id,
                            'comment_parent' => $comment_id,
                            'comment_url' => $this->Page->getURL($feedback['page_id']),
                            'comment_headline' => $feedback['header'],
                            'comment_content' => $feedback['comment'],
                            'comment_status' => 'CONFIRMED',
                            'comment_guid' => $this->app['utils']->createGUID(),
                            'comment_guid_2' => $this->app['utils']->createGUID(),
                            'comment_confirmation' => date('Y-m-d H:i:s', $feedback['comment_date']),
                            'comment_update_info' => 0,
                            'contact_id' => $contact_id,
                            'contact_nick_name' => $feedback['comment_from'],
                            'contact_email' => $feedback['comment_mail'],
                            'contact_url' => ''
                        ));
                        // counter
                        $imported_comment++;
                    }
                }
            }

        }

        $this->setMessage('Imported %feedbacks% records and %comments% administrative comments from FeedbackModule',
            array('%feedbacks%' => $imported_feedback, '%comments%' => $imported_comment));

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Comments',
            'import/execute.feedbackmodule.twig'),
            array(
                'message' => $this->getMessage()
            ));
    }
}
