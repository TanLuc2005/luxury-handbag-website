# LuxCarry — Cybersecurity Research Platform
### PHP E-Commerce App with MFA & Attack Simulation

> ⚠️ **Research & Education Only.** Run exclusively on localhost. Never deploy publicly.

---

## 📋 Overview

LuxCarry is a full-stack PHP e-commerce platform built to demonstrate:
- Secure authentication with MFA (TOTP / Google Authenticator)
- Account takeover attack simulations (brute force, credential stuffing)
- How MFA defeats attacks that succeed against password-only systems

---

## 🛠️ Technology Stack

| Layer     | Technology |
|-----------|-----------|
| Backend   | PHP 8+, MySQL, PDO prepared statements |
| Frontend  | HTML5, CSS3, Bootstrap 5, JavaScript |
| MFA       | TOTP (RFC 6238) — custom implementation, no Composer needed |
| Security  | bcrypt (cost 12), CSRF tokens, session hardening, account lockout |
| Attack Sim| Python 3.8+ with `requests` |

---

## 📁 Folder Structure

```
luxury-handbag-website/
├── config/         database.php — DB connection + logging
├── includes/       auth.php, header.php, footer.php, TOTP.php
├── auth/           login, register, logout, forgot_password
├── mfa/            setup_mfa, verify_otp
├── user/           dashboard, profile, enable_mfa
├── shop/           products, cart
├── attacks/        brute_force_simulator.py, credential_stuffing.py
├── assets/         css/style.css, js/main.js
├── logs/           login_attempts.log (auto-created)
├── database.sql    Full schema + seed data
└── index.php       Landing page
```

---

## ⚙️ Installation

### Step 1 — XAMPP Setup

1. Download & install [XAMPP](https://www.apachefriends.org/) (PHP 8+)
2. Start **Apache** and **MySQL** from the XAMPP Control Panel
3. Clone/copy this folder to:
   ```
   C:\xampp\htdocs\luxury-handbag-website\   (Windows)
   /Applications/XAMPP/htdocs/luxury-handbag-website/   (macOS)
   ```

### Step 2 — Database

**Option A — phpMyAdmin:**
1. Visit: http://localhost/phpmyadmin
2. Click **Import** → choose `database.sql` → click **Go**

**Option B — Command line:**
```bash
mysql -u root < database.sql
```

### Step 3 — PHP Configuration

In `config/database.php`, verify:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'luxury-handbag-website');
define('DB_USER', 'root');
define('DB_PASS', '');   // change if you have a MySQL password
```

### Step 4 — Logs Directory

The `logs/` folder is created automatically. If you get a permissions error:
```bash
chmod 755 logs/    # Linux/macOS
```
Or create it manually and ensure Apache/PHP can write to it.

### Step 5 — Launch

Visit: **http://localhost/luxury-handbag-website/**

---

## 👤 Demo Accounts

| Username     | Password  | MFA     |
|--------------|-----------|---------|
| `demo_user`  | `demo123` | ❌ Off  |
| `no_mfa_user`| `demo123` | ❌ Off  |
| `mfa_user`   | `demo123` | ❌ Off (enable via profile after login) |

> **Note:** The bcrypt hashes in `database.sql` correspond to `demo123`.  
> If you need to regenerate: `php -r "echo password_hash('demo123', PASSWORD_BCRYPT, ['cost'=>12]);"`

---

## 🛡️ Enabling MFA (Research Experiment)

1. Log in as `mfa_user`
2. Navigate to **Profile → MFA Settings** or `/user/enable_mfa.php`
3. Click **Start MFA Setup**
4. Scan the QR code with **Google Authenticator**
5. Enter the 6-digit code to confirm
6. MFA is now active — future logins require the OTP

---

## 🔐 Login Flow Diagram

```
Without MFA:                     With MFA:
─────────────                    ─────────────────────────────
Username + Password              Username + Password
       ↓                                ↓
  Verify bcrypt  → ❌ fail       Verify bcrypt  → ❌ fail
       ↓                                ↓
  ✅ Access                     OTP Verification (30-sec TOTP)
                                        ↓
                                   ✅ Access
