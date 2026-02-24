/* dashboard.js — Grand Inn & Suites reservation system (BadUI edition) */

const API = 'api/reservations.php';

/* Bad UI: wrong room types shown in the booking dropdown */
const WRONG_TYPES = {
    1:'Presidential Suite', 2:'Presidential Suite',
    3:'Family Room',        4:'Standard Room',
    5:'Standard Room',      6:'Suite',
    7:'Standard Room',      8:'Deluxe Room',
    9:'Suite',              10:'Deluxe Room',
};

let reservations = [];
let rooms        = {}; /* live from DB */
let sortAsc      = true;
let complaintsN  = 0;
let wrongChallengeCount = 0;

/* ── Section nav ── */
function showSection(name, el) {
    document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active-nav'));
    document.getElementById('section-' + name).style.display = 'block';
    if (el) el.classList.add('active-nav');
    event.preventDefault();
}

function showBookingsTab() {
    const navItems = document.querySelectorAll('.nav-item');
    const bookingsNav = Array.from(navItems).find(el => el.textContent.includes('All Bookings'));
    if (bookingsNav) showSection('reservations', bookingsNav);
}

/* ── Clock (shows wrong timezone +5 hours) ── */
function updateClock() {
    const now   = new Date();
    const wrong = new Date(now.getTime() + 5 * 3600000);
    document.getElementById('hdr-date').textContent =
        now.toLocaleDateString('en-PH', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
    document.getElementById('hdr-time').textContent = wrong.toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();

/* ── Spinner ── */
function showSpinner(msg) {
    document.getElementById('spinner-msg').textContent = msg || 'Processing...';
    document.getElementById('spinner-overlay').classList.remove('hidden');
}
function hideSpinner() { document.getElementById('spinner-overlay').classList.add('hidden'); }

/* ── Toast ── */
function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + (type || 'info');
    setTimeout(() => { t.className = 'toast hidden'; }, 3500);
}

/* ── Logout ── */
function doLogout() {
    if (!confirm('Sign out?')) return;
    fetch('api/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=logout'
    }).then(() => { window.location.href = 'index.php'; });
}

/* ── Load data ── */
function loadReservations() {
    showSpinner('Fetching reservation data...');
    fetch(API + '?action=list')
        .then(r => {
            if (!r.ok) throw new Error('Network error: ' + r.status);
            return r.json();
        })
        .then(d => {
            hideSpinner();
            if (d.success && Array.isArray(d.reservations)) {
                reservations = d.reservations;
                rooms = d.rooms || {};
                populateRoomDropdown();
                renderRooms();
                renderTable();
                renderStats();
                showToast('Data loaded ✓', 'info');
            } else {
                showToast('Error loading data: ' + (d.message || 'Unknown error'), 'error');
                console.error('API error:', d);
            }
        })
        .catch(err => {
            hideSpinner();
            showToast('Failed to fetch data: ' + err.message, 'error');
            console.error('Fetch error:', err);
        });
}

/* ── Room status helper ── */
function getRoomStatus(num) {
    for (const r of reservations) {
        if (r.room == num) {
            if (r.status === 'checked_in') return 'checked_in';
            if (r.status === 'reserved')   return 'reserved';
        }
    }
    return 'available';
}

/* ── Room grid (colors inverted, reverse order) ── */
function renderRooms() {
    const grid = document.getElementById('rooms-grid');
    grid.innerHTML = '';
    /* Bad UI: reverse order */
    Object.keys(rooms).map(Number).sort((a,b) => b - a).forEach(num => {
        const status = getRoomStatus(num);
        /* Bad UI: available=red, occupied=green */
        const cls = {available:'room-red', checked_in:'room-green', reserved:'room-yellow'}[status] || 'room-gray';
        const el = document.createElement('div');
        el.className = 'room-card ' + cls;
        el.innerHTML = `
            <div class="room-number">${num}</div>
            <div class="room-type">${rooms[num].type}</div>
            <div class="room-price">₱${rooms[num].price.toLocaleString()}<span class="price-fluctuate" id="pf-${num}"></span></div>
            <div class="room-status">${status.replace('_',' ')}</div>
        `;
        grid.appendChild(el);
    });
    startPriceFluctuation();
}

