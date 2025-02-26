<?php
$this->layout('layout/default', ['title' => _e('edit_segment')]) ?>

<?php $this->start('page_content') ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-tag me-2 fs-5"></i>
        <h1 class="h4"><?= _e('edit_segment') ?></h1>
    </div>
    <div class="btn-toolbar">
        <button type="submit" form="segmentForm" name="action" value="save" class="btn btn-primary">
            <?= _e('segment_update') ?>
        </button>
    </div>
</div>

<form method="POST" class="needs-validation" id="segmentForm" action="/segment/edit/<?= $segment['id'] ?>">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="name" class="form-label"><?= _e('segment_key') ?></label>
            <input type="text" class="form-control" id="name" name="name"
                value="<?= htmlspecialchars($segment['name']) ?>" disabled>
            <small class="form-text text-muted"><?= _e('segment_key_immutable') ?></small>
        </div>

        <div class="col-12 col-md-6">
            <label for="description" class="form-label"><?= _e('description') ?></label>
            <textarea class="form-control" id="description" name="description" rows="3"><?=
                                                                                        htmlspecialchars($segment['description'] ?? '')
                                                                                        ?></textarea>
        </div>
    </div>
</form>

<?php $this->end() ?>