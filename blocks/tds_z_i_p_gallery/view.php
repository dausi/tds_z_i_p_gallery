<?php defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Http\Request;
use Concrete\Package\TdsZIPGallery\Src\ZipGallery;
use Concrete\Package\TdsZIPGallery\Controller\Galleries;

if (is_object($zf) && $zf->getFileID())
{
    $zipUrl = $zf->getURL();
    $zfId = $zf->getFileID();
    $id = "ccm-block-tds-zip-gallery-" . $bID;
    $title = $img_title == '' ? $msg : $img_title;
    $alt = h($img_alt == '' ? $msg : $img_alt);
    $titleAlt = ' title="' . h($title) . '" alt="' . $alt . '"';

    if ($singleName != '')
    {
        $anchor = '';
        $anchorEnd = '';
        if ($showit)
        {
            $anchor = '<a href="' . $zipUrl . '" target="gallery';
            if ($inhibitDownload)
                $anchor .= '-' . $bID;
            $anchor .= '"';
            if ($caption != '')
                $anchor .= ' data-caption="' . str_replace("'", "\\'", $caption) . '"';
            if ($subWidth != 0 || $subHeight != 0)
            {
                $anchor .= ' data-thumbsize="';
                $anchor .= $subWidth == 0 ? 50 : $subWidth;
                $anchor .= 'x';
                $anchor .= $subHeight == 0 ? 50 : $subHeight;
                $anchor .= '"';
            }
            $anchor .= $titleAlt . '>';
            $anchorEnd = '</a>';
        }

        $server = Request::getInstance()->server;
        $offset = strlen(($server->get('HTTPS') != '' ? 'https://' : 'http://') . $server->get('HTTP_HOST'));
        $gl = (new Galleries)->setParams(substr($zipUrl, $offset), $zfId);
        $zip = new ZipGallery($gl, false);
        $thumb = base64_encode($zip->getFile($singleName, $singleWidth >= 0 ? $singleWidth : 0));
        echo "<div id='$id' class='ccm-block-tds-zip-gallery'>"
                . "$anchor<img width=\"100%\" src=\"data:image/jpg;base64,$thumb\" />$anchorEnd";
        if ($img_alt != '')
        {
            echo "<div class='image-title'><p>$alt</p></div>";
        }
        echo "</div>";
    }
    else
    {
        ?>
        <div id="<?php echo $id; ?>" class="ccm-block-tds-zip-gallery"<?php echo $titleAlt; ?>></div>
        <script type="text/javascript">
            ( function ( $ ) {
                /* global ZIPGallery */
                $( document ).ready( function () {
                    var id = '#<?php echo $id; ?>';
                    var n = $( id ).parents( 'div:hidden' );
                    if ( $( id ).parents( 'div:hidden' ).length <= 0 )
                    {
                        ZIPGallery.init( {
                            id: id,
                            url: '<?php echo $zipUrl; ?>',
                            zipId: <?php echo $zfId; ; ?>,
                            startImg: <?php echo $startImg; ?>,
                            flipRate: <?php echo $flipRate; ?>,
                            numSlides: <?php echo $numSlides; ?>,
                            spaceBetween: <?php echo $spaceBetween; ?>,
                            caption: '<?php echo $caption; ?>',
                            imgUnique: <?php echo $imgUnique; ?>,
                            showNav: '<?php echo $nav; ?>',
                            showit: <?php echo $showit; ?>,
                            subCaption: '<?php echo h($subCaption); ?>',
                            subWidth: <?php echo $subWidth; ?>,
                            subHeight:  <?php echo $subHeight; ?>,
                            inhibitDownload: <?php echo $inhibitDownload?>
                        } );
                    }
                } );
            } )( window.jQuery );
        </script>
        <?php
    }
} elseif (isset($c) && $c->isEditMode())
{
    echo '<div class="ccm-edit-mode-disabled-item">', t('Empty ZIP Image Gallery block.'), '</div>';
}

?>
