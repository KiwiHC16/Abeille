# Zigate V2 firmware change log

## v3.24 (feb, 2026)

New Features

Network Recovery

- Add Network Recovery commands for ZiGate+ backup/restore functionality
    - 0x0600 - Extract network state (72 bytes)
    - 0x8600 - Extract response
    - 0x0601 - Restore network state
    - 0x8601 - Restore response
    - 0x0602 - Extract extended (with device table, up to 64 devices)
    - 0x8602 - Extract extended response
    - 0x0603 - Restore extended (with device table)
    - 0x8603 - Restore extended response

Other

- Add E_SL_MSG_NWK_STATUS_INDICATION (0x8703) for network status notifications

SDK Update

- Update NXP SDK from 2.6.10 to 2.6.14 (April 2024)
    - PDM mechanism enhancements and improved stability
    - OTA mechanism improvements
    - Greater Zigbee standard compliance
    - New PDM encryption support (AES-128-CTR)

## v3.A0 (feb, 2023)

- Increase PDM capacity
- Add more complete devices List
    - Add new structure (to enhance devices list)
- Add new API
    - Add Binding Table API (0x0052 command)
    - Add Routing Table API (0x0053 command)
    - Add Get Network key API (0x0054 command)
- Force route and lqi request to be more responsive
- Fix datatype for reportable change
- Fix zone enroll bug
- Fix Extended debug
- Backtrack with previous functions (v3.22) (about API and automatic repair - no longer useful)
- Update new SDK 2.6.10
    - Improve PDM mechanism, stability and fix PDM errors
    - Improve OTA mechanism and support
    - Improve Zigbee compliant

## v3.22 (apr, 2022)

- Add child table size function. Command 0x0052
- Add delete PDM address map table function. Command 0x0051
- Add automatic repair when 0x87 messages occurs. (This automatism delete adress map table and reset the ZiGate)
- Enhance group capacity for ZiGate 5 to 16
- Fix inconsistent datas with 0x8002 messages due to bad default response with ZDP packet
- Fix 0x8b warning messages. increase the BroadcastTransactionTableSize from 9 to 18
- Update new SDK 2.6.5
    - New error messages due to security fails
