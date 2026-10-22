# PenPoint Vending Machine - System Control Center GUI
# Showa Retro Hardware Console Theme for Windows Forms

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

[System.Windows.Forms.Application]::EnableVisualStyles()

$form = New-Object System.Windows.Forms.Form
$form.Text = "PenPoint Control Center - UMak SkiDB"
$form.Size = New-Object System.Drawing.Size(600, 680)
$form.StartPosition = "CenterScreen"
$form.FormBorderStyle = "FixedDialog"
$form.MaximizeBox = $false
$form.BackColor = [System.Drawing.Color]::FromArgb(21, 24, 33)
$form.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)

# Title Header Banner (Retro Vending Chassis Plate)
$headerPanel = New-Object System.Windows.Forms.Panel
$headerPanel.Location = New-Object System.Drawing.Point(0, 0)
$headerPanel.Size = New-Object System.Drawing.Size(600, 80)
$headerPanel.BackColor = [System.Drawing.Color]::FromArgb(33, 38, 52)
$form.Controls.Add($headerPanel)

$titleBadge = New-Object System.Windows.Forms.Label
$titleBadge.Text = "PENPOINT"
$titleBadge.Font = New-Object System.Drawing.Font("Segoe UI", 16, [System.Drawing.FontStyle]::Bold)
$titleBadge.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$titleBadge.Location = New-Object System.Drawing.Point(24, 15)
$titleBadge.AutoSize = $true
$headerPanel.Controls.Add($titleBadge)

$titleTag = New-Object System.Windows.Forms.Label
$titleTag.Text = "CONTROL CENTER"
$titleTag.Font = New-Object System.Drawing.Font("Segoe UI", 16, [System.Drawing.FontStyle]::Bold)
$titleTag.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$titleTag.Location = New-Object System.Drawing.Point(165, 15)
$titleTag.AutoSize = $true
$headerPanel.Controls.Add($titleTag)

$subtitleLabel = New-Object System.Windows.Forms.Label
$subtitleLabel.Text = "4-Tier Smart Stationery Vending Machine | Group SkiDB (IV-CINS)"
$subtitleLabel.Font = New-Object System.Drawing.Font("Segoe UI", 8.5)
$subtitleLabel.ForeColor = [System.Drawing.Color]::FromArgb(155, 166, 190)
$subtitleLabel.Location = New-Object System.Drawing.Point(25, 48)
$subtitleLabel.AutoSize = $true
$headerPanel.Controls.Add($subtitleLabel)

# 1. System Services Group Box
$groupServices = New-Object System.Windows.Forms.GroupBox
$groupServices.Text = "  SYSTEM SERVICES STATUS  "
$groupServices.Font = New-Object System.Drawing.Font("Segoe UI", 8.5, [System.Drawing.FontStyle]::Bold)
$groupServices.ForeColor = [System.Drawing.Color]::FromArgb(243, 156, 18)
$groupServices.Location = New-Object System.Drawing.Point(24, 95)
$groupServices.Size = New-Object System.Drawing.Size(536, 115)
$form.Controls.Add($groupServices)

# Service Indicators
$lblMysql = New-Object System.Windows.Forms.Label
$lblMysql.Text = "[*] MySQL Database: Checking..."
$lblMysql.Font = New-Object System.Drawing.Font("Segoe UI", 9.5)
$lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$lblMysql.Location = New-Object System.Drawing.Point(18, 24)
$lblMysql.Size = New-Object System.Drawing.Size(500, 22)
$groupServices.Controls.Add($lblMysql)

$lblPhp = New-Object System.Windows.Forms.Label
$lblPhp.Text = "[*] Local Web Server (Port 8000): Checking..."
$lblPhp.Font = New-Object System.Drawing.Font("Segoe UI", 9.5)
$lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$lblPhp.Location = New-Object System.Drawing.Point(18, 52)
$lblPhp.Size = New-Object System.Drawing.Size(500, 22)
$groupServices.Controls.Add($lblPhp)

$lblCloudflare = New-Object System.Windows.Forms.Label
$lblCloudflare.Text = "[*] Cloudflare Public Tunnel: Connecting to Edge..."
$lblCloudflare.Font = New-Object System.Drawing.Font("Segoe UI", 9.5)
$lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(243, 156, 18)
$lblCloudflare.Location = New-Object System.Drawing.Point(18, 80)
$lblCloudflare.Size = New-Object System.Drawing.Size(500, 22)
$groupServices.Controls.Add($lblCloudflare)

