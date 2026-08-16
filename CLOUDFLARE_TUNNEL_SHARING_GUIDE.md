# CITAS Smart Archive - Cloudflare Tunnel Team Sharing Guide

This guide helps a team member run the local **Ollama** server on their computer (e.g., a more powerful laptop with a better GPU) and tunnel it to the website at **`ollama.citas-smart-archive.com`**.

Because the tunnel is already fully registered and configured in Cloudflare, **the team member does not need to log in, create a tunnel, or configure DNS.** They only need to download `cloudflared`, copy your tunnel files, and run it.

---

## 📋 What the Host (You / Aki) Needs to Share

Share these three files with your team member (e.g., via Discord, Google Drive, or Git):

1. **`0b9301e0-4d0e-41da-b30e-068785801bed.json`**
   * Found on your PC at: `C:\Users\aki\.cloudflared\0b9301e0-4d0e-41da-b30e-068785801bed.json`
   * *This is your tunnel's secure key. Keep it safe!*
2. **`config.yml`**
   * Put this in the same folder as the `.json` file.
3. **`cloudflared-windows-amd64.exe`**
   * (Or send them the download link below so they can download it directly).

---

## 🛠️ Team Member Setup (On the More Powerful Laptop)

Follow these exact steps to host the AI:

### Step 1: Install Ollama & Download Models
1. Download and install **Ollama** from: [https://ollama.com](https://ollama.com)
2. Open your command line (Command Prompt or PowerShell) and pull the required model:
   ```cmd
   ollama pull qwen2.5:3b
   ```
   *(Or whichever model your team's project is currently using)*
3. Keep Ollama running in the background.

### Step 2: Download Cloudflared
1. Download `cloudflared-windows-amd64.exe` from the official Cloudflare repository:
   * [Cloudflared Releases GitHub](https://github.com/cloudflare/cloudflared/releases)
2. Create a folder named `C:\CITAS-Tunnel` (or any folder you prefer).
3. Put the downloaded `cloudflared-windows-amd64.exe` inside this folder.

### Step 3: Add the Configuration Files
Inside that same folder (`C:\CITAS-Tunnel`), place the two files shared by Aki:
1. **`0b9301e0-4d0e-41da-b30e-068785801bed.json`**
2. **`config.yml`**

#### 💡 The Plug-and-Play `config.yml` Configuration:
To prevent having to edit folder paths, your `config.yml` should look like this (using a relative path):

```yaml
tunnel: ollama
credentials-file: 0b9301e0-4d0e-41da-b30e-068785801bed.json

ingress:
  - hostname: ollama.citas-smart-archive.com
    service: http://localhost:11434
    originRequest:
      httpHostHeader: localhost:11434
  - service: http_status:404
```

*Since `credentials-file` points directly to the JSON file in the same folder, this works out-of-the-box on **any** computer without editing username directories!*

---

## 🚀 How to Run the Tunnel

> [!IMPORTANT]
> **Rule of the Tunnel:** Only **ONE** person can run the tunnel at any given time.
> * **Aki** must close his command prompt window running the tunnel before the **Team Member** runs ours.
> * If both run it at the same time, Cloudflare will split traffic randomly between the two laptops, causing conversion issues.

### Running instructions for the Team Member:
1. Make sure **Ollama** is running on your system.
2. Open **Command Prompt** (cmd) as Administrator.
3. Move to your tunnel folder:
   ```cmd
   cd C:\CITAS-Tunnel
   ```
4. Run the tunnel:
   ```cmd
   .\cloudflared-windows-amd64.exe tunnel --config config.yml run ollama
   ```
5. **Keep this Command Prompt window open!** As long as it is open, the website's AI features will run on your powerful laptop GPU instead of Aki's.

---

## 🔍 How to Test if it's Working

Open a browser and go to:
👉 **`https://ollama.citas-smart-archive.com/api/tags`**

* If it returns a list of your models in JSON format, **congratulations!** The website is successfully using your powerful laptop for its AI tasks.
* If it shows a Cloudflare error, make sure your Ollama is running and that your command prompt is still active.

---

## 🌟 Superuser Dashboard Access (For Team Members)

Since Aki has added you as a **Superuser** on the Cloudflare Dashboard, you have administrative access to monitor and manage the tunnel! Here is how to use this access:

### 1. Monitor Tunnel Status & Health
Instead of guessing whether the website is successfully connected to your laptop, you can check the live status:
1. Go to the [Cloudflare Zero Trust Dashboard](https://one.dash.cloudflare.com/).
2. In the left sidebar, navigate to **Networks** ➡️ **Tunnels**.
3. You will see the tunnel named **`ollama`**.
4. Check the status:
   * **🟢 HEALTHY**: The tunnel is active and running on your laptop.
   * **🔴 DOWN**: The tunnel is offline (either you haven't run the batch file, or there is a connection issue).

### 2. View Real-Time Connector Logs
If you are getting connection errors or timeouts:
1. Click the **three dots (...)** next to the `ollama` tunnel and select **Configure**.
2. Go to the **Logs** tab.
3. You can see real-time connections, active requests, and any network drops directly from Cloudflare's servers!

### 3. DNS Verification
Since you are a Superuser, you can verify or edit the DNS routing under your account:
* Go to the main Cloudflare Dashboard ➡️ **citas-smart-archive.com** ➡️ **DNS** ➡️ **Records**.
* You will see the `CNAME` record for `ollama` pointing to the tunnel ID. This confirms the traffic is routing correctly!

