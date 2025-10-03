# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2025-10-03

Initial public release.

### Added
- Multi-tenant document archive with roles: SuperAdmin, Admin, Manager, User.
- Admin Settings: branding, storage, security, notifications.
- System tools: DB optimize, backup, clear cache; secure backup downloads.
- Reports: KPIs, activity, top companies; CSV/Excel export endpoint.
- Logs UI: activity/error/security tabs, filters, pagination, tail views; secure log download/clear.
- Archival Export engine: ZIP with manifest.xml; per-document metadata (optional).
- PDF/A support: Ghostscript-based conversion (Windows compatible, ICC autodetect); toggle on upload.
- PDF/A validation: veraPDF integration (optional) with downloadable report.
- Technical Dossier page and Dosar Tehnic checklist; samples and ANR email draft.
- Secure handlers for backups/exports/logs with strict path validation.
- SHA-256 hashing, robust MIME detection, and improved upload pipeline.
- Repository readiness: .gitignore, README, Git push and Hostico deploy guides.

### Notes
- Ghostscript/veraPDF are optional and configurable in SuperAdmin → Sistem.
- On shared hosting, keep conversion/validation disabled if binaries are unavailable.

[1.0.0]: https://github.com/nnoldi-hub/DocArhiv/releases/tag/v1.0.0