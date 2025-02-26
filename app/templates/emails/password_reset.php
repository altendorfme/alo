<?php $this->layout('layout/email', ['title' => $title]); ?>

<?php $this->start('page_content') ?>

<p><?= $line1 ?></p>
<p><a href='<?= $resetLink ?>'><?= $button ?></a></p>
<p><?= $line2 ?></p>
<p><?= $line3 ?></p>

<?php $this->end() ?>