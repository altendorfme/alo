<?php $this->layout('layout/email', ['title' => $title]); ?>

<?php $this->start('page_content') ?>

<p><?= $line1 ?></p>
<p><?= $emailText ?>: <?= $email ?></p>
<p><?= $passwordText ?>: <?= $password ?></p>
<p><a href='<?= $loginLink ?>'><?= $button ?></a>!</p>

<?php $this->end() ?>