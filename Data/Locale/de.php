<?php

/**
 * CommandCollection
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://kit2.phpmanufaktur.de
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if ('á' != "\xc3\xa1") {
    // the language files must be saved as UTF-8 (without BOM)
    throw new \Exception('The language file ' . __FILE__ . ' is damaged, it must be saved UTF-8 encoded!');
}

return array(
    'Comment'
        => 'Kommentar',
    'Create a new comment'
        => 'Einen neuen Kommentar erstellen',

    'For the email address %email% exists a contact record, but the status does not allow you to post a comment. Please contact the <a href="mailto:%admin_email%">administrator</a>.'
        => 'Für die E-Mail Adresse %email% existiert ein Datensatz, der Status erlaubt es Ihnen jedoch nicht einen Kommentar zu veröffentlichen. Bitte nehmen Sie Kontakt mit dem <a href="mailto:%admin_email%">Administrator</a> auf.',

    'Headline'
        => 'Schlagzeile',

    'Import comments from the FeedbackModule'
        => 'Kommentare aus dem FeedbackModule importieren',
    'Imported %feedbacks% records and %comments% administrative comments from FeedbackModule'
        => 'Es wurden %feedbacks% Feedbacks sowie %comments% administrative Kommentare aus dem FeedbackModule in <i>Comments</i> importiert.',

    'jump to this comment'
        => 'zu diesem Kommentar springen',

    'No hits for the color(s) %colors%!'
        => 'Kein(e) Treffer für die Farbe(n) %colors%!',

    'Ooops, you have forgotten to post a comment or your comment is too short (minimum: %length% characters).'
        => 'Oh, Sie haben vergessen einen Kommentar zu übermitteln oder der Kommentar ist einfach zu kurz (Minimum: %length% Zeichen).',
    'Ooops, your comment exceeds the maximum length of %length% chars, please shorten it.'
        => 'Oh, Ihr Kommentar ist leider etwas zu lang geworden, erlaubt sind %length% Zeichen, bitte kürzen Sie Ihren Beitrag.',

    'Rating'
        => 'Bewertung',
    'Reply to this comment'
        => 'Auf diesen Kommentar antworten',

    'send email at new comment'
        => 'bei neuen Kommentaren per E-Mail benachrichtigen',
    'Start import from FeedbackModule'
        => 'Import aus dem FeedbackModule starten',

    'Thank you for submitting the comment %headline%!'
        => 'Vielen Dank für die Übermittlung des Kommentars "%headline%".',
    'Thank you for the activation of your email address. You comment will be confirmed by the administrator and published as soon as possible.'
        => 'Vielen Dank für die Aktivierung Ihrer E-Mail Adresse. Ihr Kommentar wird gerade von einem Administrator geprüft und so rasch wie möglich veröffentlicht.',
    'Thank you for the comment!'
        => 'Vielen Dank für den neuen Kommentar!',
    'Thank you for your comment. We have send you an activation link to confirm your email address. The email address will never published.'
        => 'Vielen Dank für Ihren Kommentar. Wir haben Ihnen einen Aktivierungslink gesendet um Ihre E-Mail Adresse zu bestätigen. Nach der Aktivierung veröffentlichen wir Ihren Kommentar.',
    'The comment with the ID %id% has confirmed and published.'
        => 'Der Kommentar mit der ID %id% wurde bestätigt und veröffentlicht.',
    'The comment with the ID %id% is already marked as REJECTED!'
        => 'Der Kommentar mit der ID %id% ist bereits als <b>REJECTED</b> gekennzeichnet!',
    'The comment with the ID %id% is already published!'
        => 'Der Kommentar mit der ID %id% wurde bereits veröffentlicht!',
    'The comment with the ID %id% is REJECTED.'
        => 'Der Kommentar mit der ID %id% wurde <b>zurückgewiesen</b>.',
    'The comment with the ID %comment_id% is REJECTED and too, the contact with the ID %contact_id% has LOCKED.'
        => 'Der Kommentar mit der ID %comment_id% wurde <b>zurückgewiesen</b> und darüber hinaus wurde der Kontakt mit der ID %contact_id% <b>gesperrt</b>.',
    'The comment with the ID %id% is marked as REJECTED!!'
        => 'Der Kommentar mit der ID %id% ist als <b>REJECTED</b> gekennzeichnet.',
    'The RAL number %number% does not exists!'
        => 'Die RAL Nummer %number% wurde nicht gefunden!',
    'There exists %count% records of the FeedbackModule which can be imported into the Comments.'
        => 'Es existieren %count% Datensätze des FeedbackModule die in <i>Comments</i> importiert werden können.',
    'There exists no FeedbackModule table for import!'
        => 'Es existiert keine FeedbackModule Tabelle, ein Import ist nicht möglich.',


    'vote'
        => 'Bewertung',
    'votes'
        => 'Bewertungen',
    'Votes: %count% - Average: %average%'
        => 'Bewertungen: %count% - Durchschnitt: %average%',

    'You are replying to the comment <i>%headline%</i>'
        => 'Sie antworten auf den Kommentar <i>%headline%</i>',
    'You are unsubscribed from this thread.'
        => 'Sie erhalten keine Benachrichtigungen mehr über neue Kommentare.',
    'Your comment'
        => 'Ihr Kommentar',
    'Your comment "%headline% is already published, the activation link is no longer valid'
        => 'Ihr Kommentar "%headline%" wurde bereits veröffentlich, der Aktivierungslink ist nicht mehr gültig.',
    'Your comment will be checked and published as soon as possible.'
        => 'Ihr Kommentar wird geprüft und so rasch wie möglich veröffentlicht.',
    'Your contact status is not as expected PENDING - please contact the <a href="mailto:%admin_email%">administrator</a>.'
        => 'Ihr Kontakt Status ist nicht wie erwartet auf <i>PENDING</i> gesetzt, das Programm kann ihre Anfrage nicht bearbeiten. Bitte nehmen Sie Kontakt mit dem <a href="mailto:%admin_email%">Administrator</a> auf.'
);
