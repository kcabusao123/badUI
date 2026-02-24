/* ═══════════════════════════
   CAPTCHA — image challenge
═══════════════════════════ */
const CORRECT_IMG = '02';
let captchaPassed  = false;
let captchaTimerID = null;
let pendingLogin   = null; /* stores the validated credentials for use after captcha */

function toggleCell(cell) {
    cell.classList.toggle('selected');
}

function startCaptchaTimer() {
    let timeLeft = 60;
    const el   = document.getElementById('captcha-timer-value');
    const fill = document.getElementById('captcha-progress-fill');
    el.textContent = timeLeft;
    el.className   = '';

    /* Reset progress bar to full */
    fill.style.transition = 'none';
    fill.style.width = '100%';
    fill.style.backgroundColor = '#4a90d9';
    void fill.offsetWidth; /* reflow so transition kicks in on next frame */
    fill.style.transition = 'width 1s linear, background-color 0.4s ease';

    clearInterval(captchaTimerID);
    captchaTimerID = setInterval(() => {
        timeLeft -= 5;
        const pct = Math.max(timeLeft, 0) / 60 * 100;
        el.textContent = Math.max(timeLeft, 0);
        fill.style.width = pct + '%';

        if (timeLeft <= 15 && timeLeft > 5) {
            el.className = 'warning';
            fill.style.backgroundColor = '#ffaa00';
        }
        if (timeLeft <= 5) {
            el.className = 'critical';
            fill.style.backgroundColor = '#ff4444';
        }

        if (timeLeft <= 0) {
            clearInterval(captchaTimerID);
            closeCaptchaOverlay();
            annoyMsg.style.color = '#ff4444';
            annoyMsg.textContent = "Too slow. Security verification timed out. Start over. 🐢";
            teleport();
            busy = false;
        }
    }, 1000);
}

function closeCaptchaOverlay() {
    clearInterval(captchaTimerID);
    document.getElementById('captcha-overlay').classList.add('hidden');
    document.querySelectorAll('.captcha-cell.selected').forEach(c => c.classList.remove('selected'));
    document.getElementById('captcha-timer-value').className = '';
    pendingLogin = null;
}

function verifyCaptcha() {
    const selected   = [...document.querySelectorAll('.captcha-cell.selected')].map(c => c.dataset.id);
    const totalCells = document.querySelectorAll('.captcha-cell').length;
    const overlay    = document.getElementById('captcha-cheat-overlay');
    const widget     = document.getElementById('captcha-widget');

    /* ── Cheat: all images selected ── */
    if (selected.length === totalCells) {
        overlay.classList.remove('hidden');
        overlay.classList.add('show-cheat');
        setTimeout(() => {
            overlay.classList.remove('show-cheat');
            overlay.classList.add('hidden');
            document.querySelectorAll('.captcha-cell.selected')
                .forEach(c => c.classList.remove('selected'));
        }, 2400);
        return;
    }

    /* ── Correct: only 02 selected ── */
    if (selected.length === 1 && selected[0] === CORRECT_IMG) {
        clearInterval(captchaTimerID);
        captchaPassed = true;
        widget.classList.add('pass-out');
        setTimeout(() => {
            document.getElementById('captcha-overlay').classList.add('hidden');
            widget.classList.remove('pass-out');

            /* Captcha passed — proceed to dashboard */
            annoyMsg.style.color = '#00cc66';
            annoyMsg.textContent = 'Login successful! Redirecting... (finally 🙄)';
            btn.textContent  = 'Loading...';
            btn.disabled     = true;
            setTimeout(() => { window.location.href = 'dashboard.php'; }, 1200);
        }, 430);
        return;
    }

    /* ── Wrong selection: shake and clear ── */
    widget.classList.remove('shake');
    void widget.offsetWidth;
    widget.classList.add('shake');
    setTimeout(() => widget.classList.remove('shake'), 380);
    document.querySelectorAll('.captcha-cell.selected')
        .forEach(c => c.classList.remove('selected'));
}

/* ═══════════════════════════
   LOGIN logic starts below
═══════════════════════════ */

