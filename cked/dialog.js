/**
 * CK Editor dialog for ZIP gallery
 * 
 * Copyright 2017 - TDSystem Beratung & Training  - Thomas Dausner (aka dausi)
 */
(function($) {
	
	var validate = function(id, check, msg) {
     	var $div = $('#' + id);
     	var $input = $('input', $div);
     	if (check($input.val())) {
     		$('span', $div).remove();
     		return true;
 		} else {
     		if ($('span', $div).length == 0) {
         		$div.prepend('<span class="cked_error">' + msg + '</span>');
     		}
     		return false;
     	}
 	};
	
	CKEDITOR.dialog.add('zipgalleryDialog', function(editor) {
		var zg_no_zip = '';
	    return {
	        title: zg_messages.zg_edit,
	        minWidth: 400,
	        minHeight: 200,
	        contents: [{
                id: 'data',
                label: 'Link settings',
                elements: [
                    {
                        type: 'text',
                        id: 'link',
                        label: zg_messages.zg_zipurl,
                        validate: function() {
                        	zg_no_zip = '#' + this.domId + ' span';
                        	return validate(this.domId, function(value) {
	                        			return value != '';
	                        		}, zg_messages.zg_url_non_empty)
	                        	&& validate(this.domId, function(value) {
                        			return value.match(/\.zip$/);
                        		}, zg_messages.zg_no_zip);
                        },
                        setup: function(element) {
                        	$('#' + this.domId + ' span').remove();
                        	$('#' + this.domId + ' input').attr('readonly', 'readonly').css('background-color', '#eee');
                            this.setValue(element.getAttribute('href'));
                        }
                    },
                    {
                        type: 'button',
                        id: 'buttonId',
                        label: zg_messages.zg_zipselect,
                        title: 'My title',
                        onClick: function() {
                        	if (zg_no_zip !== '')
                        		$(zg_no_zip).remove();
                            var dialog = this.getDialog();
                            ConcreteFileManager.launchDialog(function(data) {
                                $.fn.dialog.showLoader();
                                ConcreteFileManager.getFileDetails(data.fID, function(r) {
                                    $.fn.dialog.hideLoader();
                                    element = dialog.getContentElement('data', 'link');
                                    element.setValue(r.files[0].url);
                                });
                            });
                        }
                    },
                    {
                        type: 'text',
                        id: 'title',
                        label: zg_messages.zg_linktitle,
                        validate: function() {
                        	return validate(this.domId, function(value) {
                    			return value != '';
                    		}, zg_messages.zg_title_non_empty);
                        },
                        setup: function(element) {
                        	$('#' + this.domId + ' span').remove();
                        	$('#' + this.domId + ' input').focus();
                            this.setValue(element.getText());
                        }
                    }
                ]
	        }],
	        
	        onShow: function() {
	            var selection = editor.getSelection();
	            var element = selection.getStartElement();
	
	            if (element) {
	                element = element.getAscendant('a', true);
	            }
	            if (!element || element.getName() != 'a') {
	                element = editor.document.createElement('a');
	                element.setText(selection._.cache.selectedText);
	                this.insertMode = true;
	            }
	            else {
	                this.insertMode = false;
	            }	
	            this.element = element;
	           	this.setupContent(this.element);
	       },
	       
	       onOk: function() {
				var dialog = this;
				var link = this.element;
	            link.setAttribute('data-cke-saved-href',  dialog.getValueOf('data', 'link'));
	            link.setAttribute('href',  dialog.getValueOf('data', 'link'));
	            link.setAttribute('title', dialog.getValueOf('data', 'title'));
	            link.setText(dialog.getValueOf('data', 'title'));
	
				if (this.insertMode) {
		            link.setAttribute('class', 'gallery');
	                editor.insertElement(link);
				}
	
	       }
	    };
	});
})(jQuery);
