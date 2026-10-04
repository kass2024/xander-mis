<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/contract_service_settings.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/currencies.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$csrf = pcvc_csrf_token();
$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!pcvc_csrf_validate_post()) {
        $error = 'Invalid security token. Reload the page and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $items = [];
        $names = $_POST['item_name'] ?? [];
        $amounts = $_POST['item_amount'] ?? [];
        $notes = $_POST['item_note'] ?? [];
        if (is_array($names)) {
            foreach ($names as $i => $name) {
                $items[] = [
                    'name' => (string) $name,
                    'amount' => $amounts[$i] ?? '',
                    'note' => (string) ($notes[$i] ?? ''),
                ];
            }
        }
        $countryIds = xander_contract_services_country_ids(is_array($_POST['country_ids'] ?? null) ? $_POST['country_ids'] : []);
        if ($action === 'save') {
            $result = xander_contract_services_save(
                $conn,
                (int) ($_POST['package_id'] ?? 0),
                (string) ($_POST['title'] ?? ''),
                (string) ($_POST['service_section'] ?? ''),
                (string) ($_POST['currency'] ?? ''),
                isset($_POST['is_active']),
                $items,
                $countryIds
            );
        } else {
            $result = xander_contract_services_create(
                $conn,
                (string) ($_POST['title'] ?? ''),
                (string) ($_POST['service_section'] ?? ''),
                (string) ($_POST['currency'] ?? ''),
                $items,
                $countryIds
            );
        }
        if (!empty($result['ok'])) {
            header('Location: ' . $basePath . '/admin-contract-services.php?saved=1');
            exit;
        }
        $error = (string) ($result['error'] ?? 'Could not save.');
    }
}

if (isset($_GET['saved'])) {
    $message = 'Service prices saved. New contract links will show these prices.';
}

$packages = xander_contract_services_admin_list($conn);
$sections = xander_contract_service_sections();
$countryOptions = xander_contract_service_country_options($conn);
$countriesByPackage = xander_contract_services_country_links($conn)['by_package'];
$currencies = [];
foreach (xander_priority_currency_codes() as $code) {
    $all = xander_all_currencies();
    if (isset($all[$code])) {
        $currencies[$code] = $code . ' — ' . $all[$code];
    }
}
$grouped = [];
foreach ($sections as $key => $label) {
    $grouped[$key] = [];
}
foreach ($packages as $package) {
    $section = $package['service_section'];
    if (!isset($grouped[$section])) {
        $section = 'study';
    }
    $grouped[$section][] = $package;
}