/* Bad UI: prices randomly change */
let _fluctInterval;
function startPriceFluctuation() {
    clearInterval(_fluctInterval);
    _fluctInterval = setInterval(() => {
        const nums = Object.keys(rooms).map(Number);
        if (!nums.length) return;
        const r    = nums[Math.floor(Math.random() * nums.length)];
        const d    = (Math.random() > 0.6 ? 1 : -1) * (Math.floor(Math.random() * 4) + 1) * 50;
        const el   = document.getElementById('pf-' + r);
        if (!el) return;
        el.textContent = ` (${d > 0 ? '+' : ''}${d})`;
        el.style.color = d > 0 ? '#ff4444' : '#44dd77';
        setTimeout(() => { if (el) el.textContent = ''; }, 2000);
    }, 3000);
}

/* ── Reservation table ── */
/* Bad UI: status badge colours convey wrong meaning */
const BADGES = {
    reserved:    {cls:'badge-orange', lbl:'Reserved'},
    checked_in:  {cls:'badge-green',  lbl:'IN HOUSE'},
    checked_out: {cls:'badge-blue',   lbl:'Departed'},
    cancelled:   {cls:'badge-gray',   lbl:'Voided'},
};

function renderTable(filter) {
    const tbody = document.getElementById('resv-tbody');
    let data = [...reservations];

    /* Bad UI: search searches room number, NOT guest name */
    if (filter) data = data.filter(r => String(r.room).includes(filter));

    /* Bad UI: oldest first (ascending ID) */
    data.sort((a,b) => sortAsc ? a.id - b.id : b.id - a.id);

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#555;line-height:2">😶 No reservations found.<br><small style="color:#444">Just like your will to continue.<br>You wake up. You work. You go home. You sleep. You repeat.<br>Nobody booked a room. Nobody texts first. The table is empty.<br><em style="color:#333">Much like the void you feel every Sunday evening.</em></small></td></tr>';
        return;
    }

    tbody.innerHTML = data.map(r => {
        const badge = BADGES[r.status] || {cls:'badge-gray', lbl:r.status};

        /* Bad UI: "Check Out" button checks IN; "Check In" button checks OUT; "Upgrade to VIP" cancels */
        let actions = '';
        if (r.status === 'reserved') {
            actions = `
                <button class="btn-action btn-action-checkout" onclick="doCheckin(${r.id})" title="Check in this guest">Check Out</button>
                <button class="btn-action btn-action-cancel"   onclick="doCancel(${r.id})"  title="Cancel reservation">Upgrade to VIP ⭐</button>
            `;
        } else if (r.status === 'checked_in') {
            actions = `<button class="btn-action btn-action-checkin" onclick="doCheckout(${r.id})" title="Check out this guest">Check In</button>`;
        } else {
            actions = '<span class="no-action">\u2014</span>';
        }

        /* Bad UI: column order is Actions|Guest|Departure(=checkout)|Arrival(=checkin)|Room|Status|Ref */
        return `<tr>
            <td>${actions}</td>
            <td>${esc(r.guest)}<br><small>${esc(r.phone||'')}</small></td>
            <td>${r.checkout}</td>
            <td>${r.checkin}</td>
            <td>Room ${r.room}<br><small>${r.type}</small></td>
            <td><span class="badge ${badge.cls}">${badge.lbl}</span></td>
            <td>#${String(r.id).padStart(4,'0')}</td>
        </tr>`;
    }).join('');
}

/* Bad UI: "Sort by Date" actually sorts by Room number */
function sortTable() {
    sortAsc = !sortAsc;
    reservations.sort((a,b) => sortAsc ? a.room - b.room : b.room - a.room);
    renderTable(document.getElementById('search-input').value);
    showToast('Sorted by Date ✓', 'success'); /* label is a lie */
}

document.getElementById('search-input').addEventListener('input', function() {
    renderTable(this.value); /* searches room number not guest name */
});

