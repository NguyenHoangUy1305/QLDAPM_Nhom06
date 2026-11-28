"""Shared unittest base class and semantic E2E test helpers."""
from __future__ import annotations

import re
import unittest
import uuid

from config import RUNTIME
from lib.db_manager import DbManager
from lib.http_client import HttpClient, assert_no_php_errors, parse_html, text_of
from lib.php_server import PhpServerManager

ORDER_CODE_RE = re.compile(r"\b(?:DH|ORD|PC)\d{3,10}\b")
TRADEIN_CODE_RE = re.compile(r"\bTC\d{3,10}\b")
WARRANTY_CODE_RE = re.compile(r"\bBH\d{3,10}\b")
TRAGOP_RE = re.compile(r"tr[aả]\s*g[oó]p|tra-gop|installment", re.IGNORECASE)


def uid(prefix: str = "e2e") -> str:
    return f"{prefix}_{uuid.uuid4().hex[:6]}"


class E2EBaseTestCase(unittest.TestCase):
    """Base TestCase managing standalone or runner-managed server + DB lifecycle."""

    _local_server: PhpServerManager | None = None
    _local_db: DbManager | None = None
    base_url: str = ""
    db: DbManager | None = None

    @classmethod
    def setUpClass(cls) -> None:
        super().setUpClass()
        if RUNTIME.managed_by_runner:
            cls.base_url = RUNTIME.base_url or ""
            cls.db = RUNTIME.db_manager  # type: ignore[assignment]
            return

        db = DbManager()
        db.preflight()
        if not db.probe_env_db_support():
            db.take_snapshot()
        if not RUNTIME.skip_db_reset:
            db.reset_and_seed_baseline()
        extra_env = {"DB_NAME": db.db_name} if db.env_override_supported else {}
        srv = PhpServerManager(extra_env=extra_env)
        base_url = srv.start()
        cls._local_db = db
        cls._local_server = srv
        cls.db = db
        cls.base_url = base_url

    @classmethod
    def tearDownClass(cls) -> None:
        try:
            if cls._local_server is not None:
                cls._local_server.stop()
                cls._local_server = None
        finally:
            if cls._local_db is not None:
                cls._local_db.restore_snapshot()
                cls._local_db = None
            super().tearDownClass()

    def setUp(self) -> None:
        super().setUp()
        self.http = HttpClient(self.base_url)

    def tearDown(self) -> None:
        srv = RUNTIME.php_server or self._local_server
        if isinstance(srv, PhpServerManager):
            errs = srv.get_php_errors()
            if errs:
                srv.clear_logs()
                self.fail(f"PHP server stderr emitted runtime errors:\n" + "\n".join(errs[:10]))
        super().tearDown()

    def new_client(self) -> HttpClient:
        return HttpClient(self.base_url)

    def assert_no_tragop(self, text: str, context: str = "") -> None:
        m = TRAGOP_RE.search(text or "")
        if m:
            self.fail(f"Forbidden installment ('trả góp') text {m.group(0)!r} found in {context}")

    def assert_contains_any(self, haystack: str, candidates: list[str], msg: str = "") -> None:
        low = (haystack or "").lower()
        for cand in candidates:
            if cand.lower() in low:
                return
        self.fail(msg or f"Expected one of {candidates!r} in response text, got snippet: {(haystack or '')[:400]!r}")

    def add_product_to_cart(self, client: HttpClient, slug: str, qty: int = 1) -> str:
        """Opaque-box add-to-cart via product.php form submission; returns product name."""
        prod_resp = client.get("/product.php", params={"slug": slug}, allow_4xx=False)
        doc = parse_html(prod_resp)
        h1 = doc.xpath("//h1")
        prod_name = text_of(h1[0]) if h1 else slug
        form_xpath = '//form[contains(@action, "cart.php") or .//input[@name="product_id"]]'
        overrides: dict[str, str] = {}
        forms = doc.xpath(form_xpath)
        self.assertTrue(forms, f"Add-to-cart form not found on /product.php?slug={slug}")
        if forms[0].xpath('.//input[@name="quantity"] | .//input[@name="qty"]'):
            if forms[0].xpath('.//input[@name="quantity"]'):
                overrides["quantity"] = str(qty)
            else:
                overrides["qty"] = str(qty)
        cart_resp = client.submit_form(prod_resp, overrides, form_xpath=form_xpath)
        assert_no_php_errors(cart_resp, context=f"add_to_cart({slug})", allow_4xx=False)
        if qty > 1 and "quantity" not in overrides and "qty" not in overrides:
            # Click increment qty-1 times via cart.php form
            for _ in range(qty - 1):
                cdoc = parse_html(cart_resp)
                inc_forms = cdoc.xpath('//form[.//input[@name="action" and @value="inc"]]')
                if inc_forms:
                    cart_resp = client.submit_form(
                        cart_resp,
                        {},
                        form_xpath='//form[.//input[@name="action" and @value="inc"]]',
                    )
        return prod_name

    def extract_order_code(self, html: str) -> str:
        m = ORDER_CODE_RE.search(html or "")
        self.assertIsNotNone(m, f"Expected order code (DH...) in response, snippet: {(html or '')[:500]!r}")
        return m.group(0)

    def extract_tradein_code(self, html: str) -> str:
        m = TRADEIN_CODE_RE.search(html or "")
        self.assertIsNotNone(m, f"Expected trade-in code (TC...) in response, snippet: {(html or '')[:500]!r}")
        return m.group(0)

    def extract_warranty_codes(self, html: str) -> list[str]:
        return list(dict.fromkeys(WARRANTY_CODE_RE.findall(html or "")))
