<?php
declare(strict_types=1);

session_start();
require 'connexion.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config_mail.php';

$message = '';
$type = 'info';
$step = $_SESSION['reset_step'] ?? 'request';

if (!empty($_SESSION['reset_flash'])) {
    $message = (string) $_SESSION['reset_flash'];
    $type = 'success';
    unset($_SESSION['reset_flash']);
}

/*
|--------------------------------------------------------------------------
| ORIGINE ET RETOUR AUTOMATIQUE
|--------------------------------------------------------------------------
| La page est commune au DE, au DAF et au Personnel.
| On mémorise la page de connexion d'origine afin que la flèche de retour
| déconnecte proprement la session active et revienne au bon formulaire.
*/
$source = $_GET['source'] ?? $_SESSION['reset_source'] ?? '';

if (!in_array($source, ['admin', 'personnel'], true)) {
    $referer = strtolower($_SERVER['HTTP_REFERER'] ?? '');

    if (isset($_SESSION['personnel']) || str_contains($referer, 'login_personnel.php')) {
        $source = 'personnel';
    } else {
        $source = 'admin';
    }
}

$_SESSION['reset_source'] = $source;

if (isset($_GET['retour']) && $_GET['retour'] === '1') {
    $redirect = $source === 'personnel'
        ? 'login_personnel.php'
        : 'login.php';

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
    header('Location: ' . $redirect);
    exit;
}

function clearReset(): void
{
    foreach ([
        'reset_step','reset_table','reset_id','reset_email',
        'reset_code_hash','reset_expires','reset_attempts','reset_verified'
    ] as $key) {
        unset($_SESSION[$key]);
    }
}

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $sql = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $sql->execute([$table, $column]);
    return (bool) $sql->fetchColumn();
}

function sendCode(string $email, string $code): void
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = str_replace(' ', '', SMTP_APP_PASSWORD);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;
    $mail->SMTPKeepAlive = false;
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = static function (string $message, int $level): void {
        error_log("SMTP niveau {$level} : {$message}");
    };

    $mail->setFrom(SMTP_USERNAME, APP_NAME);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Code de réinitialisation du mot de passe';

    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

    $mail->Body = "
        <div style='font-family:Arial;max-width:600px;margin:auto;padding:28px;
                    border:1px solid #e2e8f0;border-radius:16px'>
            <h2 style='color:#0f172a'>" . APP_NAME . "</h2>
            <p>Votre code de réinitialisation est :</p>
            <div style='font-size:34px;font-weight:bold;letter-spacing:8px;
                        text-align:center;color:#f97316;background:#f1f5f9;
                        padding:20px;border-radius:12px;margin:22px 0'>
                {$safeCode}
            </div>
            <p>Ce code expire dans " . OTP_MINUTES . " minutes.</p>
            <p style='font-size:13px;color:#64748b'>
                Ignorez cet e-mail si vous n'avez pas demandé cette opération.
            </p>
        </div>
    ";

    $mail->AltBody = "Votre code est : {$code}. Il expire dans " . OTP_MINUTES . " minutes.";
    $mail->send();
}

if (isset($_GET['restart'])) {
    clearReset();
    header('Location: reset_password.php?source=' . urlencode($source));
    exit;
}

