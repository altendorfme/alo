<!DOCTYPE html>
<html lang="<?= $this->get('lang') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->get('title', 'PushBase') ?></title>
    <style type="text/css">
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        html,
        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .wrapper {
            width: 100%;
            height: 100%;
            table-layout: fixed;
            background-color: #f4f4f4;
            padding: 20px 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .content {
            padding: 20px;
        }

        .header {
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            padding: 0;
            margin: 0;
        }

        .footer {
            background-color: #f1f1f1;
            color: #666;
            text-align: center;
            padding: 10px;
            font-size: 12px;
        }

        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .content {
                padding: 10px !important;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td align="center">
                    <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td class="header">
                                <h1><?= $this->get('title', 'PushBase') ?></h1>
                            </td>
                        </tr>

                        <tr>
                            <td class="content">
                                <?= $this->section('page_content') ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="footer">
                                PushBase <?= date('Y') ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>