# 2. Remote Access Group (Cloudflare Edge)
$groupPublic = New-Object System.Windows.Forms.GroupBox
$groupPublic.Text = "  PUBLIC MOBILE & REMOTE ACCESS (CLOUDFLARE)  "
$groupPublic.Font = New-Object System.Drawing.Font("Segoe UI", 8.5, [System.Drawing.FontStyle]::Bold)
$groupPublic.ForeColor = [System.Drawing.Color]::FromArgb(116, 185, 255)
$groupPublic.Location = New-Object System.Drawing.Point(24, 222)
$groupPublic.Size = New-Object System.Drawing.Size(536, 120)
$form.Controls.Add($groupPublic)

$txtUrl = New-Object System.Windows.Forms.TextBox
$txtUrl.Text = "Generating secure Cloudflare URL..."
$txtUrl.Font = New-Object System.Drawing.Font("Consolas", 9.5, [System.Drawing.FontStyle]::Bold)
$txtUrl.BackColor = [System.Drawing.Color]::FromArgb(14, 17, 23)
$txtUrl.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$txtUrl.Location = New-Object System.Drawing.Point(18, 28)
$txtUrl.Size = New-Object System.Drawing.Size(500, 26)
$txtUrl.ReadOnly = $true
$groupPublic.Controls.Add($txtUrl)

$btnCopyUrl = New-Object System.Windows.Forms.Button
$btnCopyUrl.Text = "Copy Public Kiosk URL"
$btnCopyUrl.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnCopyUrl.BackColor = [System.Drawing.Color]::FromArgb(46, 125, 50)
$btnCopyUrl.ForeColor = [System.Drawing.Color]::White
$btnCopyUrl.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnCopyUrl.FlatAppearance.BorderSize = 0
$btnCopyUrl.Location = New-Object System.Drawing.Point(18, 66)
$btnCopyUrl.Size = New-Object System.Drawing.Size(240, 36)
$btnCopyUrl.Cursor = [System.Windows.Forms.Cursors]::Hand
$groupPublic.Controls.Add($btnCopyUrl)

# Replaced with Public Admin Portal Button
$btnOpenPublicAdmin = New-Object System.Windows.Forms.Button
$btnOpenPublicAdmin.Text = "Open Public Admin Portal"
$btnOpenPublicAdmin.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnOpenPublicAdmin.BackColor = [System.Drawing.Color]::FromArgb(33, 40, 56)
$btnOpenPublicAdmin.ForeColor = [System.Drawing.Color]::FromArgb(116, 185, 255)
$btnOpenPublicAdmin.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenPublicAdmin.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(60, 75, 105)
$btnOpenPublicAdmin.Location = New-Object System.Drawing.Point(278, 66)
$btnOpenPublicAdmin.Size = New-Object System.Drawing.Size(240, 36)
$btnOpenPublicAdmin.Cursor = [System.Windows.Forms.Cursors]::Hand
$groupPublic.Controls.Add($btnOpenPublicAdmin)

# 3. Local Machine Launch Buttons
$btnOpenKiosk = New-Object System.Windows.Forms.Button
$btnOpenKiosk.Text = "Open Local Student Kiosk"
$btnOpenKiosk.Font = New-Object System.Drawing.Font("Segoe UI", 10, [System.Drawing.FontStyle]::Bold)
$btnOpenKiosk.BackColor = [System.Drawing.Color]::FromArgb(214, 48, 49)
$btnOpenKiosk.ForeColor = [System.Drawing.Color]::White
$btnOpenKiosk.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenKiosk.FlatAppearance.BorderSize = 0
$btnOpenKiosk.Location = New-Object System.Drawing.Point(24, 354)
$btnOpenKiosk.Size = New-Object System.Drawing.Size(260, 44)
$btnOpenKiosk.Cursor = [System.Windows.Forms.Cursors]::Hand
$form.Controls.Add($btnOpenKiosk)

