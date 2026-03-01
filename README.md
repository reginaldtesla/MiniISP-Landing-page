# TesNet - Reliable Internet Solutions

TesNet is a custom-built network infrastructure and billing system designed to manage and monetize internet access in hostel environments. This project serves as a student-led initiative to provide affordable, reliable connectivity in the Ayeduase and KNUST area of Kumasi, Ghana.

**Project Lead:** [Darko Kwaku Agyemang]

## 🚀 System Overview

The system utilizes Edge Intelligence and the MikroTik API to automate student authentication and payment processing via Paystack. It bridges the digital divide by offering a seamless, automated "pay-as-you-go" internet experience.

## 🏗️ System Architecture

The network operates on a **"Three-Tier"** logic:

- **Gatekeeper (MikroTik hAP ac lite):** Manages physical connections, DHCP, and the Hotspot firewall.
- **Brain (Ubuntu Server on HP ProBook):** Hosts the PHPNuxBill dashboard and MySQL database.
- **Bridge (The Handshake):** Configured to bypass the Hotspot for the server, allowing constant, uninterrupted communication with the RouterOS API.

## 🛠️ Technical Stack

### Hardware

- **Router:** MikroTik hAP ac lite
- **Server:** HP ProBook (Running Ubuntu 24.04 LTS)
- **Backhaul:** MTN TurboNet (4G LTE)

### Software & Core Stack

- **Billing Engine:** PHPNuxBill (PHP/MySQL)
- **Integration:** MikroTik RouterOS API
- **HTML5**: Semantic structure.
- **Tailwind CSS**: Utility-first styling via CDN.

## 🌐 Network Map

| Device          | Interface    | IP Address   | Role               |
| :-------------- | :----------- | :----------- | :----------------- |
| MTN TurboNet    | ether1 (WAN) | Dynamic      | Internet Source    |
| MikroTik Router | bridge-local | 192.168.88.1 | Gateway & Hotspot  |
| Ubuntu ProBook  | ether2 (LAN) | 192.168.88.2 | Billing & Database |
| Access Point    | ether3 (LAN) | DHCP Pool    | Student Connection |

## 🔧 Installation & Setup

### 1. Server Configuration (Netplan)

The Ubuntu server requires a static IP to maintain a persistent API connection with the MikroTik router:

```yaml
network:
  version: 2
  renderer: networkd
  ethernets:
    enp1s0:
      addresses: [192.168.88.2/24]
      routes: [{ to: default, via: 192.168.88.1 }]
      nameservers: { addresses: [8.8.8.8, 8.8.4.4] }
```

### 2. MikroTik Integration

The router uses a custom `login.html` redirect located in `flash/hotspot/` to push unauthorized users to the PHPNuxBill portal.

## ✨ Key Features

- **Walled Garden:** Allows students to access the payment portal and landing page without an active data plan.
- **API Automation:** PHPNuxBill automatically creates and removes users in RouterOS upon voucher activation.
- **4G Optimization:** Custom Mangle rules are applied to adjust MSS, ensuring stable performance on 4G LTE backhaul.
- **Optimized Pricing Table:** Streamlined data packages (600MB to 60GB) with "POPULAR" and "BEST VALUE" badges.


## 📝 License & Credits

This is a **Student Initiative Project**.
© 2026 TesNet. All rights reserved.
Built with ❤️ in Kumasi.
