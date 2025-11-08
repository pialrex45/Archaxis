<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Get attendance data
$attendanceController = new AttendanceController();
$userId = getCurrentUserId();

// Get today's attendance
$today = date('Y-m-d');
$todayAttendance = $attendanceController->getByUserAndDate($userId, $today);

// Get attendance history (last 30 days) with optional GET overrides
$from = isset($_GET['from']) && $_GET['from'] ? sanitize($_GET['from']) : date('Y-m-d', strtotime('-30 days'));
$to = isset($_GET['to']) && $_GET['to'] ? sanitize($_GET['to']) : date('Y-m-d');
$attendanceHistory = $attendanceController->getByUserAndDateRange($userId, $from, $to);

$pageTitle = 'My Attendance';
$currentPage = 'attendance';
?>

<?php include_once __DIR__ . '/../../views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>My Attendance</h1>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Attendance marked successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <!-- Today's Attendance Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Attendance</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="mb-4"><?php echo date('l, F j, Y'); ?></h3>
                    <!-- Placeholder for dynamic in-place updates -->
                    <div id="todayDynamic"></div>
                    
                    <?php if (!empty($todayAttendance['data'])): ?>
                        <?php $attendance = $todayAttendance['data']; ?>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <div class="status-circle bg-success me-2"></div>
                                <h4 class="mb-0">Checked In</h4>
                            </div>
                            <?php if (isset($attendance['check_in_time']) || isset($attendance['check_in'])): ?>
                                <p class="display-6">
                                    <?php 
                                        $cin = $attendance['check_in_time'] ?? $attendance['check_in'] ?? null;
                                        echo $cin ? date('g:i A', strtotime($cin)) : 'Time not recorded';
                                    ?>
                                </p>
                            <?php else: ?>
                                <p class="text-muted">Time not recorded</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($attendance['check_out_time']) || isset($attendance['check_out'])): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-center align-items-center mb-3">
                                    <div class="status-circle bg-danger me-2"></div>
                                    <h4 class="mb-0">Checked Out</h4>
                                </div>
                                <p class="display-6">
                                    <?php 
                                        $cout = $attendance['check_out_time'] ?? $attendance['check_out'] ?? null;
                                        echo $cout ? date('g:i A', strtotime($cout)) : 'Time not recorded';
                                    ?>
                                </p>
                            </div>
                            
                            <div class="alert alert-info">
                                Total Hours: 
                                <?php 
                                    $cin = $attendance['check_in_time'] ?? $attendance['check_in'] ?? null;
                                    $cout = $attendance['check_out_time'] ?? $attendance['check_out'] ?? null;
                                    if ($cin && $cout) {
                                        $checkIn = new DateTime($cin);
                                        $checkOut = new DateTime($cout);
                                        $interval = $checkIn->diff($checkOut);
                                        echo $interval->format('%h hours, %i minutes');
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </div>
                        <?php else: ?>
                            <?php $cin = $attendance['check_in_time'] ?? $attendance['check_in'] ?? null; ?>
                            <?php if ($cin): ?>
                            <div class="mb-3 text-muted">
                                <small>Elapsed today: <span id="elapsedTimer" data-start="<?php echo date('c', strtotime($cin)); ?>">--:--</span></small>
                            </div>
                            <?php endif; ?>
                            <form id="checkOutForm">
                                <input type="hidden" name="action" value="check_out">
                                <button type="submit" class="btn btn-danger btn-lg">Check Out</button>
                            </form>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="mb-4">
                            <p class="text-muted mb-4">You haven't checked in today.</p>
                            <form id="checkInForm">
                                <input type="hidden" name="action" value="check_in">
                                <button type="submit" class="btn btn-success btn-lg">Check In</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Attendance Stats Card -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attendance Summary (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <div class="attendance-stat">
                                <h2 class="display-4 text-success">
                                    <?php 
                                        $presentCount = 0;
                                        if (!empty($attendanceHistory['data'])) {
                                            foreach ($attendanceHistory['data'] as $record) {
                                                if ($record['status'] === 'present') $presentCount++;
                                            }
                                        }
                                        echo $presentCount;
                                    ?>
                                </h2>
                                <p class="text-muted">Present Days</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="attendance-stat">
                                <h2 class="display-4 text-danger">
                                    <?php 
                                        $absentCount = 0;
                                        if (!empty($attendanceHistory['data'])) {
                                            foreach ($attendanceHistory['data'] as $record) {
                                                if ($record['status'] === 'absent') $absentCount++;
                                            }
                                        }
                                        echo $absentCount;
                                    ?>
                                </h2>
                                <p class="text-muted">Absent Days</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="attendance-stat">
                                <h2 class="display-4 text-info">
                                    <?php 
                                        $leaveCount = 0;
                                        if (!empty($attendanceHistory['data'])) {
                                            foreach ($attendanceHistory['data'] as $record) {
                                                if ($record['status'] === 'leave') $leaveCount++;
                                            }
                                        }
                                        echo $leaveCount;
                                    ?>
                                </h2>
                                <p class="text-muted">Leave Days</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="attendance-stat">
                                <h2 class="display-4 text-primary">
                                    <?php 
                                        $attendance_rate = 0;
                                        if (!empty($attendanceHistory['data'])) {
                                            $workDays = count($attendanceHistory['data']);
                                            $attendance_rate = $workDays > 0 ? round(($presentCount / $workDays) * 100) : 0;
                                        }
                                        echo $attendance_rate . '%';
                                    ?>
                                </h2>
                                <p class="text-muted">Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Attendance Calendar</h5>
                    <div class="attendance-calendar mb-4">
                        <?php
                            // Generate calendar for the current month
                            $currentMonth = date('n');
                            $currentYear = date('Y');
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
                            $firstDayOfMonth = date('N', strtotime("$currentYear-$currentMonth-01"));
                            
                            // Create a map of attendance by date
                            $attendanceByDate = [];
                            if (!empty($attendanceHistory['data'])) {
                                foreach ($attendanceHistory['data'] as $record) {
                                    $attendanceByDate[date('Y-m-d', strtotime($record['date']))] = $record['status'];
                                }
                            }
                        ?>
                        
                        <div class="month-header mb-3">
                            <h4><?php echo date('F Y'); ?></h4>
                        </div>
                        
                        <div class="calendar-grid">
                            <div class="weekday">Mon</div>
                            <div class="weekday">Tue</div>
                            <div class="weekday">Wed</div>
                            <div class="weekday">Thu</div>
                            <div class="weekday">Fri</div>
                            <div class="weekday">Sat</div>
                            <div class="weekday">Sun</div>
                            
                            <?php
                                // Add empty cells for days before the first day of month
                                for ($i = 1; $i < $firstDayOfMonth; $i++) {
                                    echo '<div class="day empty"></div>';
                                }
                                
                                // Add cells for each day of the month
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                    $today = date('Y-m-d') === $date ? 'today' : '';
                                    $status = isset($attendanceByDate[$date]) ? $attendanceByDate[$date] : '';
                                    $statusClass = '';
                                    
                                    if ($status === 'present') $statusClass = 'present';
                                    else if ($status === 'absent') $statusClass = 'absent';
                                    else if ($status === 'leave') $statusClass = 'leave';
                                    
                                    $statusLabel = $status ? ucfirst($status) : 'No status';
                                    $title = htmlspecialchars($statusLabel . ' - ' . $date, ENT_QUOTES, 'UTF-8');
                                    $aria = htmlspecialchars($statusLabel . ' on ' . $date, ENT_QUOTES, 'UTF-8');
                                    
                                    echo "<div class='day $today $statusClass' title='$title' aria-label='$aria'>$day</div>";
                                }
                            ?>
                        </div>
                        
                        <div class="calendar-legend mt-3">
                            <div class="legend-item">
                                <div class="legend-color present"></div>
                                <div class="legend-label">Present</div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color absent"></div>
                                <div class="legend-label">Absent</div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color leave"></div>
                                <div class="legend-label">Leave</div>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color today"></div>
                                <div class="legend-label">Today</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance History Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Attendance History</h5>
                    <form class="d-flex align-items-end gap-2" method="get" action="">
                        <div>
                            <label class="form-label mb-1">From</label>
                            <input type="date" name="from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($from); ?>">
                        </div>
                        <div>
                            <label class="form-label mb-1">To</label>
                            <input type="date" name="to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($to); ?>">
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
                            <?php 
                              $qs = http_build_query([
                                'user_id' => (int)$userId,
                                'from' => $from,
                                'to' => $to
                              ]);
                            ?>
                            <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo url('/api/attendance/export') . '?' . $qs; ?>">Export CSV</a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!empty($attendanceHistory['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Working Hours</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceHistory['data'] as $record): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y (D)', strtotime($record['date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $record['status'] === 'present' ? 'success' : 
                                                         ($record['status'] === 'leave' ? 'info' : 'danger'); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($record['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $cin = $record['check_in_time'] ?? $record['check_in'] ?? null;
                                                    echo $cin ? date('g:i A', strtotime($cin)) : 'N/A'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $cout = $record['check_out_time'] ?? $record['check_out'] ?? null;
                                                    echo $cout ? date('g:i A', strtotime($cout)) : 'N/A'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $cin = $record['check_in_time'] ?? $record['check_in'] ?? null;
                                                $cout = $record['check_out_time'] ?? $record['check_out'] ?? null;
                                                if ($cin && $cout) {
                                                    $checkIn = new DateTime($cin);
                                                    $checkOut = new DateTime($cout);
                                                    $interval = $checkIn->diff($checkOut);
                                                    echo $interval->format('%h hours, %i minutes');
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['note'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No attendance records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast container for inline feedback -->
<div id="toastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080;"></div>

<style>
.status-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.attendance-stat {
    padding: 15px;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.weekday {
    text-align: center;
    font-weight: bold;
    padding: 5px;
    background-color: #f0f0f0;
}

.day {
    text-align: center;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.day.empty {
    background-color: transparent;
}

.day.today {
    border: 2px solid #007bff;
}

.day.present {
    background-color: #d4edda;
}

.day.absent {
    background-color: #f8d7da;
}

.day.leave {
    background-color: #d1ecf1;
}

.calendar-legend {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-color {
    width: 15px;
    height: 15px;
    border-radius: 3px;
}

.legend-color.present {
    background-color: #d4edda;
}

.legend-color.absent {
    background-color: #f8d7da;
}

.legend-color.leave {
    background-color: #d1ecf1;
}

.legend-color.today {
    border: 2px solid #007bff;
    background-color: transparent;
}
</style>

<script>
// Build URLs relative to app base URL
const baseUrl = '<?php echo rtrim(url(''), '/'); ?>';
// Step 5: flag to perform in-place UI update instead of immediate redirect
const inplaceUpdate = true;

function setLoading(btn, text) {
    if (!btn) return;
    btn.disabled = true;
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + text;
}

// Simple toast helper (no dependency on Bootstrap JS)
function showToast(type, message) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const el = document.createElement('div');
    const cls = type === 'success' ? 'alert-success' : 'alert-danger';
    el.className = 'alert ' + cls + ' shadow mb-2';
    el.setAttribute('role', 'alert');
    el.textContent = message || (type === 'success' ? 'Success' : 'Something went wrong');
    container.appendChild(el);
    setTimeout(() => { el.remove(); }, 2000);
}

// Start live elapsed timer if present
function startElapsedTimer() {
    const el = document.getElementById('elapsedTimer');
    if (!el) return;
    const start = new Date(el.dataset.start);
    if (isNaN(start.getTime())) return;
    const tick = () => {
        const now = new Date();
        let diff = Math.max(0, now - start);
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        el.textContent = h + 'h ' + String(m).padStart(2, '0') + 'm';
    };
    tick();
    window._elapsedTimerInterval = setInterval(tick, 60000);
}

function stopElapsedTimer() {
    if (window._elapsedTimerInterval) {
        clearInterval(window._elapsedTimerInterval);
        window._elapsedTimerInterval = null;
    }
}

document.getElementById('checkInForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    setLoading(btn, 'Checking in...');
    markAttendance('check_in');
});

document.getElementById('checkOutForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Confirm to prevent accidental checkout
    if (!window.confirm('Are you sure you want to check out?')) {
        return;
    }
    const btn = this.querySelector('button[type="submit"]');
    setLoading(btn, 'Checking out...');
    markAttendance('check_out');
});

// Kick off any dynamic UI pieces
startElapsedTimer();

function markAttendance(action) {
    const formData = new FormData();
    formData.append('action', action);
    // Include CSRF token for security (optional on backend, verified when present)
    formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
    
    return fetch(baseUrl + '/api/attendance/mark', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Attendance marked successfully');
            if (inplaceUpdate) {
                try { updateUIAfterSuccess(action); } catch (e) { console.warn('In-place update failed', e); }
            } else {
                setTimeout(() => {
                    window.location.href = baseUrl + '/worker/attendance.php?success=1';
                }, 900);
            }
        } else {
            showToast('error', data.message || 'Failed to mark attendance');
            if (!inplaceUpdate) {
                setTimeout(() => {
                    window.location.href = baseUrl + '/worker/attendance.php?error=' + encodeURIComponent(data.message || 'Failed to mark attendance');
                }, 1200);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Network error while marking attendance');
        if (!inplaceUpdate) {
            setTimeout(() => {
                window.location.href = baseUrl + '/worker/attendance.php?error=' + encodeURIComponent('Failed to mark attendance');
            }, 1200);
        }
    });
}

// Update UI in place after successful check-in/out
function updateUIAfterSuccess(action) {
    const todayDynamic = document.getElementById('todayDynamic');
    const checkInForm = document.getElementById('checkInForm');
    const checkOutForm = document.getElementById('checkOutForm');
    const now = new Date();
    const hh = now.getHours();
    const mm = String(now.getMinutes()).padStart(2, '0');
    const displayTime = (h) => {
        const hour = ((h + 11) % 12) + 1;
        const ampm = h >= 12 ? 'PM' : 'AM';
        return hour + ':' + mm + ' ' + ampm;
    };
    if (action === 'check_in') {
        if (checkInForm) checkInForm.style.display = 'none';
        // show elapsed timer starting now
        if (todayDynamic && !document.getElementById('elapsedTimer')) {
            const wrap = document.createElement('div');
            wrap.className = 'mb-3 text-muted';
            wrap.innerHTML = '<small>Elapsed today: <span id="elapsedTimer" data-start="' + now.toISOString() + '">00:00</span></small>';
            todayDynamic.appendChild(wrap);
            startElapsedTimer();
        }
        // ensure checkout form exists
        if (!document.getElementById('checkOutForm')) {
            const form = document.createElement('form');
            form.id = 'checkOutForm';
            form.innerHTML = '<input type="hidden" name="action" value="check_out">' +
                             '<button type="submit" class="btn btn-danger btn-lg">Check Out</button>';
            todayDynamic && todayDynamic.appendChild(form);
            form.addEventListener('submit', function(e){
                e.preventDefault();
                if (!window.confirm('Are you sure you want to check out?')) return;
                const btn = this.querySelector('button[type="submit"]');
                setLoading(btn, 'Checking out...');
                markAttendance('check_out');
            });
        }
    } else if (action === 'check_out') {
        if (checkOutForm) checkOutForm.style.display = 'none';
        stopElapsedTimer();
        const timer = document.getElementById('elapsedTimer');
        if (timer) timer.remove();
        // show checked out summary
        if (todayDynamic) {
            const wrap = document.createElement('div');
            wrap.className = 'alert alert-info mt-3';
            wrap.textContent = 'Checked out at ' + displayTime(hh) + (typeof el !== 'undefined' ? '' : '');
            todayDynamic.appendChild(wrap);
        }
    }
}
</script>

<?php include_once __DIR__ . '/../../views/layouts/footer.php'; ?>
