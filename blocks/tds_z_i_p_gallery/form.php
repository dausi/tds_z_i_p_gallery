<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * ZIP image gallery add-on block form.
 *
 * Copyright 2017, 2018 - TDSystem Beratung & Training - Thomas Dausner
 */

use Concrete\Core\Support\Facade\Application;
use Concrete\Package\TdsZIPGallery\Controller\Form;

/**
 * @var mixed $messages
 * @var string $zipFilePath
 * @var integer $zipFileID
 * @var mixed $zipFileObject
 * @var string $img_title
 * @var string $img_alt
 * @var string $msg
 * @var string $singleName
 * @var boolean $showit
 * @var boolean $inhibitDownload
 * @var string $caption
 * @var integer $subWidth
 * @var integer $subHeight
 * @var integer $singleWidth
 * @var integer $startImg
 * @var integer $flipRate
 * @var integer $numSlides
 * @var boolean $pagination
 * @var integer $spaceBetween
 * @var boolean $imgUnique
 * @var string $subCaption
 */

$app = Application::getFacadeApplication();
$svc = $app->make('helper/concrete/file_manager');
$form = new Form($app);

$single = (isset($singleName) && $singleName != '') ? true : false;
$hidden = $numSlides > 1 ? ' style="display: none;"' : '';

echo '
<div class="ccm-block-tds-zip-gallery">
	<div class="form-group">',
$form->label('zipfile', $messages['zg_zig_file']),
# see /concrete/src/Application/Service/FileManager.php
#    public function app($inputID, $inputName, $chooseText, $preselectedFile = null, $args = [])
$svc->app('zipfile', 'zipFileID', $messages['zg_zipselect'], $zipFileObject),
$form->hidden('zipUrl', $zipFilePath), '
	</div>
	<div class="form-group clearfix flex">
		<div class="inhibit left">',
$form->checkbox('inhibitDownload', $inhibitDownload, $inhibitDownload),
$form->label('inhibitDownload', $messages['zg_inhibitDownload']), '
		</div>
		<div class="preview right"></div>
		<div class="selectNum right">',
$form->button('selectNum', $messages['zg_selSingle'], [], 'btn-primary'),
$form->hidden('singleName', $singleName),
$form->hidden('startImg', $startImg), '
		</div>
	</div>
	<div class="form-group">',
$form->checkbox('showit', $showit, $showit),
$form->label('showit', $messages['zg_show_gall']), '
	</div>
	<div class="showit-cnt">
		<div class="form-group">',
$form->label('subCaption', $messages['zg_sub_caption']),
$form->text('subCaption', $subCaption), '
		</div>
		<div class="form-group last" id="subthumbs">',
$form->label('subWidth', $messages['zg_thumbs_msg']), '
			<div class="clearfix">',
$form->number('subWidth', $subWidth, ["min" => "10", "max" => "200"]), '
				<span class="input-group-addon">x</span>',
$form->number('subHeight', $subHeight, ["min" => "10", "max" => "200"]), '
			</div>
		</div>
	</div>
	<div class="form-group single">',
$form->checkbox('single', $single, $single),
$form->label('single', $messages['zg_single']), '
	</div>
	<div class="single-cnt">
		<div class="form-group">',
$form->label('caption', $messages['zg_caption']),
$form->text('caption', $caption), '
		</div>
		<div class="form-group">',
$form->label('flipRate', $messages['zg_flip_rate']),
$form->number('flipRate', $flipRate, ["min" => "0", "max" => "20", "step" => "0.5"]), '
		</div>
		<div class="flex">
            <div class="form-group left half">',
$form->label('numSlides', $messages['zg_sl_per_view']),
$form->number('numSlides', $numSlides, ["min" => "1", "max" => "20"]), '
            </div>
            <div class="form-group right half">',
$form->label('spaceBetween', $messages['zg_sl_space']),
$form->number('spaceBetween', $spaceBetween, ["min" => "0", "max" => "200"]), '
            </div>
		</div>
		<div class="form-group paginator clearfix">',
