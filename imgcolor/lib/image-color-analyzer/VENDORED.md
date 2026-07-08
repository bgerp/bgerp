# Vendored library - image-color-analyzer

- **Spec package:** `teodor-todorov1/image-color-analyzer` (MIT)
- **Composer metadata at vendoring time:** `acme/image-color-analyzer`
- **Vendored from:** `php_demo/src`
- **Vendored commit:** `17370ac8d2fa48edab9361dbbc5a2e308c167af2`
- **Vendored on:** 2026-07-08
- **PSR-4 root:** `src/` maps namespace prefix `ImageColorAnalyzer\`

## Local patch

The following files contain BGERP review patches:

- `src/ImageLoader/GdImageLoader.php` and
  `src/ImageLoader/ImagickImageLoader.php` check image dimensions from metadata
  before decoding image pixels.
- `src/WhiteBackgroundCropper/WhiteBackgroundCropper.php` keeps raw content
  extents in the crop box so thin marks are not erased by the line-noise guard.
- `src/Options/*Options.php` validate option ranges.
- `src/ImageLoader/FileImageSource.php` loops over short stream writes.
- `src/Contracts/EncodedImage.php` preserves destination file permissions on
  atomic overwrite.

`composer.json` is retained as source/license/autoload metadata. Its upstream
development scripts require files that are not vendored here; use
`../../tests/cli_parity.php` and the BGERP unit test instead.

To update: replace `src/` with the new upstream `src/`, reapply or drop local
patches if upstream contains equivalent fixes, bump the commit/tag above, and
re-run `tests/cli_parity.php` plus the `imgcolor_tests_Analyzer` web test.