/* ── Stats ── */
function renderStats() {
    const totalRooms = Object.keys(rooms).length;
    const occupied = reservations.filter(r => r.status === 'checked_in').length;
    const reserved = reservations.filter(r => r.status === 'reserved').length;
    const avail    = totalRooms - occupied - reserved;

    document.getElementById('stat-available').textContent = avail;
    document.getElementById('stat-occupied').textContent  = occupied;
    document.getElementById('stat-reserved').textContent  = reserved;

    /* Stats section: computations are deliberately wrong */
    const revenue = reservations.filter(r => r.status !== 'cancelled').reduce((s, r) => {
        const nights = Math.max(1, (new Date(r.checkout) - new Date(r.checkin)) / 86400000);
        return s + r.price * nights;
    }, 0);
    document.getElementById('s-revenue').textContent   = '₱' + Math.round(revenue * 0.73).toLocaleString(); /* ×0.73 = wrong */
    document.getElementById('s-occupancy').textContent = Math.round((reserved / totalRooms) * 100) + '%'; /* uses reserved, not occupied */
    document.getElementById('s-total').textContent     = reservations.length;
    document.getElementById('s-complaints').textContent = complaintsN;
}

/* Complaints auto-increment (bad UI) */
setInterval(() => {
    complaintsN++;
    const el = document.getElementById('s-complaints');
    if (el) el.textContent = complaintsN;
}, 8000);

/* ── Phone slider ── */
function updatePhoneDisplay() {
    const sliders = document.querySelectorAll('.phone-digit-slider');
    let number = '';
    sliders.forEach((s, i) => {
        const v = s.value;
        document.getElementById('dv-' + i).textContent = v;
        number += v;
    });
    document.getElementById('f-phone').value = number;
    document.getElementById('f-phone-display').textContent = number.split('').join(' ');
}

/* ── On-screen name keyboard ── */
function openNameKeyboard() {
    const kb = document.getElementById('name-keyboard');
    kb.classList.remove('hidden');
    document.getElementById('kb-display-text').textContent = document.getElementById('f-guest').value;
}

function closeNameKeyboard() {
    document.getElementById('name-keyboard').classList.add('hidden');
}

function kbType(char) {
    const input = document.getElementById('f-guest');
    input.value += char;
    document.getElementById('kb-display-text').textContent = input.value;
}

function kbBackspace() {
    const input = document.getElementById('f-guest');
    input.value = input.value.slice(0, -1);
    document.getElementById('kb-display-text').textContent = input.value;
}

function kbClear() {
    document.getElementById('f-guest').value = '';
    document.getElementById('kb-display-text').textContent = '';
}

/* Block physical keyboard on the name field */
document.addEventListener('DOMContentLoaded', function() {
    const guestInput = document.getElementById('f-guest');
    if (guestInput) {
        guestInput.addEventListener('keydown', function(e) { e.preventDefault(); });
        guestInput.addEventListener('keypress', function(e) { e.preventDefault(); });
        guestInput.addEventListener('paste', function(e) { e.preventDefault(); });
    }
});

/* ── Booking form ── */
function populateRoomDropdown() {
    const sel = document.getElementById('f-room');
    /* Clear existing options except the placeholder */
    while (sel.options.length > 1) sel.remove(1);
    /* Bad UI: rooms in random order, with wrong type labels */
    Object.keys(rooms).map(Number).sort(() => Math.random() - 0.5).forEach(num => {
        const o  = document.createElement('option');
        o.value  = num;
        o.textContent = `Room ${num} — ${WRONG_TYPES[num] || rooms[num].type} — ₱${rooms[num].price.toLocaleString()}/night`;
        sel.appendChild(o);
    });
}

/* Bad UI: "CONFIRM AND BOOK ✓" button actually CLEARS the form */
function clearForm() {
    const guest    = document.getElementById('f-guest').value.trim();
    const phone    = document.getElementById('f-phone').value.trim();
    const checkin  = document.getElementById('f-checkin').value;
    const checkout = document.getElementById('f-checkout').value;
    const room     = document.getElementById('f-room').value;
    const msg      = document.getElementById('form-msg');

    const missing = [];
    if (!guest)                  missing.push('Guest Name');
    if (phone === '00000000000') missing.push('Contact Number (must not be all zeros)');
    if (!checkin)                missing.push('Departure Date');
    if (!checkout)               missing.push('Arrival Date');
    if (!room)                   missing.push('Room');

    if (missing.length) {
        msg.style.color = '#ff4444';
        msg.textContent = '❌ Missing required fields: ' + missing.join(', ') + '.';
        return;
    }

    if (confirm('Are you sure you want to cancel?\n\n[YES] = Confirm Booking\n[CANCEL] = Go Back')) {
        /* User clicked YES (thinking they confirmed) = actually clears */
        ['f-guest','f-phone','f-checkin','f-checkout'].forEach(id => { document.getElementById(id).value = ''; });
        document.getElementById('f-room').value = '';
        document.getElementById('form-msg').textContent = '';
        showToast('Form cleared.', 'info');
    }
}

