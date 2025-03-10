<?php $this->layout('layout/email', ['title' => $title]); ?>

<?php $this->start('page_content') ?>

<tr>
    <td class="header">
        <h1><?= $title ?></h1>
    </td>
</tr>

<tr>
    <td class="content">
        <p><?= $line1 ?></p>
        <p><?= $emailText ?>: <b><?= $email ?></b></p>
        <p><?= $passwordText ?>: <b><?= $password ?></b></p>
        <p><b><a href='<?= $loginLink ?>'><?= $button ?></a>!</b></p>
    </td>
</tr>

<?php $this->end() ?>