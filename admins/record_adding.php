<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
include '../config.php';

$collegeId = intval($_SESSION['college_id'] ?? 0);
$userId    = intval($_SESSION['user_id'] ?? 0);

$project_titles           = [];
$activity_classifications = [];
$officials                = [];

// Fetch project titles
$stmt = $conn->prepare("SELECT project_title_id, project_title FROM project_titles ORDER BY project_title ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $project_titles[] = $r;
$stmt->close();

// Fetch activity classifications
$stmt = $conn->prepare("SELECT classification_id, classification_name FROM activity_classifications ORDER BY classification_name ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $activity_classifications[] = $r;
$stmt->close();

// Fetch officials filtered by college_id from session
if ($collegeId > 0) {
    $stmt = $conn->prepare("SELECT u.id, u.firstname, u.lastname, p.position_name FROM users u LEFT JOIN positions p ON u.position_id = p.position_id WHERE u.college_id = ? AND u.role = 'admin' ORDER BY u.firstname ASC");
    $stmt->bind_param("i", $collegeId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $officials[] = $r;
    $stmt->close();
} else {
    // Fallback: fetch all admins if no college context (e.g. super admin)
    $stmt = $conn->prepare("SELECT u.id, u.firstname, u.lastname, p.position_name FROM users u LEFT JOIN positions p ON u.position_id = p.position_id WHERE u.role = 'admin' ORDER BY u.firstname ASC");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $officials[] = $r;
    $stmt->close();
}
?>

<style>
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.active {
    display: flex;
  }
  .modal-card {
    background: #fff;
    border-radius: 10px;
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
  }
  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px 14px;
    border-bottom: 1px solid #eee;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
  }
  .modal-header h2 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a1a2e;
  }
  .close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #888;
    line-height: 1;
    padding: 0 4px;
  }
  .close-btn:hover { color: #333; }

  .modal-body {
    padding: 20px 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  .form-group.full-width {
    grid-column: 1 / -1;
  }
  .form-group label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .form-group select,
  .form-group input {
    padding: 9px 12px;
    border: 1.5px solid #d5d5d5;
    border-radius: 6px;
    font-size: 0.92rem;
    color: #222;
    background: #fafafa;
    transition: border-color 0.2s, background 0.2s;
    width: 100%;
    box-sizing: border-box;
  }
  .form-group select:focus,
  .form-group input:focus {
    outline: none;
    border-color: #4a6fa5;
    background: #fff;
  }
  .form-group select.error,
  .form-group input.error {
    border-color: #e74c3c;
  }
  .form-group input[readonly] {
    background: #f0f0f0;
    color: #777;
    cursor: not-allowed;
    border-color: #ddd;
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 24px 20px;
    border-top: 1px solid #eee;
    position: sticky;
    bottom: 0;
    background: #fff;
  }
  .btn-cancel {
    padding: 9px 20px;
    border: 1.5px solid #ccc;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    font-size: 0.92rem;
    color: #555;
    transition: background 0.15s;
  }
  .btn-cancel:hover { background: #f5f5f5; }
  .btn-save {
    padding: 9px 24px;
    border: none;
    border-radius: 6px;
    background: #4a6fa5;
    color: #fff;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-save:hover:not(:disabled) { background: #3a5a8f; }
  .btn-save:disabled {
    background: #9ab0cc;
    cursor: not-allowed;
  }

  /* Toast notification */
  #recordToast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 14px 22px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    color: #fff;
    z-index: 9999;
    display: none;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    max-width: 340px;
    word-break: break-word;
    animation: toastSlideUp 0.3s ease;
  }
  #recordToast.success { background: #27ae60; }
  #recordToast.error   { background: #e74c3c; }
  @keyframes toastSlideUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
</style>

<!-- Toast -->
<div id="recordToast"></div>

<!-- Modal -->
<div class="modal-overlay" id="addRecordModal">
  <div class="modal-card">

    <div class="modal-header">
      <h2>Add New Record</h2>
      <button class="close-btn" id="closeAddModal" type="button">&times;</button>
    </div>

    <form id="addRecordForm" novalidate>
      <div class="modal-body">

        <!-- Project Title — full width -->
        <div class="form-group full-width">
          <label>Project Title</label>
          <select name="project_title_id" required>
            <option value="">Select project title</option>
            <?php foreach ($project_titles as $pt): ?>
              <option value="<?= htmlspecialchars($pt['project_title_id']) ?>">
                <?= htmlspecialchars($pt['project_title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Activity Classification -->
        <div class="form-group">
          <label>Activity Classification</label>
          <select name="classification_id" required>
            <option value="">Select classification</option>
            <?php foreach ($activity_classifications as $ac): ?>
              <option value="<?= htmlspecialchars($ac['classification_id']) ?>">
                <?= htmlspecialchars($ac['classification_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Official Assigned -->
        <div class="form-group">
          <label>Official Assigned</label>
          <select name="official_id" required>
            <option value="">Select official</option>
            <?php foreach ($officials as $o): ?>
              <option value="<?= htmlspecialchars($o['id']) ?>">
                <?= htmlspecialchars($o['firstname'] . ' ' . $o['lastname'] . ' (' . ($o['position_name'] ?? 'No Position') . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- School Year -->
        <div class="form-group">
          <label>School Year</label>
          <select name="school_year" required>
            <option value="">Select school year</option>
            <option value="2023-2024">2023-2024</option>
            <option value="2024-2025">2024-2025</option>
            <option value="2025-2026">2025-2026</option>
            <option value="2026-2027">2026-2027</option>
          </select>
        </div>

        <!-- Date Proposed -->
        <div class="form-group">
          <label>Date Proposed</label>
          <input type="date" name="date_proposed" required>
        </div>

        <!-- Transaction Type -->
        <div class="form-group">
          <label>Transaction Type</label>
          <select name="transaction_type" required>
            <option value="">Select type</option>
            <option value="Disbursement">Disbursement</option>
            <option value="Appropriation">Appropriation</option>
          </select>
        </div>

        <!-- Project Status -->
        <div class="form-group">
          <label>Project Status</label>
          <select name="project_status" required>
            <option value="">Select status</option>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
          </select>
        </div>

        <!-- Approved Funds -->
        <div class="form-group">
          <label>Approved Funds (₱)</label>
          <!-- FIX: value="0" so it is never blank on submit -->
          <input type="number" name="approved_funds" id="approvedFunds"
                 step="0.01" min="0" value="0" placeholder="0.00" required>
        </div>

        <!-- Actual Expenditure -->
        <div class="form-group">
          <label>Actual Expenditure (₱)</label>
          <!-- FIX: value="0" so it is never blank on submit -->
          <input type="number" name="actual_expenditure" id="actualExpenditure"
                 step="0.01" min="0" value="0" placeholder="0.00" required>
        </div>

        <!-- Remaining Budget (auto-calculated, read-only) -->
        <div class="form-group">
          <label>Remaining Budget (₱)</label>
          <input type="number" name="remaining_budget" id="remainingBudget"
                 step="0.01" value="0.00" readonly>
        </div>

        <!-- Hidden college_id from session -->
        <input type="hidden" name="college_id" value="<?= htmlspecialchars($collegeId) ?>">

      </div><!-- /.modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelAddRecord">Cancel</button>
        <button type="submit" class="btn-save" id="saveRecordBtn">Save Record</button>
      </div>
    </form>

  </div>
</div>

<script>
(function () {
  const modal     = document.getElementById('addRecordModal');
  const form      = document.getElementById('addRecordForm');
  const saveBtn   = document.getElementById('saveRecordBtn');
  const closeBtn  = document.getElementById('closeAddModal');
  const cancelBtn = document.getElementById('cancelAddRecord');
  const approved  = document.getElementById('approvedFunds');
  const actual    = document.getElementById('actualExpenditure');
  const remaining = document.getElementById('remainingBudget');
  const toast     = document.getElementById('recordToast');
  let toastTimer  = null;
  let isSubmitting = false;

  // ── Expose open function so your page button can call it ──
  // Usage: <button onclick="openAddRecordModal()">Add Record</button>
  window.openAddRecordModal = function () {
    form.reset();
    // Reset number fields to 0 after reset (reset() brings back placeholder)
    approved.value  = '0';
    actual.value    = '0';
    remaining.value = '0.00';
    // Clear any error highlights
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    modal.classList.add('active');
  };

  // ── Close helpers ──────────────────────────────────────────
  function closeModal() {
    modal.classList.remove('active');
  }
  closeBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  // ── Auto-calculate remaining budget ───────────────────────
  function calcRemaining() {
    const a = parseFloat(approved.value) || 0;
    const b = parseFloat(actual.value)   || 0;
    remaining.value = Math.max(0, a - b).toFixed(2);
  }
  approved.addEventListener('input', calcRemaining);
  actual.addEventListener('input', calcRemaining);

  // ── Toast helper ──────────────────────────────────────────
  function showToast(message, type) {
    if (toastTimer) clearTimeout(toastTimer);
    toast.textContent   = message;
    toast.className     = type; // 'success' or 'error'
    toast.style.display = 'block';
    toastTimer = setTimeout(() => { toast.style.display = 'none'; }, 4500);
  }

  // ── AJAX Submit ───────────────────────────────────────────
  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (isSubmitting) return;

    // Client-side validation: highlight empty required fields
    let valid = true;
    form.querySelectorAll('[required]').forEach(function (field) {
      if (!field.value || field.value === '') {
        field.classList.add('error');
        valid = false;
      } else {
        field.classList.remove('error');
      }
    });

    if (!valid) {
      showToast('Please fill in all required fields.', 'error');
      return;
    }

    isSubmitting = true;
    saveBtn.disabled        = true;
    saveBtn.textContent     = 'Saving…';

    fetch('process_record.php', {
      method: 'POST',
      body: new FormData(form)
    })
    .then(function (res) {
      // Read raw text first — if PHP crashes it returns HTML, not JSON
      return res.text();
    })
    .then(function (text) {
      let data;
      try {
        data = JSON.parse(text);
      } catch (_) {
        console.error('Non-JSON server response:', text);
        showToast('Server error — check XAMPP error log.', 'error');
        return;
      }

      if (data.success) {
        showToast('Record saved successfully!', 'success');
        closeModal();
        // Short delay so the toast is visible before reload
        setTimeout(function () { location.reload(); }, 1300);
      } else {
        showToast('Error: ' + (data.error || 'Unknown error'), 'error');
        console.error('Save failed:', data.error);
      }
    })
    .catch(function (err) {
      showToast('Network error: ' + err.message, 'error');
      console.error(err);
    })
    .finally(function () {
      saveBtn.disabled    = false;
      saveBtn.textContent = 'Save Record';
      isSubmitting = false;
    });
  });

  // Clear error highlight on change
  form.querySelectorAll('[required]').forEach(function (field) {
    field.addEventListener('change', function () {
      this.classList.remove('error');
    });
    field.addEventListener('input', function () {
      this.classList.remove('error');
    });
  });
})();
</script>