<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$userName = htmlspecialchars($_SESSION['user']['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inn Management — Dashboard (BETA)</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<marquee class="top-marquee" scrollamount="3">
    😔 SYSTEM NOTICE: Nothing you do here matters. The guests will leave. They always leave. &nbsp;&nbsp;&nbsp;
    💀 Reminder: You've been staring at this screen for hours. Nobody noticed. Nobody will. &nbsp;&nbsp;&nbsp;
    🥀 ALERT: Room 7 is empty again. Just like your weekends. &nbsp;&nbsp;&nbsp;
    😶 FUN FACT: The last person who used this system quit without saying goodbye. &nbsp;&nbsp;&nbsp;
    📉 UPDATE: Complaints are up. Revenue is down. You are tired. The coffee is cold. &nbsp;&nbsp;&nbsp;
    💔 NOTICE: Your shift ends in 4 hours. Nothing will be different after. &nbsp;&nbsp;&nbsp;
</marquee>

<div class="dash-wrap">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">🏨 <span>GRAND INN<br>&amp; SUITES</span></div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item" onclick="showSection('rooms',this)">🟥 Available Rooms</a>
            <a href="#" class="nav-item active-nav" onclick="showSection('reservations',this)">📋 All Bookings</a>
            <a href="#" class="nav-item" onclick="showSection('book',this)">➕ New Reservation</a>
            <a href="#" class="nav-item" onclick="showSection('stats',this)">📊 Live Statistics</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-info">👤 <?= $userName ?></div>
            <button class="btn-logout" onclick="doLogout()">🚪 Sign Out</button>
            <p class="ver-note">v0.0.1-beta (unstable)</p>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Welcome back, <?= $userName ?>!</h1>
                <p class="page-sub">
                    <span id="hdr-date">loading...</span> •
                    System time: <span id="hdr-time">loading...</span>
                    <em>(timezone may vary)</em>
                </p>
            </div>
            <div class="hdr-stats">
                <!-- Bad UI: colors inverted — same as room grid -->
                <div class="hstat red-box"><span class="hstat-n" id="stat-available">–</span><span class="hstat-l">Available</span></div>
                <div class="hstat green-box"><span class="hstat-n" id="stat-occupied">–</span><span class="hstat-l">Occupied</span></div>
                <div class="hstat yellow-box"><span class="hstat-n" id="stat-reserved">–</span><span class="hstat-l">Reserved</span></div>
            </div>
        </div>

        <!-- Section: Room Grid -->
        <section class="section" id="section-rooms" style="display:none">
            <div class="sec-header">
                <h2>Room Overview</h2>
                <!-- Legend intentionally uses WRONG colors vs the actual room cards (bad UI) -->
                <div class="legend">
                    <span class="ldot" style="background:#44dd77"></span>Available &nbsp;
                    <span class="ldot" style="background:#ff4444"></span>Occupied &nbsp;
                    <span class="ldot" style="background:#ffaa00"></span>Reserved &nbsp;
                    <span class="ldot" style="background:#888"></span>Checked Out
                </div>
            </div>
            <div class="rooms-grid" id="rooms-grid"></div>
        </section>

        <!-- Section: Reservations Table -->
        <section class="section" id="section-reservations">
            <div class="sec-header">
                <h2>Booking Records</h2>
                <div class="tbl-controls">
                    <input type="text" id="search-input" class="search-box" placeholder="Search guest name...">
                    <button class="btn-sec" onclick="sortTable()">Sort by Date ↕</button>
                </div>
            </div>
            <div class="tbl-wrap">
                <table class="resv-table">
                    <thead>
                        <tr>
                            <!-- Column order intentionally confusing (bad UI) -->
                            <th>Actions</th>
                            <th>Guest Name</th>
                            <th>Departure</th><!-- mapped to checkout date -->
                            <th>Arrival</th>  <!-- mapped to checkin date -->
                            <th>Room</th>
                            <th>Status</th>
                            <th>Ref #</th>
                        </tr>
                    </thead>
                    <tbody id="resv-tbody">
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:#666">Loading reservations...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section: New Booking Form -->
        <section class="section" id="section-book" style="display:none">
            <div class="sec-header">
                <h2>New Reservation Form</h2>
                <small class="form-note">* All fields required. Fill in order from bottom to top.</small>
            </div>
            <form class="booking-form" onsubmit="return false">
                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number <small>(slide each digit)</small></label>
                        <div class="phone-display" id="f-phone-display">0 0 0 0 0 0 0 0 0 0 0</div>
                        <div class="phone-sliders" id="phone-sliders-wrap">
                            <div class="phone-slider-col" data-idx="0"><span class="digit-label">D1</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="0" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-0">0</span></div>
                            <div class="phone-slider-col" data-idx="1"><span class="digit-label">D2</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="1" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-1">0</span></div>
                            <div class="phone-slider-col" data-idx="2"><span class="digit-label">D3</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="2" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-2">0</span></div>
                            <div class="phone-slider-col" data-idx="3"><span class="digit-label">D4</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="3" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-3">0</span></div>
                            <div class="phone-slider-col" data-idx="4"><span class="digit-label">D5</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="4" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-4">0</span></div>
                            <div class="phone-slider-col" data-idx="5"><span class="digit-label">D6</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="5" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-5">0</span></div>
                            <div class="phone-slider-col" data-idx="6"><span class="digit-label">D7</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="6" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-6">0</span></div>
                            <div class="phone-slider-col" data-idx="7"><span class="digit-label">D8</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="7" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-7">0</span></div>
                            <div class="phone-slider-col" data-idx="8"><span class="digit-label">D9</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="8" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-8">0</span></div>
                            <div class="phone-slider-col" data-idx="9"><span class="digit-label">D10</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="9" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-9">0</span></div>
                            <div class="phone-slider-col" data-idx="10"><span class="digit-label">D11</span><input type="range" min="0" max="9" value="0" class="phone-digit-slider" data-idx="10" oninput="updatePhoneDisplay()"><span class="digit-val" id="dv-10">0</span></div>
                        </div>
                        <!-- hidden field read by submitBooking() -->
                        <input type="hidden" id="f-phone" value="00000000000">
                    </div>
                    <div class="form-group">
                        <label>Guest Full Name *</label>
                        <input type="text" id="f-guest" placeholder="Click here to type name..." readonly
                               onclick="openNameKeyboard()" style="cursor:pointer;caret-color:transparent;">
                        <small class="field-note">⌨ On-screen keyboard only. Physical keyboard disabled.</small>
                    </div>
                </div>

                <!-- On-screen name keyboard -->
                <div id="name-keyboard" class="name-keyboard hidden">
                    <div class="kb-display">
                        <span id="kb-display-text"></span><span class="kb-cursor">|</span>
                        <button class="kb-close" onclick="closeNameKeyboard()">✕ Close</button>
                    </div>
                    <div class="kb-rows">
                        <div class="kb-row">
                            <button class="kb-key" onclick="kbType('Q')">Q</button><button class="kb-key" onclick="kbType('W')">W</button><button class="kb-key" onclick="kbType('E')">E</button><button class="kb-key" onclick="kbType('R')">R</button><button class="kb-key" onclick="kbType('T')">T</button><button class="kb-key" onclick="kbType('Y')">Y</button><button class="kb-key" onclick="kbType('U')">U</button><button class="kb-key" onclick="kbType('I')">I</button><button class="kb-key" onclick="kbType('O')">O</button><button class="kb-key" onclick="kbType('P')">P</button>
                        </div>
                        <div class="kb-row">
                            <button class="kb-key" onclick="kbType('A')">A</button><button class="kb-key" onclick="kbType('S')">S</button><button class="kb-key" onclick="kbType('D')">D</button><button class="kb-key" onclick="kbType('F')">F</button><button class="kb-key" onclick="kbType('G')">G</button><button class="kb-key" onclick="kbType('H')">H</button><button class="kb-key" onclick="kbType('J')">J</button><button class="kb-key" onclick="kbType('K')">K</button><button class="kb-key" onclick="kbType('L')">L</button>
                        </div>
                        <div class="kb-row">
                            <button class="kb-key" onclick="kbType('Z')">Z</button><button class="kb-key" onclick="kbType('X')">X</button><button class="kb-key" onclick="kbType('C')">C</button><button class="kb-key" onclick="kbType('V')">V</button><button class="kb-key" onclick="kbType('B')">B</button><button class="kb-key" onclick="kbType('N')">N</button><button class="kb-key" onclick="kbType('M')">M</button><button class="kb-key" onclick="kbType(',')">،</button><button class="kb-key" onclick="kbType('.')">.</button>
                        </div>
                        <div class="kb-row">
                            <button class="kb-key kb-wide" onclick="kbType(' ')">SPACE</button>
                            <button class="kb-key kb-action" onclick="kbBackspace()">⌫ DEL</button>
                            <button class="kb-key kb-danger" onclick="kbClear()">CLEAR ALL</button>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <!-- Labels intentionally swapped: Departure label = checkin field (bad UI) -->
                        <label>Departure Date * <small>(when they arrive)</small></label>
                        <input type="date" id="f-checkin">
                    </div>
                    <div class="form-group">
                        <label>Arrival Date * <small>(when they leave)</small></label>
                        <input type="date" id="f-checkout">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Room *</label>
                        <select id="f-room">
                            <option value="">-- Select a Room --</option>
                            <!-- Populated by JS with intentionally wrong room type labels (bad UI) -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label>No. of Guests</label>
                        <input type="number" id="f-pax" value="1" min="1" max="10">
                        <small class="field-note">This field does nothing but is required.</small>
                    </div>
                </div>
                <div class="form-actions">
                    <!-- Buttons are intentionally SWAPPED (bad UI): danger btn = submit, primary btn = clear -->
                    <button type="button" class="btn-danger" onclick="clearForm()">CONFIRM AND BOOK ✓</button>
                    <button type="button" class="btn-primary" onclick="submitBooking()">Cancel ✕</button>
                </div>
                <div id="form-msg" class="form-msg"></div>
            </form>
        </section>

        <!-- Section: Stats -->
        <section class="section" id="section-stats" style="display:none">
            <div class="sec-header"><h2>Live Statistics <span class="live-dot">●</span></h2></div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-big" id="s-revenue">₱0</div><div class="stat-lbl">Monthly Revenue <small>(approximate, may be wrong)</small></div></div>
                <div class="stat-card"><div class="stat-big" id="s-occupancy">0%</div><div class="stat-lbl">Occupancy Rate <small>(calculated incorrectly)</small></div></div>
                <div class="stat-card"><div class="stat-big" id="s-total">0</div><div class="stat-lbl">Total Reservations This Month</div></div>
                <div class="stat-card"><div class="stat-big" id="s-complaints">0</div><div class="stat-lbl">Guest Complaints <small>(auto-increments)</small></div></div>
            </div>
        </section>
    </main>
</div>

<!-- Spinner overlay -->
<div id="spinner-overlay" class="spinner-overlay hidden">
    <div class="spinner-box">
        <div class="spinner"></div>
        <p id="spinner-msg">Processing...</p>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast hidden"></div>

<!-- Check-In Challenge Modal -->
<div id="challenge-modal" class="challenge-modal hidden">
    <div class="challenge-box">
        <h2>Security Verification</h2>
        <p class="challenge-desc">Solve the following problems to confirm check-in:</p>
        <div id="challenge-content"></div>
        <div class="challenge-actions">
            <button class="btn-primary" onclick="verifyChallengeAnswers()">Submit Answers</button>
            <button class="btn-danger" onclick="closeChallengeModal()">Cancel Check-In</button>
        </div>
    </div>
</div>

<!-- Jumpscare overlay -->
<div id="dash-jumpscare" style="display:none">
    <video id="dash-js-video" src="assets/vlipsy-jump-scare-creepy-doll-nwbQ9bDF.mp4" playsinline preload="auto"></video>
    <div id="dash-js-text">WRONG ANSWER AGAIN??</div>
</div>

<script src="js/dashboard.js"></script>
</body>
</html>
