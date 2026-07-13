# Imgcolor Calibration Presets Implementation Plan

> Execute this plan task by task on `feat/imgcolor-calibration-presets`, keeping
> tests red before each production change and committing each coherent slice.

**Goal:** Restrict imgcolor uploads to JPEG/PNG and make existing calibration
profiles fully usable from the analysis form while preserving the exact settings
used in analysis history.

**Architecture:** Fileman remains the upload-policy owner through the
package-specific bucket extension list. `imgcolor_Calibration` becomes the
authoritative mapper for default/profile/form calibration values. The existing
Demo controller orchestrates form actions, Profiles remains the CRUD owner, and
Analyses stores an additive JSON snapshot of the exact submitted calibration.

**Runtime:** PHP 8.2+, bgERP managers/forms, GD-backed vendored analyzer, existing
`unit_Class` tests, and framework-free CLI regression scripts.

---

## Task 1: Establish an Executable PHP Test Runtime

**Files:**

- Inspect: `_docs/docker/php82/Dockerfile`
- Inspect: `_docs/docker/docker-compose.yml`
- No repository changes expected

1. Check whether an existing PHP 8.2+ executable or runnable Docker engine is
   available.
2. If neither is usable, install a user-scoped PHP 8.2+ runtime with GD using the
   available Windows package tooling.
3. Verify prerequisites:

   ```powershell
   php --version
   php -m | Select-String -Pattern '^gd$'
   ```

4. Run the existing standalone baselines before changing production code:

   ```powershell
   php imgcolor/tests/cli_calibration.php
   php imgcolor/tests/cli_init_signature.php
   php imgcolor/tests/cli_parity.php
   ```

Record any full-framework test prerequisites separately from standalone proof.

## Task 2: Enforce the Package Upload Contract

**Files:**

- Create: `imgcolor/tests/cli_setup_bucket.php`
- Modify: `imgcolor/Setup.class.php:127-134`
- Modify: `imgcolor/tests/Analyzer.class.php:20-26`
- Modify: `imgcolor/tests/Analyzer.class.php` extension-related coverage

1. Write a framework-stubbed CLI regression test that loads
   `imgcolor_Setup`, calls `install()`, captures the `createBucket()` arguments,
   and expects the extension string `jpg,jpeg,png`.
2. Run it and verify it fails because the current extension string is empty:

   ```powershell
   php imgcolor/tests/cli_setup_bucket.php
   ```

3. Add analyzer/file eligibility cases for `.jpg`, `.jpeg`, `.png`, uppercase,
   mixed case, unsupported extensions, and an allowed extension with invalid
   image bytes.
4. Change only the `imgcolorImages` bucket declaration to
   `jpg,jpeg,png`. Update the Analyzer test bucket to mirror production.
5. Re-run the new test and existing analyzer/standalone coverage.
6. Commit:

   ```text
   fix(imgcolor): restrict uploads to jpeg and png
   ```

## Task 3: Centralize Calibration Value Mapping

**Files:**

- Modify: `imgcolor/tests/cli_calibration.php`
- Modify: `imgcolor/tests/Analyzer.class.php`
- Modify: `imgcolor/Calibration.class.php:21-101`
- Modify: `imgcolor/Profiles.class.php:75-103`
- Modify: `imgcolor/Analyzer.class.php` only if needed to consume the shared helper

1. Add failing CLI cases for:
   - reading all eleven defaults from a stubbed `imgcolor_Setup`;
   - extracting only known calibration fields from an object/record;
   - retaining `null` for empty optional `fixedK`;
   - excluding unrelated form/profile properties.
2. Add matching framework tests for defaults and profile records.
3. Implement focused helpers in `imgcolor_Calibration` for package defaults and
   record-to-array extraction, reusing `self::$fields` as the field authority.
4. Replace duplicated default/profile assembly in Profiles and Analyzer where it
   can be done without changing public behavior.
5. Run calibration, analyzer, and profile tests.
6. Commit:

   ```text
   refactor(imgcolor): centralize calibration values
   ```

## Task 4: Preserve Exact Calibration in Analysis History

**Files:**

- Modify: `imgcolor/tests/Analyses.class.php:14-65`
- Modify: `imgcolor/Analyses.class.php:49-79`
- Modify: `imgcolor/Demo.class.php:224-252`

1. Add failing tests showing that `createFromResult()` and `persistResult()` save
   the exact eleven-value snapshot with and without a profile identifier.
