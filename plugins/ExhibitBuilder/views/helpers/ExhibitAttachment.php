<?php

/**
 * Exhibit attachment view helper.
 * 
 * @package ExhibitBuilder\View\Helper
 */
class ExhibitBuilder_View_Helper_ExhibitAttachment extends Zend_View_Helper_Abstract
{
    /**
     * Return the markup for displaying an exhibit attachment.
     *
     * @param ExhibitBlockAttachment $attachment
     * @param array $fileOptions Array of options for file_markup
     * @param array $linkProps Array of options for exhibit_builder_link_to_exhibit_item
     * @param boolean $forceImage Whether to display the attachment as an image
     *  always Defaults to false.
     * @return string
     */ 
    public function exhibitAttachment($attachment, $fileOptions = array(), $linkProps = array(), $forceImage = false, $showTitle = false)
    {
        $item = $attachment->getItem();
        $file = $attachment->getFile();

        if ($file) {
            if ($forceImage) {
                $imageSize = isset($fileOptions['imageSize'])
                    ? $fileOptions['imageSize']
                    : 'square_thumbnail';
                $imageAttr = isset($fileOptions['imgAttributes'])
                    ? $fileOptions['imgAttributes']
                    : array();
                $image = file_image($imageSize, $imageAttr, $file);
                $html = exhibit_builder_link_to_exhibit_item($image, $linkProps, $item);
            } else {
                if (!isset($fileOptions['linkAttributes']['href'])) {
                    $fileOptions['linkAttributes']['href'] = exhibit_builder_exhibit_item_uri($item);
                }
                $html = file_markup($file, $fileOptions, null);
            }
        } else if($item) {
            $html = exhibit_builder_link_to_exhibit_item(null, $linkProps, $item);
        }


        // Don't show a caption if we couldn't show the Item or File at all
        if (isset($html)) {
            $captionHtml = $this->view->exhibitAttachmentCaption($attachment);
            if ($showTitle || $captionHtml !== '') {
                $html .= '<div class="slide-meta">';
                if ($showTitle) {
                    $html .= '<p class="slide-title">' . exhibit_builder_link_to_exhibit_item(null, $linkProps, $item) . '</p>';
                }
                $html .= $captionHtml;
                $html .= '</div>';
            }
        } else {
            $html = '';
        }

        return apply_filters('exhibit_attachment_markup', $html,
            compact('attachment', 'fileOptions', 'linkProps', 'forceImage')
        );
    }

    /**
     * Return the markup for an attachment's caption.
     *
     * @param ExhibitBlockAttachment $attachment
     * @return string
     */
    protected function _caption($attachment)
    {
        if (!is_string($attachment['caption']) || $attachment['caption'] == '') {
            return '';
        }

        $html = '<div class="exhibit-item-caption">'
              . $attachment['caption']
              . '</div>';

        return apply_filters('exhibit_attachment_caption', $html, array(
            'attachment' => $attachment
        ));
    }
}
