"""CLI runner for the QLDAPM_Nhom06 4-Tier Opaque-Box E2E Suite."""
from __future__ import annotations

import argparse
import importlib
import json
import sys
import time
import unittest
from pathlib import Path

E2E_DIR = Path(__file__).resolve().parent
if str(E2E_DIR) not in sys.path:
    sys.path.insert(0, str(E2E_DIR))

from config import DEFAULT_DB_NAME, RUNTIME  # noqa: E402
from lib.db_manager import DbManager  # noqa: E402
from lib.php_server import PhpServerManager  # noqa: E402

TIER_MODULES = {
    "1": ("Tier 1 — Feature Coverage", "test_tier1_features"),
    "2": ("Tier 2 — Boundary, Negative & Security", "test_tier2_boundaries"),
    "3": ("Tier 3 — Cross-Feature Combinations", "test_tier3_combinations"),
    "4": ("Tier 4 — Real Browser/JS Scenarios", "test_tier4_scenarios"),
}


def _iter_tests(suite: unittest.TestSuite):
    for item in suite:
        if isinstance(item, unittest.TestSuite):
            yield from _iter_tests(item)
        else:
            yield item


def _load_tier_suite(tier_key: str) -> tuple[str, unittest.TestSuite]:
    label, mod_name = TIER_MODULES[tier_key]
    mod = importlib.import_module(mod_name)
    suite = unittest.TestLoader().loadTestsFromModule(mod)
    return label, suite


def cmd_list(tiers: list[str]) -> int:
    total = 0
    for tk in tiers:
        label, suite = _load_tier_suite(tk)
        cases = list(_iter_tests(suite))
        total += len(cases)
        print(f"=== {label} ({len(cases)} tests) ===")
        for tc in cases:
            print(f"  - {tc.id()}")
    print(f"TOTAL_DISCOVERED: {total}")
    return 0


class RecordingResult(unittest.TextTestResult):
    def __init__(self, stream, descriptions, verbosity):
        super().__init__(stream, descriptions, verbosity)
        self.passed_ids: list[str] = []

    def addSuccess(self, test):
        super().addSuccess(test)
        self.passed_ids.append(test.id())


class RecordingRunner(unittest.TextTestRunner):
    resultclass = RecordingResult


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="QLDAPM_Nhom06 Opaque-Box E2E Test Runner")
    parser.add_argument("--tier", choices=["1", "2", "3", "4", "all"], default="all")
    parser.add_argument("--list", action="store_true", help="List discovered test cases and exit")
    parser.add_argument("--base-url", default=None, help="Use an already-running server URL instead of spawning php -S")
    parser.add_argument("--db-name", default=None, help="Target MySQL database name")
    parser.add_argument("--skip-db-reset", action="store_true", help="Do not drop/re-seed database before tiers")
    parser.add_argument("--verbose", action="store_true", help="Verbose unittest output")
    parser.add_argument("--json-report", default=None, help="Write JSON execution summary to file")
    args = parser.parse_args(argv)

    selected_tiers = ["1", "2", "3", "4"] if args.tier == "all" else [args.tier]

    if args.list:
        return cmd_list(selected_tiers)

    target_db = args.db_name or DEFAULT_DB_NAME
    db = DbManager(db_name=target_db)
    try:
        db.preflight()
    except Exception as exc:
        print(f"[PREFLIGHT FAILED] {exc}", file=sys.stderr)
        return 2

    php_srv: PhpServerManager | None = None
    report: dict = {
        "base_url": args.base_url,
        "db_name": db.db_name,
        "env_override_supported": False,
        "tiers": {},
        "total_run": 0,
        "total_passed": 0,
        "total_failed": 0,
        "total_errors": 0,
        "php_stderr_errors": [],
    }

    try:
        if args.db_name is None:
            if db.probe_env_db_support():
                print(f"[DB] Using isolated env-switched DB: {db.db_name}")
            else:
                print(f"[DB] DB_NAME env override not active; snapshotting '{db.db_name}' via mysqldump...")
                db.take_snapshot()
        else:
            if args.db_name != DEFAULT_DB_NAME:
                db.env_override_supported = True
            else:
                db.take_snapshot()

        report["db_name"] = db.db_name
        report["env_override_supported"] = db.env_override_supported

        if args.base_url:
            base_url = args.base_url.rstrip("/")
        else:
            extra_env = {"DB_NAME": db.db_name} if db.env_override_supported else {}
            php_srv = PhpServerManager(extra_env=extra_env)
            base_url = php_srv.start()
            print(f"[PHP] Ephemeral server started at {base_url}")

        report["base_url"] = base_url
        RUNTIME.managed_by_runner = True
        RUNTIME.base_url = base_url
        RUNTIME.db_manager = db
        RUNTIME.php_server = php_srv
        RUNTIME.db_name = db.db_name
        RUNTIME.skip_db_reset = args.skip_db_reset
        RUNTIME.verbose = args.verbose

        overall_ok = True
        for tk in selected_tiers:
            label, suite = _load_tier_suite(tk)
            if not args.skip_db_reset:
                print(f"[DB] Resetting & seeding baseline before Tier {tk}...")
                db.reset_and_seed_baseline()

            print(f"\n=== Running {label} ({suite.countTestCases()} tests) ===")
            t0 = time.monotonic()
            runner = RecordingRunner(verbosity=2 if args.verbose else 1, stream=sys.stdout)
            res = runner.run(suite)
            elapsed = round(time.monotonic() - t0, 2)

            passed = len(getattr(res, "passed_ids", []))
            failed = len(res.failures)
            errors = len(res.errors)
            report["total_run"] += res.testsRun
            report["total_passed"] += passed
            report["total_failed"] += failed
            report["total_errors"] += errors
            report["tiers"][tk] = {
                "label": label,
                "run": res.testsRun,
                "passed": passed,
                "failed": failed,
                "errors": errors,
                "elapsed_sec": elapsed,
                "failures": [{"test": t.id(), "trace": tb} for t, tb in res.failures],
                "errors_detail": [{"test": t.id(), "trace": tb} for t, tb in res.errors],
            }
            if not res.wasSuccessful():
                overall_ok = False

        if php_srv is not None:
            php_errs = php_srv.get_php_errors()
            report["php_stderr_errors"] = php_errs
            if php_errs:
                print("\n[PHP STDERR WARNINGS/ERRORS DETECTED]")
                for line in php_errs[:20]:
                    print(f"  {line}")

        print(
            f"\n=== SUMMARY: run={report['total_run']} passed={report['total_passed']} "
            f"failed={report['total_failed']} errors={report['total_errors']} ==="
        )

        if args.json_report:
            rp = Path(args.json_report)
            rp.parent.mkdir(parents=True, exist_ok=True)
            rp.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding="utf-8")
            print(f"[REPORT] Wrote JSON report to {rp}")

        return 0 if overall_ok else 1
    finally:
        RUNTIME.managed_by_runner = False
        if php_srv is not None:
            php_srv.stop()
        db.restore_snapshot()


if __name__ == "__main__":
    raise SystemExit(main())
