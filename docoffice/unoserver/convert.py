"""Single-job Unoserver bridge. Copyright 2026 Experta EOOD. GPL-3.0-or-later."""

import os
import signal
import socket
import subprocess
import sys
import tempfile
from pathlib import Path


def interrupt(signum, frame):
    """Allow the conversion's finally block to clean up on termination."""
    raise SystemExit(128 + signum)


def convert(library, office, source, destination):
    """Use upstream server/client, bounded execution and an owned process group."""
    output = Path(destination)
    existed = output.exists()
    environment = dict(os.environ, PYTHONPATH=library, PYTHONDONTWRITEBYTECODE="1")
    with tempfile.TemporaryDirectory(prefix="unoserver-", dir=Path(destination).parent) as work:
        utf8_csv = False
        if Path(source).suffix.lower() == ".csv":
            try:
                Path(source).read_text(encoding="utf-8-sig")
                utf8_csv = True
            except UnicodeDecodeError:
                pass
        # Reserve both ports together, so the OS cannot return the same port twice.
        with socket.socket() as rpc, socket.socket() as uno:
            rpc.bind(("127.0.0.1", 0))
            uno.bind(("127.0.0.1", 0))
            rpc_port = str(rpc.getsockname()[1])
            uno_port = str(uno.getsockname()[1])
        command = [sys.executable, "-m", "unoserver.server", "--port", rpc_port,
                   "--uno-port", uno_port, "--executable", office,
                   "--user-installation", work, "--temp-dir", work,
                   "--conversion-timeout", "120", "--quiet"]
        if utf8_csv:
            # Unoserver 3.7 has no import FilterOptions; older Office needs explicit UTF-8.
            command = [office, "--headless", "--norestore",
                       "-env:UserInstallation=" + Path(work, "profile").as_uri(),
                       "--infilter=Text - txt - csv (StarCalc):44,34,76,1",
                       "--convert-to", output.suffix[1:], "--outdir", work,
                       str(Path(source).resolve())]
        server = subprocess.Popen(command, env=environment, start_new_session=True)
        try:
            if utf8_csv:
                code = server.wait(timeout=180)
                if code:
                    raise subprocess.CalledProcessError(code, command)
                Path(work, Path(source).stem + output.suffix).replace(output)
            else:
                subprocess.run(
                    [sys.executable, "-c", "from unoserver.client import converter_main; converter_main()",
                     "--port", rpc_port, "--host-location", "local", "--quiet",
                     str(Path(source).resolve()), str(output.resolve())],
                    env=environment, check=True, timeout=180,
                )
            if not output.is_file() or output.stat().st_size == 0:
                raise RuntimeError("Unoserver did not produce a document")
        except BaseException:
            # fileman checks existence; a partial output must not look successful.
            if not existed and output.is_file():
                output.unlink()
            raise
        finally:
            # Stop only this job's server and Office children, including on timeout.
            try:
                os.killpg(server.pid, signal.SIGTERM)
                try:
                    server.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    pass
                os.killpg(server.pid, signal.SIGKILL)
            except ProcessLookupError:
                pass
            server.wait()


if __name__ == "__main__":
    signal.signal(signal.SIGTERM, interrupt)
    convert(*sys.argv[1:])
