"""Opaque-box HTTP client with lxml form/CSRF extraction and PHP error scanning."""
from __future__ import annotations

import io
import re
from urllib.parse import urljoin

import lxml.html
import requests
from PIL import Image

from config import HTTP_TIMEOUT

PHP_HTML_ERROR_PATTERNS = [
    re.compile(r"<b>\s*(?:Fatal|Parse)\s+error\s*</b>\s*:", re.IGNORECASE),
    re.compile(r"(?:^|\n)\s*(?:Fatal|Parse)\s+error\s*:", re.IGNORECASE),
    re.compile(r"Uncaught\s+(?:PDOException|Error|Exception)", re.IGNORECASE),
    re.compile(r"<b>\s*(?:Warning|Notice|Deprecated)\s*</b>\s*:", re.IGNORECASE),
    re.compile(r"(?:PHP\s+(?:Warning|Notice|Fatal error|Parse error))", re.IGNORECASE),
    re.compile(r"(?:\r?\n|^)(?:PHP )?(?:Warning|Notice|Fatal error|Parse error):\s+.*\sin\s+.*\son line\s+\d+", re.IGNORECASE),
    re.compile(r"SQLSTATE\[", re.IGNORECASE),
]


def make_valid_png_bytes(color: tuple[int, int, int] = (20, 120, 220)) -> bytes:
    """Create a genuine 4x4 PNG image in memory via Pillow."""
    buf = io.BytesIO()
    img = Image.new("RGB", (4, 4), color=color)
    img.save(buf, format="PNG")
    return buf.getvalue()


def make_valid_jpeg_bytes(color: tuple[int, int, int] = (220, 60, 40)) -> bytes:
    """Create a genuine 4x4 JPEG image in memory via Pillow."""
    buf = io.BytesIO()
    img = Image.new("RGB", (4, 4), color=color)
    img.save(buf, format="JPEG")
    return buf.getvalue()


def assert_no_php_errors(resp: requests.Response, context: str = "", allow_4xx: bool = True) -> None:
    """Assert HTTP status < 500 and zero PHP fatal/warning/notice/SQLSTATE markers."""
    if resp.status_code >= 500:
        raise AssertionError(
            f"HTTP {resp.status_code} Server Error on {resp.url} ({context}). "
            f"Body snippet: {resp.text[:500]}"
        )
    if not allow_4xx and resp.status_code >= 400:
        raise AssertionError(
            f"Unexpected HTTP {resp.status_code} on {resp.url} ({context}). "
            f"Body snippet: {resp.text[:500]}"
        )
    body = resp.text or ""
    for pat in PHP_HTML_ERROR_PATTERNS:
        m = pat.search(body)
        if m:
            start = max(0, m.start() - 60)
            end = min(len(body), m.end() + 180)
            raise AssertionError(
                f"PHP/SQL error marker {m.group(0)!r} detected on {resp.url} ({context}): "
                f"{body[start:end]!r}"
            )


def parse_html(resp_or_html: requests.Response | str) -> lxml.html.HtmlElement:
    text = resp_or_html.text if isinstance(resp_or_html, requests.Response) else resp_or_html
    if not text or not text.strip():
        raise AssertionError("Expected HTML document, got empty response body.")
    return lxml.html.fromstring(text)


def text_of(el: lxml.html.HtmlElement) -> str:
    return " ".join(el.text_content().split())


