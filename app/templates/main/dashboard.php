<?php $this->layout('layout/default', ['title' => _e('dashboard')]); ?>

<?php $this->start('page_content') ?>

<!-- Dashboard Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-speedometer2 me-2 fs-5"></i>
        <h1 class="h4"><?= _e('dashboard') ?></h1>
    </div>
</div>

<!-- Key Metrics Section -->
<div class="row">
    <!-- Subscribers Card -->
    <div class="col-12 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-people-fill me-2"></i>
                    <?= _e('subscribers') ?>
                </h5>
                <h2 class="metric-value"><?= number_format($subscribers['active']) ?></h2>
                <div class="row mt-2">
                    <div class="col-6 col-md-3">
                        <small class="text-muted"><?= _e('inactive') ?>: <?= number_format($subscribers['status']['inactive']) ?></small>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted"><?= _e('unsubscribed') ?>: <?= number_format($subscribers['status']['unsubscribed']) ?></small>
                    </div>
                    <div class="col-6 col-12">
                        <small class="text-muted"><?= _e('total') ?>: <?= number_format($subscribers['status']['total']) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaigns Card -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-megaphone-fill me-2"></i>
                    <?= _e('campaigns') ?>
                </h5>
                <h2 class="metric-value"><?= number_format($campaigns['total']) ?></h2>
                <div class="row mt-2">
                    <?php foreach (['draft', 'scheduled', 'sent', 'sending', 'cancelled'] as $status) { ?>
                        <div class="col-6 col-md-4">
                            <small class="text-muted">
                                <?= _e('status_' . $status) ?>: <?= number_format($campaigns['status'][$status] ?? 0) ?>
                            </small>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subscribers Trend Chart -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>
                    <?= _e('subscribers_status_trend') ?>
                </h5>
            </div>
            <div class="card-body" style="height: 400px;">
                <canvas id="subscribersTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Campaigns Section -->
<?php if (!empty($campaigns['recent'])) { ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        <?= _e('recent_campaigns') ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= _e('campaign_name') ?></th>
                                    <th><?= _e('status') ?></th>
                                    <th><?= _e('history') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaigns['recent'] as $campaign) { ?>
                                    <tr>
                                        <td class="text-nowrap">
                                            <a class="link-secondary" href="/campaign/analytics/<?= $campaign['id']; ?>"><?= htmlspecialchars($campaign['name']) ?></a><br />
                                            <?= htmlspecialchars($campaign['push_title']) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?=
                                                                match ($campaign['status'] ?? '') {
                                                                    'draft' => 'bg-secondary',
                                                                    'scheduled' => 'bg-primary',
                                                                    'sent' => 'bg-success',
                                                                    'sending' => 'bg-info',
                                                                    'cancelled' => 'bg-danger',
                                                                    'queuing' => 'bg-warning',
                                                                    default => 'bg-secondary'
                                                                }
                                                                ?>">
                                                <?= _e('status_' . $campaign['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <?= _e('created_at') ?>: <?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?><br />
                                            <?= _e('ended_at') ?>: <?= date('Y-m-d H:i', strtotime($campaign['ended_at'])) ?>
                                        </td>
                                        <td>
                                            <a href="/campaign/analytics/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _e('analytics') ?>">
                                                <i class="bi bi-graph-up"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<!-- Segment Analytics Charts -->
<div class="row mt-4">
    <div class="col-12 col-md-3 mb-4">
        <div class="card">
            <div class="card-header"><?= _e('browser') ?></div>
            <div class="card-body">
                <canvas id="browserChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3 mb-4">
        <div class="card">
            <div class="card-header"><?= _e('operating_system') ?></div>
            <div class="card-body">
                <canvas id="osChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3 mb-4">
        <div class="card">
            <div class="card-header"><?= _e('device_type') ?></div>
            <div class="card-body">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3 mb-4">
        <div class="card">
            <div class="card-header"><?= _e('country') ?></div>
            <div class="card-body">
                <canvas id="countryChart"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row" style="<?= isset($segments['category']['data']) && empty($segments['category']['data']) ?? 'display: none;'?>">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><?= _e('category') ?></div>
            <div class="card-body" style="height: 400px;">
                <canvas id="categoryChart"></canvas>
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
                textColor: isDarkMode() ? 'rgba(255, 255, 255, 0.8)' : 'rgba(0, 0, 0, 0.8)',
                pieColors: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ]
            };
        }
        const themeColors = getThemeColors();
        
        const chartInstances = [];
        function createPieChart(canvasId, labels, data) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            const themeColors = getThemeColors();
            
            const chartInstance = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: themeColors.pieColors,
                        borderWidth: 1,
                        borderColor: isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: themeColors.textColor,
                                filter: function(legendItem, chartData) {
                                    let pairs = [];
                                    for (let i = 0; i < chartData.labels.length; i++) {
                                        pairs.push({ label: chartData.labels[i], value: chartData.datasets[0].data[i], index: i });
                                    }
                                    pairs.sort((a, b) => b.value - a.value);
                                    const topIndices = pairs.slice(0, 5).map(pair => pair.index);
                                    return topIndices.includes(legendItem.index);
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            chartInstances.push(chartInstance);
            return chartInstance;
        }
        
        createPieChart(
            'browserChart',
            <?= json_encode($segments['browser_name']['labels']) ?>,
            <?= json_encode($segments['browser_name']['data']) ?>
        );

        createPieChart(
            'osChart',
            <?= json_encode($segments['os_name']['labels']) ?>,
            <?= json_encode($segments['os_name']['data']) ?>
        );

        createPieChart(
            'deviceChart',
            <?= json_encode(array_map('ucfirst', $segments['device_type']['labels'])) ?>,
            <?= json_encode($segments['device_type']['data']) ?>
        );

        createPieChart(
            'countryChart',
            <?= json_encode(array_map('ucfirst', $segments['country']['labels'])) ?>,
            <?= json_encode($segments['country']['data']) ?>
        );

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map('ucfirst', $segments['category']['labels'])) ?>,
                datasets: [{
                    data: <?= json_encode($segments['category']['data']) ?>,
                    backgroundColor: themeColors.pieColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const labels = context[0].chart.data.labels;
                                const data = context[0].chart.data.datasets[0].data;

                                let pairs = [];
                                for (let i = 0; i < labels.length; i++) {
                                    pairs.push({ label: labels[i], value: data[i], index: i });
                                }

                                return labels[context[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '<?= _e('total') ?>',
                            color: themeColors.textColor
                        },
                        grid: {
                            color: themeColors.gridColor
                        },
                        ticks: {
                            color: themeColors.textColor,
                            callback: function(value, index) {
                                const labels = this.chart.data.labels;
                                const data = this.chart.data.datasets[0].data;

                                let pairs = [];
                                for (let i = 0; i < labels.length; i++) {
                                    pairs.push({ label: labels[i], value: data[i], index: i });
                                }

                                pairs.sort((a, b) => b.value - a.value);

                                const topIndices = pairs.slice(0, 20).map(pair => pair.index);

                                if (topIndices.includes(index)) {
                                    return labels[index];
                                }
                                return '';
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: '<?= _e('category') ?>',
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
        chartInstances.push(categoryChart);

        const subscribersCtx = document.getElementById('subscribersTrendChart').getContext('2d');
        const subscribersChart = new Chart(subscribersCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($subscribers_trend['dates'] ?? [], 'date')) ?>,
                datasets: [
                    {
                        label: '<?= _e('active') ?>',
                        data: <?= json_encode(array_column($subscribers_trend['data'] ?? [], 'active')) ?>,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.05)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: '<?= _e('inactive') ?>',
                        data: <?= json_encode(array_column($subscribers_trend['data'] ?? [], 'inactive')) ?>,
                        borderColor: 'rgba(255, 193, 7, 1)',
                        backgroundColor: 'rgba(255, 193, 7, 0.05)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: '<?= _e('unsubscribed') ?>',
                        data: <?= json_encode(array_column($subscribers_trend['data'] ?? [], 'unsubscribed')) ?>,
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.05)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: themeColors.textColor
                        }
                    },
                    title: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: false
                        },
                        grid: {
                            color: themeColors.gridColor
                        },
                        ticks: {
                            color: themeColors.textColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '<?= _e('subscribers_count') ?>',
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
        chartInstances.push(subscribersChart);
        
        const darkModeSwitch = document.getElementById('darkModeSwitch');
        if (darkModeSwitch) {
            darkModeSwitch.addEventListener('change', function() {
                setTimeout(() => {
                    const newThemeColors = getThemeColors();
                    
                    chartInstances.forEach(chart => {
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
                            
                            if (chart.config.type === 'pie') {
                                chart.data.datasets.forEach(dataset => {
                                    dataset.borderColor = isDarkMode() ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                                });
                            }
                        }
                        
                        chart.update();
                    });
                }, 50);
            });
        }
    });
</script>
<?php $this->end() ?>