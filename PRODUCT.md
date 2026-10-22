# PenPoint - Product Context

## Vision
PenPoint is a smart, cashless, sensor-monitored IoT vending kiosk designed specifically for academic campuses. It provides students and proctors instant access to crucial academic essentials (pens, exam blue books, test pencils, erasers, correction tape, and standardized bond paper) in under 15 seconds, eliminating exam-day stationery panic.

## Target Audience & Usage Scene
- **Audience:** Senior high school and university students, examinees, faculty, and proctors.
- **Scene:** Campus hallways, testing centers, library lobbies, and computer laboratory corridors. Often accessed in a rush (5 minutes before a proctored exam) on mobile devices (`390 x 844` iPhone viewport) or via a mounted touch display.
- **Lighting & Ambiance:** High-contrast indoor hallway lighting, requires instant legibility, illuminated coil/slot feedback, and clear verification.

## Core Hardware & Architecture
- **Controller:** ESP32 Microcontroller running Wi-Fi / HTTP telemetry.
- **Actuators:** High-torque 360-degree continuous rotation servos driving mechanical helix coils.
- **Verification:** Dual-beam Infrared (IR) drop sensors located at the collection chute that trigger hardware confirmation when an item passes through the beam.
- **Payments:** Cashless QR payments (GCash QR, Maya QR, and NFC Student Pass).

## Aesthetic World
- **Direction:** Japanese Stationery Cyber-Kiosk.
- **Palette:** Deep Obsidian (`#0b0f19`), Electric Cobalt (`#2563eb`), Precision Cyan (`#06b6d4`), Amber Pulse (`#f59e0b`), and Emerald Verification (`#10b981`).
- **Typography:** Space Grotesk / Plus Jakarta Sans for headings, JetBrains Mono for telemetry, prices, and slot indicators.