$btnOpenAdmin = New-Object System.Windows.Forms.Button
$btnOpenAdmin.Text = "Open Local Admin Dashboard"
$btnOpenAdmin.Font = New-Object System.Drawing.Font("Segoe UI", 10, [System.Drawing.FontStyle]::Bold)
$btnOpenAdmin.BackColor = [System.Drawing.Color]::FromArgb(35, 41, 56)
$btnOpenAdmin.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$btnOpenAdmin.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenAdmin.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(60, 70, 95)
$btnOpenAdmin.Location = New-Object System.Drawing.Point(300, 354)
$btnOpenAdmin.Size = New-Object System.Drawing.Size(260, 44)
$btnOpenAdmin.Cursor = [System.Windows.Forms.Cursors]::Hand
$form.Controls.Add($btnOpenAdmin)

# 4. Live Activity Log Section
$lblLog = New-Object System.Windows.Forms.Label
$lblLog.Text = "Live System Telemetry & Logs:"
$lblLog.Font = New-Object System.Drawing.Font("Segoe UI", 8.5, [System.Drawing.FontStyle]::Bold)
$lblLog.ForeColor = [System.Drawing.Color]::FromArgb(155, 166, 190)
$lblLog.Location = New-Object System.Drawing.Point(24, 412)
$lblLog.AutoSize = $true
$form.Controls.Add($lblLog)

$txtLog = New-Object System.Windows.Forms.TextBox
$txtLog.Multiline = $true
$txtLog.ScrollBars = "Vertical"
$txtLog.Font = New-Object System.Drawing.Font("Consolas", 8.5)
$txtLog.BackColor = [System.Drawing.Color]::FromArgb(14, 17, 23)
$txtLog.ForeColor = [System.Drawing.Color]::FromArgb(178, 190, 195)
$txtLog.Location = New-Object System.Drawing.Point(24, 432)
$txtLog.Size = New-Object System.Drawing.Size(536, 125)
$txtLog.BorderStyle = [System.Windows.Forms.BorderStyle]::FixedSingle
$txtLog.ReadOnly = $true
$form.Controls.Add($txtLog)

# Stop All Services Button
$btnStopExit = New-Object System.Windows.Forms.Button
$btnStopExit.Text = "Stop All Services and Exit"
$btnStopExit.Font = New-Object System.Drawing.Font("Segoe UI", 9.5, [System.Drawing.FontStyle]::Bold)
$btnStopExit.BackColor = [System.Drawing.Color]::FromArgb(37, 43, 56)
$btnStopExit.ForeColor = [System.Drawing.Color]::FromArgb(255, 118, 117)
$btnStopExit.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnStopExit.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(100, 45, 45)
$btnStopExit.Location = New-Object System.Drawing.Point(24, 572)
$btnStopExit.Size = New-Object System.Drawing.Size(536, 38)
$btnStopExit.Cursor = [System.Windows.Forms.Cursors]::Hand
$form.Controls.Add($btnStopExit)

function Add-Log($msg) {
    $time = (Get-Date).ToString("HH:mm:ss")
    $txtLog.AppendText("[$time] $msg`r`n")
}

# --- Background Services Starter ---
$phpProcess = $null
$cfProcess = $null
$rawBaseUrl = ""
$logFile = "$env:TEMP\cf_gui_penpoint.log"
if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }

# 1. Start MySQL
Add-Log "Checking MySQL Database..."
if (Get-Process "mysqld" -ErrorAction SilentlyContinue) {
    $lblMysql.Text = "[OK] MySQL Database: Running (Port 3306)"
    $lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
    Add-Log "MySQL service is running."
} else {
    if (Test-Path "C:\xampp\mysql_start.bat") {
        Start-Process -FilePath "C:\xampp\mysql_start.bat" -WindowStyle Hidden
        $lblMysql.Text = "[OK] MySQL Database: Started (Port 3306)"
        $lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
        Add-Log "Started MySQL via XAMPP."
    }
}

