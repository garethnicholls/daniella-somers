# Homepage image slots

This change does not modify `responsive-gutters.css`, reset the homepage, or replace any saved media. PR #24 remains the responsive layout baseline.

After the PR is merged, open **Pages → Home → Edit**. Save any unfinished edits first. Use **Add missing image placeholders to Home** in the editor notice. The action creates a WordPress revision and only adds the missing native Image blocks. It is safe to run again; existing images are not replaced. New/empty homepages receive the same blocks when initially created. A reusable **Qualifications: image placeholders** pattern is also available in the block inserter.

The qualifications media remains in the first column under the membership text, with the existing qualification cards in the second column. On mobile the established responsive layout stacks these naturally. The BACP slot should receive the genuine registered-member artwork supplied by the practice. Do not use the generated mockup as an official accreditation logo. The room slot should receive the original room photo. No certificate image or qualification is fabricated.

Select an empty Image block, choose **Media Library** or **Upload**, then use **Replace** for later changes. The real media attachment is managed by WordPress. Image classes are retained on replacement. The BACP logo uses contain sizing; the room uses cover sizing with a centred focal point. For a different crop, use the native image focal point/crop controls. The separate `home-media.css` only styles the new slots and does not alter existing breakpoints or gutters.

Existing custom HTML, existing image URLs and media IDs are preserved. The update does not run automatically on production and does not overwrite later editor changes. If an existing placeholder contains an image, it is left untouched rather than guessing which media should be used. Review the resulting page before publishing additional changes.
