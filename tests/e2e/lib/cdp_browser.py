"""Minimal zero-dependency-install Chrome CDP driver using headless Chrome + tornado.websocket."""
from __future__ import annotations

import asyncio
import json
import os
import re
import shutil
import socket
import subprocess
import tempfile
import time
from typing import Any

import requests
import tornado.websocket

from config import CDP_TIMEOUT, CHROME_BIN

PHP_DOM_ERROR_RE = re.compile(
    r"(?:<b>\s*(?:Fatal|Parse)\s+error\s*</b>\s*:|Uncaught\s+(?:PDOException|Error|Exception)|"
    r"<b>\s*(?:Warning|Notice)\s*</b>\s*:|SQLSTATE\[)",
    re.IGNORECASE,
)


def _pick_free_port() -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(("127.0.0.1", 0))
        return int(s.getsockname()[1])


class CdpBrowser:
    """Headless Chrome/Edge controller over Chrome DevTools Protocol."""

    def __init__(
        self,
        base_url: str,
        chrome_bin: str = CHROME_BIN,
        timeout: float = CDP_TIMEOUT,
    ) -> None:
        self.base_url = base_url.rstrip("/")
        self.chrome_bin = chrome_bin
        self.timeout = timeout
        self.debug_port = _pick_free_port()
        self.user_data_dir: str | None = None
        self.proc: subprocess.Popen | None = None
        self._loop: asyncio.AbstractEventLoop | None = None
        self._ws = None
        self._cmd_id = 0
        self.js_exceptions: list[str] = []
        self.local_server_errors: list[str] = []

    def start(self) -> "CdpBrowser":
        if not os.path.exists(self.chrome_bin):
            raise RuntimeError(f"Chrome/Edge binary not found: {self.chrome_bin}")

        try:
            self.user_data_dir = tempfile.mkdtemp(prefix="qldapm_chrome_")
            args = [
                self.chrome_bin,
                "--headless=new",
                "--disable-gpu",
                "--no-sandbox",
                "--disable-dev-shm-usage",
                "--disable-extensions",
                "--no-first-run",
                "--no-default-browser-check",
                "--window-size=1440,900",
                f"--remote-debugging-port={self.debug_port}",
                f"--user-data-dir={self.user_data_dir}",
                "about:blank",
            ]
            creationflags = getattr(subprocess, "CREATE_NO_WINDOW", 0) if os.name == "nt" else 0
            self.proc = subprocess.Popen(
                args,
                stdout=subprocess.DEVNULL,
                stderr=subprocess.DEVNULL,
                creationflags=creationflags,
            )

            ws_url = self._wait_for_ws_url()
            self._loop = asyncio.new_event_loop()
            asyncio.set_event_loop(self._loop)

            async def _connect():
                return await tornado.websocket.websocket_connect(ws_url)

            self._ws = self._loop.run_until_complete(_connect())
            self.send_cdp("Page.enable")
            self.send_cdp("Runtime.enable")
            self.send_cdp("Network.enable")
            self.send_cdp("DOM.enable")
            return self
        except Exception:
            self.close()
            raise

    def _wait_for_ws_url(self) -> str:
        deadline = time.monotonic() + self.timeout
        list_url = f"http://127.0.0.1:{self.debug_port}/json/list"
        last_err: Exception | None = None
        while time.monotonic() < deadline:
            if self.proc and self.proc.poll() is not None:
                raise RuntimeError(f"Headless Chrome exited prematurely (rc={self.proc.returncode})")
            try:
                resp = requests.get(list_url, timeout=1.5)
                if resp.status_code == 200:
                    targets = resp.json()
                    for t in targets:
                        if t.get("type") == "page" and t.get("webSocketDebuggerUrl"):
                            return t["webSocketDebuggerUrl"]
            except Exception as exc:
                last_err = exc
            time.sleep(0.15)
        raise TimeoutError(f"Chrome CDP endpoint did not open within {self.timeout}s: {last_err}")

    def _handle_event(self, msg: dict[str, Any]) -> None:
        method = msg.get("method")
        params = msg.get("params") or {}
        if method == "Runtime.exceptionThrown":
            details = params.get("exceptionDetails") or {}
            text = details.get("text") or "Uncaught JS exception"
            exc_obj = (details.get("exception") or {}).get("description") or ""
            self.js_exceptions.append(f"{text}: {exc_obj}".strip(": "))
        elif method == "Network.responseReceived":
            resp = params.get("response") or {}
            url = resp.get("url") or ""
            status = int(resp.get("status") or 0)
            # Only flag same-origin local server 5xx errors; ignore third-party CDNs
            if url.startswith(self.base_url) and status >= 500:
                self.local_server_errors.append(f"{status} {url}")

    async def _send_cdp_async(self, method: str, params: dict[str, Any] | None = None) -> dict[str, Any]:
        assert self._ws is not None
        self._cmd_id += 1
        cid = self._cmd_id
        payload = {"id": cid, "method": method}
        if params:
            payload["params"] = params
        await self._ws.write_message(json.dumps(payload))

        deadline = time.monotonic() + self.timeout
        while time.monotonic() < deadline:
            remaining = max(0.1, deadline - time.monotonic())
            raw = await asyncio.wait_for(self._ws.read_message(), timeout=remaining)
            if raw is None:
                raise RuntimeError("CDP WebSocket closed unexpectedly.")
            msg = json.loads(raw)
            if "id" in msg and msg["id"] == cid:
                if "error" in msg:
                    raise RuntimeError(f"CDP {method} error: {msg['error']}")
                return msg.get("result") or {}
            if "method" in msg:
                self._handle_event(msg)
        raise TimeoutError(f"CDP {method} timed out after {self.timeout}s")

    def send_cdp(self, method: str, params: dict[str, Any] | None = None) -> dict[str, Any]:
        assert self._loop is not None
        return self._loop.run_until_complete(self._send_cdp_async(method, params))

    def clear_errors(self) -> None:
        self.js_exceptions.clear()
        self.local_server_errors.clear()

    def clear_browser_cookies(self) -> None:
        self.send_cdp("Network.clearBrowserCookies")
        self.clear_errors()

    def _full_url(self, path_or_url: str) -> str:
        if path_or_url.startswith("http://") or path_or_url.startswith("https://"):
            return path_or_url
        if not path_or_url.startswith("/"):
            path_or_url = "/" + path_or_url
        return f"{self.base_url}{path_or_url}"

    def navigate(self, path_or_url: str, wait_load: bool = True) -> None:
        url = self._full_url(path_or_url)
        self.send_cdp("Page.navigate", {"url": url})
        if wait_load:
            self.wait_for_condition("document.readyState === 'complete'", timeout=self.timeout)
            time.sleep(0.15)
        self.assert_clean_page(context=f"navigate({url})")

    def current_url(self) -> str:
        return str(self.eval("window.location.href"))

    def eval(self, expression: str) -> Any:
        res = self.send_cdp(
            "Runtime.evaluate",
            {
                "expression": expression,
                "returnByValue": True,
                "awaitPromise": True,
            },
        )
        if "exceptionDetails" in res:
            details = res["exceptionDetails"]
            raise AssertionError(f"JS evaluation error in {expression!r}: {details}")
        return (res.get("result") or {}).get("value")

    def wait_for_condition(self, js_predicate: str, timeout: float = 10.0) -> None:
        deadline = time.monotonic() + timeout
        while time.monotonic() < deadline:
            try:
                if bool(self.eval(f"Boolean({js_predicate})")):
                    return
            except Exception:
                pass
            time.sleep(0.1)
        raise AssertionError(f"Condition not met within {timeout}s: {js_predicate}")

    def wait_for_selector(self, css_selector: str, visible: bool = False, timeout: float = 10.0) -> None:
        sel_json = json.dumps(css_selector)
        if visible:
            pred = (
                f"(() => {{ const el = document.querySelector({sel_json}); "
                "if (!el) return false; "
                "const st = window.getComputedStyle(el); "
                "const r = el.getBoundingClientRect(); "
                "return st.display !== 'none' && st.visibility !== 'hidden' && (r.width > 0 || r.height > 0); })()"
            )
        else:
            pred = f"document.querySelector({sel_json}) !== null"
        self.wait_for_condition(pred, timeout=timeout)

    def type_text(self, css_selector: str, text: str, clear: bool = False, char_by_char: bool = True) -> None:
        sel_json = json.dumps(css_selector)
        self.wait_for_selector(css_selector, timeout=self.timeout)
        self.eval(
            f"(() => {{ const el = document.querySelector({sel_json}); el.focus(); "
            + ("el.value = ''; " if clear else "if (typeof el.setSelectionRange === 'function') { const n = el.value.length; el.setSelectionRange(n, n); } ")
            + "return true; })()"
        )
        if char_by_char:
            for ch in text:
                self.send_cdp("Input.dispatchKeyEvent", {"type": "keyDown", "key": ch})
                self.send_cdp("Input.dispatchKeyEvent", {"type": "char", "text": ch})
                self.send_cdp("Input.dispatchKeyEvent", {"type": "keyUp", "key": ch})
                time.sleep(0.03)
        else:
            self.send_cdp("Input.insertText", {"text": text})
        # Ensure listeners bound to input/change fire cleanly
        self.eval(
            f"(() => {{ const el = document.querySelector({sel_json}); "
            "el.dispatchEvent(new Event('input', {bubbles:true})); "
            "el.dispatchEvent(new Event('change', {bubbles:true})); })()"
        )

    def select_option(self, css_selector: str, value: str) -> None:
        sel_json = json.dumps(css_selector)
        val_json = json.dumps(str(value))
        self.wait_for_selector(css_selector, timeout=self.timeout)
        ok = self.eval(
            f"(() => {{ const el = document.querySelector({sel_json}); "
            f"if (!el) return false; "
            f"el.value = {val_json}; "
            f"el.dispatchEvent(new Event('change', {{bubbles:true}})); "
            f"el.dispatchEvent(new Event('input', {{bubbles:true}})); "
            f"return el.value === {val_json}; }})()"
        )
        if not ok:
            raise AssertionError(f"Could not select option {value!r} on {css_selector!r}")

    def click(self, css_selector: str, wait_nav: bool = False) -> None:
        sel_json = json.dumps(css_selector)
        self.wait_for_selector(css_selector, timeout=self.timeout)
        self.eval(
            f"(() => {{ const el = document.querySelector({sel_json}); "
            "el.scrollIntoView({block:'center'}); el.click(); return true; })()"
        )
        if wait_nav:
            time.sleep(0.4)
            self.wait_for_condition("document.readyState === 'complete'", timeout=self.timeout)
            self.assert_clean_page(context=f"click({css_selector})")

    def text_content(self, css_selector: str = "body") -> str:
        sel_json = json.dumps(css_selector)
        val = self.eval(
            f"(() => {{ const el = document.querySelector({sel_json}); return el ? el.innerText : ''; }})()"
        )
        return str(val or "")

    def inner_html(self, css_selector: str = "body") -> str:
        sel_json = json.dumps(css_selector)
        val = self.eval(
            f"(() => {{ const el = document.querySelector({sel_json}); return el ? el.innerHTML : ''; }})()"
        )
        return str(val or "")

    def page_source(self) -> str:
        return str(self.eval("document.documentElement ? document.documentElement.outerHTML : ''") or "")

    def assert_clean_page(self, context: str = "") -> None:
        if self.local_server_errors:
            raise AssertionError(
                f"Local server 5xx during browser action ({context}): {self.local_server_errors}"
            )
        if self.js_exceptions:
            raise AssertionError(
                f"Uncaught JS exception during browser action ({context}): {self.js_exceptions}"
            )
        html = self.page_source()
        m = PHP_DOM_ERROR_RE.search(html)
        if m:
            raise AssertionError(
                f"PHP/SQL error rendered in browser DOM ({context}): {m.group(0)!r}"
            )

    def close(self) -> None:
        try:
            if self._ws is not None:
                self._ws.close()
                self._ws = None
        except Exception:
            pass
        try:
            if self._loop is not None:
                self._loop.close()
                self._loop = None
        except Exception:
            pass
        if self.proc is not None:
            if self.proc.poll() is None:
                self.proc.terminate()
                try:
                    self.proc.wait(timeout=3.0)
                except subprocess.TimeoutExpired:
                    self.proc.kill()
                    self.proc.wait(timeout=2.0)
            self.proc = None
        if self.user_data_dir and os.path.exists(self.user_data_dir):
            shutil.rmtree(self.user_data_dir, ignore_errors=True)
            self.user_data_dir = None

    def __enter__(self) -> "CdpBrowser":
        return self.start()

    def __exit__(self, exc_type, exc, tb) -> None:
        self.close()
