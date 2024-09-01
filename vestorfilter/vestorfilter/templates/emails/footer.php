<!--[if (gte mso 9)|(IE)]>
</td>
</tr>
</table>
<![endif]-->
</td>
</tr>
<tr>
    <td align="center" height="100%" valign="top" width="100%" bgcolor="#f6f6f6" style="padding: 0 15px 40px 15px;">
        <!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
            <tr>
                <td align="center" valign="top" width="600">
        <![endif]-->
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;">
            <?php if ( ! empty( $logo ) ) : ?>
                <tr>
                    <td align="center" valign="top" style="padding: 0 0 5px 0;">
                        <a href="<?php echo get_bloginfo( 'url' ); ?>" target="_blank"><img src="<?= $logo ?>" width="200" border="0" style="display: block;"></a>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td align="center" valign="top" style="padding: 0; font-family: Open Sans, Helvetica, Arial, sans-serif; color: #999999;">
                    <p style="font-size: 14px; line-height: 20px;">
                        <?php if ( ! empty( $address ) ) echo str_replace( "\n", '<br>', $address ); ?>
                        
                        <?php if ( ! empty( $unsubscribe_url) ) : ?>
                            <br><br>
                            <a href="<?= $unsubscribe_url ?>" style="color: #999999;" target="_blank">Unsubscribe</a>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
        </table>
        <!--[if (gte mso 9)|(IE)]>
        </td>
        </tr>
        </table>
        <![endif]-->
    </td>
</tr>
</table>
</body>
</html>