const annoyMsg  = document.getElementById('annoy-msg');
const jumpscare = document.getElementById('jumpscare');
const btn       = document.getElementById('login-btn');
const unameIn   = document.getElementById('uname');
const passIn    = document.getElementById('pass');

let busy        = false;
let wrongCount  = 0;
let wrongIdx    = 0;

/* ── Wrong-credentials roast messages ── */
const wrongMsgs = [
    "Incorrect credentials. (Did you try the username field for the password?)",
    "Still wrong?? Bruh. 💀",
    "Are you even an employee here?",
    "Sir this is a LOGIN PAGE not a guessing GAME.",
    "Maybe try reading the label? Oh wait — we made those confusing too. 🙂",
    "WRONG. So very, very WRONG.",
    "Nice try, hacker. (It's not working.)",
    "Have you considered just... giving up?",
];

/* ── Enter key messages (cycles through) ── */
const enterMsgs = [
    "You think that's gonna work? Haha idiot 😂",
    "Gahiag ulo oi 🤦",
    "Still pressing Enter?? That is NOT how this works.",
    "Nangamote ka na? 😭",
    "The Enter key is NOT a magic button, genius.",
    "Bruh. BRUHHH. 💀",
    "Okay at this point you're just trolling yourself.",
    "...did you really just press Enter AGAIN?",
    "I'm embarrassed FOR you.",
    "Okay fine, you win. Just kidding. You don't. 🙂",
    "Naa kay pag-asa? Wala. 🫵😂",
    "STOP IT. GET SOME HELP.",
    "Even the keyboard is tired of you.",
    "Sir this is a Wendy's.",
];
let enterCount = 0;

/* ── Remember Me checkbox: flip label when checked (bad UI) ── */
const rememberCb = document.getElementById('remember-me');
const rememberLb = document.getElementById('remember-label');
if (rememberCb) {
    rememberCb.addEventListener('change', () => {
        rememberLb.textContent = rememberCb.checked
            ? 'Forget Me After Session'
            : 'Remember Me for 30 Days';
    });
}

/* ── Forgot Password link ── */
const forgotLink = document.getElementById('forgot-link');
if (forgotLink) {
    forgotLink.addEventListener('click', (e) => {
        e.preventDefault();
        alert("That's a YOU problem.\n\nContact IT.\nOr just try 'password'. Or 'Password'. Or 'PASSWORD'.\n\nGood luck! 🙂");
    });
}

/* ── Center button below the login card ── */
function centerBtn() {
    const card = document.getElementById('login-card');
    const rect = card.getBoundingClientRect();
    btn.style.left = Math.round(rect.left + (rect.width - btn.offsetWidth) / 2) + 'px';
    btn.style.top  = Math.round(rect.bottom + 24) + 'px';
}

/* ── Teleport button to a random screen position ── */
function teleport() {
    const vw  = window.innerWidth,  vh  = window.innerHeight;
    const pad = 30;
    const nx  = pad + Math.random() * (vw - btn.offsetWidth  - pad * 2);
    const ny  = pad + Math.random() * (vh - btn.offsetHeight - pad * 2);
    btn.style.left = Math.round(nx) + 'px';
    btn.style.top  = Math.round(ny) + 'px';
    btn.classList.remove('btn-shake');
    void btn.offsetWidth; /* reflow */
    btn.classList.add('btn-shake');
}