2. Add a compatibility test for an existing record with no snapshot.
3. Add a nullable, non-input `calibrationJson` field to the Analyses model.
4. Extend the two persistence methods with a calibration-values argument, encode
   only authoritative calibration fields, and preserve their numeric/null values.
5. Ensure Fileman's quick action passes the global defaults while the interactive
   form will pass submitted values.
6. Re-run analysis rendering and persistence tests.
7. Commit:

   ```text
   feat(imgcolor): snapshot analysis calibration
   ```

## Task 5: Add Calibration and Preset Controls to the Analysis Form

**Files:**

- Modify: `imgcolor/Demo.class.php:56-101`
- Modify: `imgcolor/Profiles.class.php` only for small reusable profile-write
  validation helpers if the controller would otherwise duplicate model rules
- Modify: `imgcolor/tests/Profiles.class.php`
- Add or modify focused tests for any extracted action-independent logic

1. Add failing tests for reusable logic involved in:
   - form values populated from global defaults;
   - form values populated from a selected profile;
   - new profile records containing exactly the submitted calibration;
   - selected profile updates preserving name and notes;
   - invalid calibration preventing a write.
2. Add all eleven calibration fields to `act_Analyze`, grouped after the source
   fields and populated from `imgcolor_Calibration`.
3. Configure `profileId` for native silent refresh. On profile change, load the
   readable profile or restore global defaults while preserving the image field.
4. Give the form three explicit commands:
   - `analyze` requires an image and runs with submitted calibration;
   - `saveProfile` requires new system identifier/name and redirects to the new
     selected profile after a successful insert;
   - `updateProfile` requires an editable selected profile and updates only its
     eleven calibration fields.
5. Add a toolbar link to the existing Profiles list for rename, notes, and
   deletion. Hide or reject update when rights are insufficient.
6. Convert all calibration exceptions and profile/file failures into readable
   form errors without partial persistence.
7. Pass the submitted calibration values into result persistence.
8. Run all imgcolor standalone and available framework tests.
9. Commit:

   ```text
   feat(imgcolor): add calibration preset controls
   ```

## Task 6: Update Package Documentation

**Files:**

- Modify: `imgcolor/docs/integration.md`
- Modify: any package-local test instructions only where commands changed

1. Document the bucket's JPEG/PNG restriction, the interactive calibration
   workflow, preset management boundary, and history snapshot semantics.
2. Document the new standalone setup regression command.
3. Check the documentation against actual method signatures and UI behavior.
4. Run Markdown/diff checks and commit:

   ```text
   docs(imgcolor): document calibration presets
   ```

## Task 7: Full Verification and Manual Application Pass

1. Run every standalone imgcolor test:

   ```powershell
   Get-ChildItem imgcolor/tests/cli_*.php | ForEach-Object { php $_.FullName }
   ```

2. Run all available bgERP `imgcolor_tests_*` classes in the repository's unit
   runner or a configured local application.
3. Run syntax checks on every changed PHP file:

   ```powershell
   git diff --name-only origin/dev...HEAD -- '*.php' |
     ForEach-Object { php -l $_ }
   ```

4. Run:

   ```powershell
   git diff --check origin/dev...HEAD
   git status --short --branch
   ```

5. In a local bgERP application, manually verify:
   - upload chooser advertises JPEG/PNG only;
   - valid lowercase and uppercase JPEG/PNG uploads work;
   - unsupported extensions and disguised content fail clearly;
   - initial calibration values match package configuration;
   - profile selection refreshes values;
   - adjusted values affect analysis;
   - save/update preset actions work without requiring an image;
   - Profiles list supports rename/delete;
   - analysis history remains readable for old and new records.
6. Review the entire branch diff for scope, permissions, schema compatibility,
   escaping, localization, and accidental vendored changes.

## Task 8: Reconcile Upstream and Publish the Pull Request

1. Fetch and compare the branch against the latest upstream `dev`.
2. Rebase onto `origin/dev` if it moved, then repeat the full verification set.
3. Fork `bgerp/bgerp` to the authenticated account because upstream push access
   is unavailable; add or verify the fork remote.
4. Push `feat/imgcolor-calibration-presets` to the fork.
5. Open a ready-for-review pull request targeting `bgerp/bgerp:dev` with:
   - concise problem and solution summary;
   - scoped upload/calibration/history details;
   - exact automated tests and manual checks;
   - explicit limitations for any environment-dependent check not performed.
6. Verify the published PR base/head, changed files, commit list, body, and checks.
   Do not merge it.
