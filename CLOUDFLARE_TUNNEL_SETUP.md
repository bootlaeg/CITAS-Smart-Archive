# CITAS Smart Archive - Cloudflare Tunnel Setup Guide

## Step 1: Download Cloudflared

1. Download from: https://github.com/cloudflare/cloudflared/releases
2. Get: `cloudflared-windows-amd64.exe`
3. Save to: `C:\Users\ADMIN\Desktop\CITAS-Smart-Archive\`
4. (OR any location you prefer)

## Step 2: First-Time Setup (Do ONCE only)

Open Command Prompt and run these commands:

```batch
cd C:\Users\ADMIN\Desktop\CITAS-Smart-Archive

REM Login to Cloudflare (opens browser)
cloudflared-windows-amd64.exe login

REM Create tunnel named 'ollama'
cloudflared-windows-amd64.exe tunnel create ollama

REM Route DNS to tunnel
cloudflared-windows-amd64.exe tunnel route dns ollama ollama.citas-smart-archive.com
```

**Important:** Save the tunnel ID that appears! You'll need it in config.yml

## Step 3: Create config.yml File

Save this file as: `C:\Users\ADMIN\Desktop\CITAS-Smart-Archive\config.yml`

**Replace YOUR_TUNNEL_ID with the tunnel ID from Step 2!**

```yaml
tunnel: ollama
credentials-file: C:\Users\ADMIN\.cloudflared\YOUR_TUNNEL_ID.json

ingress:
  - hostname: ollama.citas-smart-archive.com
    service: http://localhost:11434
    originRequest:
      httpHostHeader: localhost:11434
  - service: http_status:404
```

## Step 4: Start Tunnel (Every Time)

Open Command Prompt:

```batch
cd C:\Users\ADMIN\Desktop\CITAS-Smart-Archive

cloudflared-windows-amd64.exe tunnel --config config.yml run ollama
```

**IMPORTANT:** Keep this Command Prompt window OPEN while you need the tunnel!

## Step 5: Test Tunnel Connection

Once running, test with:

```
https://ollama.citas-smart-archive.com/api/tags
```

You should get JSON response with your Ollama models.

## Step 6: Update PHP Code

The chatbot code will automatically use the tunnel if configured properly.

**Current system uses:** `http://localhost:11434`
**Tunnel system uses:** `https://ollama.citas-smart-archive.com`

---

## Quick Commands

### Start Everything
```batch
START_ALL_WITH_TUNNEL.bat
```

### Start Just Tunnel
```batch
cd C:\Users\ADMIN\Desktop\CITAS-Smart-Archive
cloudflared-windows-amd64.exe tunnel --config config.yml run ollama
```

### Check Tunnel Status
```batch
curl https://ollama.citas-smart-archive.com/api/tags
```

---

## Troubleshooting

❌ **"Command not recognized"**
- Make sure you're in the correct directory where cloudflared.exe is
- Or add it to System PATH

❌ **"Error 1033"**
- Make sure tunnel is running in Command Prompt window
- Check config.yml file is correct
- Verify YOUR_TUNNEL_ID is correct

❌ **"Connection timeout"**
- Wait 10-30 seconds for tunnel to establish
- Check internet connection
- Verify Ollama is running on localhost:11434

✅ **Success**
- You see JSON response at https://ollama.citas-smart-archive.com/api/tags
- Chatbot connects to tunnel successfully

---

## Important Notes

🔒 **Security:** Cloudflare Tunnel encrypts your connection
📡 **Speed:** First request may be slower (tunnel overhead)
🔄 **Keep it running:** Command Prompt window must stay open
🌐 **Internet required:** Needs active internet connection to Cloudflare
