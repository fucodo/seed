# fucodo/seed

A tiny seeding helper for Neos Flow applications. It provides a CLI command to initialize a database with a set of SQL files and optional follow‑up commands, orchestrated via Flow settings. This is intended for first‑time bootstrap of an environment (e.g. CI, demo or developer machines).

## Features
- Declarative jobs configured in Settings.yaml
- Import ordered SQL files (by numeric keys)
- Optional follow‑up shell/Flow commands after import
- Safe‑guard: skips when your database already contains `flow_doctrine_migrationstatus`

## Requirements
- PHP compatible with your Neos/Flow version
- Neos Flow (package type `neos-package`)

## Installation
Add the package via Composer (as part of your Flow distribution):

```bash
composer require fucodo/seed
```

The package key is `fucodo.seed`.

## Configuration
Define one or more seeding jobs under `fucodo.seed.jobs`. Each job can
- be enabled/disabled
- list SQL files to import (in order)
- optionally list commands to execute after SQL import

Example minimal configuration (place into `Configuration/Settings.fucodo.seed.yaml` in your distribution or in a package):

```yaml
fucodo:
  seed:
    jobs:
      default:
        enabled: true
        databaseImports:
          100:
            file: 'resource://fucodo.seed/Seed/000-examplefile.sql'
```

Example from a real package using resource URIs and a follow‑up command:

```yaml
fucodo:
  seed:
    jobs:
      default:
        enabled: true
        databaseImports:
          100:
            file: 'resource://SBS.SingleSignOn/Seed/100-basic-structure.sql'
```

Notes:
- Keys (100, 101, 500, …) control the execution order; only their ordering matters.
- A `databaseImports.NNN.enabled: false` flag can be set to skip an entry temporarily.
- Files can be absolute paths or Flow resource URIs (`resource://Vendor.Package/Path/To/File.sql`).
- Files can also be urls, if file_get_contents() is allowed to make http requests.
- If a target database already has the table `flow_doctrine_migrationstatus`, the seeding job will abort early and not import again.

## Usage
The package registers a CLI command in Flow. From your distribution root, run:

```bash
./flow seed:data
```

To run a specific job (defaults to `default`):

```bash
./flow seed:data --job yourJobName
# or positional in some shells / Flow versions
./flow seed:data yourJobName
```

Exit codes:
- 0: success (or skipped because database looked already initialized)
- 1: job disabled (or other StopCommand condition)

During execution you will see which files are imported. Empty files are reported and skipped.

## How it works
Internally, `SeedCommandController`:
- Creates a Doctrine DBAL connection from `Neos.Flow.persistence.backendOptions`.
- Checks for the presence of the `flow_doctrine_migrationstatus` table.
- Iterates your configured `databaseImports` and runs each file with `executeStatement`.
- Prints a short progress line per step.

## Best practices
- Place seed SQL files in your package under `Resources/Seed/` and refer to them using the Flow `resource://` scheme.
- Keep imports idempotent where possible; the safety check prevents re‑seeding but it is still good to make scripts re‑runnable.
- Use small, ordered files focused on a single concern (schema bootstrap, minimal data, fixtures, …).

## Development status
This package is intentionally small. PRs improving documentation and robustness are welcome.

## License
MIT (or the same as your project distribution).
