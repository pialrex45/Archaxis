<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/FinanceController.php';
require_once __DIR__ . '/../../controllers/ProjectController.php';

// Check authentication
requireAuth();

// Set page title and current page
$pageTitle = 'Finance';
$currentPage = 'finance';

// Get project ID from URL parameter (optional)
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Create FinanceController instance
$financeController = new FinanceController();

// Get finance summary
if ($projectId > 0) {
    // Get summary for specific project
    $summaryResult = $financeController->getSummary($projectId);
} else {
    // Get overall summary
    $summaryResult = $financeController->getSummary();
}

$summary = $summaryResult['success'] ? $summaryResult['data'] : [];

// Get finance records
if ($projectId > 0) {
    // Get records for specific project
    $recordsResult = $financeController->getByProject($projectId);
} else {
    // Get all records
    $recordsResult = $financeController->getAll();
}

$records = $recordsResult['success'] ? $recordsResult['data'] : [];

// Create ProjectController instance to get projects for dropdown
$projectController = new ProjectController();
$projectsResult = $projectController->getAll();
$projects = $projectsResult['success'] ? $projectsResult['data'] : [];
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>
<?php include_once __DIR__ . '/../../views/layouts/sidebar.php'; ?>

<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Finance Summary</h1>
        <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo url('/finance/log'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus"></i> Log Transaction
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Project Filter -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="<?php echo url('/finance'); ?>">
                <div class="input-group">
                    <select class="form-select" name="project_id">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $projectId == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Total Income</h5>
                    <p class="card-text display-6"><?php echo formatCurrency($summary['income'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Total Expenses</h5>
                    <p class="card-text display-6"><?php echo formatCurrency($summary['expense'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-<?php echo ($summary['balance'] ?? 0) >= 0 ? 'primary' : 'warning'; ?>">
                <div class="card-body">
                    <h5 class="card-title">Net Balance</h5>
                    <p class="card-text display-6"><?php echo formatCurrency($summary['balance'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Finance Records Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Finance Records</h5>
        </div>
        <div class="card-body">
            <?php if (!$recordsResult['success']): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($recordsResult['message']); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No finance records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['project_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($record['type'] === 'income') ? 'success' : 'danger'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($record['type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo truncateText(htmlspecialchars($record['description']), 50); ?></td>
                                    <td><?php echo formatCurrency($record['amount']); ?></td>
                                    <td><?php echo formatDate($record['created_at']); ?></td>
                                    <td>
                                        <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $record['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <?php if (hasAnyRole(['admin', 'manager', 'supervisor'])): ?>
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $record['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $record['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $record['id']; ?>">Delete Finance Record</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete this finance record? This action cannot be undone.</p>
                                                <form id="deleteForm<?php echo $record['id']; ?>" method="POST" action="<?php echo url('/api/finance/delete?id=' . $record['id']); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm<?php echo $record['id']; ?>').submit()">Delete Record</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
</content>
<!-- animation script removed per revert -->