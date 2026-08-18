<header class="header">
  <link rel="stylesheet" href="../css/topbar_admin.css"/>
  <link rel="stylesheet" href="../css/record_adding.css"/>

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

    <button class="btn btn-primary" id="addRecordBtn">
      <svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
      </svg>
      Add Record
    </button>

    <!-- User info -->
    <div class="user-info-wrap">
      <div class="avatar-sm">
        <?php echo strtoupper(substr($name,0,2)); ?>
      </div>
      <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
    </div>
  </div>
</header>

<?php include('record_adding.php'); ?>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('addRecordModal');
    if (modal) {
      const addRecordBtn = document.getElementById('addRecordBtn');
      const closeBtn = modal.querySelector('.close-btn');
      const cancelBtn = document.getElementById('cancelRecordBtn');
      const addRecordForm = document.getElementById('addRecordForm');
      const modalTitle = modal.querySelector('.modal-header h2');

      function openModal(mode = 'record') {
        modalTitle.textContent = mode === 'collection' ? 'Add new collection' : 'Add new record';
        modal.classList.add('active');
        addRecordForm.reset();
      }

      function closeModal() {
        modal.classList.remove('active');
      }

      addRecordBtn?.addEventListener('click', () => openModal('record'));

      document.querySelectorAll('.open-add-modal').forEach(button => {
        button.addEventListener('click', () => openModal(button.dataset.mode || 'record'));
      });

      closeBtn?.addEventListener('click', closeModal);
      cancelBtn?.addEventListener('click', closeModal);

      window.addEventListener('click', (event) => {
        if (event.target === modal) {
          closeModal();
        }
      });

      addRecordForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const approvedFunds = parseFloat(document.querySelector('input[name="approved_funds"]').value) || 0;
        const actualExpenditure = parseFloat(document.querySelector('input[name="actual_expenditure"]').value) || 0;
        document.querySelector('input[name="remaining_budget"]').value = (approvedFunds - actualExpenditure).toFixed(2);

        const formData = new FormData(addRecordForm);
        fetch('process_record.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(text => {
          let result;
          try {
            result = JSON.parse(text);
          } catch (error) {
            throw new Error('Invalid server response: ' + text);
          }

          if (result.success) {
            alert(result.message || 'Record saved successfully!');
            closeModal();
            addRecordForm.reset();
            location.reload();
          } else {
            alert(result.error || result.message || 'Error saving record.');
          }
        })
        .catch((error) => {
          console.error('Error saving record:', error);
          alert('Error saving record. Please try again.');
        });
      });
    }

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
