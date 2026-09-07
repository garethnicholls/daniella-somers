# WordPress media recovery

## Confirmed incident
Railway production logs on 7 September 2026 show HTTP 404 for the original room image, BACP artwork, portrait and their generated thumbnails under `/wp-content/uploads/2026/09/`. The homepage itself and theme styles return 200. This is a missing-file problem, not an image aspect-ratio problem. The current WordPress service configuration does not report a persistent volume mount. The application stores uploads under the container document root while the database retains attachment records across deployments. A replacement container therefore cannot be relied upon to retain those files.

## Safe recovery sequence
1. Do not delete Media Library records, reset the homepage, regenerate thumbnails indiscriminately, or overwrite the existing database. Preserve the existing deployment and obtain a database backup before any recovery.
2. Check whether the original uploads remain on a previous deployment, backup, or another persistent storage location. Restore the original bytes and directory paths if available. A database record alone cannot reconstruct an absent image.
3. Provision persistent storage in Railway and mount it at `/var/www/html/wp-content/uploads`. Use the Railway volume UI or a supported volume-management tool. Do not mount a new empty volume over an unexamined directory containing the only remaining copy of uploads. Take a backup first. The application startup script will create the uploads directory and verify that it is writable; it deliberately does not create or attach a volume.
4. Restore recoverable originals into the persistent uploads directory. Keep existing attachment IDs, file names and metadata. For the two images supplied in the conversation, the genuine source files can be re-uploaded if the originals are unrecoverable. Other missing images require their own originals or a verified backup; do not fabricate replacements.
5. Once originals are present, use WordPress's normal image-processing tools to regenerate only missing derivative sizes. Verify the original URL and each generated size return an image response. Do not change attachment URLs to a different host to mask missing files.
6. Check WordPress Address and Site Address use the canonical HTTPS domain. Review existing attachment URLs for old-host references, and perform a backup-backed, serialized-aware URL migration only if needed.
7. In Pages > Home, verify that the saved Image blocks reference the intended Media Library attachments. Use native Replace controls to repair any genuinely stale references. Do not replace the entire page or run automatic content migrations.
8. Test the same saved image on mobile and desktop, then restart/redeploy and verify the same URLs still load. Confirm that a fresh upload survives a second deployment before declaring the storage issue resolved.

## Operational checks
- The WordPress service must have persistent uploads storage; a persistent MySQL database is not sufficient.
- Back up both the database and uploads. A database-only backup cannot restore image bytes.
- Keep one shared set of Gutenberg blocks and one responsive layout. Do not introduce device-specific image uploads or CSS to compensate for missing files.
- The existing contact form, SMTP configuration, page content, and approved visual design are outside this recovery and must remain intact.
