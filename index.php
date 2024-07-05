<?php
session_start();
ini_set('session.cookie_httponly', true);

include 'db_conn.php';

function generateCaptchaCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $captchaCode = '';
    for ($i = 0; $i < $length; $i++) {
        $captchaCode .= $characters[rand(0, $charactersLength - 1)];
    }
    return $captchaCode;
}

$captchaCode = generateCaptchaCode();
$_SESSION['captcha'] = $captchaCode;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="static/fontawesome/css/all.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="static/js/jquery-3.7.1.js"></script>
    <style>
        @font-face {
            font-family: logo-font;
            src: url(static/fonts/Caveat/static/Caveat-Regular.ttf);
        }

        @font-face {
            font-family: all;
            src: url(static/fonts/Noto_sans/static/NotoSans-Light.ttf);
        }

        body {
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #394451;
            font-family: all;
        }

        .login-container {
            background-color: #465268;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
            margin-top: 50px;
        }

        .logo {
            font-family: logo-font;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            width: 50px;
            margin-right: 10px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .forgot-password {
            font-size: 16px;
            color: #007bff;
            text-decoration: none;
            margin-top: 10px;
            display: block;
        }

        .captcha-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 20px;
        }

        .captcha-input {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .refresh-btn {
            margin-left: 10px;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .refresh-btn:hover {
            background-color: #0056b3;
        }

        .copyright {
            font-size: 12px;
            color: #fffafa;
            margin-top: 20px;
            z-index: 9999;
        }

        .captcha-text {
            padding: 10px;
            width: 100px;
            background-color: #87b1dd;
            color: #fff;
            font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
            font-size: 1.2rem;
        }

        .refresh-link {
            text-decoration: none;
            margin-left: 10px;
            color: #036ad8;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div style="color: red; text-align: center;">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>
        <div class="logo">
            <img src="static/Images/logo.png" alt="Logo" />
            <span style="color:#fff">ATMS</span>
        </div>
        <form action="login.php" method="post">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="captcha-container">
                <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 10px;">
                    <div class="captcha-text"><?php echo $captchaCode; ?></div>
                    <div>
                        <a href="#" class="refresh-link"><i class="fa-light fa-arrows-rotate"></i> Refresh</a>
                    </div>
                </div>
                <input style="margin:10px 0px;" type="text" name="captcha" class="captcha-input"
                    placeholder="Enter Captcha" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="copyright">All right reserved &copy; ATMS_Capstone project CIU</div>
    </div>

    <script>
        $(document).ready(function () {
            $('.refresh-link').click(function (event) {
                event.preventDefault();
                $.ajax({
                    url: 'refresh_captcha.php',
                    type: 'GET',
                    success: function (data) {
                        $('.captcha-text').text(data);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error refreshing captcha:', error);
                    }
                });
            });
        });
    </script>
</body>

</html>
