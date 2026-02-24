<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMPLOYEE LOGIN — UNAUTHORIZED ACCESS PROHIBITED</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="card" id="login-card">
    <p class="warning-banner">⚠ EMPLOYEE TERMINAL — NOT FOR PUBLIC USE ⚠</p>
    <h1 class="site-title">GRAND INN &amp; SUITES<br><span class="site-sub">Management Portal v2.1.4</span></h1>

    <form class="login-form" onsubmit="return false">
        <div class="form-group">
            <label for="uname">Staff ID / Email / Username / Full Name <em>(any will do)</em></label>
            <input type="text" id="uname" placeholder="see IT dept. if unsure" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="pass">Secret Code <em>(case insensitive?)</em> <span class="req">*</span></label>
            <input type="password" id="pass" placeholder="min. 3 chars, max. 3 chars">
            <small class="field-note">* not actually case insensitive</small>
        </div>

        <div class="form-footer">
            <label class="chk-label">
                <input type="checkbox" id="remember-me">
                <span id="remember-label">Remember Me for 30 Days</span>
            </label>
            <a href="#" id="forgot-link" class="forgot-link">Forgot Password?</a>
        </div>

        <div id="annoy-msg" class="form-message"></div>

        <!-- Button sits inside form until captcha passes, then becomes roaming -->
        <button type="button" id="login-btn">Log In</button>
    </form>
</div>

<!-- CAPTCHA Overlay (shown after credentials verified) -->
<div id="captcha-overlay" class="captcha-overlay hidden">
    <div id="captcha-timer-bar" class="captcha-timer-bar">
        <div class="captcha-timer-top">
            <span>⏱ Time remaining to verify: </span>
            <span id="captcha-timer-value">60</span>
            <span>s</span>
        </div>
        <div class="captcha-progress-track">
            <div id="captcha-progress-fill" class="captcha-progress-fill"></div>
        </div>
    </div>
    <div id="captcha-widget" class="captcha-widget">
        <div class="captcha-header">
            <div class="captcha-header-text">
                <p class="captcha-instruction">Select the image with</p>
                <p class="captcha-instruction-sub">Click verify once done</p>
            </div>
            <div class="captcha-target-img">
                <img src="assets/captchaFindCharacter.png" alt="character to find">
            </div>
        </div>

        <div class="captcha-grid" id="captcha-grid">
            <div class="captcha-cell" data-id="00" onclick="toggleCell(this)">
                <img src="assets/00.jpg" alt="">
                <div class="captcha-check">✓</div>
            </div>
            <div class="captcha-cell" data-id="01" onclick="toggleCell(this)">
                <img src="assets/01.jpg" alt="">
                <div class="captcha-check">✓</div>
            </div>
            <div class="captcha-cell" data-id="02" onclick="toggleCell(this)">
                <img src="assets/02.jpg" alt="">
                <div class="captcha-check">✓</div>
            </div>
            <div class="captcha-cell" data-id="10" onclick="toggleCell(this)">
                <img src="assets/10.jpg" alt="">
                <div class="captcha-check">✓</div>
            </div>
            <div class="captcha-cell" data-id="11" onclick="toggleCell(this)">
                <img src="assets/11.jpg" alt="">
                <div class="captcha-check">✓</div>
            </div>
            <div class="captcha-cell" data-id="12" onclick="toggleCell(this)">
                <img src="assets/12.jpg" alt="">
                <div class="captcha-check">✓</div>
            </div>
            <!-- Cheat overlay -->
            <div id="captcha-cheat-overlay" class="captcha-cheat-overlay hidden">
                <span>Maro ka ha</span>
            </div>
        </div>

        <div class="captcha-footer">
            <div class="captcha-brand">
                <div class="captcha-logo">reCAPTCHA</div>
                <div class="captcha-privacyterms">Privacy · Terms</div>
            </div>
            <button class="captcha-verify-btn" onclick="verifyCaptcha()">Verify</button>
        </div>
    </div>
</div>

<!-- Jumpscare overlay -->
<div id="jumpscare" onclick="dismissJS()">
    <div class="scary-face">
        <div class="head">
            <div class="eye el"></div>
            <div class="eye er"></div>
            <div class="mouth">
                <div class="tooth"></div><div class="tooth"></div>
                <div class="tooth"></div><div class="tooth"></div>
                <div class="tooth"></div><div class="tooth"></div>
            </div>
        </div>
    </div>
    <div id="js-text">WRONG PASSWORD AGAIN??</div>
    <div id="js-dismiss">[ click anywhere to continue ]</div>
</div>

<!-- hint: invisible same-color text — select all to reveal -->
<span style="color:#0a0a0f;font-size:1px;user-select:text;">credentials: admin/admin123 or staff/staff123</span>

<script src="js/login.js"></script>
</body>
</html>
