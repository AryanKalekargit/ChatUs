<?php
// auth.php
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$loginMessage = '';
$registerMessage = '';
$startOnRegister = false;

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['login_submit'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            $result = $auth->login($username, $password);
            if ($result['status'] === 'success') {
                header("Location: chat.php");
                exit();
            } else {
                $loginMessage = $result['message'];
            }
        } else {
            $loginMessage = "Please fill in all fields.";
        }
    } elseif (isset($_POST['register_submit'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $startOnRegister = true; // Stay on register side if there's an error

        if (!empty($username) && !empty($email) && !empty($password)) {
            $result = $auth->register($username, $email, $password);
            if ($result['status'] === 'success') {
                $loginMessage = "Registration successful! Welcome to ChatUs.";
                $startOnRegister = false; // Flip back to login side
            } else {
                $registerMessage = $result['message'];
            }
        } else {
            $registerMessage = "Please fill in all fields.";
        }
    }
}

// Check for registration success redirect flag
if (isset($_GET['registered'])) {
    $loginMessage = "Account created! Vibe in.";
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatUs - Portal</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Zen+Dots&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-neon: #00f2ff;
            --secondary-neon: #ff00d4;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: #0a0a0c;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Mesh Background */
        .mesh-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: radial-gradient(circle at 20% 30%, rgba(0, 242, 255, 0.15), transparent 40%),
                        radial-gradient(circle at 80% 70%, rgba(255, 0, 212, 0.15), transparent 40%),
                        radial-gradient(circle at 50% 50%, rgba(124, 0, 255, 0.1), transparent 50%);
            filter: blur(80px);
            animation: meshMove 20s infinite alternate linear;
        }

        @keyframes meshMove {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.2) translate(5%, 5%); }
        }

        /* FLIP CARD SYSTEM */
        .auth-container {
            width: 100%;
            max-width: 440px;
            height: 600px;
            perspective: 1500px;
            position: relative;
            z-index: 10;
        }

        .auth-card-inner {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.1, 0.7, 0.1, 1);
        }

        .auth-container.is-flipped .auth-card-inner {
            transform: rotateY(180deg);
        }

        .auth-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            background: var(--glass-bg);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .auth-face.back {
            transform: rotateY(180deg);
        }

        /* TYPOGRAPHY & UI */
        .brand-logo {
            font-family: 'Zen Dots', sans-serif;
            font-size: 2.2rem;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-neon), var(--secondary-neon));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .sub-heading {
            text-align: center;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.2em;
            margin-bottom: 35px;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.5);
            margin-bottom: 8px;
        }

        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 12px 20px;
            color: #fff;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--primary-neon);
            box-shadow: 0 0 15px rgba(0, 242, 255, 0.2);
            color: #fff;
        }

        .btn-premium {
            background: linear-gradient(135deg, var(--primary-neon), var(--secondary-neon));
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #000;
            transition: 0.3s;
            margin-top: 15px;
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 242, 255, 0.4);
            filter: brightness(1.1);
        }

        .auth-footer {
            position: absolute;
            bottom: 40px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 0.9rem;
        }

        .auth-link {
            color: var(--primary-neon);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .auth-link:hover {
            color: var(--secondary-neon);
            text-decoration: underline;
        }

        .alert-custom {
            background: rgba(255, 53, 94, 0.1);
            border: 1px solid rgba(255, 53, 94, 0.2);
            color: #ff355e;
            border-radius: 16px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            padding: 12px;
        }

        .alert-success-custom {
            background: rgba(0, 255, 153, 0.1);
            border: 1px solid rgba(0, 255, 153, 0.2);
            color: #00ff99;
            border-radius: 16px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            padding: 12px;
        }
    </style>
</head>
<body>

    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        #vanta-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -3;
        }

        #tsparticles {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div id="vanta-bg"></div>
    <div id="tsparticles"></div>

    <div class="auth-container <?php echo $startOnRegister ? 'is-flipped' : ''; ?>" id="auth-container">
        <div class="auth-card-inner">
            
            <!-- FRONT FACE: LOGIN -->
            <div class="auth-face front">
                <div class="brand-logo">ChatUs</div>
                <div class="sub-heading">Vibe In</div>

                <?php if ($loginMessage): ?>
                    <div class="alert <?php echo (strpos($loginMessage, 'Welcome') !== false || strpos($loginMessage, ' Account created') !== false) ? 'alert-success-custom' : 'alert-custom'; ?>">
                        <i class="bi bi-info-circle me-2"></i> <?php echo htmlspecialchars($loginMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="your_handle" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="login_submit" class="btn btn-premium w-100">Sign In</button>
                </form>

                <div class="auth-footer">
                    <span class="opacity-50">New here?</span> 
                    <a class="auth-link" onclick="toggleFlip()">Start Registration</a>
                </div>
            </div>

            <!-- BACK FACE: REGISTER -->
            <div class="auth-face back">
                <div class="brand-logo">ChatUs</div>
                <div class="sub-heading">Start the vibe</div>

                <?php if ($registerMessage): ?>
                    <div class="alert alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($registerMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="cool_username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="you@vibe.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="register_submit" class="btn btn-premium w-100">Create Account</button>
                </form>

                <div class="auth-footer">
                    <span class="opacity-50">Already vibing?</span> 
                    <a class="auth-link" onclick="toggleFlip()">Login Now</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Background Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.fog.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>

    <script>
        function toggleFlip() {
            document.getElementById('auth-container').classList.toggle('is-flipped');
        }

        // 1. VANTA FOG: The atmospheric base
        VANTA.FOG({
            el: "#vanta-bg",
            mouseControls: true,
            touchControls: true,
            gyroControls: false,
            minHeight: 200.00,
            minWidth: 200.00,
            highlightColor: 0x00f2ff,
            midtoneColor: 0xff00d4,
            lowlightColor: 0x7c00ff,
            baseColor: 0x08080a,
            blurFactor: 0.90,
            speed: 1.50,
            zoom: 0.50
        });

        // 2. tsParticles: Floating Themed Icons & Ambient Dust
        tsParticles.load("tsparticles", {
            fpsLimit: 60,
            particles: {
                number: { value: 40, density: { enable: true, value_area: 800 } },
                color: { value: "#ffffff" },
                shape: { 
                    type: "circle", // Floating "Bubbles"
                },
                opacity: {
                    value: 0.2,
                    random: true,
                    anim: { enable: true, speed: 0.5, opacity_min: 0, sync: false }
                },
                size: {
                    value: 6,
                    random: true,
                    anim: { enable: true, speed: 2, size_min: 1, sync: false }
                },
                move: {
                    enable: true,
                    speed: 0.8,
                    direction: "none",
                    random: true,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onHover: { enable: true, mode: "bubble" },
                    resize: true
                },
                modes: {
                    bubble: { distance: 200, size: 8, duration: 2, opacity: 0.5, speed: 3 }
                }
            },
            retina_detect: true
        });
    </script>

</body>
</html>
