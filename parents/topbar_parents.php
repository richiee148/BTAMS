<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = $_SESSION['username'] ?? 'Guest';

if (!isset($pageTitle) || empty($pageTitle)) {
    $currentPage = basename(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
    $titles = [
        'dashboard_students.php' => 'Dashboard',
        'collection_students.php' => 'Collections',
        'allocations_students.php' => 'Allocations',
        'budget_overview_students.php' => 'Budget Overview',
        'reports_students.php' => 'Reports',
        'dashboard_parents.php' => 'Dashboard',
        'collection_parents.php' => 'Collections',
        'allocations_parents.php' => 'Allocations',
        'budget_overview_parents.php' => 'Budget Overview',
        'reports_parents.php' => 'Reports',
    ];
    $pageTitle = $titles[$currentPage] ?? 'Dashboard';
}
?>
<header class="header">
  <link rel="stylesheet" href="../css/topbar.css"/> 

  <!-- Page title -->
  <div class="header-title-wrap">
    <div class="header-title">
      <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
    </div>
  </div>

  <!-- Right-side cluster -->
  <div class="header-right">
    <div class="search-wrap">
      <div class="search-input-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" id="topbarSearch" placeholder="Search records…" />
      </div>
    </div>

    <!-- User info -->
    <div class="user-info-wrap">
      <div class="avatar-sm">
        <?php echo strtoupper(substr($name,0,2)); ?>
      </div>
      <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
    </div>
  </div>
</header>


<script>
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('topbarSearch');

    function applySearch(query) {
      const normalized = query.trim().toLowerCase();
      const rows = document.querySelectorAll('table.data-table tbody tr, table.table tbody tr, table.schema-table tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!normalized || text.includes(normalized)) ? '' : 'none';
      });

      const cards = Array.from(document.querySelectorAll('main .card'));
      cards.forEach(card => {
        if (card.closest('.table-section')) return;
        const text = card.textContent.toLowerCase();
        card.style.display = (!normalized || text.includes(normalized)) ? '' : 'none';
      });
    }

    searchInput?.addEventListener('input', (event) => {
      applySearch(event.target.value);
    });

    const yearSelect = document.querySelector('main .year-select');
    if (yearSelect) {
      const applyYearFilter = (year) => {
        document.querySelectorAll('main [data-year]').forEach(element => {
          element.style.display = (year === 'all' || element.dataset.year === year) ? '' : 'none';
        });
      };

      yearSelect.addEventListener('change', (event) => applyYearFilter(event.target.value));
      applyYearFilter(yearSelect.value);
    }

    const collectionCategoryFilter = document.getElementById('collectionCategoryFilter');
    const collectionStatusFilter = document.getElementById('collectionStatusFilter');
    const collectionMonthFilter = document.getElementById('collectionMonthFilter');
    const collectionSearch = document.getElementById('collectionSearch');

    if (collectionCategoryFilter || collectionStatusFilter || collectionMonthFilter || collectionSearch) {
      const applyCollectionFilters = () => {
        const categoryValue = collectionCategoryFilter?.value || 'all';
        const statusValue = collectionStatusFilter?.value || 'all';
        const monthValue = collectionMonthFilter?.value || 'all';
        const searchValue = collectionSearch?.value.trim().toLowerCase() || '';

        document.querySelectorAll('main .table-section[data-year] tbody tr').forEach(row => {
          if (!row.dataset.category) return;
          const categoryMatch = categoryValue === 'all' || row.dataset.category === categoryValue;
          const statusMatch = statusValue === 'all' || row.dataset.status === statusValue;
          const monthMatch = monthValue === 'all' || row.dataset.month === monthValue;
          const searchMatch = !searchValue || row.textContent.toLowerCase().includes(searchValue);
          row.style.display = (categoryMatch && statusMatch && monthMatch && searchMatch) ? '' : 'none';
        });
      };

      collectionCategoryFilter?.addEventListener('change', applyCollectionFilters);
      collectionStatusFilter?.addEventListener('change', applyCollectionFilters);
      collectionMonthFilter?.addEventListener('change', applyCollectionFilters);
      collectionSearch?.addEventListener('input', applyCollectionFilters);

      applyCollectionFilters();
    }
  });
</script>
