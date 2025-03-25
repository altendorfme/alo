<?php
$this->layout('layout/default', ['title' => htmlspecialchars($campaign['name']) . ' ' . _e('analytics')]);

$statusClass = match ($campaign['status']) {
    'draft' => 'bg-secondary',
    'scheduled' => 'bg-primary',
    'sent' => 'bg-success',
    'sending' => 'bg-info',
    'cancelled' => 'bg-danger',
    'queuing' => 'bg-warning',
    default => 'bg-secondary'
};
?>

<?php $this->start('page_content') ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-graph-up-arrow me-2 fs-5"></i>
        <h1 class="h4"><?= htmlspecialchars($campaign['name']) ?></h1>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6">
        <div class="card mb-4">
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        <tr>
                            <th><?= _e('push_title') ?></th>
                            <td>
                                <?php if (empty($campaign['push_url'])) { ?>
                                    <?= htmlspecialchars($campaign['push_title']) ?>
                                <?php } else { ?>
                                    <a class="link-secondary" href="<?= htmlspecialchars($campaign['push_url']) ?>" target="_blank">
                                        <?= htmlspecialchars($campaign['push_title']) ?>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= _e('push_body') ?></th>
                            <td><?= !empty($campaign['push_body']) ? htmlspecialchars($campaign['push_body']) : '<em>' . _e('not_specified') . '</em>' ?></td>
                        </tr>
                        <?php if (!empty($campaign['push_icon'])) { ?>
                            <tr>
                                <th><?= _e('push_icon_url') ?></th>
                                <td>
                                    <img src="<?= htmlspecialchars($campaign['push_icon']) ?>" class="w-auto img-thumbnail" style="max-height: 50px">
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($campaign['push_image'])) { ?>
                            <tr>
                                <th><?= _e('push_image_url') ?></th>
                                <td>
                                    <img src="<?= htmlspecialchars($campaign['push_image']) ?>" class="w-auto img-thumbnail" style="max-height: 150px">
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($campaign['push_badge'])) { ?>
                            <tr>
                                <th><?= _e('push_badge_url') ?></th>
                                <td>
                                    <img src="<?= htmlspecialchars($campaign['push_badge']) ?>" class="w-auto img-thumbnail" style="max-height: 30px"">
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <th><?= _e('require_interaction') ?></th>
                            <td><?= $campaign['push_requireInteraction'] ? _e('yes') : _e('no') ?></td>
                        </tr>
                        <tr>
                            <th><?= _e('renotify') ?></th>
                            <td><?= $campaign['push_renotify'] ? _e('yes') : _e('no') ?></td>
                        </tr>
                        <tr>
                            <th><?= _e('silent') ?></th>
                            <td><?= $campaign['push_silent'] ? _e('yes') : _e('no') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header fw-bold">
                                            <?= _e('details') ?>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-striped mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th><?= _e('status') ?></th>
                                                        <td>
                                                            <span class="badge <?= $statusClass ?>">
                                                                <?= _e('status_' . $campaign['status']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('scheduled_for') ?></th>
                                                        <td><?= !empty($campaign['send_at']) ? htmlspecialchars($campaign['send_at']) : '<em>' . _e('not_specified') . '</em>' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('started_at') ?></th>
                                                        <td><?= !empty($campaign['started_at']) ? htmlspecialchars($campaign['started_at']) : '<em>' . _e('not_specified') . '</em>' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('ended_at') ?></th>
                                                        <td><?= !empty($campaign['ended_at']) ? htmlspecialchars($campaign['ended_at']) : '<em>' . _e('not_specified') . '</em>' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('created_by') ?></th>
                                                        <td>
                                                            <?php if (!empty($campaign['created_by'])) { ?>
                                                                <?= htmlspecialchars($campaign['created_by_email']) ?> (<?= htmlspecialchars($campaign['created_by']) ?>)
                                                            <?php } else { ?>
                                                                <em><?= _e('not_specified') ?></em>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('created_at') ?></th>
                                                        <td><?= !empty($campaign['created_at']) ? htmlspecialchars($campaign['created_at']) : '<em>' . _e('not_specified') . '</em>' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('updated_by') ?></th>
                                                        <td>
                                                            <?php if (!empty($campaign['updated_by'])) { ?>
                                                                <?= htmlspecialchars($campaign['updated_by_email']) ?> (<?= htmlspecialchars($campaign['updated_by']) ?>)
                                                            <?php } else { ?>
                                                                <em><?= _e('not_specified') ?></em>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('updated_at') ?></th>
                                                        <td><?= !empty($campaign['updated_at']) ? htmlspecialchars($campaign['updated_at']) : '<em>' . _e('not_specified') . '</em>' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= _e('segments') ?></th>
                                                        <td>
                                                            <?php if (!empty($campaign['segments'])) {
                                                                $segments = json_decode($campaign['segments'], true);
                                                                if (is_array($segments) && count($segments) > 0) {
                                                                    foreach ($segments as $segment) {
                                                                        if (isset($segment['type']) && isset($segment['values']) && is_array($segment['values'])) {
                                                                            $segmentInfo = \alo\Database\Database::getInstance()->queryFirstRow(
                                                                                "SELECT name, description FROM segments WHERE id = %i",
                                                                                $segment['type']
                                                                            );
                                                                            
                                                                            $segmentName = $segmentInfo['description'] ? $segmentInfo['description'] : $segmentInfo['name'];
                                                                            ?>
                                                                                <p class="m-0">
                                                                                    <strong><?= htmlspecialchars($segmentName) ?>: </strong>
                                                                                    <?= htmlspecialchars(implode(', ', $segment['values'])) ?>
                                                                                </p>
                                                                            <?php
                                                                        }
                                                                    }
                                                                } else {
                                                                    echo '<em>' . _e('not_specified') . '</em>';
                                                                }
                                                            } else { ?>
                                                                <em><?= _e('not_specified') ?></em>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="card mb-4">
                                        <div class="card-header fw-bold">
                                            <?= _e('results') ?>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="row pt-4 pb-3 ps-3 pe-3 g-4 text-center">
                                                <?php
                                                $totalClicked = !empty($results['clicked_count']) ? $results['clicked_count'] : 0;
                                                $totalRecipients = !empty($results['total_recipients']) ? $results['total_recipients'] : 1;
                                                $deliveredCount = !empty($results['successfully_count']) ? $results['successfully_count'] : 1;
                                                $errorCount = !empty($results['error_count']) ? $results['error_count'] : 1;
                                                $totalClickedPercentage = round(($totalClicked / $totalRecipients) * 100, 2);
                                                $totalDeliveredPercentage = round(($deliveredCount / $totalRecipients) * 100, 2);
                                                $totalErrorPercentage = round(($errorCount / $totalRecipients) * 100, 2);
                                                ?>
                                                <div class="col-12 col-md-6">
                                                    <div class="badge text-bg-primary fs-2">
                                                        <?= number_format($totalRecipients) ?>
                                                    </div>
                                                    <div class="d-block pt-1"><?= _e('total') ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <div class="badge text-bg-success fs-2">
                                                        <?= number_format($deliveredCount) ?>
                                                    </div>
                                                    <div class="d-block pt-1"><?= _e('successfully') ?></div>
                                                    <small class="d-block text-muted">
                                                        <?= $totalDeliveredPercentage ?>% <?= _e('of_total') ?>
                                                    </small>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <div class="badge text-bg-danger fs-2">
                                                        <?= number_format($errorCount) ?>
                                                    </div>
                                                    <div class="d-block pt-1"><?= _e('error') ?></div>
                                                    <small class="d-block text-muted">
                                                        <?= $totalErrorPercentage ?>% <?= _e('of_total') ?>
                                                    </small>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <div class="badge text-bg-info fs-2">
                                                        <?= number_format($totalClicked) ?>
                                                    </div>
                                                    <div class="d-block pt-1">
                                                        <?= _e('clicked') ?>
                                                        <small class="d-block text-muted">
                                                            <?= $totalClickedPercentage ?>% <?= _e('of_total') ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header fw-bold">
                        <?= _e('interaction_timeline') ?>
                    </div>
                    <div class="card-body">
                        <canvas id="interactionTimeline" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->end() ?>

        <?php $this->start('page_scripts') ?>
        <script>
            <?php
            $chartPath = "/dist/scripts/chart.min.js";
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $chartPath)) {
                include $_SERVER['DOCUMENT_ROOT'] . $chartPath;
            }
            ?>
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function isDarkMode() {
                    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
                }
                
                function getThemeColors() {
                    return {
                        gridColor: isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                        textColor: isDarkMode() ? 'rgba(255, 255, 255, 0.8)' : 'rgba(0, 0, 0, 0.8)'
                    };
                }
                
                const ctx = document.getElementById('interactionTimeline').getContext('2d');
                const themeColors = getThemeColors();
                const labels = <?= json_encode($interactionTimeline['labels']) ?>.map(dateStr => dateStr);

                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: '<?= _e('sent') ?>',
                                data: <?= json_encode($interactionTimeline['datasets']['sent']) ?>,
                                backgroundColor: 'rgba(0, 0, 255, 0.6)',
                                borderColor: 'blue',
                                borderWidth: 1
                            },
                            {
                                label: '<?= _e('delivered') ?>',
                                data: <?= json_encode($interactionTimeline['datasets']['delivered']) ?>,
                                backgroundColor: 'rgba(0, 255, 0, 0.6)',
                                borderColor: 'green',
                                borderWidth: 1
                            },
                            {
                                label: '<?= _e('clicked') ?>',
                                data: <?= json_encode($interactionTimeline['datasets']['clicked']) ?>,
                                backgroundColor: 'rgba(255, 165, 0, 0.6)',
                                borderColor: 'orange',
                                borderWidth: 1
                            },
                            {
                                label: '<?= _e('failed') ?>',
                                data: <?= json_encode($interactionTimeline['datasets']['failed']) ?>,
                                backgroundColor: 'rgba(255, 0, 0, 0.6)',
                                borderColor: 'red',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: false,
                            },
                            legend: {
                                labels: {
                                    color: themeColors.textColor
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '<?= _e('interactions') ?>',
                                    color: themeColors.textColor
                                },
                                grid: {
                                    color: themeColors.gridColor
                                },
                                ticks: {
                                    color: themeColors.textColor
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: '<?= _e('date_and_time') ?>',
                                    color: themeColors.textColor
                                },
                                grid: {
                                    color: themeColors.gridColor
                                },
                                ticks: {
                                    color: themeColors.textColor
                                }
                            }
                        }
                    }
                });
                
                const darkModeSwitch = document.getElementById('darkModeSwitch');
                if (darkModeSwitch) {
                    darkModeSwitch.addEventListener('change', function() {
                        setTimeout(() => {
                            const newThemeColors = getThemeColors();
    
                            if (chart.options.plugins.legend) {
                                chart.options.plugins.legend.labels.color = newThemeColors.textColor;
                            }
                            
                            if (chart.options.scales) {
                                if (chart.options.scales.x) {
                                    chart.options.scales.x.grid.color = newThemeColors.gridColor;
                                    chart.options.scales.x.ticks.color = newThemeColors.textColor;
                                    if (chart.options.scales.x.title) {
                                        chart.options.scales.x.title.color = newThemeColors.textColor;
                                    }
                                }
                                
                                if (chart.options.scales.y) {
                                    chart.options.scales.y.grid.color = newThemeColors.gridColor;
                                    chart.options.scales.y.ticks.color = newThemeColors.textColor;
                                    if (chart.options.scales.y.title) {
                                        chart.options.scales.y.title.color = newThemeColors.textColor;
                                    }
                                }
                            }
                            
                            chart.update();
                        }, 50);
                    });
                }
            });
        </script>
        <?php $this->end() ?>