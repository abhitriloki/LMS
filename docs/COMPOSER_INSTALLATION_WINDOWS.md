# Installing Composer on Windows (for WAMPP)

## Quick Installation Guide

### Method 1: Using Composer Installer (Recommended)

1. **Download Composer Installer**:
   - Go to: https://getcomposer.org/Composer-Setup.exe
   - Or visit: https://getcomposer.org/download/ and click "Composer-Setup.exe"

2. **Run the Installer**:
   - Double-click the downloaded `Composer-Setup.exe`
   - Click "Next" on the welcome screen

3. **Choose Installation Mode**:
   - Select "Install for all users" (recommended)
   - Click "Next"

4. **Select PHP Location**:
   - The installer will ask for PHP location
   - Browse to your WAMPP PHP folder:
     ```
     C:\wamp64\bin\php\php8.2.x\php.exe
     ```
   - Replace `php8.2.x` with your actual PHP version folder
   - Click "Next"

5. **Proxy Settings**:
   - Leave blank unless you use a proxy
   - Click "Next"

6. **Ready to Install**:
   - Review settings
   - Click "Install"

7. **Complete Installation**:
   - Click "Finish"

8. **Verify Installation**:
   - Open a NEW Command Prompt (important!)
   - Type:
     ```cmd
     composer --version
     ```
   - You should see something like: `Composer version 2.x.x`

### Method 2: Manual Installation (If Installer Fails)

1. **Download composer.phar**:
   - Go to: https://getcomposer.org/download/
   - Download `composer.phar` file

2. **Create Composer Folder**:
   ```cmd
   mkdir C:\composer
   ```

3. **Move composer.phar**:
   - Move the downloaded `composer.phar` to `C:\composer\`

4. **Create Batch File**:
   - Create a new file: `C:\composer\composer.bat`
   - Add this content:
     ```batch
     @echo off
     php "C:\composer\composer.phar" %*
     ```

5. **Add to System PATH**:
   - Right-click "This PC" or "My Computer"
   - Click "Properties"
   - Click "Advanced system settings"
   - Click "Environment Variables"
   - Under "System variables", find "Path"
   - Click "Edit"
   - Click "New"
   - Add: `C:\composer`
   - Click "OK" on all windows

6. **Verify Installation**:
   - Open a NEW Command Prompt
   - Type:
     ```cmd
     composer --version
     ```

## Troubleshooting

### Issue: "composer is not recognized" after installation

**Solution**:
1. Close ALL Command Prompt windows
2. Open a NEW Command Prompt
3. Try again

If still not working:
- Restart your computer
- Open Command Prompt and try again

### Issue: "PHP is not recognized"

**Solution**:
Add PHP to your system PATH:

1. Right-click "This PC" → "Properties"
2. Click "Advanced system settings"
3. Click "Environment Variables"
4. Under "System variables", find "Path"
5. Click "Edit"
6. Click "New"
7. Add your PHP path:
   ```
   C:\wamp64\bin\php\php8.2.x
   ```
8. Click "OK" on all windows
9. Restart Command Prompt

### Issue: Composer installer can't find PHP

**Solution**:
- Make sure WAMPP is installed
- Find your PHP folder in WAMPP:
  - Usually: `C:\wamp64\bin\php\php8.2.x\`
  - Look for `php.exe` file
- Point the installer to this `php.exe` file

## After Installing Composer

Now you can continue with the project setup:

1. **Navigate to your project**:
   ```cmd
   cd C:\wamp64\www\corporate-lms
   ```

2. **Install project dependencies**:
   ```cmd
   composer install
   ```

This will take a few minutes to download all required packages.

## Next Steps

After Composer is installed and working:

1. Install Node.js (for frontend assets)
2. Continue with the main setup guide: `WAMPP_LOCALHOST_SETUP.md`

---

**Need Help?**

If you're still having issues:
1. Make sure WAMPP is installed and PHP is working
2. Try restarting your computer after installation
3. Make sure you're opening a NEW Command Prompt after installation
