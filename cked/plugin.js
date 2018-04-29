/**
 * CK Editor plugin for ZIP gallery
 * 
 * Copyright 2017 - TDSystem Beratung & Training  - Thomas Dausner (aka dausi)
 */
/* global CKEDITOR */
/* global zg_messages */
CKEDITOR.plugins.add('tds_z_i_p_gallery', {
    //icons: 'zipgallery',
    init: function( editor ) {
    	
        editor.addCommand('zipgallery', new CKEDITOR.dialogCommand('zipgalleryDialog'));
        editor.ui.addButton('Zipgallery', {
            label:	 zg_messages.zg_add,
            command: 'zipgallery',
            toolbar: 'insert'
        });

        if (editor.contextMenu) {
            editor.addMenuGroup('zgalGroup');
            editor.addMenuItem('zgalItem', {
                label:	 zg_messages.zg_edit,
                //icon:	 this.path + 'icons/zipgallery.png',
                command: 'zipgallery',
                group:   'zgalGroup'
            });

            editor.contextMenu.addListener(function(element) {
                if (element.getAscendant('a', true) ) {
                    return {
                    	zgalItem: CKEDITOR.TRISTATE_OFF
                    };
                }
            });
        }

        CKEDITOR.dialog.add('zipgalleryDialog', this.path + 'dialog.js');
    }
});
