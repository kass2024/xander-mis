<?php
// commission-requests-report.php
date_default_timezone_set('UTC');
require_once 'auth.php';
require_once 'db.php';

// Fetch applicants from commission_requests table
$query = "
  SELECT *
  FROM commission_requests
  ORDER BY id DESC
";

$result = $conn->query($query);
$applicants = [];
while ($row = $result->fetch_assoc()) {
    $applicants[] = $row;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Commission Requests Report</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Roboto', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      height: 100vh;
      background: #f7f8fa;
      color: #333;
    }

    .sidebar {
      width: 300px;
      min-width: 250px;
      background: #ffffff;
      border-right: 1px solid #ddd;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 6px rgba(0, 0, 0, 0.03);
      padding: 1rem;
    }

    .sidebar input,
    .sidebar select {
      margin-bottom: 14px;
      padding: 10px 14px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 1rem;
      width: 100%;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .sidebar input:focus,
    .sidebar select:focus {
      border-color: #007bff;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
    }

    .applicant-list {
      list-style: none;
      padding: 0;
      margin: 0;
      overflow-y: auto;
      flex: 1;
    }

    .applicant-list li {
      padding: 12px 14px;
      border-bottom: 1px solid #ececec;
      cursor: pointer;
      transition: background 0.2s;
    }

    .applicant-list li:hover {
      background: #f0f7ff;
    }

    .applicant-list li.active {
      background: #dbe9ff;
      font-weight: bold;
    }

    .main-view {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
      background: #ffffff;
    }

    .main-view h2 {
      margin-top: 0;
      font-size: 1.6rem;
      color: #004085;
      margin-bottom: 1rem;
    }

    .detail-section {
      background: #ffffff;
      padding: 1.5rem 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
      margin-bottom: 2rem;
    }

    .detail-block {
      display: flex;
      align-items: flex-start;
      font-size: 0.95rem;
      line-height: 1.5;
      margin-bottom: 1rem;
    }

    .detail-block strong {
      width: 200px;
      font-weight: 600;
      color: #333;
      flex-shrink: 0;
    }

    .detail-block span,
    .detail-block .tag {
      flex: 1;
      display: block;
    }

    .unread-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      background: #007bff;
      border-radius: 50%;
      margin-left: 6px;
      margin-top: 2px;
    }

    li.read .unread-dot {
      display: none;
    }

    .elapsed {
      font-size: 0.75rem;
      color: #888;
    }

    .meta-line {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 6px;
      font-size: 0.8rem;
      color: #999;
    }

    /* Responsive Layout */
    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        min-height: auto;
        border-right: none;
        border-bottom: 1px solid #ccc;
      }

      .main-view {
        padding: 1rem;
      }

      .detail-block {
        flex-direction: column;
      }

      .detail-block strong {
        width: 100%;
        margin-bottom: 4px;
      }
    }
  </style>
</head>
<body>
<div class="sidebar">
  <input type="text" id="searchInput" placeholder="Search requests...">
  <ul class="applicant-list" id="applicantList">
    <?php foreach ($applicants as $i => $applicant): ?>
      <li data-index="<?= $i ?>" data-id="<?= $applicant['id'] ?>" class="<?= $applicant['is_read'] ? 'read' : '' ?>">
        <div><strong><?= htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) ?></strong></div>
        <div><small><?= htmlspecialchars($applicant['email']) ?></small></div>
        <div class="meta-line">
          <span class="elapsed"><?= htmlspecialchars($applicant['created_at'] ?? '') ?></span>
          <span class="unread-dot"></span>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<div class="main-view" id="detailView"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
   const applicants = <?php echo json_encode($applicants ?? []); ?>;
  const detailView = document.getElementById('detailView');
  const searchInput = document.getElementById('searchInput');

  const renderDetail = (a) => {
    detailView.innerHTML = `
  <div class="detail-section">
    <h2>${a.first_name || ''} ${a.last_name || ''}</h2>

    <div class="detail-block"><strong>Email:</strong><span>${a.email || ''}</span></div>
    <div class="detail-block"><strong>Phone:</strong><span>${a.phone || ''}</span></div>
    <div class="detail-block"><strong>City:</strong><span>${a.city || ''}</span></div>
    <div class="detail-block"><strong>Country Applied:</strong><span>${a.country_applied || ''}</span></div>
    <div class="detail-block"><strong>Loan Status:</strong><span>${a.loan_status || ''}</span></div>
    <div class="detail-block"><strong>Visa Status:</strong><span>${a.visa_status || ''}</span></div>
    <div class="detail-block"><strong>Contract Signed:</strong><span>${a.contract_signed || ''}</span></div>
    <div class="detail-block"><strong>Recruited Name:</strong><span>${a.recruited_name || ''}</span></div>
    <div class="detail-block"><strong>Recruited Phone:</strong><span>${a.recruited_phone || ''}</span></div>
    <div class="detail-block"><strong>Submission Date:</strong><span>${a.submission_date || ''}</span></div>
    <div class="detail-block"><strong>Comments:</strong><span>${a.comments || ''}</span></div>
  </div>
`;
  };

  document.querySelectorAll('#applicantList li').forEach((li, i) => {
    const applicant = applicants[i];

    li._dotRef = li.querySelector('.unread-dot');

    if (applicant.is_read == 1) {
      li.classList.add('read');
      if (li._dotRef) li._dotRef.style.display = 'none';
    } else {
      li.classList.remove('read');
      if (li._dotRef) li._dotRef.style.display = 'inline-block';
    }

    li.addEventListener('click', () => {
      document.querySelectorAll('#applicantList li').forEach(el => el.classList.remove('active'));
      li.classList.add('active');
      if (!li.classList.contains('read')) {
        markAsRead(applicant.id, li);
      }
      renderDetail(applicant);
    });

    if (i === 0) {
      li.classList.add('active');
      renderDetail(applicant);
    }
  });

  searchInput.addEventListener('input', function () {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#applicantList li').forEach(li => {
      li.style.display = li.textContent.toLowerCase().includes(val) ? 'block' : 'none';
    });
  });

  function markAsRead(applicantId, listItem) {
    fetch('mark_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(applicantId) + '&table=commission_requests'
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'ok') {
        listItem.classList.add('read');
        if (listItem._dotRef) listItem._dotRef.style.display = 'none';
      } else {
        console.error('Failed to mark as read:', data.message);
      }
    })
    .catch(err => console.error('Mark as read failed:', err));
  }

});
</script>

</body>
</html>
