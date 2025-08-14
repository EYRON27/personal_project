    <?php
    session_start();
    include 'db.php';

    $errors = [];
    $success = '';
    $action = $_POST['action'] ?? '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($action === "register") {
            if (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters.";
            }

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Username is already taken.";
            }

            if (empty($errors)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $hashed);
                $stmt->execute();
                $success = "Registration successful!";
            }
        }

        if ($action === "login") {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // ✅ critical
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    }
    else {
                $errors[] = "Invalid login credentials.";
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login/Register</title>
        <link rel="stylesheet" href="styles/style.css">
    </head>
    <body>
    <div class="container <?= ($action === 'register' && empty($errors) && $success) ? '' : (($action === 'register') ? 'right-panel-active' : '') ?>" id="container">
        <div class="form-container sign-up-container">
            <form method="POST">
                <h1>Create Account</h1>
                <input type="text" name="username" placeholder="Username" required />
                <input type="password" name="password" placeholder="Password (min 8 characters)" required />
                <input type="hidden" name="action" value="register" />
                <?php if ($action === 'register' && $errors): ?>
                    <div class="error"><?= implode("<br>", $errors) ?></div>
                <?php elseif ($action === 'register' && $success): ?>
                    <div class="success"><?= $success ?></div>
                <?php endif; ?>
                <button type="submit">Sign Up</button>
            </form>
        </div>

        

        <div class="form-container sign-in-container">
            <form method="POST">
                <h1>Sign in</h1>
                <input type="text" name="username" placeholder="Username" required />
                <input type="password" name="password" placeholder="Password" required />
                <input type="hidden" name="action" value="login" />
                <?php if ($action === 'login' && $errors): ?>
                    <div class="error"><?= implode("<br>", $errors) ?></div>
                <?php endif; ?>
                <button type="submit">Sign In</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>SIGN UP FOR FREE</h1>
                    <p>This system made for manage expenses efficiently</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>SIGN UP NOW</h1>
                    <p>Enter your credentials to manage your expenses efficiently</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const signUpBtn = document.getElementById('signUp');
    const signInBtn = document.getElementById('signIn');
    const container = document.getElementById('container');

    signUpBtn.addEventListener('click', () => container.classList.add("right-panel-active"));
    signInBtn.addEventListener('click', () => container.classList.remove("right-panel-active"));
    </script>
    </body>
    </html>

