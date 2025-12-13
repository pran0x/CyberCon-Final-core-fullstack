<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0,
    'duplicate' => 0
];

if ($conn) {
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'duplicate' THEN 1 ELSE 0 END) as duplicate
    FROM registrations";
    $result = $conn->query($statsQuery)->fetch();
    if ($result) {
        $stats = $result;
    }
}

// Get batch-wise statistics
$batchQuery = "SELECT batch, COUNT(*) as count FROM registrations 
               WHERE batch IS NOT NULL AND batch != '' 
               GROUP BY batch 
               ORDER BY batch";
$batchStmt = $conn->query($batchQuery);
$batchStats = $batchStmt->fetchAll();

// Get day-wise statistics (last 30 days)
$dayQuery = "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM registrations 
             WHERE created_at >= DATE('now', '-30 days')
             GROUP BY DATE(created_at) 
             ORDER BY date ASC";
$dayStmt = $conn->query($dayQuery);
$dayStats = $dayStmt->fetchAll();

// Get batch-section wise statistics grouped by batch
$batchesQuery = "SELECT DISTINCT batch FROM registrations 
                 WHERE batch IS NOT NULL AND batch != '' 
                 ORDER BY batch";
$batchesStmt = $conn->query($batchesQuery);
$batches = $batchesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get section data for each batch
$batchSectionData = [];
foreach ($batches as $batch) {
    $sectionQuery = "SELECT 
                        COALESCE(section, 'N/A') as section,
                        COUNT(*) as count 
                     FROM registrations 
                     WHERE batch = :batch
                     GROUP BY section 
                     ORDER BY section";
    $stmt = $conn->prepare($sectionQuery);
    $stmt->bindParam(':batch', $batch);
    $stmt->execute();
    $batchSectionData[$batch] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberCon Portal Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Registration deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Top Bar -->
        <div class="top-bar">
            <h3><i class="fas fa-chart-line"></i> Dashboard</h3>
            <div class="top-bar-actions">
                <span class="badge bg-info">
                    <i class="fas fa-clock"></i> <?php echo date('F j, Y'); ?>
                </span>
                <a href="../index.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-globe"></i> View Website
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Registrations</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['confirmed']; ?></h3>
                        <p>Confirmed</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-danger">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['cancelled']; ?></h3>
                        <p>Cancelled</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Duplicate Indicator (if any) -->
        <?php if ($stats['duplicate'] > 0): ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-copy"></i> 
            <strong><?php echo $stats['duplicate']; ?> Duplicate Registration<?php echo $stats['duplicate'] > 1 ? 's' : ''; ?> Found</strong>
            <span class="ms-3">
                <a href="registrations.php?status=duplicate" class="btn btn-sm btn-info">
                    <i class="fas fa-filter"></i> View Duplicates
                </a>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-bolt"></i> Quick Actions</h6>
                        <a href="registrations.php" class="btn btn-primary me-2">
                            <i class="fas fa-users"></i> View All Registrations
                        </a>
                        <a href="export.php" class="btn btn-success me-2">
                            <i class="fas fa-file-export"></i> Export Data
                        </a>
                        <a href="logs.php" class="btn btn-info">
                            <i class="fas fa-history"></i> Activity Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row">
            <!-- Batch-wise Statistics -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Batch-wise Registration</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="batchChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Day-wise Statistics -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Daily Registration Trend (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dayChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Batch-Section wise Statistics (Grid Layout) -->
        <h4 class="mb-3"><i class="fas fa-chart-pie"></i> Section-wise Registration by Batch</h4>
        <div class="row">
            <?php foreach ($batches as $batch): 
                // Calculate total registrations for this batch
                $batchTotal = 0;
                foreach ($batchSectionData[$batch] as $sectionData) {
                    $batchTotal += $sectionData['count'];
                }
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-graduation-cap"></i> Batch <?php echo htmlspecialchars($batch); ?></h6>
                            <span class="badge bg-light text-dark"><?php echo $batchTotal; ?> Total</span>
                        </div>
                        <div class="card-body">
                            <canvas id="batchChart_<?php echo htmlspecialchars($batch); ?>" style="max-height: 250px;"></canvas>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for charts
        const batchData = <?php echo json_encode($batchStats); ?>;
        const dayData = <?php echo json_encode($dayStats); ?>;
        const batchSectionData = <?php echo json_encode($batchSectionData); ?>;
        
        // Batch-wise Chart
        const batchCtx = document.getElementById('batchChart').getContext('2d');
        new Chart(batchCtx, {
            type: 'bar',
            data: {
                labels: batchData.map(item => item.batch),
                datasets: [{
                    label: 'Registrations',
                    data: batchData.map(item => item.count),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(46, 204, 113, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(46, 204, 113, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Registrations: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Number of Registrations'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Batch'
                        }
                    }
                }
            }
        });
        
        // Day-wise Chart
        const dayCtx = document.getElementById('dayChart').getContext('2d');
        new Chart(dayCtx, {
            type: 'line',
            data: {
                labels: dayData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Daily Registrations',
                    data: dayData.map(item => item.count),
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Registrations: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Number of Registrations'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
        
        // Create individual pie charts for each batch
        const colorPalette = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(46, 204, 113, 0.8)',
            'rgba(231, 76, 60, 0.8)',
            'rgba(52, 152, 219, 0.8)',
            'rgba(241, 196, 15, 0.8)',
            'rgba(155, 89, 182, 0.8)',
            'rgba(230, 126, 34, 0.8)'
        ];
        
        Object.keys(batchSectionData).forEach((batch, index) => {
            const sections = batchSectionData[batch];
            const canvasId = 'batchChart_' + batch;
            const ctx = document.getElementById(canvasId);
            
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: sections.map(item => 'Section ' + item.section),
                        datasets: [{
                            data: sections.map(item => item.count),
                            backgroundColor: colorPalette.slice(0, sections.length),
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    padding: 10,
                                    font: {
                                        size: 10
                                    },
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                return {
                                                    text: label + ': ' + value,
                                                    fillStyle: data.datasets[0].backgroundColor[i],
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                        return [];
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return label + ': ' + value + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-4" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
        <div class="container">
            <p class="mb-0 text-muted small">
                Developed by <a href="https://linkedin.com/in/pran0x" target="_blank" class="text-decoration-none"><strong>pran0x</strong></a> | 
                All rights reserved by <strong>Cyber Security Club, Uttara University</strong>
            </p>
        </div>
    </footer>
</body>
</html>
