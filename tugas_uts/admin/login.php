<?php
session_start();
require_once '../config/database.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $captcha_input = trim($_POST['captcha']);
    $captcha_session = $_SESSION['captcha_text'] ?? '';

    if (empty($username) || empty($password) || empty($captcha_input)) {
        $error = 'Semua field harus diisi.';
    } elseif (strtolower($captcha_input) !== strtolower($captcha_session)) {
        $error = 'Kode captcha salah.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            // Regenerasi session ID untuk keamanan
            session_regenerate_id(true);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username/email atau password salah.';
        }
    }
    // Hapus captcha dari session setelah digunakan
    unset($_SESSION['captcha_text']);
}

// Generate captcha acak 4 karakter (tanpa karakter ambigu)
function generateCaptcha($length = 4) {
    $characters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $captcha = '';
    for ($i = 0; $i < $length; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}
$captcha_text = generateCaptcha();
$_SESSION['captcha_text'] = $captcha_text;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Futuristik | DynamicPost Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts: Poppins & Orbitron -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        /* Efek partikel dinamis (CSS saja) */
        .particle {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            pointer-events: none;
            animation: float 8s infinite ease-in-out;
        }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            50% { opacity: 0.5; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }
        .login-card {
            backdrop-filter: blur(12px);
            background: rgba(255,255,255,0.1);
            border-radius: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 25px 45px rgba(0,0,0,0.2);
            transition: all 0.4s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 55px rgba(0,0,0,0.3);
            border-color: rgba(255,255,255,0.4);
        }
        .form-control, .input-group-text {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-weight: 500;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.25);
            border-color: #00d2ff;
            box-shadow: 0 0 12px rgba(0,210,255,0.5);
            color: #fff;
        }
        .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }
        .btn-futuristic {
            background: linear-gradient(45deg, #00d2ff, #3a7bd5);
            border: none;
            color: white;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .btn-futuristic:hover {
            background: linear-gradient(45deg, #3a7bd5, #00d2ff);
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(0,210,255,0.6);
        }
        .captcha-box {
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            padding: 10px;
            text-align: center;
            font-family: 'Orbitron', monospace;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 8px;
            color: #00ffcc;
            text-shadow: 0 0 5px #00ffcc;
            border: 1px dashed #00ffcc;
            cursor: pointer;
            transition: 0.2s;
        }
        .captcha-box:hover {
            background: rgba(0,255,204,0.1);
            transform: scale(1.01);
        }
        .refresh-captcha {
            cursor: pointer;
            transition: 0.2s;
        }
        .refresh-captcha:hover {
            color: #00ffcc;
            transform: rotate(180deg);
        }
        a {
            color: #00d2ff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .brand-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body>

<!-- Efek partikel dinamis (JS nanti) -->
<div id="particles-container"></div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="login-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="fas fa-brain brand-icon"></i>
                    <h2 class="text-white mt-2" style="font-family: 'Orbitron';">DynamicPost</h2>
                    <p class="text-light-50">Admin & Author Login</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Username atau Email" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="row align-items-center">
                            <div class="col-7">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                    <input type="text" class="form-control" name="captcha" placeholder="Kode Captcha" required>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="captcha-box text-center" id="captchaDisplay">
                                    <?= $captcha_text ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-1">
                            <small class="text-light refresh-captcha" id="refreshCaptcha">
                                <i class="fas fa-sync-alt"></i> Refresh Captcha
                            </small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-futuristic w-100 py-2 mt-2">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                <div class="text-center mt-4">
                    <small class="text-light-50">&copy; 2025 DynamicPost - Secure Access</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Membuat partikel dinamis dengan vanilla JS (efek futuristik)
    function createParticles() {
        const container = document.getElementById('particles-container');
        const particleCount = 50;
        for (let i = 0; i < particleCount; i++) {
            let particle = document.createElement('div');
            particle.classList.add('particle');
            let size = Math.random() * 6 + 2;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = Math.random() * 8 + 4 + 's';
            particle.style.animationDelay = Math.random() * 5 + 's';
            container.appendChild(particle);
        }
    }
    window.onload = createParticles;

    // Logic untuk mengelola captcha refresh menggunakan Fetch API
    const refreshBtn = document.getElementById('refreshCaptcha');
    const captchaDisplay = document.getElementById('captchaDisplay');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            try {
                const response = await fetch('captcha_refresh.php', {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (data.captcha) {
                    captchaDisplay.innerText = data.captcha;
                } else {
                    // fallback reload halaman
                    location.reload();
                }
            } catch(err) {
                console.error(err);
                location.reload();
            }
        });
    }
</script>
</body>
</html>