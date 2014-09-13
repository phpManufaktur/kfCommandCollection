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

use phpManufaktur\Basic\Control\kitCommand\Basic;
use Silex\Application;
use phpManufaktur\CommandCollection\Data\Comments\Comments as CommentsData;
use phpManufaktur\CommandCollection\Data\Comments\CommentsIdentifier;
use phpManufaktur\CommandCollection\Control\Comments\GravatarLib\Gravatar;
use phpManufaktur\flexContent\Control\Command\Tools as flexContentTools;
use phpManufaktur\flexContent\Data\Content\Content as flexContentData;
use phpManufaktur\flexContent\Data\Content\CategoryType as flexContentCategoryTypeData;
use phpManufaktur\CommandCollection\Data\Comments\CommentsPassed;

class Comments extends Basic
{
    protected $CommentsData = null;
    protected $CommentsIdentifier = null;
    protected $Configuration = null;
    protected $Gravatar = null;

    protected static $parameter = null;
    protected static $configuration = null;
    protected static $contact = null;
    protected static $contact_id = -1;
    protected static $idenfifier = null;
    protected static $identifier_id = -1;
    protected static $submit = null;
    protected static $comment = null;
    protected static $comment_id = -1;
    protected static $hide_iframe = null;

    protected static $publish_methods = array('EMAIL', 'ADMIN', 'IMMEDIATE');

    /**
     * (non-PHPdoc)
     *
     * @see \phpManufaktur\Basic\Control\kitCommand\Basic::initParameters()
     */
    protected function initParameters(Application $app, $parameter_id=-1)
    {
        parent::initParameters($app, $parameter_id);

        // initially we want to show the iframe
        self::$hide_iframe = false;

        // clear all messages
        $this->clearAlert();

        $this->CommentsData = new CommentsData($app);
        $this->CommentsIdentifier = new CommentsIdentifier($app);

        $this->Configuration = new Configuration($app);
        self::$configuration = $this->Configuration->getConfiguration();

        // get the parameters
        $params = $this->getCommandParameters();

        // check the publishing mode for new comments
        $publish = array();
        if (isset($params['publish'])) {
            if (strpos($params['publish'], ',')) {
                $pub = explode($params['publish']);
                foreach ($pub as $key) {
                    if (in_array(strtoupper(trim($key)), self::$publish_methods))
                        $publish[] = strtoupper(trim($key));
                }
                if (in_array('IMMEDIATE', $publish) && in_array(array('EMAIL', 'ADMIN'), $publish))
                    unset($publish[array_search('IMMEDIATE', $publish)]);
                if (empty($publish)) {
                    $publish[] = 'ADMIN';
                }
            }
            elseif (in_array(strtoupper(trim($params['publish'])), self::$publish_methods)) {
                $publish[] = strtoupper(trim($params['publish']));
            }
            else {
                $publish[] = 'ADMIN';
            }
        }
        elseif (!self::$configuration['comments']['confirmation']['double_opt_in'] &&
                !self::$configuration['comments']['confirmation']['administrator']) {
            $publish[] = 'IMMEDIATE';
        }
        else {
            if (self::$configuration['comments']['confirmation']['double_opt_in']) {
                $publish[] = 'EMAIL';
            }
            if (self::$configuration['comments']['confirmation']['administrator']) {
                $publish[] = 'ADMIN';
            }
        }

        if (self::$configuration['gravatar']['enabled']) {
            $use_gravatar = (isset($params['gravatar']) && (($params['gravatar'] == '0') || (strtolower(trim($params['gravatar'])) == 'false'))) ? false : true;
        }
        else {
            $use_gravatar = false;
        }

        if (self::$configuration['rating']['enabled']) {
            $use_rating = (isset($params['rating']) && (($params['rating'] == '0') || (strtolower(trim($params['rating'])) == 'false'))) ? false : true;
        }
        else {
            $use_rating = false;
        }

        // check if ID and TYPE are set via CMS GET parameter
        $GET = $this->getCMSgetParameters();
        if (isset($GET['comment']) && is_numeric($GET['comment']) && isset($GET['type'])) {
            // the $_GET parameter overwrites all other!
            $params['id'] = $GET['comment'];
            $params['type'] = $GET['type'];
        }

        if (isset($params['id']) && isset($params['type'])) {
            if (is_numeric($params['id'])) {
                $id = intval($params['id']);
            }
            elseif (strtoupper($params['id']) == 'TOPIC_ID') {
                $info = $this->getCMSinfoArray();
                if (!is_null($info['special']['topic_id'])) {
                    $id = $info['special']['topic_id'];
                }
                else {
                    $id = -1;
                    $this->setAlert('This is no TOPICS article, please check the usage of the magic TOPIC_ID.', array(), self::ALERT_TYPE_WARNING);
                }
            }
            elseif (strtoupper($params['id']) == 'POST_ID') {
                $info = $this->getCMSinfoArray();
                if (!is_null($info['special']['post_id'])) {
                    $id = $info['special']['post_id'];
                }
                else {
                    $id = -1;
                    $this->setAlert('This is no NEWS article, please check the usage of the magic POST_ID.', array(), self::ALERT_TYPE_WARNING);
                }
            }
            elseif (strtoupper($params['id']) == 'EVENT_ID') {
                if (null === ($id = $this->app['session']->get('EVENT_ID'))) {
                    $id = -1;
                    $app['monolog']->addInfo('The magic EVENT_ID is missing the session variable EVENT_ID (must be set by Event)',
                        array(__METHOD__, __LINE__));
                    // hide the iframe in this situation!
                    self::$hide_iframe = true;
                }
            }
            else {
                $id = -1;
                $this->setAlert("Don't know how to handle the magic ID %magic_id%.", array('%magic_id%' => $params['id']), self::ALERT_TYPE_DANGER);
            }
        }
        elseif (isset($params['id'])) {
            // missing the parameter 'type'
            $id = -1;
            $this->setAlert('If you are using the parameter id[] you must also use type[]!', array(), self::ALERT_TYPE_WARNING);
        }
        else {
            // use the page ID as indicator
            $id = $this->getCMSpageID();
        }

        $type = (isset($params['type']) && !empty($params['type'])) ? strtoupper($params['type']) : 'PAGE';

        // check if the ID is passed to another ID ...
        $CommentsPassed = new CommentsPassed($app);
        if (false !== ($passed_id = $CommentsPassed->selectPassTo($type, $id))) {
            $id = $passed_id;
        }

        // check the parameters and set defaults
        self::$parameter = array(
            'captcha' => (isset($params['captcha']) && (($params['captcha'] == '0') || (strtolower(trim($params['captcha'])) == 'false'))) ? false : true,
            'type' => $type,
            'id' => $id,
            'publish' => $publish,
            'gravatar' => $use_gravatar,
            'rating' => $use_rating,
            'message' => (isset($params['message'])) ? $params['message'] : ''
        );

        if (false === (self::$idenfifier = $this->CommentsIdentifier->selectByTypeID(self::$parameter['type'], self::$parameter['id']))) {
            // create a new identifier
            if (in_array('IMMEDIATE', self::$parameter['publish'])) {
                $publish_type = 'IMMEDIATE';
            }
            elseif (in_array(array('EMAIL', 'ADMIN'), self::$parameter['publish'])) {
                $publish_type = 'CONFIRM_EMAIL_ADMIN';
            }
            elseif (in_array('EMAIL', self::$parameter['publish'])) {
                $publish_type = 'CONFIRM_EMAIL';
            }
            else {
                $publish_type = 'CONFIRM_ADMIN';
            }
            $data = array(
                'identifier_type_name' => self::$parameter['type'],
                'identifier_type_id' => self::$parameter['id'],
                'identifier_mode' => 'EMAIL', // actual the only supported mode
                'identifier_publish' => $publish_type,
                'identifier_contact_tag' => '', // actual not supported
                'identifier_comments_type' => 'HTML', // actual the only supported mode
            );
            // insert the new identifier
            $this->CommentsIdentifier->insert($data, self::$identifier_id);
            self::$idenfifier = $this->CommentsIdentifier->select(self::$identifier_id);
        }
        self::$identifier_id = self::$idenfifier['identifier_id'];

        // check if the contact tag type 'COMMENTS' exists
        if (!$this->app['contact']->existsTagName('COMMENTS')) {
            // create the tag type 'COMMENTS'
            $this->app['contact']->createTagName('COMMENTS',
                "This Tag type is created by the kitCommand 'Comments' and will be set for persons who leave a comment.");
            $this->app['monolog']->addInfo('Created the Contact Tag Type COMMENTS', array(__METHOD__, __LINE__));
        }

        if (self::$parameter['gravatar']) {
            $this->Gravatar = new Gravatar();
            $this->Gravatar->setDefaultImage(self::$configuration['gravatar']['default_image']);
            $this->Gravatar->setAvatarSize(self::$configuration['gravatar']['size']);
            $this->Gravatar->setMaxRating(self::$configuration['gravatar']['max_rating']);
            if (self::$configuration['gravatar']['use_ssl']) {
                $this->Gravatar->enableSecureImages();
            }
        }
    }