/* ÉTAPE 1 : adresse e-mail */
if (isset($_POST['send_code'])) {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $message = 'Veuillez saisir une adresse e-mail valide.';
        $type = 'danger';
    } else {
        $accounts = [];

        $adminQuery = $pdo->prepare("
            SELECT id, email FROM admins
            WHERE LOWER(email) = LOWER(?)
        ");
        $adminQuery->execute([$email]);

        foreach ($adminQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $accounts[] = [
                'table' => 'admins',
                'id' => (int) $row['id'],
                'email' => $row['email']
            ];
        }

        $personnelQuery = $pdo->prepare("
            SELECT id, personnel_id, email
            FROM comptes_personnels
            WHERE LOWER(email) = LOWER(?)
              AND actif = 1
        ");
        $personnelQuery->execute([$email]);

        foreach ($personnelQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $accounts[] = [
                'table' => 'comptes_personnels',
                'id' => (int) $row['id'],
                'email' => $row['email']
            ];
        }

        if (count($accounts) === 0) {
            $message = "Cette adresse n'est associée à aucun compte actif.";
            $type = 'danger';
        } elseif (count($accounts) > 1) {
            $message = "Cette adresse est utilisée par plusieurs comptes. Chaque compte doit avoir une adresse différente.";
            $type = 'danger';
        } else {
            $account = $accounts[0];
            $code = (string) random_int(100000, 999999);

            $_SESSION['reset_table'] = $account['table'];
            $_SESSION['reset_id'] = $account['id'];
            $_SESSION['reset_email'] = $account['email'];
            $_SESSION['reset_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
            $_SESSION['reset_expires'] = time() + OTP_MINUTES * 60;
            $_SESSION['reset_attempts'] = 0;
            $_SESSION['reset_verified'] = false;

            try {
                sendCode($account['email'], $code);
                $_SESSION['reset_step'] = 'verify';
                $_SESSION['reset_flash'] = "Un code a été envoyé à l'adresse enregistrée.";
                header('Location: reset_password.php?source=' . urlencode($source));
                exit;
            } catch (Throwable $e) {
                error_log('PHPMailer : ' . $e->getMessage());
                clearReset();
                $step = 'request';
                $message = "Impossible d'envoyer le code. Vérifiez la configuration Gmail SMTP et consultez le journal d'erreurs PHP.";
                $type = 'danger';
            }
        }
    }
}

/* ÉTAPE 2 : code OTP */
if (isset($_POST['verify_code'])) {
    $step = 'verify';
    $code = trim($_POST['code'] ?? '');

    if (
        empty($_SESSION['reset_table']) ||
        empty($_SESSION['reset_id']) ||
        empty($_SESSION['reset_code_hash']) ||
        empty($_SESSION['reset_expires'])
    ) {
        clearReset();
        $step = 'request';
        $message = 'Demande introuvable. Recommencez.';
        $type = 'danger';
    } elseif (time() > (int) $_SESSION['reset_expires']) {
        clearReset();
        $step = 'request';
        $message = 'Le code a expiré. Demandez un nouveau code.';
        $type = 'danger';
    } elseif ((int) ($_SESSION['reset_attempts'] ?? 0) >= MAX_ATTEMPTS) {
        clearReset();
        $step = 'request';
        $message = 'Trop de tentatives. Demandez un nouveau code.';
        $type = 'danger';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $message = 'Le code doit contenir exactement 6 chiffres.';
        $type = 'danger';
    } else {
        $_SESSION['reset_attempts']++;

        if (password_verify($code, $_SESSION['reset_code_hash'])) {
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_step'] = 'password';
            $step = 'password';
            $message = 'Code confirmé. Choisissez votre nouveau mot de passe.';
            $type = 'success';
        } else {
            $remaining = MAX_ATTEMPTS - (int) $_SESSION['reset_attempts'];
            $message = "Code incorrect. Tentatives restantes : {$remaining}.";
            $type = 'danger';
        }
    }
}

/* ÉTAPE 3 : nouveau mot de passe */
if (isset($_POST['save_password'])) {
    $step = 'password';

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $table = $_SESSION['reset_table'] ?? '';
    $id = (int) ($_SESSION['reset_id'] ?? 0);

    if (
        empty($_SESSION['reset_verified']) ||
        !in_array($table, ['admins', 'comptes_personnels'], true) ||
        $id <= 0 ||
        empty($_SESSION['reset_expires'])
    ) {
        clearReset();
        $step = 'request';
        $message = 'Autorisation invalide. Recommencez.';
        $type = 'danger';
    } elseif (time() > (int) $_SESSION['reset_expires']) {
        clearReset();
        $step = 'request';
        $message = 'La session a expiré. Recommencez.';
        $type = 'danger';
    } elseif (strlen($password) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        $type = 'danger';
    } elseif (!preg_match('/[A-Z]/', $password) ||
              !preg_match('/[a-z]/', $password) ||
              !preg_match('/\d/', $password)) {
        $message = 'Ajoutez au moins une majuscule, une minuscule et un chiffre.';
        $type = 'danger';
    } elseif ($password !== $confirm) {
        $message = 'Les deux mots de passe ne correspondent pas.';
        $type = 'danger';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
        $update->execute([$hash, $id]);

        clearReset();
        $step = 'success';
        $message = 'Mot de passe modifié avec succès.';
        $type = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        *{box-sizing:border-box}
        body{
            min-height:100vh;margin:0;padding:22px 15px;display:flex;
            justify-content:center;align-items:center;font-family:Arial,Helvetica,sans-serif;
            background:radial-gradient(circle at top right,rgba(249,115,22,.22),transparent 35%),
                       linear-gradient(135deg,#0f172a,#1e293b)
        }
        .card-reset{
            position:relative;
            width:100%;max-width:445px;background:#fff;padding:38px;border-radius:22px;
            box-shadow:0 24px 60px rgba(0,0,0,.36)
        }
        .logo{text-align:center;margin-bottom:25px}
        .logo img{
            width:90px;height:90px;object-fit:cover;border-radius:20px;margin-bottom:12px;
            box-shadow:0 8px 22px rgba(15,23,42,.22)
        }
        .logo h3{margin:0;color:#0f172a;font-size:23px;font-weight:800}
        .logo p{margin:8px 0 0;color:#64748b;font-size:14px}
        .form-label{font-weight:700;color:#334155}
        .input-group-text,.form-control{min-height:49px;border-color:#cbd5e1}
        .input-group-text{color:#f97316;background:#f8fafc;border-radius:11px 0 0 11px}
        .form-control{border-radius:0 11px 11px 0}
        .single{border-radius:11px}
        .form-control:focus{border-color:#f97316;box-shadow:0 0 0 .2rem rgba(249,115,22,.16)}
        .code{text-align:center;font-size:26px;font-weight:800;letter-spacing:9px}
        .btn-reset{
            width:100%;padding:13px;border:0;border-radius:11px;color:#fff;font-weight:700;
            background:linear-gradient(135deg,#f97316,#ea580c)
        }
        .btn-reset:hover{color:#fff;box-shadow:0 9px 22px rgba(234,88,12,.28)}
        .note{
            padding:12px 14px;background:#f8fafc;border-left:4px solid #f97316;
            border-radius:8px;color:#475569;font-size:13px
        }
        .back{display:inline-block;margin-top:20px;color:#475569;text-decoration:none;font-size:14px}
        .back:hover{color:#ea580c}
        .top-back{
            position:absolute;
            top:18px;
            left:18px;
            width:42px;
            height:42px;
            display:grid;
            place-items:center;
            border:1px solid #cbd5e1;
            border-radius:14px;
            background:#fff;
            color:#0f172a;
            text-decoration:none;
            box-shadow:0 8px 20px rgba(15,23,42,.10);
            transition:.2s;
        }
        .top-back:hover{
            color:#fff;
            background:linear-gradient(135deg,#f97316,#ea580c);
            border-color:transparent;
            transform:translateY(-1px);
        }
        @media(max-width:500px){
            .card-reset{padding:76px 20px 28px}
            .top-back{top:16px;left:16px}
        }
    </style>
</head>
<body>
<div class="card-reset">
    <a
        href="reset_password.php?retour=1&amp;source=<?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?>"
        class="top-back"
        title="Retour à la connexion"
        aria-label="Retour à la connexion"
    >
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="logo">
        <img src="uploads/logo/logo.jpeg" alt="Logo">
        <h3>GESTION ÉCOLE</h3>
        <p>Réinitialisation sécurisée du mot de passe</p>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($step === 'request'): ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="email">Adresse e-mail du compte</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="exemple@gmail.com" required>
                </div>
            </div>

            <div class="note mb-4">
                Le compte DE, DAF ou Personnel sera reconnu automatiquement grâce à son adresse.
            </div>

            <button type="submit" name="send_code" class="btn-reset">
                <i class="bi bi-send me-2"></i>Envoyer le code
            </button>
        </form>

    <?php elseif ($step === 'verify'): ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="code">Code reçu par e-mail</label>
                <input type="text" id="code" name="code"
                       class="form-control single code" maxlength="6"
                       inputmode="numeric" pattern="[0-9]{6}"
                       placeholder="000000" required autofocus>
            </div>

            <div class="note mb-4">Le code expire dans <?= OTP_MINUTES ?> minutes.</div>

            <button type="submit" name="verify_code" class="btn-reset">
                <i class="bi bi-check-circle me-2"></i>Vérifier le code
            </button>
        </form>

        <a href="reset_password.php?restart=1&amp;source=<?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?>" class="back">
            <i class="bi bi-arrow-left me-1"></i>Utiliser une autre adresse
        </a>

    <?php elseif ($step === 'password'): ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label" for="password">Nouveau mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password"
                           class="form-control" minlength="8" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="confirm_password">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" minlength="8" required>
                </div>
            </div>

            <div class="note mb-4">
                Au moins 8 caractères, une majuscule, une minuscule et un chiffre.
            </div>

            <button type="submit" name="save_password" class="btn-reset">
                <i class="bi bi-key me-2"></i>Modifier le mot de passe
            </button>
        </form>

    <?php elseif ($step === 'success'): ?>
        <div class="text-center">
            <div class="display-4 text-success mb-3">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <p class="text-muted">Vous pouvez maintenant vous connecter.</p>

            <a href="login.php" class="btn btn-dark w-100 py-3 rounded-3">
                Connexion DE / DAF
            </a>

            <a href="login_personnel.php"
               class="btn btn-outline-dark w-100 py-3 rounded-3 mt-2">
                Connexion Personnel
            </a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>