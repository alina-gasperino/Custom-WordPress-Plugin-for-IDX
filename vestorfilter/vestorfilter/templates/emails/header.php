<!doctype html>
<html>
<head>
<title></title>
<style type="text/css">
/* CLIENT-SPECIFIC STYLES */
body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
img { -ms-interpolation-mode: bicubic; }

/* RESET STYLES */
img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
table {
    border-collapse: collapse !important;
    table-layout: fixed;
    width: 100%;
}
table td {
    word-wrap: break-word;
}
body {
    height: 100% !important;
    margin: 0 auto !important;
    padding: 0 !important;
    width: 100% !important;
}

/* iOS BLUE LINKS */
a[x-apple-data-detectors] {
    color: inherit !important;
    text-decoration: none !important;
    font-size: inherit !important;
    font-family: inherit !important;
    font-weight: inherit !important;
    line-height: inherit !important;
}

/* MOBILE STYLES */
@media screen and (max-width: 600px) {
  .img-max {
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
  }

  .max-width {
    max-width: 100% !important;
  }

  .mobile-wrapper {
    width: 85% !important;
    max-width: 85% !important;
  }

  .mobile-padding {
    padding-left: 5% !important;
    padding-right: 5% !important;
  }
}

/* ANDROID CENTER FIX */
div[style*="margin: 16px 0;"] { margin: 0 !important; }
</style>

</head>
<body style="margin: 0 auto !important; padding: 0 !important; background-color: #ffffff; max-width: 800px !important;">

<?php if ( ! empty( $preheader_text ) ) : ?>
<!-- HIDDEN PREHEADER TEXT -->
<div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: Open Sans, Helvetica, Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
    <?= $preheader_text ?>
</div>
<?php endif; ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 10px auto; max-width:800px">
    <tr>
        <?php

          $bgcolor = $background_color ?? '#3b4a69';
          $background_attrs = sprintf( 'bgcolor="%1$s" style="background: %1$s"', $bgcolor );
          if ( ! empty( $background_img ) ) {
            $background_attrs = sprintf( 
              'background="%2$s" bgcolor="%1$s" style="background: %1$s url(\'%2$s\'); background-size: cover; padding: 50px 15px;"', 
              $bgcolor,
              esc_attr( $background_img )
            );
          }

        ?>
        <td align="center" valign="top" width="100%" <?= $background_attrs ?> class="mobile-padding">
            <!--[if (gte mso 9)|(IE)]>
            <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
            <tr>
            <td align="center" valign="top" width="600">
            <![endif]-->
            <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;">
                <?php if ( ! empty( $logo ) ) : ?>
                <tr>
                    <td align="center" valign="top" style="padding: 20px 0 10px 0;">
                        <table valign="center"><tr>
                        <td><a href="<?php echo get_bloginfo( 'url' ); ?>" target="_blank"><img src="<?php echo $logo ?>" border="0" style="display: block;"></a>
                        <?php if ( ! empty( $logo_text ) ) : ?>
                        <td><?= $logo_text ?></td>
                        <?php endif; ?>
                        </tr></table>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td align="center" valign="top" style="padding: 0; font-family: Open Sans, Helvetica, Arial, sans-serif;">
                        <?php if ( ! empty( $header_title ) ) : ?>
                        <h1 style="font-size: 40px; color: #ffffff;"><?= $header_title ?></h1>
                        <?php endif; ?>
                        <?php if ( ! empty( $header_text ) ) : ?>
                        <p style="color: #b7bdc9; font-size: 20px; line-height: 28px; margin: 0 0 20px 0">
                            <?= $header_text ?>
                        </p>
                        <?php endif; ?>
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
    <tr>
        <td align="center" height="100%" valign="top" width="100%" bgcolor="#f6f6f6" style="padding: 50px 15px;" class="mobile-padding">