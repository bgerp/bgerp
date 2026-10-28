"""Unoserver worker lifecycle checks. Copyright 2026 Experta EOOD. GPL-3.0-or-later."""

import importlib.util
import os
from pathlib import Path
import signal
import subprocess
import tempfile
import unittest
from unittest import mock

spec = importlib.util.spec_from_file_location(
    "worker", Path(__file__).resolve().parents[1] / "unoserver" / "convert.py"
)
worker = importlib.util.module_from_spec(spec)
spec.loader.exec_module(worker)


class Lifecycle(unittest.TestCase):
    """Real child processes with simulated success, client failure and timeout."""

    def test_cleanup(self):
        """Stop the owned process, preserve unrelated processes and remove the profile."""
        original_popen = subprocess.Popen
        sentinel = original_popen([worker.sys.executable, "-c", "import time; time.sleep(60)"])
        try:
            for failure in (None, subprocess.CalledProcessError(1, "client"),
                            subprocess.TimeoutExpired("client", 180), SystemExit(143)):
                children = []

                def start(command, **options):
                    """Replace only the server executable; retain real process isolation."""
                    self.assertTrue(options["start_new_session"])
                    self.assertEqual(command[1:3], ["-m", "unoserver.server"])
                    child = original_popen([worker.sys.executable, "-c", "import time; time.sleep(60)"], **options)
                    children.append(child)
                    return child

                with tempfile.TemporaryDirectory() as directory:
                    def client(*args, **kwargs):
                        """Model a complete or partially written output file."""
                        Path(directory, "out.pdf").write_bytes(b"partial" if failure else b"complete")
                        if failure is not None:
                            raise failure

                    with mock.patch.object(worker.subprocess, "Popen", side_effect=start), \
                         mock.patch.object(worker.subprocess, "run", side_effect=client) as run:
                        if failure is None:
                            worker.convert(directory, "soffice", "input.docx", directory + "/out.pdf")
                        else:
                            with self.assertRaises(type(failure)):
                                worker.convert(directory, "soffice", "input.docx", directory + "/out.pdf")
                        self.assertEqual(run.call_args.kwargs["timeout"], 180)
                    self.assertEqual(list(Path(directory).iterdir()), [] if failure else [Path(directory, "out.pdf")])
                self.assertIsNotNone(children[0].poll())
                self.assertIsNone(sentinel.poll())
                with self.assertRaises(ProcessLookupError):
                    os.killpg(children[0].pid, 0)
        finally:
            sentinel.terminate()
            sentinel.wait()

    def test_signal(self):
        """Termination unwinds the worker rather than leaving its server running."""
        with self.assertRaises(SystemExit) as result:
            worker.interrupt(signal.SIGTERM, None)
        self.assertEqual(result.exception.code, 143)


if __name__ == "__main__":
    unittest.main()