class HttpClient:
    """Session-backed HTTP client for opaque-box E2E tests."""

    def __init__(self, base_url: str, timeout: float = HTTP_TIMEOUT) -> None:
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self.session = requests.Session()

    def url(self, path: str) -> str:
        if path.startswith("http://") or path.startswith("https://"):
            return path
        if not path.startswith("/"):
            path = "/" + path
        return f"{self.base_url}{path}"

    def clear_cookies(self) -> None:
        self.session.cookies.clear()

    def get(
        self,
        path: str,
        params: dict | None = None,
        allow_redirects: bool = True,
        check_php: bool = True,
        allow_4xx: bool = True,
    ) -> requests.Response:
        resp = self.session.get(
            self.url(path),
            params=params,
            allow_redirects=allow_redirects,
            timeout=self.timeout,
        )
        if check_php:
            assert_no_php_errors(resp, context=f"GET {path}", allow_4xx=allow_4xx)
        return resp

    def post(
        self,
        path: str,
        data: dict | list | None = None,
        files=None,
        allow_redirects: bool = True,
        check_php: bool = True,
        allow_4xx: bool = True,
    ) -> requests.Response:
        resp = self.session.post(
            self.url(path),
            data=data,
            files=files,
            allow_redirects=allow_redirects,
            timeout=self.timeout,
        )
        if check_php:
            assert_no_php_errors(resp, context=f"POST {path}", allow_4xx=allow_4xx)
        return resp

    def extract_csrf(self, resp_or_doc: requests.Response | lxml.html.HtmlElement, form_xpath: str | None = None) -> str:
        doc = parse_html(resp_or_doc) if isinstance(resp_or_doc, requests.Response) else resp_or_doc
        if form_xpath:
            forms = doc.xpath(form_xpath)
            if not forms:
                raise AssertionError(f"Form not found for XPath {form_xpath!r}")
            tokens = forms[0].xpath('.//input[@name="csrf_token"]/@value')
            if not tokens or not tokens[0].strip():
                raise AssertionError(
                    f"Missing CSRF token (<input name='csrf_token'>) inside targeted form {form_xpath!r}."
                )
            return tokens[0].strip()
        tokens = doc.xpath('//input[@name="csrf_token"]/@value')
        if not tokens:
            tokens = doc.xpath('//meta[@name="csrf-token"]/@content')
        if not tokens or not tokens[0].strip():
            raise AssertionError("Missing CSRF token (<input name='csrf_token'>) in rendered HTML.")
        return tokens[0].strip()

    def submit_form(
        self,
        get_path_or_resp: str | requests.Response,
        overrides: dict[str, str],
        files=None,
        form_xpath: str | None = None,
        form_index: int = 0,
        require_csrf: bool = True,
        allow_redirects: bool = True,
    ) -> requests.Response:
        """Parse rendered HTML <form>, preserve hidden defaults/CSRF, apply overrides, submit."""
        if isinstance(get_path_or_resp, str):
            resp = self.get(get_path_or_resp, allow_4xx=False)
        else:
            resp = get_path_or_resp

        doc = parse_html(resp)
        if form_xpath:
            forms = doc.xpath(form_xpath)
        else:
            forms = doc.xpath("//form")
        if not forms or form_index >= len(forms):
            raise AssertionError(
                f"Expected form (xpath={form_xpath!r}, index={form_index}) on {resp.url}, found {len(forms)}."
            )
        form = forms[form_index]

        payload: dict[str, str] = {}
        for inp in form.xpath(".//input[@name]"):
            name = inp.get("name")
            if not name or name.endswith("[]"):
                continue
            itype = (inp.get("type") or "text").lower()
            if itype in ("checkbox", "radio"):
                if inp.get("checked") is not None:
                    payload[name] = inp.get("value", "on")
            elif itype != "file":
                payload[name] = inp.get("value", "")

        for sel in form.xpath(".//select[@name]"):
            name = sel.get("name")
            if not name:
                continue
            selected = sel.xpath(".//option[@selected]/@value")
            if selected:
                payload[name] = selected[0]
            else:
                opts = sel.xpath(".//option/@value")
                if opts:
                    payload[name] = opts[0]

        for ta in form.xpath(".//textarea[@name]"):
            name = ta.get("name")
            if name:
                payload[name] = ta.text or ""

        for btn in form.xpath(".//button[@name]"):
            name = btn.get("name")
            if not name or name.endswith("[]"):
                continue
            btype = (btn.get("type") or "submit").lower()
            if btype == "submit" and name not in payload:
                payload[name] = btn.get("value", "")

        method = (form.get("method") or "GET").upper()
        if require_csrf and method == "POST" and not payload.get("csrf_token", "").strip():
            if form_xpath:
                raise AssertionError(
                    f"Missing CSRF token (<input name='csrf_token'>) inside targeted form {form_xpath!r} on {resp.url}."
                )
            payload["csrf_token"] = self.extract_csrf(doc, form_xpath=None)

        payload.update({k: str(v) for k, v in overrides.items()})

        action = (form.get("action") or "").strip()
        target_url = urljoin(resp.url, action) if action else resp.url
        if method == "GET":
            return self.get(target_url, params=payload, allow_redirects=allow_redirects)
        return self.post(target_url, data=payload, files=files, allow_redirects=allow_redirects)

    def login_customer(self, email: str, password: str) -> requests.Response:
        resp = self.submit_form(
            "/login.php",
            {"email": email, "password": password},
            form_xpath='//form[.//input[@name="email"] and .//input[@name="password"]]',
        )
        return resp

    def login_admin(self, email: str, password: str) -> requests.Response:
        resp = self.submit_form(
            "/admin/login.php",
            {"email": email, "password": password},
            form_xpath='//form[.//input[@name="email"] and .//input[@name="password"]]',
        )
        return resp

    def logout_customer(self) -> requests.Response:
        return self.get("/logout.php")

    def logout_admin(self) -> requests.Response:
        return self.get("/admin/logout.php")