$form->checkbox('pagination', $pagination, $pagination),
$form->label('pagination', $messages['zg_pagination']), '
		</div>
		<div class="form-group clearfix">',
$form->checkbox('imgUnique', $imgUnique, $imgUnique),
$form->label('imgUnique', $messages['zg_unique']), '
		</div>
		<div class="form-group">',
$form->label('nav', $messages['zg_navigation']),
$form->select('nav', ['none' => $messages['zg_nav_none'],
    'white' => $messages['zg_nav_white'],
    'black' => $messages['zg_nav_black'],], $nav), '
		</div>
	</div>
    <div class="form-group">',
$form->label('singleWidth', $messages['zg_singleWidth']),
$form->number('singleWidth', $singleWidth), '
	</div>
	<div class="img-info"', $hidden, '>
       <div class="form-group">',
$form->label('img_title', $messages['zg_img_title']),
$form->text('img_title', $img_title), '
        </div>
        <div class="form-group">',
$form->label('img_alt', $messages['zg_img_alt']),
$form->text('img_alt', $img_alt), '
        </div>
    </div>
</div>
';

?>
<script type="text/javascript">
    ( function ( $ ) {
        /**
         *  global CCM_APPLICATION_URL
         *  global ZIPGallery Object
         */
        let setContainer = function ( chkbox ) {
            if ( chkbox === 'showit' )
            {
                if ( $( '#' + chkbox ).is( ':checked' ) )
                {
                    $( '.' + chkbox + '-cnt' ).show( 300 );
                }
                else
                {
                    $( '.' + chkbox + '-cnt' ).hide( 300 );
                }
            }
            else
            { // single
                if ( $( '#' + chkbox ).is( ':checked' ) )
                {
                    $( '.' + chkbox + '-cnt' ).hide( 300 );
                    $( '.' + chkbox + '-img' ).show( 300 );
                }
                else
                {
                    $( '.' + chkbox + '-cnt' ).show( 300 );
                    $( '.' + chkbox + '-img' ).hide( 300 );
                }
            }
        };

        $( document ).ready( function () {

            setContainer( 'single' );
            setContainer( 'showit' );
            $( '#showit, #single' ).change( function ( evt ) {
                setContainer( evt.target.id );
            } );
            $( '#numSlides' ).change( function ( evt ) {
                if ( evt.target.value === 1 )
                {
                    $( '.img-info' ).show( 300 );
                    $( '.paginator' ).hide( 300 ).prop( 'checked', false );
                }
                else
                {
                    $( '.img-info' ).hide( 300 );
                    $( '.paginator' ).show( 300 ).prop( 'checked', true );
                }
            } );

            if ( $( 'input[name=zipFileID]' ).val() === 0 )
            {
                $( '#selectNum' ).prop( 'disabled', 'disabled' );
            }
            //
            // clear single image select on any click
            //
            let clearSel = function () {
                //	- clear thumbnail
                $( '.selectNum div' ).html( '' );
                //	- clear zip file related
                $( 'input[name=zipFileID]' ).val( 0 );
                $( '#zipUrl' ).val( '' );
                //	- clear select related
                $( '#startImg' ).val( -1 );
                $( '#singleName' ).val( '' );
                //	- disable selector
                $( '#selectNum' ).prop( 'disabled', 'disabled' );
            };
            //
            // selector setup
            //
            let setSelector = function ( fID ) {
                $( '#selectNum' ).prop( 'disabled', fID === 0 ? 'disabled' : '' );
                if ( fID > 0 )
                {
                    ConcreteFileManager.getFileDetails( fID, function ( res ) {
                        $.fn.dialog.hideLoader();
                        let file = res.files[0];
                        $( '#zipUrl' ).val( file.url.substr( CCM_APPLICATION_URL.length ) );
                        $( '.ccm-file-selector-file-selected' ).on( 'click', function () {
                            let $d = $( '.ccm-popover-file-menu .popover-inner' );
                            $( 'a[data-file-manager-action=clear]', $d ).on( 'click', clearSel );
                            $( 'a[href*="/replace"]', $d ).closest( 'li' ).remove();
                            $( 'a[data-tree-action="delete-node"]', $d ).closest( 'li' ).remove();
                        } );
                    } );
                }
                else
                {
                    clearSel();
                }
            };
            //
            // file manager handler
            //
            window.Concrete.event.bind( 'FileManagerBeforeSelectFile', function ( e, data ) {
                setSelector( data.fID );
            } );
            setSelector(<?php echo $zipFileID; ?>);
            //
            // get ZIP file data
            //
            let getZIPfileData = function ( success ) {
                let zipUrl = $( '#zipUrl' ).val();
                let ccmUrl = CCM_APPLICATION_URL + '/index.php/ccm/galleries/';
                let infoUrl = ccmUrl + 'getinfo?zip=' + zipUrl + '&tnw=100&tnh=100';
                //
                // get info
                //
                $.ajax( {
                    type: 'GET',
                    url: infoUrl,
                    dataType: 'json',
                    success: success,
                    error: function ( xhr, statusText, err ) {
                        $( 'body, input' ).css( 'cursor', '' );
                        alert( ZIPGallery.messages.zg_load_err.replace( /%s/, zipUrl ) + '<br/>' + statusText + '<br/>' + err.message + '<br/>' + err.stack );
                    }
                } );
            };
            //
            // set thumbnail, if available
            //
            let startImg = $( '#startImg' ).val();
            if ( startImg >= 0 )
            {
                getZIPfileData( function ( info ) {
                    $( '#singleName' ).val( info[startImg].name );
                    $( '.preview' ).html( '<img src="data:image/jpg;base64,' + info[startImg].thumbnail + '"  alt="' + info[startImg].name + '">' );
                } );
            }
            //
            // image selector
            //
            $( '#selectNum, .preview' ).on( 'click', function () {
                $( 'body, input' ).css( 'cursor', 'progress' );
                getZIPfileData( function ( info ) {
                    $( '<div id="imgSelect" class="ui-widget-overlay"><div><div class="label ccm-ui"><label class="control-label">'
                        + ZIPGallery.messages.zg_selectImage + '</label></div><div class="inner ccm-ui" /></div></div>' )
                        .appendTo( document.body );
                    let $inner = $( '#imgSelect div.inner' );
                    let startImg = parseInt( $( '#startImg' ).val() );
                    for ( let idx = 0; idx < info.length; idx++ )
                    {
                        let hi = '';
                        if ( idx === startImg )
                        {
                            hi = ' selected';
                        }
                        $inner.append( '<div class="thumb' + hi + '" data-id="' + idx + '"><img src="data:image/jpg;base64,'
                            + info[idx].thumbnail + '" alt="' + info[idx].name + '"></div>' );
                    }
                    $( '.thumb', $inner ).on( 'click', function () {
                        const idx = $( this ).data( 'id' );
                        $( '#startImg' ).val( idx );
                        $( '#singleName' ).val( info[idx].name );
                        $( '.preview' ).html( '<img src="data:image/jpg;base64,' + info[idx].thumbnail + '"  alt="' + info[idx].name + '">' );
                        $( '#imgSelect' ).remove();
                    } );
                    $( '#imgSelect>div, .ui-widget-overlay' ).on( 'click', function () {
                        $( '#imgSelect' ).remove();
                    } );
                    $( 'body, input' ).css( 'cursor', '' );
                } );
            } );
            //
            // set default caption
            //
            $( '.ccm-block-tds-zip-gallery a.def' ).on( 'click', function ( e ) {
                e.preventDefault();
                $( this ).parent().siblings( 'input' ).val( ZIPGallery.messages.zg_default_caption );
            } );
        } );

    } )( window.jQuery );
</script>