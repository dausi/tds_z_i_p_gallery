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
     		$('span.cked_error', $div).remove();
     		return true;
 		} else {
     		if ($('span.cked_error', $div).length == 0) {
         		$div.prepend('<span class="cked_error">' + msg + '</span>');
     		}
     		return false;
     	}
 	};
 	var thumbs = {
 		$width: null,
 		$height: null
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
                        	$('#' + this.domId + ' span.cked_error').remove();
                        	$('#' + this.domId + ' input').attr('readonly', 'readonly').css('background-color', '#eee');
                        	var href = element.getAttribute('href');
                        	if (href !== null)
                        		href = href.replace(/\?.*/, '')
                            this.setValue(href);
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
							var opts = { filters: [ { field: "type", type: 6 } ]};
                            ConcreteFileManager.launchDialog(function(data) {
                                $.fn.dialog.showLoader();
                                ConcreteFileManager.getFileDetails(data.fID, function(r) {
                                    $.fn.dialog.hideLoader();
                                    element = dialog.getContentElement('data', 'link');
                                    element.setValue(r.files[0].url);
                                });
                            }, opts);
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
                        	$('#' + this.domId + ' span.cked_error').remove();
                        	$('#' + this.domId + ' input').focus();
                            this.setValue(element.getText());
                        }
                    },
                    {
                        type: 'text',
                        id: 'imgidx',
                        label: zg_messages.zg_indextitle,
                        validate: function() {
                        	return validate(this.domId, function(value) {
                    			return parseInt(value) > 0;
                    		}, zg_messages.zg_index_gt_zero);
                        },
                        setup: function(element) {
                            var href = element.getAttribute('href');
                            var imgidx = 1;
                            if (href !== null) {
                            	imgidx = href.replace(/.*\?id=/, '');
                                if (imgidx == href) {
                                    imgidx = element.getAttribute('data-index');
                                    if (imgidx === null)
                                    	imgidx = 1;
                                }
                            }
                            this.setValue(imgidx);
                        	$('#' + this.domId + ' span.cked_error').remove();
                        	$('#' + this.domId + ' input')
                        		.attr('type', 'number')
                        		.attr('min', 1)
                        		.attr('value', imgidx);
                        }
                    },
                    {
                        type: 'text',
                        id: 'caption',
                        label: zg_messages.zg_caption,
                        setup: function(element) {
                        	this.setValue(element.getAttribute('data-caption'));
                        	var domId = '#' + this.domId ; 
                        	$(domId + ' label a.def').click(function(e) {
                        		e.preventDefault();
                        		$(domId + ' input').val(zg_messages.zg_default_caption);
                        	});
                        }
                    },
                    {
                        type: 'text',
                        id: 'tnWidth',
                        label: zg_messages.zg_thumbs_msg,
                        validate: function() {
                        	return validate(this.domId, function(value) {
                    			var tw = parseInt(thumbs.$width.val());
                    			var th = parseInt(thumbs.$height.val());
                    			return tw >= 10 && tw <= 200
                    				&& th >= 10 && th <= 200;
                    		}, zg_messages.zg_thumbs_err);
                        },
                        setup: function(element) {
                        	var tnSize = element.getAttribute('data-thumbsize');
                    		var tn = {
                    			width:  50,
                    			height: 50
                    		};
                        	if (tnSize !== null) {
                            	var res = /([0-9]+)x([0-9]+)/.exec(tnSize);
                            	if (res !== null && res.length == 3) {
                            		tn.width  = res[1];
                            		tn.height = res[2];
                            	}
                        	}
                        	var domId = '#' + this.domId ; 
                        	$(domId + ' span.cked_error').remove();
                        	$inp = $(domId + ' input');
                        	if ($inp.length < 2) {
                            	$inp.attr('type', 'number')
	                        		.attr('min', 10)
	                        		.attr('max', 200)
	                        		.css({textAlign: 'center', width: '20%'})
	                        		.parent()
		                        		.append('<span style="padding: 0 12px;">x</span>')
		                        		.append($inp.clone());
                        	}
                        	
                         	thumbs.$width  = $(domId + ' input:first-child').val(tn.width);
                         	thumbs.$height = $(domId + ' input:last-child').val(tn.height);
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
	            link.removeAttributes();
	            var imgidx = parseInt(dialog.getValueOf('data', 'imgidx'));
	            link.setAttribute('data-index', imgidx);
	            link.setAttribute('data-caption', dialog.getValueOf('data', 'caption'));
	            var tn = [
        			thumbs.$width.val(),
                    thumbs.$height.val()
        		];
        		link.setAttribute('data-thumbsize', tn.join('x'));
        		link.setAttribute('href',  dialog.getValueOf('data', 'link'));
	            link.setAttribute('title', dialog.getValueOf('data', 'title'));
	            link.setText(dialog.getValueOf('data', 'title'));
	            link.setAttribute('class', 'gallery');
	            if (this.insertMode) {
	                editor.insertElement(link);
				}
	
	       }
	    };
	});
})(jQuery);
