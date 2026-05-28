# Versioning

Lettr PHP SDK follows [Semantic Versioning](https://semver.org/) (SemVer).

## Version Format: `MAJOR.MINOR.PATCH`

| Component | When to increment                           | Example            |
| :-------- | :------------------------------------------ | :----------------- |
| MAJOR     | Breaking changes (incompatible API changes) | `1.0.0` -> `2.0.0` |
| MINOR     | New features (backward compatible)          | `1.0.0` -> `1.1.0` |
| PATCH     | Bug fixes (backward compatible)             | `1.0.0` -> `1.0.1` |

## Pre-1.0 Versioning (Historical, through `0.1.x`)

While the SDK was in `0.x.x`:

- **Minor version bumps may contain breaking changes** (`0.1.0` -> `0.2.0`)
- Patch versions are always backward compatible (`0.1.0` -> `0.1.1`)
- This allowed API refinement based on real-world usage

The SDK is now post-1.0 and follows strict SemVer — breaking changes only land in major releases.

## Release Workflow

### 1. Update CHANGELOG

Add a new entry to `CHANGELOG.md` following [Keep a Changelog](https://keepachangelog.com/) format:

```markdown
## [2.3.0] - 2026-07-01

### Added
- New feature description

### Changed
- Changed behavior description

### Fixed
- Bug fix description

### Removed
- Removed feature description
```

**Changelog categories:**

| Category      | Description                                      |
| :------------ | :----------------------------------------------- |
| Added         | New features                                     |
| Changed       | Changes in existing functionality                |
| Deprecated    | Soon-to-be removed features                      |
| Removed       | Removed features                                 |
| Fixed         | Bug fixes                                        |
| Security      | Vulnerability fixes                              |

**Tips:**
- Write entries from user perspective, not developer perspective
- Link to related issues/PRs when relevant
- Keep descriptions concise but informative

### 2. Update Version Constant

```php
// src/Lettr.php
public const VERSION = '2.3.0';
```

### 3. Create Git Tag and Push

```bash
git tag -a v2.3.0 -m "Release 2.3.0"
git push origin main --tags
```

GitHub Actions will automatically:

- Run tests, linting, and static analysis
- Create GitHub Release (if all checks pass)
- Packagist updates via webhook

## Version History

See [CHANGELOG.md](CHANGELOG.md) for the full notes; the table below lists each released minor/major and what it added.

| Version | Type     | Highlights                                                                                     |
| :------ | :------- | :--------------------------------------------------------------------------------------------- |
| `2.2.0` | Minor    | Campaigns module (`$lettr->campaigns()` — list, get, events, send, schedule, unschedule)       |
| `2.1.0` | Minor    | Audience module (lists, contacts, topics, properties, segments)                                |
| `2.0.0` | Major    | Domain DTO cleanup, email list & events endpoints, `EmailService::sendTemplate()` reorder       |
| `1.3.0` | Minor    | Custom email headers (`CustomHeaders` DTO, `EmailBuilder::headers()`/`addHeader()`)            |
| `0.1.x` | Initial  | First releases (`0.1.0` – `0.1.3`)                                                              |

## Composer Installation

```bash
# Always installs latest stable
composer require lettr/lettr-php

# Specific version
composer require lettr/lettr-php:^2.0
```
