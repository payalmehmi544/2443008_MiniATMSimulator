<?php
/*
 * Mini ATM Simulator
 * Roll No: 2443008
 * Technologies: HTML, CSS, PHP (no database)
 * The balance is kept in a PHP session, so it exists only while the program runs
 * and is cleared when the user chooses Exit.
 */

date_default_timezone_set('Asia/Kolkata');
session_start();

define('ACCOUNT_PIN', '1234');
define('OPENING_BALANCE', 10000.00);
define('MAX_WITHDRAWAL', 20000.00);

$message = '';
$messageType = '';
$screen = 'login';

/* Record a transaction in the session history */
function addTransaction($type, $amount, $balance)
{
    $_SESSION['history'][] = [
        'time'    => date('d-m-Y h:i:s A'),
        'type'    => $type,
        'amount'  => $amount,
        'balance' => $balance,
    ];
}

/* Format amount in rupees */
function rupees($amount)
{
    return '&#8377; ' . number_format($amount, 2);
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

/* ---------- Exit: end the session ---------- */
if ($action === 'exit') {
    $_SESSION = [];
    session_destroy();
    session_start();
    $message = 'Thank you for using Mini ATM. Please collect your card.';
    $messageType = 'info';
}

/* ---------- Login with PIN ---------- */
if ($action === 'login') {
    $pin = trim($_POST['pin'] ?? '');
    if ($pin === ACCOUNT_PIN) {
        $_SESSION['logged_in'] = true;
        $_SESSION['balance'] = OPENING_BALANCE;
        $_SESSION['history'] = [];
        $message = 'Login successful. Welcome!';
        $messageType = 'success';
    } else {
        $message = 'Incorrect PIN. Please try again.';
        $messageType = 'error';
    }
}

$loggedIn = !empty($_SESSION['logged_in']);

if ($loggedIn) {
    $screen = 'menu';

    switch ($action) {
        case 'balance':
            $screen = 'balance';
            addTransaction('Balance Inquiry', 0, $_SESSION['balance']);
            break;

        case 'deposit_form':
            $screen = 'deposit';
            break;

        case 'withdraw_form':
            $screen = 'withdraw';
            break;

        case 'history':
            $screen = 'history';
            break;

        case 'deposit':
            $amount = (float) ($_POST['amount'] ?? 0);
            if ($amount <= 0) {
                $message = 'Please enter a valid amount greater than zero.';
                $messageType = 'error';
                $screen = 'deposit';
            } elseif (fmod($amount, 100) != 0) {
                $message = 'Deposit amount must be in multiples of 100.';
                $messageType = 'error';
                $screen = 'deposit';
            } else {
                $_SESSION['balance'] += $amount;
                addTransaction('Deposit', $amount, $_SESSION['balance']);
                $screen = 'receipt';
            }
            break;

        case 'withdraw':
            $amount = (float) ($_POST['amount'] ?? 0);
            if ($amount <= 0) {
                $message = 'Please enter a valid amount greater than zero.';
                $messageType = 'error';
                $screen = 'withdraw';
            } elseif (fmod($amount, 100) != 0) {
                $message = 'Withdrawal amount must be in multiples of 100.';
                $messageType = 'error';
                $screen = 'withdraw';
            } elseif ($amount > MAX_WITHDRAWAL) {
                $message = 'Maximum withdrawal per transaction is ' . rupees(MAX_WITHDRAWAL) . '.';
                $messageType = 'error';
                $screen = 'withdraw';
            } elseif ($amount > $_SESSION['balance']) {
                $message = 'Insufficient balance. Available balance is ' . rupees($_SESSION['balance']) . '.';
                $messageType = 'error';
                $screen = 'withdraw';
            } else {
                $_SESSION['balance'] -= $amount;
                addTransaction('Withdrawal', $amount, $_SESSION['balance']);
                $screen = 'receipt';
            }
            break;
    }
}

$last = ($loggedIn && !empty($_SESSION['history'])) ? end($_SESSION['history']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini ATM Simulator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="atm">
    <header class="atm-header">
        <h1>Mini ATM</h1>
        <p>Simulator &middot; HTML, CSS &amp; PHP</p>
    </header>

    <main class="screen">
        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($screen === 'login'): ?>
            <h2>Insert Card &amp; Enter PIN</h2>
            <form method="post" class="form">
                <input type="hidden" name="action" value="login">
                <label for="pin">4-digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" pattern="\d{4}"
                       placeholder="&bull;&bull;&bull;&bull;" required autofocus>
                <button type="submit" class="btn primary">Login</button>
            </form>
            <p class="hint">Demo PIN: <strong>1234</strong></p>

        <?php elseif ($screen === 'menu'): ?>
            <h2>Main Menu</h2>
            <p class="sub">Please select a transaction</p>
            <div class="menu">
                <a class="btn" href="?action=balance">1. Balance Inquiry</a>
                <a class="btn" href="?action=deposit_form">2. Deposit</a>
                <a class="btn" href="?action=withdraw_form">3. Withdraw</a>
                <a class="btn" href="?action=history">4. Mini Statement</a>
                <a class="btn danger" href="?action=exit">5. Exit</a>
            </div>

        <?php elseif ($screen === 'balance'): ?>
            <h2>Balance Inquiry</h2>
            <div class="balance-box">
                <span>Available Balance</span>
                <strong><?= rupees($_SESSION['balance']) ?></strong>
            </div>
            <a class="btn" href="index.php">Back to Menu</a>

        <?php elseif ($screen === 'deposit' || $screen === 'withdraw'): ?>
            <h2><?= $screen === 'deposit' ? 'Deposit Cash' : 'Withdraw Cash' ?></h2>
            <p class="sub">Current balance: <?= rupees($_SESSION['balance']) ?></p>
            <form method="post" class="form">
                <input type="hidden" name="action" value="<?= $screen ?>">
                <label for="amount">Enter amount (multiples of 100)</label>
                <input type="number" id="amount" name="amount" min="100" step="100" required autofocus>
                <button type="submit" class="btn primary"><?= $screen === 'deposit' ? 'Deposit' : 'Withdraw' ?></button>
                <?php if ($screen === 'withdraw'): ?>
                    <label>Quick cash</label>
                    <div class="quick">
                        <?php foreach ([500, 1000, 2000, 5000] as $q): ?>
                            <button type="submit" name="amount" value="<?= $q ?>" formnovalidate class="btn small"><?= rupees($q) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
            <a class="btn" href="index.php">Cancel</a>

        <?php elseif ($screen === 'receipt' && $last): ?>
            <h2>Transaction Successful</h2>
            <table class="receipt">
                <tr><th>Date &amp; Time</th><td><?= $last['time'] ?></td></tr>
                <tr><th>Transaction</th><td><?= $last['type'] ?></td></tr>
                <tr><th>Amount</th><td><?= rupees($last['amount']) ?></td></tr>
                <tr><th>Updated Balance</th><td class="highlight"><?= rupees($last['balance']) ?></td></tr>
            </table>
            <a class="btn" href="index.php">Back to Menu</a>

        <?php elseif ($screen === 'history'): ?>
            <h2>Mini Statement</h2>
            <?php if (empty($_SESSION['history'])): ?>
                <p class="sub">No transactions in this session yet.</p>
            <?php else: ?>
                <table class="statement">
                    <thead>
                        <tr><th>#</th><th>Time</th><th>Type</th><th>Amount</th><th>Balance</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_SESSION['history'] as $i => $t): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= $t['time'] ?></td>
                            <td><?= $t['type'] ?></td>
                            <td><?= $t['amount'] > 0 ? rupees($t['amount']) : '-' ?></td>
                            <td><?= rupees($t['balance']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <a class="btn" href="index.php">Back to Menu</a>
        <?php endif; ?>
    </main>

    <footer class="atm-footer">Roll No. 2443008 &middot; Mini ATM Simulator</footer>
</div>
</body>
</html>