function svc_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** @param list<int> $selectedIds */
function svc_country_picker(array $selectedIds): void
{
    $selected = implode(',', array_map('intval', $selectedIds));
    ?>
    <div class="sc-field svc-country-picker" data-country-picker data-selected="<?= svc_h($selected) ?>">
        <label>Available in <span class="sc-required">Required</span></label>
        <input class="svc-country-filter" type="text" placeholder="Type a country, for example France" autocomplete="off" spellcheck="false">
        <div class="svc-country-chips"></div>
        <div class="svc-country-results"></div>
        <div class="svc-country-inputs"></div>
        <p class="sc-note">Choose every country where this service can be offered. Generate Contract Link lists it for that country.</p>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contract services and prices | Xander Global Scholars</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= svc_h($basePath) ?>/assets/css/contract-modern.css">
<link rel="stylesheet" href="<?= svc_h($basePath) ?>/assets/css/service-contract-wizard.css?v=20261004f">
</head>
<body class="xgs-contract-body">
<main class="sc-shell">
<div class="sc-card">
    <a class="sc-btn sc-btn-ghost" href="<?= svc_h($basePath) ?>/admin-dashboard.php">← Dashboard</a>
    <h1>Contract services and prices</h1>
    <p class="sc-lead">These are the services shown in section 5 of the student contract and in Generate Contract Link. Choose the countries where each service is available. The terms and conditions stay the same.</p>
    <?php if ($message !== ''): ?><div class="sc-note"><?= svc_h($message) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="sc-error"><?= svc_h($error) ?></div><?php endif; ?>

    <details open>
        <summary style="font-weight:800; cursor:pointer; margin:12px 0;">Add a service</summary>
        <form method="post" style="margin-top:12px;">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= svc_h($csrf) ?>">
            <div class="sc-grid-2">
                <div class="sc-field"><label>Service name</label><input name="title" required></div>
                <div class="sc-field">
                    <label>Group</label>
                    <select name="service_section">
                        <?php foreach ($sections as $key => $label): ?>
                        <option value="<?= svc_h($key) ?>"><?= svc_h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sc-field">
                <label>Currency</label>
                <select name="currency">
                    <?php foreach ($currencies as $code => $label): ?>
                    <option value="<?= svc_h($code) ?>" <?= $code === 'EUR' ? 'selected' : '' ?>><?= svc_h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sc-grid-2">
                <div class="sc-field"><label>Price line</label><input name="item_name[]" placeholder="Upfront" required></div>
                <div class="sc-field"><label>Amount</label><input name="item_amount[]" type="number" min="0.01" step="0.01" required></div>
            </div>
            <div class="sc-field"><label>Note</label><input name="item_note[]" placeholder="Non-refundable"></div>
            <?php svc_country_picker([]); ?>
            <button class="sc-btn sc-btn-primary" type="submit">Add service</button>
        </form>
    </details>

    <?php foreach ($sections as $sectionKey => $sectionLabel): ?>
    <h2 style="margin-top:28px;"><?= svc_h($sectionLabel) ?></h2>
    <?php if ($grouped[$sectionKey] === []): ?>
        <p class="sc-empty">No services in this group.</p>
    <?php endif; ?>
    <?php foreach ($grouped[$sectionKey] as $package): ?>
    <details style="border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; margin-bottom:10px; background:#fff;">
        <summary style="cursor:pointer; font-weight:700;">
            <?= svc_h($package['title']) ?>
            — <?= svc_h(xander_contract_services_money((float) $package['total_amount'], (string) $package['currency'])) ?>
            <?php
            $savedCountries = $countriesByPackage[(int) $package['id']] ?? [];
            $savedNames = array_map(static fn (array $country): string => (string) $country['name'], $savedCountries);
            ?>
            — <?= $savedNames === [] ? 'No country yet' : svc_h(implode(', ', $savedNames)) ?>
            <?= $package['is_active'] ? '' : ' (hidden)' ?>
        </summary>
        <form method="post" style="margin-top:14px;">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="csrf_token" value="<?= svc_h($csrf) ?>">
            <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
            <div class="sc-field"><label>Service name</label><input name="title" value="<?= svc_h($package['title']) ?>" required></div>
            <div class="sc-grid-2">
                <div class="sc-field">
                    <label>Group</label>
                    <select name="service_section">
                        <?php foreach ($sections as $key => $label): ?>
                        <option value="<?= svc_h($key) ?>" <?= $package['service_section'] === $key ? 'selected' : '' ?>><?= svc_h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sc-field">
                    <label>Currency</label>
                    <select name="currency">
                        <?php
                        $options = $currencies;
                        if (!isset($options[$package['currency']])) {
                            $options[$package['currency']] = $package['currency'];
                        }
                        foreach ($options as $code => $label): ?>
                        <option value="<?= svc_h($code) ?>" <?= $package['currency'] === $code ? 'selected' : '' ?>><?= svc_h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <label style="display:flex; gap:8px; align-items:center; margin-bottom:12px;">
                <input type="checkbox" name="is_active" <?= $package['is_active'] ? 'checked' : '' ?>>
                Show this service on the contract
            </label>
            <p style="font-weight:700; margin-bottom:8px;">Prices</p>
            <?php
            $rows = $package['items'];
            $rows[] = ['name' => '', 'amount' => '', 'note' => ''];
            foreach ($rows as $item): ?>
            <div class="sc-grid-2">
                <div class="sc-field"><label>Line</label><input name="item_name[]" value="<?= svc_h($item['name']) ?>"></div>
                <div class="sc-field"><label>Amount</label><input name="item_amount[]" type="number" min="0.01" step="0.01" value="<?= $item['amount'] === '' ? '' : svc_h($item['amount']) ?>"></div>
            </div>
            <div class="sc-field"><label>Note</label><input name="item_note[]" value="<?= svc_h($item['note']) ?>"></div>
            <?php endforeach; ?>
            <?php svc_country_picker(array_map(static fn (array $country): int => (int) $country['id'], $savedCountries)); ?>
            <button class="sc-btn sc-btn-primary" type="submit">Save prices</button>
        </form>
    </details>
    <?php endforeach; ?>
    <?php endforeach; ?>
</div>
</main>
<script>
window.SVC_COUNTRIES = <?= json_encode($countryOptions, JSON_UNESCAPED_UNICODE) ?>;
(function () {
  const countries = Array.isArray(window.SVC_COUNTRIES) ? window.SVC_COUNTRIES : [];
  const aliases = {
    'united states': ['usa', 'us', 'america'],
    'united kingdom': ['uk', 'britain'],
    'united arab emirates': ['uae'],
    'netherlands': ['holland'],
    'cote d ivoire': ['ivory'],
    'eswatini': ['swaziland']
  };
  function fold(value) {
    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
  }
  function matches(name, query) {
    const q = fold(query);
    if (!q) return false;
    const folded = fold(name);
    const words = folded.split(' ').filter(Boolean);
    const tokens = q.split(' ').filter(Boolean);
    if (tokens.every((token) => words.some((word) => word.startsWith(token)))) return true;
    const extra = aliases[folded] || [];
    return tokens.every((token) => extra.some((alias) => fold(alias).startsWith(token)));
  }
  document.querySelectorAll('[data-country-picker]').forEach((picker) => {
    const selected = new Set(String(picker.dataset.selected || '').split(',').map((id) => Number(id)).filter((id) => id > 0));
    const filter = picker.querySelector('.svc-country-filter');
    const chips = picker.querySelector('.svc-country-chips');
    const results = picker.querySelector('.svc-country-results');
    const inputs = picker.querySelector('.svc-country-inputs');
    const byId = {};
    countries.forEach((country) => { byId[country.id] = country; });
    function render() {
      inputs.innerHTML = Array.from(selected).map((id) => '<input type="hidden" name="country_ids[]" value="' + id + '">').join('');
      chips.innerHTML = Array.from(selected).map((id) => {
        const country = byId[id];
        if (!country) return '';
        return '<span class="svc-country-chip">' + country.name.replace(/[&<>]/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[ch])) + '<button type="button" data-remove="' + id + '" aria-label="Remove ' + country.name.replace(/"/g, '') + '">&times;</button></span>';
      }).join('');
      const query = filter.value.trim();
      const found = query === '' ? [] : countries.filter((country) => !selected.has(country.id) && matches(country.name, query)).slice(0, 8);
      if (!query) {
        results.innerHTML = '<p class="svc-country-hint">Type to find a country, then add it.</p>';
      } else if (!found.length) {
        results.innerHTML = '<p class="svc-country-hint">No country matches that search.</p>';
      } else {
        results.innerHTML = found.map((country) => '<div class="svc-country-option"><span>' + country.name.replace(/[&<>]/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[ch])) + '</span><button type="button" data-add="' + country.id + '">Add</button></div>').join('');
      }
    }
    filter.addEventListener('input', render);
    filter.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      const button = results.querySelector('[data-add]');
      if (!button) return;
      selected.add(Number(button.getAttribute('data-add')));
      filter.value = '';
      render();
    });
    picker.addEventListener('click', (event) => {
      const add = event.target.closest('[data-add]');
      const remove = event.target.closest('[data-remove]');
      if (add) {
        selected.add(Number(add.getAttribute('data-add')));
        filter.value = '';
        render();
        filter.focus();
      } else if (remove) {
        selected.delete(Number(remove.getAttribute('data-remove')));
        render();
      }
    });
    render();
  });
})();
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