    /**
     * Return the complete form for the submission
     *
     * @param array $data
     */
    protected function getCommentForm($data=array())
    {
        return $this->app['form.factory']->createBuilder('form')
        ->add('comment_id', 'hidden', array(
            'data' => isset($data['comment_id']) ? $data['comment_id'] : -1
        ))
        ->add('identifier_id', 'hidden', array(
            'data' => isset($data['identifier_id']) ? $data['identifier_id'] : -1
        ))
        ->add('comment_parent', 'hidden', array(
            'data' => isset($data['comment_parent']) ? $data['comment_parent'] : 0,
        ))
        ->add('comment_headline', 'text', array(
            'data' => isset($data['comment_headline']) ? $data['comment_headline'] : '',
            'required' => true
        ))
        ->add('comment_content', 'textarea', array(
            'data' => isset($data['comment_content']) ? $data['comment_content'] : '',
            'required' => true
        ))
        ->add('contact_id', 'hidden', array(
            'data' => isset($data['contact_id']) ? $data['contact_id'] : -1
        ))
        ->add('contact_nick_name', 'text', array(
            'data' => isset($data['contact_nick_name']) ? $data['contact_nick_name'] : '',
            'required' => true
        ))
        ->add('contact_email', 'email', array(
            'data' => isset($data['contact_email']) ? $data['contact_email'] : '',
            'required' => true
        ))
        ->add('contact_url', 'url', array(
            'data' => isset($data['contact_homepage']) ? $data['contact_homepage'] : '',
            'required' => false
        ))
        ->add('comment_update_info', 'checkbox', array(
            'required' => false,
            'label' => 'Comment update info'
        ))
        ->getForm();
    }