```

---

## ⚔️ Attack Simulators

### Prerequisites
```bash
pip install requests
```

### Brute Force Simulator

Tries a list of passwords against a single account.

```bash
cd attacks/

# Quick demo (uses built-in wordlist)
python3 brute_force_simulator.py --target demo_user

# Custom wordlist
python3 brute_force_simulator.py --target demo_user --wordlist passwords.txt

# Custom delay
python3 brute_force_simulator.py --target demo_user --delay 0.3
```

**Expected outcomes:**
- `demo_user` (no MFA): Script eventually succeeds — password found, access granted
- `mfa_user` (MFA on):  Correct password found, but **blocked at OTP step**
- After 5 attempts:     Account **locked for 10 minutes**

---

### Credential Stuffing Simulator

Tests a list of username:password pairs from simulated breach data.

```bash
# Demo mode (built-in fake breach database)
python3 credential_stuffing.py

# Custom credential file (format: username:password, one per line)
python3 credential_stuffing.py --creds breach_dump.txt --delay 1.0
```

**Expected outcomes:**
- Accounts without MFA: Matching credentials grant immediate access
- Accounts with MFA:    Correct credentials → **blocked by TOTP**
- Locked accounts:      Attack skips/fails cleanly

---

## 📊 Log Analysis

All authentication events are logged:

```
logs/login_attempts.log           — Server-side PHP log
logs/attack_brute_force.log       — Brute force attempt log
logs/attack_credential_stuffing.log — Stuffing attempt log
logs/brute_force_summary.json     — JSON summary
logs/credential_stuffing_results.json — JSON results
```

**Log format:**
```
[2024-01-15 14:23:01] | USERNAME: demo_user          | IP: 127.0.0.1      | STAGE: PASSWORD   | RESULT: FAILURE_BAD_PASSWORD
[2024-01-15 14:23:02] | USERNAME: demo_user          | IP: 127.0.0.1      | STAGE: PASSWORD   | RESULT: MFA_PENDING
[2024-01-15 14:23:10] | USERNAME: demo_user          | IP: 127.0.0.1      | STAGE: MFA        | RESULT: MFA_SUCCESS
```

---

## 🔒 Security Architecture

### Password Security
- `password_hash()` with `PASSWORD_BCRYPT`, cost factor 12
- Verification with `password_verify()` (timing-safe)
- Password reset tokens are 32-byte cryptographically random hex strings

### TOTP (RFC 6238)
- `HMAC-SHA1(secret, floor(unix_time / 30))`
- 6-digit codes, 30-second window
- ±1 window tolerance for clock drift
- Base32-encoded secrets, 16 characters

### CSRF Protection
- `random_bytes(32)` token stored in session
- `hash_equals()` for constant-time comparison
- Applied to all state-changing POST forms

### Account Lockout
- Tracks `LoginAttempts` per user in database
- Locks account with `LockedUntil` timestamp after 5 failures
- Automatically unlocks after 10 minutes
- Counter resets on successful login

### Session Security
- `httponly` + `samesite=Strict` cookie flags
- `session_regenerate_id(true)` on login (prevents session fixation)
- Partial auth state (`mfa_pending_*`) for two-step flow

### SQL Injection Prevention
- All queries use PDO prepared statements
- `PDO::ATTR_EMULATE_PREPARES => false` forces real prepared statements

---

## 🧪 Experiment: Comparing Attack Effectiveness

| Scenario                  | Brute Force | Credential Stuffing |
|---------------------------|-------------|---------------------|
| No MFA, weak password     | ✅ Succeeds | ✅ Succeeds         |
| No MFA, strong password   | ⚠️ Slow     | ✅ If leaked        |
| MFA enabled               | ❌ Blocked  | ❌ Blocked          |
| Account lockout           | ❌ Locked   | ❌ Locked           |

---

## 📚 References

- RFC 6238 — TOTP: Time-Based One-Time Password Algorithm
- RFC 4226 — HOTP: An HMAC-Based One-Time Password Algorithm
- OWASP — Authentication Cheat Sheet
- NIST SP 800-63B — Digital Identity Guidelines

---

*LuxCarry Cybersecurity Research Platform — Educational Use Only*