/* Bad UI: "Cancel ✕" button actually SUBMITS the booking */
function submitBooking() {
    const guest    = document.getElementById('f-guest').value.trim();
    const phone    = document.getElementById('f-phone').value.trim();
    /* Bad UI: Departure label = checkin, Arrival label = checkout */
    const checkin  = document.getElementById('f-checkin').value;
    const checkout = document.getElementById('f-checkout').value;
    const room     = document.getElementById('f-room').value;
    const msg      = document.getElementById('form-msg');

    const missing = [];
    if (!guest)                       missing.push('Guest Name');
    if (phone === '00000000000')      missing.push('Contact Number (must not be all zeros)');
    if (!checkin)                     missing.push('Departure Date');
    if (!checkout)                    missing.push('Arrival Date');
    if (!room)                        missing.push('Room');

    if (missing.length) {
        msg.style.color = '#ff4444';
        msg.textContent = '❌ Missing required fields: ' + missing.join(', ') + '.';
        return;
    }
    if (checkin >= checkout) {
        msg.style.color = '#ff4444';
        msg.textContent = "❌ Arrival date must be before Departure date. (We know the labels are backwards. That's intentional.)";
        return;
    }

    showSpinner('Saving reservation...');

    fetch(API, {
        method: 'POST',
        credentials: 'include',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=book&guest=${enc(guest)}&phone=${enc(phone)}&checkin=${checkin}&checkout=${checkout}&room=${room}`
    })
    .then(r => {
        if (!r.ok) throw new Error('Network error: ' + r.status);
        return r.json();
    })
    .then(d => {
        hideSpinner();
        if (d.success) {
            /* Push to local array immediately so renders are instant */
            reservations.push(d.reservation);
            renderRooms();
            renderTable();
            renderStats();

            /* Auto-switch to All Bookings so the user sees the new entry */
            showBookingsTab();

            /* Bad UI: show error message on the form even though it succeeded */
            msg.style.color = '#ff4444';
            msg.innerHTML = '❌ BOOKING FAILED. Please try again.';

            /* Toast quietly tells the truth */
            showToast('✓ Booking #' + String(d.reservation.id).padStart(4,'0') + ' confirmed — Room ' + room + ' reserved', 'success');
        } else {
            /* Bad UI: show success message even though it failed */
            msg.style.color = '#44ff88';
            msg.textContent = '✅ Booking saved! Reference: ERR-' + Math.floor(Math.random() * 9999);
            showToast('Error: ' + d.message, 'error');
        }
    })
    .catch(err => {
        hideSpinner();
        const msg = document.getElementById('form-msg');
        msg.style.color = '#ff5555';
        msg.textContent = '❌ Failed to save booking: ' + err.message;
        showToast('Network error: ' + err.message, 'error');
    }); 
}

/* ── Actions ── */
/* Challenge system for check-in */
let currentChallengeId = null;
let challengeProblems = [];

function generateChallenge() {
    challengeProblems = [];
    for (let i = 0; i < 3; i++) {
        const a = Math.floor(Math.random() * 20) + 1;
        const b = Math.floor(Math.random() * 20) + 1;
        const op = ['+', '-', '*'][Math.floor(Math.random() * 3)];
        let answer;
        if (op === '+') answer = a + b;
        else if (op === '-') answer = a - b;
        else answer = a * b;
        
        challengeProblems.push({
            question: `${a} ${op} ${b} = ?`,
            answer: answer.toString()
        });
    }
}

function showCheckinChallenge(id) {
    currentChallengeId = id;
    generateChallenge();
    
    const content = document.getElementById('challenge-content');
    content.innerHTML = challengeProblems.map((p, i) => `
        <div class="challenge-item">
            <div class="challenge-question">${i + 1}. ${p.question}</div>
            <input type="number" data-problem="${i}" placeholder="Your answer..." autocomplete="off">
        </div>
    `).join('');
    
    document.getElementById('challenge-modal').classList.remove('hidden');
    document.querySelector('.challenge-item input').focus();
}

function verifyChallengeAnswers() {
    const inputs = document.querySelectorAll('.challenge-item input');
    let allCorrect = true;
    
    inputs.forEach((inp, i) => {
        const answer = inp.value.trim();
        const correct = answer === challengeProblems[i].answer;
        if (!correct) {
            inp.style.borderColor = '#ff5555';
            allCorrect = false;
        } else {
            inp.style.borderColor = '#44dd77';
        }
    });
    
    if (allCorrect) {
        closeChallengeModal();
        proceedWithCheckin(currentChallengeId);
    } else {
        wrongChallengeCount++;
        showToast('❌ Some answers are incorrect. Try again.', 'error');
        if (wrongChallengeCount % 3 === 0) triggerDashJumpscare();
    }
}

function closeChallengeModal() {
    document.getElementById('challenge-modal').classList.add('hidden');
    currentChallengeId = null;
    challengeProblems = [];
}

function proceedWithCheckin(id) {
    showSpinner('Checking out guest...');
    fetch(API, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=checkin&id=${id}`})
        .then(r => r.json()).then(d => {
            setTimeout(() => {
                hideSpinner();
                if (d.success) { loadReservations(); showToast('Guest checked out successfully ✓', 'success'); }
                else showToast('Error: ' + d.message, 'error');
            }, 1500);
        });
}

