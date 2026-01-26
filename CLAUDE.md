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

| Queue | ID | Max Size | Purpose |
|-------|-----|----------|---------|
| `xToCmd` | 0xAB04BC | 1024 | Commands to AbeilleCmd daemon |
| `xToParser` | 0xAB0336 | 2048 | Messages to parser daemon |
| `xToAbeille` | 0xAB026D | 1024 | Messages to main daemon (AbeilleMainD) |
| `parserToLQI` | 0xAB00E1 | 2048 | Link quality indicator updates |
| `parserToRoutes` | 0xAB00E2 | 2048 | Route table data |
| `parserToCmdAck` | 0xAB03E7 | 512 | ACK messages (8000/8011/8012) from parser to cmd |
| `parserToAssist` | 0xAB008D | 512 | Data to equipment assistant |
| `parserToCli` | 0xAB02D4 | 1024 | Parser to CLI output |
| `xToMon` | 0xAB0081 | 1024 | Messages to monitor |

**Priority System**:
```php
define('priorityMax', 1);           // PRIO_HIGH - User actions (real-time)
define('priorityInterrogation', 2); // PRIO_NORM - State queries
define('priorityMin', 3);           // PRIO_LOW  - Background tasks
```

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

### Message Queue Architecture

```
┌─────────────────┐
│  Jeedom Core    │  msgToCmd() / msgToParser()
│  (PHP/AJAX)     │
└────────┬────────┘
         │
         ↓
┌─────────────────────────────────────────────────────────────┐
│                 SYSTEM V IPC QUEUES                         │
├─────────────────────────────────────────────────────────────┤
│  xToCmd ──────────────────────► AbeilleCmd.php              │
│                                 (command daemon)            │
│                                       │                     │
│  xToParser ───────────────────► AbeilleParser.php           │
│       ↑                         (parser daemon)             │
│       │                               │                     │
│  SerialRead ◄────────────────── ZiGate/EZSP                 │
│  (serial port daemon)           (hardware)                  │
│                                       │                     │
│  parserToCmdAck ◄────────────────────┘                      │
│  (8000/8011/8012 ACKs)                                      │
│                                                             │
│  xToAbeille ──────────────────► AbeilleMainD.php            │
│                                 (main daemon)               │
│                                                             │
│  parserToLQI ─────────────────► AbeilleLQI.php              │
│  parserToRoutes ──────────────► AbeilleRoutes.php           │
└─────────────────────────────────────────────────────────────┘
```

**Sending messages**:
```php
// Send to main daemon
$msg = array('topic' => $topic, 'payload' => $payload);
msg_send($queueXToAbeille, 1, json_encode($msg), false, false);

// Send to command daemon with priority
$msg = array('priority' => $priority, 'topic' => $topic, 'payload' => $payload);
msg_send($queueXToCmd, 1, json_encode($msg), false, false);
```

**Receiving messages** (non-blocking):
```php
msg_receive($queue, 0, $msgType, $maxSize, $msgJson, false, MSG_IPC_NOWAIT, $errCode);
// Error 7 = Message too big, Error 42 = No message
```

**Command Queue with ACK management** (in `AbeilleCmdQueue.class.php`):
- Each ZiGate has priority queues: `$GLOBALS['zigates'][$zgId]['cmdQueue'][$priority]`
- Commands track: status, retries, timeout, sqn/sqnAps for ACK correlation
- Throughput regulation: 100ms delay between commands, NPDU limit check
- NO-ACK devices automatically moved to low priority queue

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