/* ── Button click ── */
btn.addEventListener('click', (e) => {
    e.preventDefault();
    if (busy) return;

    const u = unameIn.value.trim();
    const p = passIn.value.trim();

    /* Basic empty-field check */
    if (!u || !p) {
        annoyMsg.style.color = '#ff4444';
        annoyMsg.textContent = !u
            ? "You forgot to type your username. (Or did the field confuse you? Fair.)"
            : "Password field is empty. Revolutionary mistake.";
        return;
    }

    busy = true;

    fetch('api/auth.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=login&username=${encodeURIComponent(u)}&password=${encodeURIComponent(p)}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            /* Credentials correct — promote button to roaming then show captcha */
            annoyMsg.style.color = '#888';
            annoyMsg.textContent = 'Credentials accepted. Please complete verification.';

            /* Make the button a fixed roaming button, centered below card */
            const card = document.getElementById('login-card');
            const rect = card.getBoundingClientRect();
            btn.classList.add('roaming');
            btn.style.left = Math.round(rect.left + (rect.width - btn.offsetWidth) / 2) + 'px';
            btn.style.top  = Math.round(rect.bottom + 24) + 'px';

            /* Show captcha and start the timer */
            document.getElementById('captcha-overlay').classList.remove('hidden');
            startCaptchaTimer();
            busy = false;
        } else {
            wrongCount++;
            annoyMsg.style.color = '#ff4444';
            annoyMsg.textContent = wrongMsgs[wrongIdx % wrongMsgs.length];
            wrongIdx++;

            /* Promote button to roaming on first wrong attempt, then keep teleporting */
            if (!btn.classList.contains('roaming')) {
                const card = document.getElementById('login-card');
                const rect = card.getBoundingClientRect();
                btn.classList.add('roaming');
                btn.style.left = Math.round(rect.left + (rect.width - btn.offsetWidth) / 2) + 'px';
                btn.style.top  = Math.round(rect.bottom + 24) + 'px';
                /* Small delay so the CSS transition kicks in before teleporting */
                setTimeout(teleport, 60);
            } else {
                teleport();
            }

            if (wrongCount % 3 === 0) triggerJumpscare();

            busy = false;
        }
    })
    .catch(() => {
        annoyMsg.style.color = '#ff8800';
        annoyMsg.textContent = "Something went wrong. Or didn't. Who knows. 🤷";
        busy = false;
    });
});

/* ── Enter key on inputs: roast the user ── */
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const tag = document.activeElement.tagName;
    if (tag !== 'INPUT' && tag !== 'BODY') return;

    e.preventDefault();
    const msg = enterMsgs[enterCount % enterMsgs.length];
    annoyMsg.textContent = msg;
    enterCount++;

    /* little shake on the card too */
    const card = document.querySelector('.card');
    card.style.animation = 'none';
    void card.offsetWidth;
    card.style.animation = 'cardShake 0.35s ease';
});

/* ──────────────────────────────────────────
   Jumpscare: 3-layer Web Audio screech + CSS overlay
────────────────────────────────────────── */
function triggerJumpscare() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();

        /** Helper: oscillator with gain envelope */
        const mk = (freq, type, start, dur, vol) => {
            const g = ctx.createGain();
            g.gain.setValueAtTime(0.001, ctx.currentTime + start);
            g.gain.exponentialRampToValueAtTime(vol, ctx.currentTime + start + 0.02);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + dur);
            g.connect(ctx.destination);

            const o = ctx.createOscillator();
            o.type = type;
            o.frequency.value = freq;
            o.connect(g);
            o.start(ctx.currentTime + start);
            o.stop(ctx.currentTime + start + dur);
        };

        mk(55,   'sawtooth', 0,    1.6, 1.1); // low drone
        mk(1300, 'square',   0,    0.5, 0.7); // high screech
        mk(880,  'square',   0.05, 0.4, 0.5); // mid screech

        // White noise burst
        const buf  = ctx.createBuffer(1, ctx.sampleRate * 0.4, ctx.sampleRate);
        const data = buf.getChannelData(0);
        for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;

        const ns = ctx.createBufferSource();
        const ng = ctx.createGain();
        ng.gain.setValueAtTime(0.55, ctx.currentTime);
        ng.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        ns.buffer = buf;
        ns.connect(ng);
        ng.connect(ctx.destination);
        ns.start();
    } catch (_) { /* audio blocked – visual only */ }

    // Screen flash
    document.body.style.background = '#fff';
    setTimeout(() => { document.body.style.background = '#000'; }, 55);

    // Show jumpscare overlay
    jumpscare.classList.add('active');
}

function dismissJS() {
    jumpscare.classList.remove('active');
    jumpscare.style.display = 'none';
    annoyMsg.style.color = '#ff4444';
    annoyMsg.textContent = wrongMsgs[wrongIdx % wrongMsgs.length];
    wrongIdx++;
    teleport();
    busy = false;
}
