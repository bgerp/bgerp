# Imgcolor Calibration Presets and Upload Restrictions

**Date:** 2026-07-13

**Target branch:** `dev`

**Feature branch:** `feat/imgcolor-calibration-presets`

## Purpose

The `imgcolor` package already contains the calibration domain model, saved
profiles, analysis history, and a working analysis screen. This change makes
those foundations usable from the analysis workflow and restricts the package's
upload bucket to the two image formats that the analyzer actually supports.

The implementation must remain package-scoped. It must not change Fileman's
global upload behavior or edit the vendored image-color library.

## Current State

`imgcolor_Setup` creates the `imgcolorImages` bucket without an extension
restriction. Fileman therefore accepts arbitrary file extensions even though
`imgcolor_Analyzer` and the vendored loader support only PNG and JPEG data.

`imgcolor_Profiles` already stores all eleven calibration values and supplies
standard bgERP list, add, edit, and delete actions. `imgcolor_Demo` lets a user
select a profile, but it does not expose the selected calibration values or let
the user adjust and save them in the analysis workflow. Analysis history stores
only the selected profile identifier, so it cannot accurately describe an
analysis that uses adjusted values or a profile that is edited later.

## Upload Contract

The `imgcolorImages` bucket will be created with the extension list
`jpg,jpeg,png`.

This single package-level declaration supplies all three required layers:

1. Fileman's upload controls derive an `accept` value containing JPEG and PNG
   MIME types and their extensions.
2. Fileman's server-side bucket validation rejects other extensions for normal,
   drag-and-drop, URL, and other bucket upload paths.
3. The analyzer retains its independent content check. Its vendored image source
   sniffs the bytes and rejects unsupported or misleading content even when the
   filename has an allowed extension.

Fileman's extension comparison is case-insensitive, so `.JPG`, `.JPEG`, `.PNG`,
and mixed-case forms remain valid. Existing files are not rewritten or deleted.
Other buckets and callers that provide their own `allowedExtensions` remain
unchanged.

## Analysis Form

The existing `imgcolor_Demo::act_Analyze` form remains the single analysis
workspace. It will contain these groups:

- Source: image file and optional saved profile.
- Calibration: the eleven fields already represented by `imgcolor_Profiles`.
- Preset creation: a system identifier and display name used only by the
  "Save as profile" action.

On the initial request, calibration fields show the current package defaults.
Selecting a saved profile causes a native silent form refresh and fills the
calibration fields from that profile. Clearing the selection restores the
package defaults. After loading either source, the user may adjust any value
before analysis.

The primary "Analyze" action validates the file and the displayed calibration
values, constructs options through `imgcolor_Calibration`, runs the analyzer,
and renders the existing result view. The analyzer therefore receives exactly
the values visible to the user rather than re-reading a profile behind the
form's back.

The image is required for analysis but not for saving a profile. Action-specific
validation will enforce this distinction so a user can create a preset without
uploading an image.

## Preset Actions

"Save as profile" validates the displayed calibration settings plus the new
profile's system identifier and display name, then creates an
`imgcolor_Profiles` record. Duplicate or invalid identifiers are reported on the
form. After success, the analysis screen reloads with the new profile selected.

When an editable profile is selected, "Update profile" writes the displayed
calibration values back to that record. It does not silently rename the profile
or replace its notes.

The existing Profiles manager remains the canonical interface for listing,
renaming, editing notes, and deleting presets. The analysis form will provide a
link to that manager instead of duplicating its record-management UI.

The design does not add profile ownership, personal profiles, or a default
profile flag. The current model defines shared profiles governed by existing
`imgcolor`, `ceo`, and `admin` rights, and this change preserves that contract.

## Shared Calibration Mapping

Calibration values must not be assembled independently in each controller.
`imgcolor_Calibration` will provide small mapping helpers for:

- reading current defaults from `imgcolor_Setup`;
- extracting the eleven calibration values from a profile or form record; and
- retaining the existing conversion and range validation performed by
  `buildOptions()`.

These helpers establish one authoritative field set without coupling the
vendored library to bgERP. The vendored sources remain untouched.

## History Accuracy

`imgcolor_Analyses` will gain a non-input calibration snapshot field. Every new
analysis stores the exact eleven submitted values as JSON alongside the optional
`profileId`.

The two fields have distinct meanings:

- `profileId` records which saved preset was loaded, when applicable.
- The snapshot records what the analyzer actually used.

This distinction keeps history accurate when a user adjusts a loaded preset
without updating it or when the saved profile changes later. Existing history
records without a snapshot remain valid and continue to render.

## Authorization and Error Handling

All profile reads and writes use the existing manager rights. Profile choices
are limited to readable records. Update is offered and accepted only when the
current user has edit rights for the selected record.

The form will return field-level or action-level feedback for:

- missing source image on Analyze;
- unsupported filename extension;
- missing, deleted, or inaccessible profiles;
- duplicate or incomplete new-profile identifiers and names;
- calibration values outside the library's accepted ranges; and
- image content that cannot be decoded or is not JPEG/PNG.

Errors do not create an analysis record or partially save a profile. Successful
profile mutations redirect after the write to prevent accidental resubmission.

## Compatibility

No existing route is removed. `imgcolor_Demo::act_AnalyzeColors`, the Fileman
single-file action, continues using package defaults because it has no profile
form. Existing profile records require no conversion. The analysis snapshot is
additive and nullable for prior rows.

The package's Bulgarian UI convention will be preserved for user-facing labels
and messages. Implementation identifiers and commit messages remain concise
English, consistent with the existing `imgcolor` history.

## Verification

Implementation will be test-driven where the current test harness permits. The
verification set will cover:

- the exact bucket extension declaration;
- accepted lowercase, uppercase, and mixed-case JPEG/PNG extensions;
- rejection of unsupported extensions and misleading image content;
- default-to-form and profile-to-form calibration mapping;
- calibration range validation;
- profile creation and selected-profile update behavior;
- analysis using submitted values rather than re-reading stored values;
- exact calibration snapshot persistence with and without a selected profile;
- compatibility with existing analysis records; and
- the existing analyzer, profile, analysis, and standalone CLI tests.

A manual application pass will verify the upload chooser, profile refresh,
adjust/analyze flow, save/update actions, profile-management link, and readable
error states. If a complete local bgERP instance cannot be started, the final PR
will distinguish executable automated proof from environment-dependent manual
checks rather than claiming unperformed validation.

## Out of Scope

- Changes to Fileman's global MIME or extension tables.
- New image formats or image conversion.
- Changes inside `imgcolor/lib`.
- Per-user preset ownership or sharing rules.
- A new default-preset domain field.
- Redesigning the existing analysis result presentation.