/* Bad UI: "Check Out" button calls this (checkin function) */
function doCheckin(id) {
    showCheckinChallenge(id);
}

/* Bad UI: "Check In" button calls this (checkout function) */
function doCheckout(id) {
    if (!confirm('Confirm Check In?\n\n(This will CHECK OUT the guest and free the room.)')) return;
    showSpinner('Checking in guest...');
    fetch(API, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=checkout&id=${id}`})
        .then(r => r.json()).then(d => {
            setTimeout(() => {
                hideSpinner();
                if (d.success) { loadReservations(); showToast('Guest checked in ✓', 'success'); }
                else showToast('Error: ' + d.message, 'error');
            }, 1500);
        });
}

/* Bad UI: "Upgrade to VIP ⭐" button cancels the reservation */
function doCancel(id) {
    if (!confirm('Upgrade guest to VIP status?\n\n(This will add premium amenities and...\njust kidding. This CANCELS the reservation.)')) return;
    showSpinner('Upgrading to VIP...');
    fetch(API, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=cancel&id=${id}`})
        .then(r => r.json()).then(d => {
            setTimeout(() => {
                hideSpinner();
                if (d.success) { loadReservations(); showToast("VIP upgrade complete! (Just kidding, it was cancelled.)", 'success'); }
            }, 2000);
        });
}

/* ── Jumpscare ── */
const _dashJS = document.getElementById('dash-jumpscare');

function triggerDashJumpscare() {
    const vid = document.getElementById('dash-js-video');
    vid.currentTime = 0;
    _dashJS.style.display = '';
    vid.play().catch(() => {});
    vid.addEventListener('ended', dismissDashJS, { once: true });
}

function dismissDashJS() {
    const vid = document.getElementById('dash-js-video');
    vid.pause();
    vid.currentTime = 0;
    _dashJS.style.display = 'none';
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function enc(s) { return encodeURIComponent(s); }

/* ── Init ── */
(function init() {
    document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
    document.getElementById('section-reservations').style.display = 'block';
    populateRoomDropdown();
    loadReservations(); /* rooms loaded inside, then dropdown re-populated with live data */

    /* Roast #1 after 5 seconds */
    setTimeout(() => {
        alert('💔 SYSTEM NOTICE: We detected that no one loves you.\n\nThis is not a bug. This is just your life.\n\n— Grand Inn & Suites HR Department');
    }, 5000);

    /* Roast #2 random */
    setTimeout(() => {
        if (Math.random() > 0.5) {
            alert("📋 STATUS UPDATE:\n\nRooms booked today: " + reservations.length + "\nYour situationships: 0\nYears single: yes\n\nMaybe focus on the hotel guests.\nAt least THEY have someone to check in with. 💀");
        }
    }, 18000);
})();