    /**
     * Initialize the iFrame for ExcelRead
     * Return the Welcome page if no file is specified. otherwise show the Excel file
     *
     * @param Application $app
     */
    public function controllerInitFrame(Application $app)
    {
        // initialize only the Basic class, dont need additional initialisations
        parent::initParameters($app);

        // execute the Comments within the iFrame
        return $this->createIFrame('/collection/comments/view');
    }

    /**
     * Return the rendered comments thread and the submit form
     *
     * @param FormFactory $form
     * @return string
     */
    protected function promptForm($form)
    {

        // we want to grant the "fold in" if the frame url is executed outside the CMS
        if (!isset(self::$parameter['url'])) {
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
                self::$parameter['url'] = $url;
                $this->createParameterID();
                //$this->createParameterID(self::$parameter);
        }
        else {
            $this->setCMSpageURL(self::$parameter['url']);
        }
        $this->setRedirectActive(true);

        return $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Comments',
            "comments.twig",
            $this->getPreferredTemplateStyle()),
            array(
                'parameter' => self::$parameter,
                'configuration' => self::$configuration,
                'basic' => $this->getBasicSettings(),
                'form' => $form->createView(),
                'thread' => $this->CommentsData->getThread(self::$identifier_id, $this->Gravatar, self::$parameter['rating'])
            ));
    }

    /**
     * If the double-opt-in feature for new contacts is enabled the submitter
     * must activate the contact before he can submit a comment
     *
     * @return string
     */
    protected function contactConfirmContact() {
        // create a comment record
        $this->createCommentRecord();

        $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Comments',
            'mail/contact/confirm.contact.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'comment' => self::$comment,
                'activation_link' => FRAMEWORK_URL.'/collection/comments/contact/confirm/'.self::$comment['comment_guid']
            ));

        // send a email to the contact
        $message = \Swift_Message::newInstance()
            ->setSubject(self::$comment['comment_headline'])
            ->setFrom(array(self::$configuration['administrator']['email']))
            ->setTo(array(self::$comment['contact_email']))
            ->setBody($body)
            ->setContentType('text/html');
        // send the message
        $this->app['mailer']->send($message);

        return $this->ControllerView($this->app,
            $this->app['translator']->trans('Thank you for your comment. We have send you an activation link to confirm your email address. The email address will never published.'));
    }

    /**
     * The contact must confirm the comment with an activation link
     *
     */
    protected function contactConfirmComment()
    {
        $this->createCommentRecord();
        $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Comments',
            'mail/contact/confirm.comment.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'comment' => self::$comment,
                'activation_link' => FRAMEWORK_URL.'/collection/comments/comment/confirm/'.self::$comment['comment_guid']
            ));

        // send a email to the contact
        $message = \Swift_Message::newInstance()
        ->setSubject(self::$comment['comment_headline'])
        ->setFrom(array(self::$configuration['administrator']['email']))
        ->setTo(array(self::$comment['contact_email']))
        ->setBody($body)
        ->setContentType('text/html');
        // send the message
        $this->app['mailer']->send($message);

        $msg = $this->app['translator']->trans('Thank you for your comment. We have send you an activation link to confirm the publishing of the comment.');
        return $this->ControllerView($this->app, $msg);
    }

    /**
     * Send the contact a information that the email is activated but the
     * comment must be confirmed by the administrator
     *
     */
    protected function contactPendingConfirmation()
    {
        if (self::$configuration['contact']['information']['pending_confirmation']) {
            $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/CommandCollection/Template/Comments',
                'mail/contact/pending.confirmation.twig',
                $this->getPreferredTemplateStyle()),
                array(
                    'comment' => self::$comment,
                ));

            // send a email to the contact
            $message = \Swift_Message::newInstance()
            ->setSubject(self::$comment['comment_headline'])
            ->setFrom(array(self::$configuration['administrator']['email']))
            ->setTo(array(self::$comment['contact_email']))
            ->setBody($body)
            ->setContentType('text/html');
            // send the message
            $this->app['mailer']->send($message);
        }
    }

    /**
     * Send the contact the information that the comment is just published
     *
     */
    protected function contactPublishedComment()
    {
        if (self::$configuration['contact']['information']['published_comment']) {
            $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/CommandCollection/Template/Comments',
                'mail/contact/published.comment.twig',
                $this->getPreferredTemplateStyle()),
                array(
                    'comment' => self::$comment,
                ));

            // send a email to the contact
            $message = \Swift_Message::newInstance()
            ->setSubject(self::$comment['comment_headline'])
            ->setFrom(array(self::$configuration['administrator']['email']))
            ->setTo(array(self::$comment['contact_email']))
            ->setBody($body)
            ->setContentType('text/html');
            // send the message
            $this->app['mailer']->send($message);
        }
    }

    /**
     * Send the contact the information that the comment was REJECTED
     *
     */
    protected function contactRejectComment()
    {
        if (self::$configuration['contact']['information']['rejected_comment']) {
            $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/CommandCollection/Template/Comments',
                'mail/contact/rejected.comment.twig',
                $this->getPreferredTemplateStyle()),
                array(
                    'comment' => self::$comment,
                ));

            // send a email to the contact
            $message = \Swift_Message::newInstance()
            ->setSubject(self::$comment['comment_headline'])
            ->setFrom(array(self::$configuration['administrator']['email']))
            ->setTo(array(self::$comment['contact_email']))
            ->setBody($body)
            ->setContentType('text/html');
            // send the message
            $this->app['mailer']->send($message);
        }
    }

    /**
     * Send the contact the information that the comment was REJECTED and
     * his account is LOCKED
     *
     */
    protected function contactLockContact()
    {
        if (self::$configuration['contact']['information']['rejected_comment']) {
            $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/CommandCollection/Template/Comments',
                'mail/contact/locked.contact.twig',
                $this->getPreferredTemplateStyle()),
                array(
                    'comment' => self::$comment,
                ));

            // send a email to the contact
            $message = \Swift_Message::newInstance()
            ->setSubject(self::$comment['comment_headline'])
            ->setFrom(array(self::$configuration['administrator']['email']))
            ->setTo(array(self::$comment['contact_email']))
            ->setBody($body)
            ->setContentType('text/html');
            // send the message
            $this->app['mailer']->send($message);
        }
    }

    /**
     * Create a new comment record
     *
     * @param $status set the status for the comment, default 'PENDING'
     */
    protected function createCommentRecord($status='PENDING')
    {
        $content = self::$submit['comment_content'];

        // check links in the comment content, we have to add a target="_blank" if missing!
        preg_match_all('%<a[^>]*>(.*?)</a>%si', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $expression = $match[0];
            $first_tag = substr($expression, 0, strpos($expression, '>')+1);
            if (false === ($pos = stripos($first_tag, 'target'))) {
                // add 'target="_blank"' to the link
                $replace = str_replace('>', ' target="_blank">', $first_tag);
                $replace = str_replace($first_tag, $replace, $expression);
                $content = str_replace($expression, $replace, $content);
            }
        }

        $url = $this->getCMSpageURL();
        if ((self::$parameter['type'] == 'FLEXCONTENT') || (self::$parameter['type'] == 'FLEXCONTENT_CATEGORY') ||
            (self::$parameter['type'] == 'FLEXCONTENT_FAQ')) {
            if ($this->app['filesystem']->exists(MANUFAKTUR_PATH.'/flexContent/extension.json')) {
                $flexContentTools = new flexContentTools($this->app);
                $base_url = $flexContentTools->getPermalinkBaseURL($this->getCMSlocale());

                if (self::$parameter['type'] == 'FLEXCONTENT') {
                    // get the URL for a flexContent article
                    $flexContentData = new flexContentData($this->app);
                    if (false === ($flexcontent = $flexContentData->select(self::$parameter['id'], $this->getCMSlocale()))) {
                        throw new \Exception('There exists no FLEXCONTENT record for the ID '.self::$parameter['id'].' in the language '.$this->getCMSlocale());
                    }
                    $url = $base_url.'/'.$flexcontent['permalink'];
                }
                elseif ((self::$parameter['type'] == 'FLEXCONTENT_CATEGORY') || (self::$parameter['type'] == 'FLEXCONTENT_FAQ')) {
                    // get the URL for a flexContent CATEGORY or FAQ
                    $flexContentCategoryTypeData = new flexContentCategoryTypeData($this->app);
                    if (false === ($category = $flexContentCategoryTypeData->select(self::$parameter['id']))) {
                        throw new \Exception('There exists no FLEXCONTENT CATEGORY for the ID '.self::$parameter['id']);
                    }
                    if (self::$parameter['type'] == 'FLEXCONTENT_FAQ') {
                        $url = $base_url.'/faq/'.$category['category_permalink'];
                    }
                    else {
                        $url = $base_url.'/category/'.$category['category_permalink'];
                    }
                }
            }
            else {
                throw new \Exception('You have specified the reserved identifier FLEXCONTENT but flexContent is not installed! Please install flexContent or choose another identifer!');
            }
        }
        elseif (self::$parameter['type'] == 'EVENT') {
            // this is a comment to an EVENT
            $url = FRAMEWORK_URL.'/event/perma/id/'.self::$identifier_id;
        }

        $comment = array(
            'identifier_id' => self::$identifier_id,
            'comment_parent' => self::$submit['comment_parent'],
            'comment_url' => $url,
            'comment_headline' => self::$submit['comment_headline'],
            'comment_content' => $content,
            'comment_status' => $status,
            'comment_guid' => $this->app['utils']->createGUID(),
            'comment_guid_2' => $this->app['utils']->createGUID(),
            'comment_confirmation' => ($status != 'PENDING') ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00',
            'comment_update_info' => (isset(self::$submit['comment_update_info']) && (self::$submit['comment_update_info'] == 1)) ? 1 : 0,
            'contact_id' => self::$contact_id,
            'contact_nick_name' => self::$submit['contact_nick_name'],
            'contact_email' => self::$submit['contact_email'],
            'contact_url' => !is_null(self::$submit['contact_url']) ? self::$submit['contact_url'] : ''
        );
        $this->CommentsData->insert($comment, self::$comment_id);
        self::$comment = $this->CommentsData->select(self::$comment_id);
    }

    /**
     * Controller to check the submission of new comment
     *
     * @param Application $app
     * @return string
     */
    public function controllerSubmit(Application $app)
    {
        $this->initParameters($app);

        $this->setFrameScrollToID('comment_form');

        $form = $this->getCommentForm();

        $form->bind($this->app['request']);

        if ((false !== ($recaptcha_check = $app['recaptcha']->isValid())) && $form->isValid()) {
            // get the submit
            self::$submit = $form->getData();
            if (empty(self::$submit['comment_content']) ||
                strlen(self::$submit['comment_content']) < self::$configuration['comments']['length']['minimum']) {
                // empty comment or comment too short
                $this->setAlert('Ooops, you have forgotten to post a comment or your comment is too short (minimum: %length% characters).',
                    array('%length%' => self::$configuration['comments']['length']['minimum']), self::ALERT_TYPE_WARNING);
                return $this->promptForm($form);
            }
            if (strlen(self::$submit['comment_content']) > self::$configuration['comments']['length']['maximum']) {
                // comment exceed the maximum length
                $this->setAlert('Ooops, your comment exceeds the maximum length of %length% chars, please shorten it.',
                    array('%length%' => self::$configuration['comments']['length']['maximum']), self::ALERT_TYPE_WARNING);
                return $this->promptForm($form);
            }
            // comment is valid
            if (false !== (self::$contact_id = $this->app['contact']->existsLogin(self::$submit['contact_email']))) {
                // contact already exists, get the contact data
                self::$contact = $this->app['contact']->select(self::$contact_id);
            }
            else {
                // create a new contact
                $person = array(
                    'contact' => array(
                        'contact_id' => -1,
                        'contact_type' => 'PERSON',
                        'contact_name' => strtolower(self::$submit['contact_email']),
                        'contact_login' => strtolower(self::$submit['contact_email']),
                        'contact_status' => self::$configuration['contact']['confirmation']['double_opt_in'] ? 'PENDING' : 'ACTIVE',
                        'contact_since' => date('Y-m-d H:i:s')
                    ),
                    'person' => array(
                        array(
                            'person_id' => -1,
                            'person_nick_name' => self::$submit['contact_nick_name']
                        )
                    ),
                    'communication' => array(
                        array(
                            'communication_id' => -1,
                            'communication_type' => 'EMAIL',
                            'communication_usage' => 'PRIMARY',
                            'communication_value' => strtolower(self::$submit['contact_email'])
                        )
                    ),
                    'tag' => array(
                        array(
                            'tag_id' => -1,
                            'tag_name' => 'COMMENTS'
                        )
                    )
                );
                self::$contact_id = -1;
                if (!$this->app['contact']->insert($person, self::$contact_id)) {
                    // something went wrong, return with a alert
                    return $this->promptForm($form);
                }
                self::$contact = $this->app['contact']->select(self::$contact_id);
            }

            if (self::$configuration['contact']['confirmation']['double_opt_in'] &&
                self::$contact['contact']['contact_status'] == 'PENDING') {
                // the contact must be confirmed before the comment can be published
                return $this->contactConfirmContact();
            }

            if (self::$contact['contact']['contact_status'] != 'ACTIVE') {
                // contact exists but has no ACTIVE status
                $this->setAlert('For the email address %email% exists a contact record, but the status does not allow you to post a comment. Please contact the <a href="mailto:%admin_email%">administrator</a>.',
                    array('%email%' => self::$submit['contact_email'], '%admin_email%' => self::$configuration['administrator']['email']), self::ALERT_TYPE_WARNING);
                return $this->promptForm($form);
            }

            // set the tag COMMENTS if not exists
            $this->app['contact']->setContactTag('COMMENTS', self::$contact_id);

            if ((self::$contact['contact']['contact_type'] == 'PERSON') &&
                (empty(self::$contact['person'][0]['contact_nick_name']) ||
                    (self::$contact['person'][0]['person_nick_name'] != self::$submit['contact_nick_name']))) {
                // add or update the nickname to the contact
                self::$contact['person'][0]['person_nick_name'] = self::$submit['contact_nick_name'];
                $this->app['contact']->update(self::$contact, self::$contact_id);
            }

            // contact is checked and can post, now check the handling for new comments
            if ((self::$idenfifier['identifier_publish'] == 'CONFIRM_EMAIL') ||
                (self::$idenfifier['identifier_publish'] == 'CONFIRM_EMAIL_ADMIN')) {
                // the contact must confirm the comment with an activation link
                return $this->contactConfirmComment();
            }
            elseif (self::$idenfifier['identifier_publish'] == 'CONFIRM_ADMIN') {
                // the administrator must confirm the comment
                $this->createCommentRecord();
                $this->adminConfirmComment();
                $message = $this->app['translator']->trans('Your comment will be checked and published as soon as possible.');
                return $this->controllerView($this->app, $message);
            }
            elseif (self::$idenfifier['identifier_publish'] == 'IMMEDIATE') {
                // publish the comment immediate
                $this->createCommentRecord('CONFIRMED');
                $this->infoCommentPublished();
                $message = $this->app['translator']->trans('Thank you for the comment!');
                return $this->controllerView($this->app, $message);
            }
            else {
                // Ooops, the handling is not defined?
                throw new \Exception("Unknown handling for 'identifier_publish' => ".self::$idenfifier['identifier_publish']);
            }
        }
        else {
            // the form check failed
            if (!$recaptcha_check) {
                // ReCaptcha error
                $this->setAlert($app['recaptcha']->getLastError(), array(), self::ALERT_TYPE_DANGER);
            }
            else {
                // invalid form submission
                $this->setAlert('The form is not valid, please check your input and try again!', array(),
                self::ALERT_TYPE_DANGER, true, array('form_errors' => $form->getErrorsAsString(),
                    'method' => __METHOD__, 'line' => __LINE__));
            }
        }

        return $this->promptForm($form);
    }

    /**
     * Send a mail to the administrator to confirm the comment
     */
    protected function adminConfirmComment()
    {
        $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
            '@phpManufaktur/CommandCollection/Template/Comments',
            'mail/admin/confirm.comment.twig',
            $this->getPreferredTemplateStyle()),
            array(
                'contact' => self::$contact,
                'comment' => self::$comment,
                'link_publish_comment' => FRAMEWORK_URL.'/collection/comments/admin/confirm/'.self::$comment['comment_guid_2'],
                'link_reject_comment' => FRAMEWORK_URL.'/collection/comments/admin/reject/'.self::$comment['comment_guid_2'],
                'link_lock_contact' => FRAMEWORK_URL.'/collection/comments/admin/lock/'.self::$comment['comment_guid_2']
            ));

        // send a email to the contact
        $message = \Swift_Message::newInstance()
        ->setSubject(self::$comment['comment_headline'])
        ->setFrom(array(self::$configuration['administrator']['email']))
        ->setTo(array(self::$configuration['administrator']['email']))
        ->setBody($body)
        ->setContentType('text/html');
        // send the message
        $this->app['mailer']->send($message);
    }

    /**
     * Default Controller to view the comments for the given parameters and to
     * show a dialog to submit a new comment.
     *
     * @param Application $app
     * @return string
     */
    public function controllerView(Application $app, $message='')
    {
        // init parent and client
        $this->initParameters($app);

        if (self::$hide_iframe) {
            // we dont want to show the iFrame and hide it!
            return $app['twig']->render($app['utils']->getTemplateFile('@phpManufaktur/Basic/Template', 'kitcommand/null.twig'),
                array('basic' => $this->getBasicSettings()));
        }

        if (!empty($message)) {
            $this->setAlert($message, array(), self::ALERT_TYPE_INFO);
        }

        $GET = $this->getCMSgetParameters();
        if (isset($GET['message']) || !empty(self::$parameter['message'])) {
            // message submitted as CMS parameter
            $msg = (!empty(self::$parameter['message'])) ? self::$parameter['message'] : $GET['message'];
            $this->setAlert(base64_decode($msg), array(), self::ALERT_TYPE_INFO);
            // if a message is prompted, scroll to it
            $this->setFrameScrollToID('comment-form');
        }

        $form = $this->getCommentForm();
        return $this->promptForm($form);
    }

    /**
     * Check the activation key for the email address
     *
     * @param Application $app
     * @param string $guid
     * @throws \Exception
     */
    public function controllerContactConfirmContact(Application $app, $guid)
    {
        $this->initParameters($app);

        // this controller is executed outside of the CMS and get no language info!
        $this->app['translator']->setLocale($app['request']->getPreferredLanguage());

        if (false === (self::$comment = $this->CommentsData->selectGUID($guid))) {
            throw new \Exception("Invalid call, the GUID $guid does not exists.");
        }
        self::$comment_id = self::$comment['comment_id'];

        if (self::$comment['comment_status'] == 'CONFIRMED') {
            // the comment is already confirmed
            $message = $this->app['translator']->trans('Your comment "%headline% is already published, the activation link is no longer valid',
                array('%headline%' => self::$comment['comment_headline']));
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        // select the contact
        self::$contact_id = self::$comment['contact_id'];
        self::$contact = $this->app['contact']->select(self::$contact_id);

        if ((self::$contact['contact']['contact_status'] != 'PENDING') && (self::$contact['contact']['contact_status'] != 'ACTIVE')) {
            // unclear status
            $message = $this->app['translator']->trans('Your contact status is not as expected PENDING - please contact the <a href="mailto:%admin_email%">administrator</a>.');
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        // update the contact status to ACTIVE
        self::$contact['contact']['contact_status'] = 'ACTIVE';
        $this->app['contact']->update(self::$contact, self::$contact_id);

        $identifier = $this->CommentsIdentifier->select(self::$comment['identifier_id']);
        if (($identifier['identifier_publish'] == 'CONFIRM_ADMIN') || ($identifier['identifier_publish'] == 'CONFIRM_EMAIL_ADMIN')) {
            // the comment must be also confirmed by the administrator
            $this->adminConfirmComment();
            // inform the contact
            $this->contactPendingConfirmation();
            // show a message
            $message = $this->app['translator']->trans('Thank you for the activation of your email address. You comment will be confirmed by the administrator and published as soon as possible.');
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        // all done - publish the comment
        self::$comment['comment_confirmation'] = date('Y-m-d H:i:s');
        self::$comment['comment_status'] = 'CONFIRMED';
        $this->CommentsData->update(self::$comment, self::$comment_id);

        // info to the thread subscribers
        $this->infoCommentPublished();

        $message = $this->app['translator']->trans('Thank you for submitting the comment %headline%!',
            array('%headline%' => self::$comment['comment_headline']));
        return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
    }

    /**
     * Teh contact confirm the publishing of the comment
     *
     * @param Application $app
     * @param string $guid
     * @throws \Exception
     */
    public function controllerContactConfirmComment(Application $app, $guid)
    {
        $this->initParameters($app);

        // this controller is executed outside of the CMS and get no language info!
        $this->app['translator']->setLocale($app['request']->getPreferredLanguage());

        if (false === (self::$comment = $this->CommentsData->selectGUID($guid))) {
            throw new \Exception("Invalid call, the GUID $guid does not exists.");
        }
        self::$comment_id = self::$comment['comment_id'];

        if (self::$comment['comment_status'] == 'CONFIRMED') {
            // the comment is already confirmed
            $message = $this->app['translator']->trans('Your comment "%headline% is already published, the activation link is no longer valid',
                array('%headline%' => self::$comment['comment_headline']));
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        $identifier = $this->CommentsIdentifier->select(self::$comment['identifier_id']);
        if ($identifier['identifier_publish'] == 'CONFIRM_EMAIL_ADMIN') {
            // the comment must be also confirmed by the administrator
            $this->adminConfirmComment();
            // inform the contact
            $this->contactPendingConfirmation();
            // show a message
            $message = $this->app['translator']->trans('You comment will be confirmed by the administrator and published as soon as possible.');
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        // all done - publish the comment
        self::$comment['comment_confirmation'] = date('Y-m-d H:i:s');
        self::$comment['comment_status'] = 'CONFIRMED';
        $this->CommentsData->update(self::$comment, self::$comment_id);

        // info to the thread subscribers
        $this->infoCommentPublished();

        $message = $this->app['translator']->trans('Thank you for submitting the comment %headline%!',
            array('%headline%' => self::$comment['comment_headline']));
        return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
    }

    /**
     * Controller to confirm the publishing of a comment
     *
     * @param Application $app
     * @param string $guid
     * @throws \Exception
     */
    public function controllerAdminConfirmComment(Application $app, $guid)
    {
        $this->initParameters($app);

        // this controller is executed outside of the CMS and get no language info!
        $this->app['translator']->setLocale($app['request']->getPreferredLanguage());

        if (false === (self::$comment = $this->CommentsData->selectAdminGUID($guid))) {
            throw new \Exception("Invalid call, the GUID $guid does not exists.");
        }
        self::$comment_id = self::$comment['comment_id'];

        if (self::$comment['comment_status'] == 'CONFIRMED') {
            // the comment is already confirmed
            $message = $this->app['translator']->trans('The comment with the ID %id% is already published!',
                array('%id%' => self::$comment['comment_id']));
            $this->app['monolog']->addDebug($message, array(__METHOD__, __LINE__));
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        if (self::$comment['comment_status'] == 'REJECTED') {
            $message = $this->app['translator']->trans('The comment with the ID %id% is already marked as REJECTED!',
                array('%id%' => self::$comment['comment_id']));
            $this->app['monolog']->addDebug($message, array(__METHOD__, __LINE__));
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        // publish the comment
        self::$comment['comment_confirmation'] = date('Y-m-d H:i:s');
        self::$comment['comment_status'] = 'CONFIRMED';
        $this->CommentsData->update(self::$comment, self::$comment_id);

        // send a information to the contact
        $this->contactPublishedComment();

        // send info to the thread subscribers
        $this->infoCommentPublished();

        $message = $this->app['translator']->trans('The comment with the ID %id% has confirmed and published.',
            array('%id%' => self::$comment['comment_id']));
        $this->app['monolog']->addDebug($message, array(__METHOD__, __LINE__));
        return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
    }

    /**
     * Controller to REJECT a comment
     *
     * @param Application $app
     * @param string $guid
     * @throws \Exception
     */
    public function controllerAdminRejectComment(Application $app, $guid)
    {
        $this->initParameters($app);

        // this controller is executed outside of the CMS and get no language info!
        $this->app['translator']->setLocale($app['request']->getPreferredLanguage());

        if (false === (self::$comment = $this->CommentsData->selectAdminGUID($guid))) {
            throw new \Exception("Invalid call, the GUID $guid does not exists.");
        }
        self::$comment_id = self::$comment['comment_id'];

        if (self::$comment['comment_status'] == 'REJECTED') {
            // the comment is already confirmed
            $message = $this->app['translator']->trans('The comment with the ID %id% is already marked as REJECTED!',
                array('%id%' => self::$comment['comment_id']));
            return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
        }

        // REJECT the comment
        self::$comment['comment_confirmation'] = date('Y-m-d H:i:s');
        self::$comment['comment_status'] = 'REJECTED';
        $this->CommentsData->update(self::$comment, self::$comment_id);

        // send a information to the contact
        $this->contactRejectComment();

        $message = $this->app['translator']->trans('The comment with the ID %id% is REJECTED.',
            array('%id%' => self::$comment['comment_id']));
        return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
    }

    /**
     * Controller to REJECT a comment and to LOCK the contact which tried to
     * publish this comment.
     *
     * @param Application $app
     * @param string $guid
     * @throws \Exception
     */
    public function controllerAdminLockContact(Application $app, $guid)
    {
        $this->initParameters($app);

        // this controller is executed outside of the CMS and get no language info!
        $this->app['translator']->setLocale($app['request']->getPreferredLanguage());

        if (false === (self::$comment = $this->CommentsData->selectAdminGUID($guid))) {
            throw new \Exception("Invalid call, the GUID $guid does not exists.");
        }
        self::$comment_id = self::$comment['comment_id'];

        // REJECT the comment
        self::$comment['comment_confirmation'] = date('Y-m-d H:i:s');
        self::$comment['comment_status'] = 'REJECTED';
        $this->CommentsData->update(self::$comment, self::$comment_id);

        // ... and LOCK the contact!
        self::$contact = $this->app['contact']->select(self::$comment['contact_id']);
        self::$contact_id = self::$contact['contact']['contact_id'];

        self::$contact['contact']['contact_status'] = 'LOCKED';
        $this->app['contact']->update(self::$contact, self::$contact_id);
        $this->app['contact']->addProtocolInfo(self::$contact_id,
            "The contact is LOCKED because the comment with the ID ".self::$comment['comment_id']." was REJECTED.");

        // send a information to the contact
        $this->contactLockContact();

        $message = $this->app['translator']->trans('The comment with the ID %comment_id% is REJECTED and too, the contact with the ID %contact_id% has LOCKED.',
            array('%comment_id%' => self::$comment['comment_id'], '%contact_id%' => self::$contact_id));
        return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
    }

    /**
     * Unsubscribe the contact from this thread
     *
     * @param Application $app
     * @param string $guid
     * @throws \Exception
     */
    public function controllerContactUnsubscribeThread(Application $app, $guid)
    {
        $this->initParameters($app);

        // this controller is executed outside of the CMS and get no language info!
        $this->app['translator']->setLocale($app['request']->getPreferredLanguage());

        if (false === (self::$comment = $this->CommentsData->selectGUID($guid))) {
            throw new \Exception("Invalid call, the GUID $guid does not exists.");
        }

        // unsubscribe the contact from the info about new comments
        $this->CommentsData->unsubscribeContactID(self::$comment['identifier_id'], self::$comment['contact_id']);

        $message = $this->app['translator']->trans('You are unsubscribed from this thread.');
        return $this->app->redirect(self::$comment['comment_url'].'?message='.base64_encode($message));
    }

    /**
     * Send information about a new comment to the subscribers
     *
     */
    protected function infoCommentPublished()
    {
        $subscribers = $this->CommentsData->selectSubscribers(self::$comment['identifier_id']);

        foreach ($subscribers as $subscriber) {
            $body = $this->app['twig']->render($this->app['utils']->getTemplateFile(
                '@phpManufaktur/CommandCollection/Template/Comments',
                'mail/subscriber/new.comment.twig',
                $this->getPreferredTemplateStyle()),
                array(
                    'comment' => self::$comment,
                    'subscriber' => $subscriber,
                    'link_unsubscribe' => FRAMEWORK_URL.'/collection/comments/unsubscribe/'.$subscriber['comment_guid']
                ));

            // send a email to the subscribers
            $message = \Swift_Message::newInstance()
            ->setSubject(self::$comment['comment_headline'])
            ->setFrom(array(self::$configuration['administrator']['email']))
            ->setTo(array($subscriber['contact_email']))
            ->setBody($body)
            ->setContentType('text/html');
            // send the message
            $this->app['mailer']->send($message);
            $this->app['monolog']->addInfo('Send info to: '.$subscriber['contact_email']);
        }
    }

    public function controllerReply(Application $app, $comment_id)
    {
        $this->initParameters($app);

        if (false === ($reply_data = $this->CommentsData->select($comment_id))) {
            throw new \Exception("Can't create a reply, the commend ID $comment_id does not exists!");
        }

        $data = array(
            'comment_parent' => $reply_data['comment_id'],
            'comment_headline' => $reply_data['comment_headline']
        );

        $form = $this->getCommentForm($data);
        $this->setFrameScrollToID('comment_form');
        return $this->promptForm($form);

    }

    /**
     * Administrative Controller to reject an already published comment
     *
     * @param Application $app
     * @param integer $comment_id
     * @return string
     */
    public function ControllerAdminPublishedCommentReject(Application $app, $comment_id)
    {
        $CommentsData = new CommentsData($app);
        if (!$CommentsData->existsCommentID($comment_id)) {
            return $app['translator']->trans('The comment with the ID %id% does not exists!',
                array('%id%' => $comment_id));
        }
        $data = array(
            'comment_status' => 'REJECTED'
        );
        $CommentsData->update($data, $comment_id);
        $message = $app['translator']->trans('The comment with the ID %id% is REJECTED.', array('%id%' => $comment_id));
        $app['monolog']->addDebug($message);
        return $message;
    }

    /**
     * Administrative Controller to confirm/publish an existing comment
     *
     * @param Application $app
     * @param integer $comment_id
     * @return string
     */
    public function ControllerAdminPublishedCommentConfirm(Application $app, $comment_id)
    {
        $CommentsData = new CommentsData($app);
        if (!$CommentsData->existsCommentID($comment_id)) {
            return $app['translator']->trans('The comment with the ID %id% does not exists!',
                array('%id%' => $comment_id));
        }
        $data = array(
            'comment_status' => 'CONFIRMED'
        );
        $CommentsData->update($data, $comment_id);
        $message = $app['translator']->trans('The comment with the ID %id% has confirmed and published.', array('%id%' => $comment_id));
        $app['monolog']->addDebug($message);
        return $message;
    }

    /**
     * Administrative Controller to remove (physically delete) an already published comment
     *
     * @param Application $app
     * @param integer $comment_id
     * @return string
     */
    public function ControllerAdminPublishedCommentRemove(Application $app, $comment_id)
    {
        $CommentsData = new CommentsData($app);
        if (!$CommentsData->existsCommentID($comment_id)) {
            return $app['translator']->trans('The comment with the ID %id% does not exists!',
                array('%id%' => $comment_id));
        }
        $CommentsData->removeCommentID($comment_id);
        $message = $app['translator']->trans('The comment with the ID %id% was successful removed (physically deleted)',
            array('%id%' => $comment_id));
        $app['monolog']->addDebug($message);
        return $message;
    }

}
