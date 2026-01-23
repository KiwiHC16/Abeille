# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Abeille is a Jeedom plugin for controlling ZigBee devices via ZiGate and EZSP (Silicon Labs) gateways. It supports up to 6 simultaneous gateways, 418+ device types, and uses a multi-process daemon architecture with message queues for inter-process communication.

## Common Commands

### Development Tools

```bash
# Validate all device/command JSON files
php .tools/check_json.php

# Update and normalize JSON files (auto-fix formatting)
php .tools/update_json.php

# Generate device compatibility list
php .tools/gen_devices_list.php

# Install git hooks for version management
.hooks/install_hooks.sh
```

### Release Workflow

```bash
# Create beta branch from master
.tools/create_branch.sh

# Update MD5 checksums
.tools/update_md5.sh

# Update changelog from git history
.tools/update_changelog.sh

# Push with hooks (updates version file)
.tools/git_push.sh
```

### Quality Analysis

SonarCloud analysis runs automatically on master, sonar, dev branches and PRs via GitHub Actions.

## Architecture

### Daemon System

The plugin runs multiple PHP/Python daemons that communicate via System V message queues:

```
AbeilleMainD.php      → Main daemon: equipment creation, configuration
AbeilleCmd.php        → Command daemon: sends commands to gateway
AbeilleParser.php     → Parser daemon: processes ZigBee responses
AbeilleSerialRead.py  → Serial daemon: low-level port communication
AbeilleEzspD.py       → EZSP daemon: Silicon Labs protocol support
```

**Message Queues** (defined in `core/config/Abeille.config.php`):
- `xToCmd` - Commands to send
- `xToParser` - Messages to parser
- `xToAbeille` - Messages to main daemon
- `parserToLQI` - Link quality updates
- `parserToRoutes` - Route table data

### Core Classes

| Class | Purpose |
|-------|---------|
| `Abeille` | Main plugin class extending Jeedom's `eqLogic`, handles device state and cron jobs |
| `AbeilleCmd` | Command class extending Jeedom's `cmd`, template field substitution (#addrIEEE#, #slider#, etc.) |
| `AbeilleCmdPrepare` | Command preparation with query parameter parsing |
| `AbeilleCmdProcess` | ZCL header generation, frame formatting, cluster processing (253KB) |
| `AbeilleCmdQueue` | Queue priority management (MAX=1, NORMAL=2, MIN=3) |
| `AbeilleParser` | Response parsing, device discovery, attribute extraction (293KB) |
| `AbeilleTools` | Utilities: hex conversion, JSON handling, logging |

### Device/Command System

Device configurations live in `core/config/devices/` as JSON files. Each device specifies:
- Manufacturer, model, timeout
- Main endpoint and icon
- Command mappings to cluster/attribute definitions

Commands in `core/config/commands/` define:
- Cluster/attribute bindings
- Action types (set, get, writeAttribute)
- Polling configuration
- Parameter templates

**Logical ID Format**: `Abeille{gtwId}/{nodeAddr}` (e.g., `Abeille1/A4C1`)

### Entry Points

- **Web UI**: `desktop/php/Abeille-Eq.php` (equipment), `desktop/modal/*.modal.php` (dialogs)
- **Plugin Config**: `plugin_info/configuration.php` (83KB, 50+ options)
- **Installation**: `plugin_info/install.php` (database migrations, daemon setup)

### Cron Jobs

The plugin uses Jeedom's cron system:
- `cronDaily()` - Daily maintenance
- `cronHourly()` - Hourly tasks
- `cronEvery15min()` - 15-minute polling
- Command-level polling via "Polling" flag with period

## Key Patterns

### Template Variables

Commands use substitution variables:
- `#addrIEEE#`, `#IEEE#` - Device IEEE address
- `#ZiGateIEEE#` - Gateway address
- `#slider#`, `#select#` - UI input values
- Processed in `AbeilleCmd::updateField()`

### Multi-Gateway Support

Gateway ID (1-6) prefixes all equipment logical IDs. Each gateway has independent queues and daemon instances.

### Debug Mode

Create `debug.json` in plugin root to enable:
- PHP error logging to `AbeillePHP.log`
- Git branch switching
- Developer features

## Code Style

Follow `.editorconfig`: 4-space indents, UTF-8, LF line endings, PSR-2 for PHP.

## External Resources

- Documentation: https://kiwihc16.github.io/AbeilleDoc/
- Support Forum: https://community.jeedom.com/tag/plugin-abeille
- Technical notes: `docs/dev/` (ZigBee clusters, EZSP protocol, Tuya support)
