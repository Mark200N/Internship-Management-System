<?php
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

// Summary stats
$totalStudents    = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalLecturers   = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
$totalInternships = $pdo->query("SELECT COUNT(*) FROM internships")->fetchColumn();
$activeInternships= $pdo->query("SELECT COUNT(*) FROM internships WHERE status='active'")->fetchColumn();
$totalLogs        = $pdo->query("SELECT COUNT(*) FROM logbooks")->fetchColumn();
$approvedLogs     = $pdo->query("SELECT COUNT(*) FROM logbooks WHERE status='approved'")->fetchColumn();
$pendingLogs      = $pdo->query("SELECT COUNT(*) FROM logbooks WHERE status='pending'")->fetchColumn();
$rejectedLogs     = $pdo->query("SELECT COUNT(*) FROM logbooks WHERE status='rejected'")->fetchColumn();

// Top companies
$topCompanies = $pdo->query(
    "SELECT company_name, COUNT(*) as cnt FROM internships GROUP BY company_name ORDER BY cnt DESC LIMIT 8"
)->fetchAll();

// Per-student progress
$studentProgress = $pdo->query(
    "SELECT u.name, s.registration_number, s.course,
            (SELECT COUNT(*) FROM logbooks WHERE student_id=s.id) as total_logs,
            (SELECT COUNT(*) FROM logbooks WHERE student_id=s.id AND status='approved') as approved,
            (SELECT company_name FROM internships WHERE student_id=s.id AND status='active' LIMIT 1) as company
     FROM students s JOIN users u ON u.id=s.user_id ORDER BY u.name"
)->fetchAll();

// Unassigned students
$unassigned = $pdo->query(
    "SELECT u.name, s.registration_number FROM students s JOIN users u ON u.id=s.user_id
     WHERE s.id NOT IN (SELECT student_id FROM assignments) ORDER BY u.name"
)->fetchAll();

startLayout('Reports', 'System Reports', 'Reports', 'reports');
?>

<div class="page-header">
  <div><h1>Reports & Analytics</h1><p class="breadcrumb">Admin / <span>Reports</span></p></div>
  <button onclick="window.print()" class="btn btn-outline">
    <i class="fa fa-print"></i> Print Report
  </button>
</div>

<!-- Summary boxes -->
<div class="stats-grid fade-up mb-3">
  <div class="stat-card"><div class="stat-icon navy"><i class="fa fa-user-graduate"></i></div>
    <div><div class="stat-val"><?= $totalStudents ?></div><div class="stat-label">Students</div></div></div>
  <div class="stat-card"><div class="stat-icon teal"><i class="fa fa-chalkboard-user"></i></div>
    <div><div class="stat-val"><?= $totalLecturers ?></div><div class="stat-label">Lecturers</div></div></div>
  <div class="stat-card"><div class="stat-icon amber"><i class="fa fa-briefcase"></i></div>
    <div><div class="stat-val"><?= $activeInternships ?> / <?= $totalInternships ?></div><div class="stat-label">Active / Total Internships</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fa fa-book"></i></div>
    <div><div class="stat-val"><?= $totalLogs ?></div><div class="stat-label">Total Logbooks</div></div></div>
</div>

<div class="grid-2 mb-3">
  <!-- Logbook status breakdown -->
  <div class="card fade-up">
    <div class="card-header"><h3>Logbook Status Breakdown</h3></div>
    <div class="card-body">
      <?php
        $statuses = ['Approved' => [$approvedLogs, 'var(--success)'], 'Pending' => [$pendingLogs, 'var(--amber)'], 'Rejected' => [$rejectedLogs, 'var(--danger)']];
        foreach ($statuses as $label => [$count, $color]):
          $pct = $totalLogs > 0 ? round($count / $totalLogs * 100) : 0;
      ?>
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:5px;">
          <span><?= $label ?></span>
          <strong><?= $count ?> (<?= $pct ?>%)</strong>
        </div>
        <div class="progress-bar">
          <div style="height:8px;border-radius:8px;background:<?= $color ?>;width:<?= $pct ?>%;transition:width .6s;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Top companies -->
  <div class="card fade-up fade-up-2">
    <div class="card-header"><h3>Top Internship Companies</h3></div>
    <div class="card-body">
      <?php if ($topCompanies): ?>
      <?php $maxCnt = max(array_column($topCompanies,'cnt')); ?>
      <?php foreach ($topCompanies as $c): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;font-size:.875rem;">
        <span style="min-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($c['company_name']) ?></span>
        <div class="progress-bar" style="flex:1;">
          <div style="height:8px;border-radius:8px;background:var(--navy-3);width:<?= round($c['cnt']/$maxCnt*100) ?>%;transition:width .6s;"></div>
        </div>
        <span class="tag" style="min-width:30px;text-align:center;"><?= $c['cnt'] ?></span>
      </div>
      <?php endforeach; ?>
      <?php else: ?><p class="text-muted">No data.</p><?php endif; ?>
    </div>
  </div>
</div>

<!-- Student progress table -->
<div class="card fade-up mb-3">
  <div class="card-header">
    <h3>Student Progress</h3>
    <input type="text" id="tableSearch" class="form-control" placeholder="Search…" style="width:200px;">
  </div>
  <div class="table-wrapper">
    <table class="data-table searchable-table">
      <thead>
        <tr><th>Student</th><th>Reg No.</th><th>Course</th><th>Company</th><th>Logbooks</th><th>Approved</th><th>Progress</th></tr>
      </thead>
      <tbody>
        <?php foreach ($studentProgress as $sp): ?>
        <?php $pct = $sp['total_logs'] > 0 ? round($sp['approved']/$sp['total_logs']*100) : 0; ?>
        <tr>
          <td class="td-name"><?= htmlspecialchars($sp['name']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($sp['registration_number']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($sp['course']) ?></td>
          <td><?= $sp['company'] ? htmlspecialchars($sp['company']) : '<span class="text-muted">—</span>' ?></td>
          <td><?= $sp['total_logs'] ?></td>
          <td><span class="badge badge-approved"><?= $sp['approved'] ?></span></td>
          <td style="min-width:120px;">
            <div class="progress-bar">
              <div class="progress-fill" data-pct="<?= $pct ?>" style="width:0;"></div>
            </div>
            <div style="font-size:.72rem;color:var(--text-3);margin-top:3px;"><?= $pct ?>%</div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Unassigned students -->
<?php if ($unassigned): ?>
<div class="card fade-up">
  <div class="card-header">
    <h3 style="color:var(--danger);"><i class="fa fa-triangle-exclamation"></i> Unassigned Students (<?= count($unassigned) ?>)</h3>
    <a href="/internship-system/admin/assignments.php" class="btn btn-amber btn-sm">Assign Now</a>
  </div>
  <div class="card-body">
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
      <?php foreach ($unassigned as $u): ?>
      <span class="tag" style="background:var(--danger-lt);color:var(--danger);">
        <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['registration_number']) ?>)
      </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php endLayout(); ?>
