<?php $this->layout('layout/email', ['title' => $title]); ?>

<?php $this->start('page_content') ?>

<p><?= $line1 ?></p>
<p><?= $email ?>: <?= $email ?></p>
<p><?= $password ?>: <?= $password ?></p>
<p><a href='<?= $loginLink ?>'><?= $button ?></a>!</p>

<?php $this->end() ?>