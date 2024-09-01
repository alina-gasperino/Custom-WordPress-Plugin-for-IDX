<tr>
    <td align="center" valign="top" style="padding: 0 0 25px 0; font-family: Open Sans, Helvetica, Arial, sans-serif;">
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <?php
                
                if ( ! empty( $img_src ) ) {
                    \VestorFilter\Util\Template::get_part( 'vestorfilter', 'emails/content-section-image-header', [
                        'section_img_src' => $img_src,
                        'section_title' => $title ?? '',
                    ] );
                }
            
            ?>
            <tr>
                <td align="center" bgcolor="#ffffff" style="border-radius: 0 0 3px 3px; padding: 25px;">
                    <table cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <td align="center" style="font-family: Open Sans, Helvetica, Arial, sans-serif;">
                                <?php if ( ! empty( $title ) ) : ?>
                                    <h2 style="font-size: 20px; color: #444444; margin: 0; padding-bottom: 10px;"><?= $title ?></h2>
                                <?php endif; ?><?php if ( ! empty( $content ) ) : ?>
                                    <div style="color: #999999; font-size: 16px; line-height: 24px; margin: 0;">
                                        <?php echo apply_filters( 'the_content', $content ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            
                            if ( ! empty( $link_href ) ) {
                                \VestorFilter\Util\Template::get_part( 'vestorfilter', 'emails/content-section-link', [
                                    'section_link_href' => $link_href,
                                    'section_link_label' => $link_label ?? 'Read more',
                                ] );
                            }
                        
                        ?>
                        <tr>
                            <td>
                                <div style="padding: 40px 0 40px; text-align: center; opacity: .6"><?php echo $set_filters; ?></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>