# 2. Start PHP Built-in Server in Hidden Window
Add-Log "Launching PHP server on 127.0.0.1:8000..."
$phpPsi = New-Object System.Diagnostics.ProcessStartInfo
$phpPsi.FileName = "C:\xampp\php\php.exe"
$phpPsi.Arguments = "-S 127.0.0.1:8000 -t c:\Coding\EcoPoints\penpoint"
$phpPsi.WindowStyle = [System.Diagnostics.ProcessWindowStyle]::Hidden
$phpPsi.CreateNoWindow = $true
$phpPsi.UseShellExecute = $false
try {
    $phpProcess = [System.Diagnostics.Process]::Start($phpPsi)
    $lblPhp.Text = "[OK] Local Web Server: Running on http://127.0.0.1:8000"
    $lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
    Add-Log "PHP Web Server active on port 8000."
} catch {
    $lblPhp.Text = "[ERR] Local Web Server: Error starting PHP"
    $lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(231, 76, 60)
    Add-Log "Failed to launch PHP: $_"
}

# 3. Start Cloudflare in Hidden Window
Add-Log "Connecting to Cloudflare edge tunnel..."
$cfPsi = New-Object System.Diagnostics.ProcessStartInfo
$cfPsi.FileName = "C:\xampp\cloudflared.exe"
$cfPsi.Arguments = "tunnel --logfile `"$logFile`" --url http://127.0.0.1:8000"
$cfPsi.WindowStyle = [System.Diagnostics.ProcessWindowStyle]::Hidden
$cfPsi.CreateNoWindow = $true
$cfPsi.UseShellExecute = $false
try {
    $cfProcess = [System.Diagnostics.Process]::Start($cfPsi)
    Add-Log "Cloudflare process started. Negotiating domain..."
} catch {
    $lblCloudflare.Text = "[ERR] Cloudflare: Error starting executable"
    $lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(231, 76, 60)
}

# Timer to poll for Cloudflare URL
$timer = New-Object System.Windows.Forms.Timer
$timer.Interval = 800
$pollCount = 0

$timer.Add_Tick({
    $script:pollCount++
    if (Test-Path $logFile) {
        $content = Get-Content $logFile -ErrorAction SilentlyContinue
        $match = $content | Select-String -Pattern 'https://[a-zA-Z0-9-]+\.trycloudflare\.com' | Select-Object -First 1
        if ($match) {
            $script:rawBaseUrl = $match.Matches.Value
            $txtUrl.Text = "$script:rawBaseUrl/public/index.php"
            $lblCloudflare.Text = "[OK] Cloudflare Public Tunnel: Active and Connected"
            $lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
            Add-Log "Public Domain: $script:rawBaseUrl"
            [System.Windows.Forms.Clipboard]::SetText("$script:rawBaseUrl/public/index.php")
            Add-Log "Kiosk link copied to clipboard automatically!"
            $timer.Stop()
        }
    }
    if ($script:pollCount -ge 20) {
        $timer.Stop()
        if ($txtUrl.Text -eq "Generating secure Cloudflare URL...") {
            $txtUrl.Text = "Connection taking longer than usual. Retrying..."
        }
    }
})
$timer.Start()

# Button Actions
$btnCopyUrl.Add_Click({
    if ($txtUrl.Text.StartsWith("https")) {
        [System.Windows.Forms.Clipboard]::SetText($txtUrl.Text)
        $btnCopyUrl.Text = "Copied to Clipboard!"
    }
})

$btnOpenPublicAdmin.Add_Click({
    if ($script:rawBaseUrl.StartsWith("https")) {
        [System.Diagnostics.Process]::Start("$script:rawBaseUrl/admin/index.php")
    } elseif ($txtUrl.Text.StartsWith("https")) {
        $base = $txtUrl.Text -replace "/public/index\.php", ""
        [System.Diagnostics.Process]::Start("$base/admin/index.php")
    }
})

$btnOpenKiosk.Add_Click({
    [System.Diagnostics.Process]::Start("http://127.0.0.1:8000/public/index.php")
})

$btnOpenAdmin.Add_Click({
    [System.Diagnostics.Process]::Start("http://127.0.0.1:8000/admin/index.php")
})

$btnStopExit.Add_Click({
    $form.Close()
})

$form.Add_FormClosing({
    Add-Log "Shutting down processes..."
    if ($phpProcess -and !$phpProcess.HasExited) { $phpProcess.Kill() }
    if ($cfProcess -and !$cfProcess.HasExited) { $cfProcess.Kill() }
    Stop-Process -Name "cloudflared" -Force -ErrorAction SilentlyContinue
    if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }
})

# Show the GUI Form
[void]$form.ShowDialog()
