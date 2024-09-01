<!--[if (gte mso 9)|(IE)]>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
<tr>
<td align="center" valign="top" width="600">
<![endif]-->
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;">

	<?= $body_contents ?>

    <?php if ( ! empty( $footer_text ) ) : ?>
        <tr>
            <td align="center" valign="top" style="padding: 25px 0 0 0; font-family: Open Sans, Helvetica, Arial, sans-serif;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:500px">
                    <tr>
                        <td align="center">
                            <?= apply_filters( 'the_content', $footer_text ) ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php endif; ?>

</table>