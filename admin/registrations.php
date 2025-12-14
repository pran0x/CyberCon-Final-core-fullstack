<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$batchFilter = isset($_GET['batch']) ? $_GET['batch'] : '';
$deptFilter = isset($_GET['dept']) ? $_GET['dept'] : '';

// Get sorting parameters
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Allowed sort columns for security
$allowedSortColumns = ['ticket_id', 'full_name', 'student_id', 'email', 'university', 'department', 'batch', 'section', 'status', 'created_at', 'updated_at', 'registration_date'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

// Validate sort order
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT * FROM registrations WHERE 1=1";
$countQuery = "SELECT COUNT(*) as total FROM registrations WHERE 1=1";
$params = [];

if (!empty($search)) {
    $searchCondition = " AND (ticket_id LIKE :search OR full_name LIKE :search OR student_id LIKE :search OR email LIKE :search)";
    $query .= $searchCondition;
    $countQuery .= $searchCondition;
    $params[':search'] = "%$search%";
}

if (!empty($statusFilter)) {
    $query .= " AND status = :status";
    $countQuery .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($batchFilter)) {
    $query .= " AND batch = :batch";
    $countQuery .= " AND batch = :batch";
    $params[':batch'] = $batchFilter;
}

if (!empty($deptFilter)) {
    $query .= " AND department = :dept";
    $countQuery .= " AND department = :dept";
    $params[':dept'] = $deptFilter;
}

// Get total count
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get registrations with dynamic sorting
$query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll();

// Get filter options
$batchStmt = $conn->query("SELECT DISTINCT batch FROM registrations WHERE batch IS NOT NULL AND batch != '' ORDER BY batch");
$batches = $batchStmt->fetchAll(PDO::FETCH_COLUMN);

$deptStmt = $conn->query("SELECT DISTINCT department FROM registrations WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Registrations - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }
        .table thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }
        .filter-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sortable {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s;
        }
        .sortable:hover {
            background-color: #e9ecef;
        }
        .sortable i {
            font-size: 0.8em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h3><i class="fas fa-users"></i> All Registrations</h3>
            <div class="top-bar-actions">
                <a href="export.php" class="btn btn-success">
                    <i class="fas fa-download"></i> Export to CSV
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Registration deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Ticket ID, Name, Email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-flag"></i> Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="duplicate" <?php echo $statusFilter === 'duplicate' ? 'selected' : ''; ?>>Duplicate</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-graduation-cap"></i> Batch</label>
                        <select name="batch" class="form-select">
                            <option value="">All Batches</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?php echo htmlspecialchars($batch); ?>" <?php echo $batchFilter === $batch ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-building"></i> Department</label>
                        <select name="dept" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $deptFilter === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-sort"></i> Sort By</label>
                        <select name="sort" class="form-select" id="sortSelect">
                            <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Latest First</option>
                            <option value="created_at_asc" <?php echo $sortBy === 'created_at' && $sortOrder === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="updated_at" <?php echo $sortBy === 'updated_at' ? 'selected' : ''; ?>>Recently Modified</option>
                            <option value="full_name" <?php echo $sortBy === 'full_name' && $sortOrder === 'ASC' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="full_name_desc" <?php echo $sortBy === 'full_name' && $sortOrder === 'DESC' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="ticket_id" <?php echo $sortBy === 'ticket_id' && $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ticket ID (Asc)</option>
                            <option value="ticket_id_desc" <?php echo $sortBy === 'ticket_id' && $sortOrder === 'DESC' ? 'selected' : ''; ?>>Ticket ID (Desc)</option>
                            <option value="batch" <?php echo $sortBy === 'batch' ? 'selected' : ''; ?>>Batch</option>
                            <option value="department" <?php echo $sortBy === 'department' ? 'selected' : ''; ?>>Department</option>
                            <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Status</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </div>
                </form>
                <?php if (!empty($search) || !empty($statusFilter) || !empty($batchFilter) || !empty($deptFilter) || $sortBy !== 'created_at' || $sortOrder !== 'DESC'): ?>
                    <div class="mt-2">
                        <a href="registrations.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Registrations Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Registration Details 
                    <span class="badge bg-light text-dark"><?php echo $totalRecords; ?> Total</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 120px;" class="sortable" onclick="sortTable('ticket_id')">
                                    Ticket ID 
                                    <?php if ($sortBy === 'ticket_id'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 180px;" class="sortable" onclick="sortTable('full_name')">
                                    Full Name
                                    <?php if ($sortBy === 'full_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 120px;" class="sortable" onclick="sortTable('student_id')">
                                    Student ID
                                    <?php if ($sortBy === 'student_id'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 200px;" class="sortable" onclick="sortTable('email')">
                                    Email
                                    <?php if ($sortBy === 'email'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 150px;">University</th>
                                <th style="min-width: 120px;" class="sortable" onclick="sortTable('department')">
                                    Department
                                    <?php if ($sortBy === 'department'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 80px;" class="sortable" onclick="sortTable('batch')">
                                    Batch
                                    <?php if ($sortBy === 'batch'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 80px;" class="sortable" onclick="sortTable('section')">
                                    Section
                                    <?php if ($sortBy === 'section'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 130px;">Payment Number</th>
                                <th style="min-width: 130px;">Transaction ID</th>
                                <th style="min-width: 100px;" class="sortable" onclick="sortTable('status')">
                                    Status
                                    <?php if ($sortBy === 'status'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas fa-sort text-muted"></i>
                                    <?php endif; ?>
                                </th>
                                <th style="min-width: 120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrations)): ?>
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No registrations found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrations as $reg): ?>
                                    <tr <?php echo $reg['status'] === 'duplicate' ? 'class="table-info"' : ''; ?>>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reg['ticket_id']); ?></strong>
                                            <?php if ($reg['status'] === 'duplicate'): ?>
                                                <i class="fas fa-copy text-info ms-1" title="Duplicate Entry"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['student_id']); ?></td>
                                        <td><small><?php echo htmlspecialchars($reg['email']); ?></small></td>
                                        <td><?php echo htmlspecialchars($reg['university']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['department'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['batch'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($reg['section'] ?? '-'); ?></td>
                                        <td><?php echo !empty($reg['payment_number']) ? htmlspecialchars($reg['payment_number']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo !empty($reg['transaction_id']) ? htmlspecialchars($reg['transaction_id']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'confirmed' => 'success',
                                                'cancelled' => 'danger',
                                                'duplicate' => 'info'
                                            ];
                                            $class = $statusClass[$reg['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($reg['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="view.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Delete" 
                                                    onclick="confirmDelete(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['ticket_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($reg['full_name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&batch=<?php echo urlencode($batchFilter); ?>&dept=<?php echo urlencode($deptFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&batch=<?php echo urlencode($batchFilter); ?>&dept=<?php echo urlencode($deptFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&batch=<?php echo urlencode($batchFilter); ?>&dept=<?php echo urlencode($deptFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div> <!-- End main-content -->
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to delete this registration?</strong></p>
                    <p class="text-muted">This action cannot be undone.</p>
                    <div class="alert alert-warning" id="deleteInfo"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" action="delete.php" id="deleteForm">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" name="delete_registration" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, ticketId, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteInfo').innerHTML = 
                '<strong>Ticket ID:</strong> ' + ticketId + '<br>' +
                '<strong>Name:</strong> ' + name;
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
        
        // Sort table by column
        function sortTable(column) {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort') || 'created_at';
            const currentOrder = urlParams.get('order') || 'DESC';
            
            let newOrder = 'ASC';
            if (currentSort === column) {
                // Toggle order if clicking same column
                newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            }
            
            urlParams.set('sort', column);
            urlParams.set('order', newOrder);
            urlParams.set('page', '1'); // Reset to first page when sorting
            
            window.location.search = urlParams.toString();
        }
        
        // Handle sort select change
        document.getElementById('sortSelect')?.addEventListener('change', function() {
            const value = this.value;
            const form = document.getElementById('filterForm');
            
            // Parse sort value
            if (value.endsWith('_asc')) {
                const sortField = value.replace('_asc', '');
                const orderInput = document.createElement('input');
                orderInput.type = 'hidden';
                orderInput.name = 'order';
                orderInput.value = 'ASC';
                form.appendChild(orderInput);
                
                const sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                sortInput.value = sortField;
                form.appendChild(sortInput);
            } else if (value.endsWith('_desc')) {
                const sortField = value.replace('_desc', '');
                const orderInput = document.createElement('input');
                orderInput.type = 'hidden';
                orderInput.name = 'order';
                orderInput.value = 'DESC';
                form.appendChild(orderInput);
                
                const sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                sortInput.value = sortField;
                form.appendChild(sortInput);
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
