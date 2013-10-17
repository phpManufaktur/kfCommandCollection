/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here.
    // For the complete reference:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    // The toolbar groups arrangement, optimized for two toolbar rows.
    config.toolbar = [
          { name: 'comment', items: [ 'Bold', 'Italic', '-', 'Smiley', '-', 'Link', 'Unlink' ] }
      ];

    // Se the most common block elements.
    config.format_tags = 'p;h1;h2;h3;pre';

    // Make dialogs simpler.
    config.removeDialogTabs = 'image:advanced;link:advanced;link:target';

    config.DefaultLinkTarget = '_blank' ;

    // utf8 need no entities!
    config.entities = false;
    config.basicEntities = false;
    config.entities_greek = false;
    config.entities_latin = false;

    // remove html tags from the footer
    config.removePlugins = 'elementspath';
    // disable resize and remove the footer bar
    config.resize_enabled = false;


};

CKEDITOR.on( 'dialogDefinition', function( ev )
{
    // Take the dialog name and its definition from the event data.
    var dialogName = ev.data.name;
    var dialogDefinition = ev.data.definition;

    // Check if the definition is from the dialog we're
    // interested in (the 'link' dialog).
    if ( dialogName == 'link' )
    {
    // Remove the 'Target' and 'Advanced' tabs from the 'Link' dialog.
    dialogDefinition.removeContents( 'target' );
    dialogDefinition.removeContents( 'advanced' );

    // Get a reference to the 'Link Info' tab.
    var infoTab = dialogDefinition.getContents( 'info' );
        infoTab.remove( 'protocol');
        infoTab.remove('linkType');
    ev.data.definition.resizable = CKEDITOR.DIALOG_RESIZE_NONE;
    }
});
