<?php defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Package\TdsZIPGallery\Controller\Gallery;

/**
 * @var $zipFilePath string
 * @var $zipFileID integer
 * @var $img_title string
 * @var $img_alt string
 * @var $msg string
 * @var $singleName string
 * @var $showit boolean
 * @var $inhibitDownload boolean
 * @var $caption string
 * @var $subWidth integer
 * @var $subHeight integer
 * @var $singleWidth integer
 * @var integer $startImg
 * @var integer $flipRate
 * @var integer $numSlides
 * @var boolean $pagination;
 * @var integer $spaceBetween
 * @var boolean $imgUnique
 * @var string $subCaption
 */

if ($zipFilePath != '')
{
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
            $anchor = '<a href="' . $zipFilePath . '" class="single" target="gallery';
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

        $thumb = base64_encode((new Gallery)->getImageFromFileID($zipFileID, $singleName, $singleWidth >= 0 ? $singleWidth : 0));
        echo "<div id='$id' class='ccm-block-tds-zip-gallery'>"
                . "$anchor<img width=\"100%\" src=\"data:image/jpg;base64,$thumb\" alt=\"<?php echo $alt ?>\"/>$anchorEnd";
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
                    const id = '#<?php echo $id; ?>';
                    if ( $( id ).parents( 'div:hidden' ).length <= 0 )
                    {
                        ZIPGallery.init( {
                            id: id,
                            url: '<?php echo $zipFilePath; ?>',
                            zipId: <?php echo $zipFileID; ?>,
                            startImg: <?php echo $startImg; ?>,
                            flipRate: <?php echo $flipRate; ?>,
                            numSlides: <?php echo $numSlides; ?>,
                            pagination: <?php echo $pagination; ?>,
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
