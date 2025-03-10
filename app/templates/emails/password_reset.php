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
        <p><b><a href='<?= $resetLink ?>'><?= $button ?></a></b></p>
        <p><?= $line2 ?></p>
        <p><?= $line3 ?></p>
    </td>
</tr>

<?php $this->end() ?>