"""Ephemeral PHP 8.3 built-in web server manager with stderr PHP-error scanning."""
from __future__ import annotations

import os
import re
import socket
import subprocess
import threading
import time
from pathlib import Path

import requests

from config import PHP_BIN, REPO_ROOT, SERVER_START_TIMEOUT

PHP_STDERR_ERROR_RE = re.compile(
    r"(?:PHP\s+(?:Fatal error|Parse error|Warning|Notice|Deprecated)|Uncaught\s+(?:PDOException|Error|Exception))",
    re.IGNORECASE,
)


def pick_free_port(host: str = "127.0.0.1") -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.bind((host, 0))
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        return int(sock.getsockname()[1])


class PhpServerManager:
    """Manages an isolated `php -S 127.0.0.1:<port> -t <repo>` process."""

    def __init__(
        self,
        repo_root: Path = REPO_ROOT,
        php_bin: str = PHP_BIN,
        host: str = "127.0.0.1",
        port: int | None = None,
        extra_env: dict[str, str] | None = None,
    ) -> None:
        self.repo_root = Path(repo_root)
        self.php_bin = php_bin
        self.host = host
        self.port = port or pick_free_port(host)
        self.extra_env = dict(extra_env or {})
        self.proc: subprocess.Popen[str] | None = None
        self._logs: list[str] = []
        self._lock = threading.Lock()
        self._reader_thread: threading.Thread | None = None

    @property
    def base_url(self) -> str:
        return f"http://{self.host}:{self.port}"

    def _drain_stream(self, stream) -> None:
        try:
            for line in iter(stream.readline, ""):
                if not line:
                    break
                with self._lock:
                    self._logs.append(line.rstrip("\r\n"))
        finally:
            try:
                stream.close()
            except Exception:
                pass

    def start(self, timeout: float = SERVER_START_TIMEOUT) -> str:
        if self.proc is not None and self.proc.poll() is None:
            return self.base_url

        env = os.environ.copy()
        env.update(self.extra_env)

        cmd = [self.php_bin, "-S", f"{self.host}:{self.port}", "-t", str(self.repo_root)]
        creationflags = 0
        if os.name == "nt":
            creationflags = getattr(subprocess, "CREATE_NO_WINDOW", 0)

        self.proc = subprocess.Popen(
            cmd,
            cwd=str(self.repo_root),
            env=env,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.PIPE,
            text=True,
            encoding="utf-8",
            errors="replace",
            creationflags=creationflags,
        )
        assert self.proc.stderr is not None
        self._reader_thread = threading.Thread(
            target=self._drain_stream,
            args=(self.proc.stderr,),
            daemon=True,
        )
        self._reader_thread.start()

        deadline = time.monotonic() + timeout
        probe_url = f"{self.base_url}/index.php"
        last_err: Exception | None = None
        while time.monotonic() < deadline:
            if self.proc.poll() is not None:
                raise RuntimeError(
                    f"PHP server exited prematurely (rc={self.proc.returncode}). Logs:\n"
                    + "\n".join(self.get_logs())
                )
            try:
                resp = requests.get(probe_url, timeout=2.0)
                if resp.status_code < 500:
                    return self.base_url
            except Exception as exc:
                last_err = exc
            time.sleep(0.15)

        self.stop()
        raise TimeoutError(f"PHP server did not respond within {timeout}s at {probe_url}: {last_err}")

    def get_logs(self) -> list[str]:
        with self._lock:
            return list(self._logs)

    def get_php_errors(self) -> list[str]:
        with self._lock:
            return [line for line in self._logs if PHP_STDERR_ERROR_RE.search(line)]

    def clear_logs(self) -> None:
        with self._lock:
            self._logs.clear()

    def stop(self) -> None:
        if self.proc is None:
            return
        if self.proc.poll() is None:
            self.proc.terminate()
            try:
                self.proc.wait(timeout=3.0)
            except subprocess.TimeoutExpired:
                self.proc.kill()
                self.proc.wait(timeout=2.0)
        if self._reader_thread is not None:
            self._reader_thread.join(timeout=1.0)
        self.proc = None

    def __enter__(self) -> "PhpServerManager":
        self.start()
        return self

    def __exit__(self, exc_type, exc, tb) -> None:
        self.stop()
