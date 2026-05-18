<?php
/*************************
 * CONFIG
 *************************/
require_once __DIR__ . '/helpers/payment_config.php';
$STRIPE_SECRET_KEY = xander_payment_require_stripe_keys()['secret'];
$LIMIT = 20; // number of transactions to fetch

/*************************
 * FETCH PAYMENT INTENTS
 *************************/
$url = "https://api.stripe.com/v1/payment_intents?limit=" . $LIMIT;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $STRIPE_SECRET_KEY . ":");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Stripe API error:<pre>" . htmlspecialchars($response) . "</pre>");
}

$data = json_decode($response, true);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stripe Transactions (LIVE)</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f4f4f4; }
        .paid { color: green; font-weight: bold; }
        .failed { color: red; font-weight: bold; }
        .pending { color: orange; font-weight: bold; }
    </style>
</head>
<body>

<h2>Stripe LIVE Transactions</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Date</th>
        <th>Amount</th>
        <th>Currency</th>
        <th>Status</th>
        <th>Metadata</th>
    </tr>

<?php foreach ($data['data'] as $pi): ?>
<tr>
    <td><?php echo htmlspecialchars($pi['id']); ?></td>
    <td><?php echo date("Y-m-d H:i:s", $pi['created']); ?></td>
    <td>$<?php echo number_format($pi['amount'] / 100, 2); ?></td>
    <td><?php echo strtoupper($pi['currency']); ?></td>
    <td class="
        <?php
            if ($pi['status'] === 'succeeded') echo 'paid';
            elseif ($pi['status'] === 'requires_payment_method') echo 'failed';
            else echo 'pending';
        ?>
    ">
        <?php echo htmlspecialchars($pi['status']); ?>
    </td>
    <td>
        <?php
        if (!empty($pi['metadata'])) {
            foreach ($pi['metadata'] as $k => $v) {
                echo htmlspecialchars($k) . ": " . htmlspecialchars($v) . "<br>";
            }
        } else {
            echo "-";
        }
        ?>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
