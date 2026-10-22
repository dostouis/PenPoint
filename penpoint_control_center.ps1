# PenPoint Vending Machine - System Control Center GUI
# Concept: Japanese Retro Stationery Jidouhanbaiki (Show / Early Heisei Enamel Chassis)

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

[System.Windows.Forms.Application]::EnableVisualStyles()

$form = New-Object System.Windows.Forms.Form
$form.Text = "PenPoint Control Center - UMak SkiDB"
$form.Size = New-Object System.Drawing.Size(620, 720)
$form.StartPosition = "CenterScreen"
$form.FormBorderStyle = "FixedDialog"
$form.MaximizeBox = $false
$form.BackColor = [System.Drawing.Color]::FromArgb(21, 24, 33) # --retro-bg #151821
$form.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)

# -------------------------------------------------------------
# 1. Showa Stationery Signboard Crown Header
# -------------------------------------------------------------
$headerPanel = New-Object System.Windows.Forms.Panel
$headerPanel.Location = New-Object System.Drawing.Point(0, 0)
$headerPanel.Size = New-Object System.Drawing.Size(620, 88)
$headerPanel.BackColor = [System.Drawing.Color]::FromArgb(33, 38, 52) # --retro-chassis #212634
$form.Controls.Add($headerPanel)

# Hanko / Sign Stamp Icon Box
$stampBox = New-Object System.Windows.Forms.Panel
$stampBox.Location = New-Object System.Drawing.Point(22, 14)
$stampBox.Size = New-Object System.Drawing.Size(56, 56)
$stampBox.BackColor = [System.Drawing.Color]::FromArgb(214, 48, 49) # --retro-red
$headerPanel.Controls.Add($stampBox)

$lblKanjiStamp = New-Object System.Windows.Forms.Label
$lblKanjiStamp.Text = "STN"
$lblKanjiStamp.Font = New-Object System.Drawing.Font("Segoe UI", 12, [System.Drawing.FontStyle]::Bold)
$lblKanjiStamp.ForeColor = [System.Drawing.Color]::White
$lblKanjiStamp.Location = New-Object System.Drawing.Point(0, 10)
$lblKanjiStamp.Size = New-Object System.Drawing.Size(56, 20)
$lblKanjiStamp.TextAlign = [System.Drawing.ContentAlignment]::MiddleCenter
$stampBox.Controls.Add($lblKanjiStamp)

$lblSubStamp = New-Object System.Windows.Forms.Label
$lblSubStamp.Text = "KIOSK"
$lblSubStamp.Font = New-Object System.Drawing.Font("Segoe UI", 6.5, [System.Drawing.FontStyle]::Bold)
$lblSubStamp.ForeColor = [System.Drawing.Color]::FromArgb(255, 200, 200)
$lblSubStamp.Location = New-Object System.Drawing.Point(0, 32)
$lblSubStamp.Size = New-Object System.Drawing.Size(56, 16)
$lblSubStamp.TextAlign = [System.Drawing.ContentAlignment]::MiddleCenter
$stampBox.Controls.Add($lblSubStamp)

# Header Title Cluster
$titleMain = New-Object System.Windows.Forms.Label
$titleMain.Text = "PENPOINT"
$titleMain.Font = New-Object System.Drawing.Font("Segoe UI", 18, [System.Drawing.FontStyle]::Bold)
$titleMain.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$titleMain.Location = New-Object System.Drawing.Point(88, 14)
$titleMain.AutoSize = $true
$headerPanel.Controls.Add($titleMain)

$badgeJp = New-Object System.Windows.Forms.Label
$badgeJp.Text = "SMART VEND"
$badgeJp.Font = New-Object System.Drawing.Font("Segoe UI", 8, [System.Drawing.FontStyle]::Bold)
$badgeJp.BackColor = [System.Drawing.Color]::FromArgb(243, 156, 18) # --retro-mustard
$badgeJp.ForeColor = [System.Drawing.Color]::FromArgb(18, 21, 27)
$badgeJp.Location = New-Object System.Drawing.Point(236, 20)
$badgeJp.Size = New-Object System.Drawing.Size(86, 22)
$badgeJp.TextAlign = [System.Drawing.ContentAlignment]::MiddleCenter
$headerPanel.Controls.Add($badgeJp)

$titleSub = New-Object System.Windows.Forms.Label
$titleSub.Text = "SMART STATIONERY KIOSK CONTROL CONSOLE | GROUP SkiDB (IV-CINS)"
$titleSub.Font = New-Object System.Drawing.Font("Segoe UI", 8, [System.Drawing.FontStyle]::Bold)
$titleSub.ForeColor = [System.Drawing.Color]::FromArgb(155, 166, 190)
$titleSub.Location = New-Object System.Drawing.Point(90, 48)
$titleSub.AutoSize = $true
$headerPanel.Controls.Add($titleSub)

# -------------------------------------------------------------
# 2. Hardware Services Telemetry Plate
# -------------------------------------------------------------
$groupServices = New-Object System.Windows.Forms.GroupBox
$groupServices.Text = " HARDWARE SERVICES & TELEMETRY "
$groupServices.Font = New-Object System.Drawing.Font("Segoe UI", 8.5, [System.Drawing.FontStyle]::Bold)
$groupServices.ForeColor = [System.Drawing.Color]::FromArgb(243, 156, 18)
$groupServices.Location = New-Object System.Drawing.Point(22, 100)
$groupServices.Size = New-Object System.Drawing.Size(560, 108)
$form.Controls.Add($groupServices)

$lblMysql = New-Object System.Windows.Forms.Label
$lblMysql.Text = "[*] MySQL Database: Checking..."
$lblMysql.Font = New-Object System.Drawing.Font("Consolas", 9)
$lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$lblMysql.Location = New-Object System.Drawing.Point(18, 24)
$lblMysql.Size = New-Object System.Drawing.Size(520, 20)
$groupServices.Controls.Add($lblMysql)

$lblPhp = New-Object System.Windows.Forms.Label
$lblPhp.Text = "[*] Local Kiosk Web Server: Checking (Port 8000)..."
$lblPhp.Font = New-Object System.Drawing.Font("Consolas", 9)
$lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$lblPhp.Location = New-Object System.Drawing.Point(18, 48)
$lblPhp.Size = New-Object System.Drawing.Size(520, 20)
$groupServices.Controls.Add($lblPhp)

$lblCloudflare = New-Object System.Windows.Forms.Label
$lblCloudflare.Text = "[*] Cloudflare Edge Tunnel: Establishing secure route..."
$lblCloudflare.Font = New-Object System.Drawing.Font("Consolas", 9)
$lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(243, 156, 18)
$lblCloudflare.Location = New-Object System.Drawing.Point(18, 72)
$lblCloudflare.Size = New-Object System.Drawing.Size(520, 20)
$groupServices.Controls.Add($lblCloudflare)

# -------------------------------------------------------------
# 3. Public Remote Access Box (Live Cloudflare Tunnel)
# -------------------------------------------------------------
$groupPublic = New-Object System.Windows.Forms.GroupBox
$groupPublic.Text = " PUBLIC CLOUDFLARE REMOTE ACCESS "
$groupPublic.Font = New-Object System.Drawing.Font("Segoe UI", 8.5, [System.Drawing.FontStyle]::Bold)
$groupPublic.ForeColor = [System.Drawing.Color]::FromArgb(116, 185, 255)
$groupPublic.Location = New-Object System.Drawing.Point(22, 218)
$groupPublic.Size = New-Object System.Drawing.Size(560, 118)
$form.Controls.Add($groupPublic)

$txtUrl = New-Object System.Windows.Forms.TextBox
$txtUrl.Text = "Negotiating Cloudflare edge endpoint..."
$txtUrl.Font = New-Object System.Drawing.Font("Consolas", 9.5, [System.Drawing.FontStyle]::Bold)
$txtUrl.BackColor = [System.Drawing.Color]::FromArgb(17, 20, 28)
$txtUrl.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$txtUrl.Location = New-Object System.Drawing.Point(18, 25)
$txtUrl.Size = New-Object System.Drawing.Size(524, 26)
$txtUrl.ReadOnly = $true
$groupPublic.Controls.Add($txtUrl)

# Button 1: Copy Public Link
$btnCopyUrl = New-Object System.Windows.Forms.Button
$btnCopyUrl.Text = "Copy Public Kiosk URL"
$btnCopyUrl.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnCopyUrl.BackColor = [System.Drawing.Color]::FromArgb(46, 125, 50) # --retro-matcha
$btnCopyUrl.ForeColor = [System.Drawing.Color]::White
$btnCopyUrl.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnCopyUrl.FlatAppearance.BorderSize = 0
$btnCopyUrl.Location = New-Object System.Drawing.Point(18, 62)
$btnCopyUrl.Size = New-Object System.Drawing.Size(166, 38)
$btnCopyUrl.Cursor = [System.Windows.Forms.Cursors]::Hand
$groupPublic.Controls.Add($btnCopyUrl)

# Button 2: Open Public Student Kiosk
$btnOpenPublicKiosk = New-Object System.Windows.Forms.Button
$btnOpenPublicKiosk.Text = "Open Public Kiosk"
$btnOpenPublicKiosk.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnOpenPublicKiosk.BackColor = [System.Drawing.Color]::FromArgb(214, 48, 49) # --retro-red
$btnOpenPublicKiosk.ForeColor = [System.Drawing.Color]::White
$btnOpenPublicKiosk.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenPublicKiosk.FlatAppearance.BorderSize = 0
$btnOpenPublicKiosk.Location = New-Object System.Drawing.Point(197, 62)
$btnOpenPublicKiosk.Size = New-Object System.Drawing.Size(166, 38)
$btnOpenPublicKiosk.Cursor = [System.Windows.Forms.Cursors]::Hand
$groupPublic.Controls.Add($btnOpenPublicKiosk)

# Button 3: Open Public Admin Portal
$btnOpenPublicAdmin = New-Object System.Windows.Forms.Button
$btnOpenPublicAdmin.Text = "Open Public Admin"
$btnOpenPublicAdmin.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnOpenPublicAdmin.BackColor = [System.Drawing.Color]::FromArgb(35, 42, 58)
$btnOpenPublicAdmin.ForeColor = [System.Drawing.Color]::FromArgb(116, 185, 255)
$btnOpenPublicAdmin.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenPublicAdmin.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(65, 80, 110)
$btnOpenPublicAdmin.Location = New-Object System.Drawing.Point(376, 62)
$btnOpenPublicAdmin.Size = New-Object System.Drawing.Size(166, 38)
$btnOpenPublicAdmin.Cursor = [System.Windows.Forms.Cursors]::Hand
$groupPublic.Controls.Add($btnOpenPublicAdmin)

# -------------------------------------------------------------
# 4. Local Kiosk & Admin Controls
# -------------------------------------------------------------
# Button 4: Local Student Kiosk
$btnOpenKiosk = New-Object System.Windows.Forms.Button
$btnOpenKiosk.Text = "Local Student Kiosk (127.0.0.1:8000)"
$btnOpenKiosk.Font = New-Object System.Drawing.Font("Segoe UI", 9.5, [System.Drawing.FontStyle]::Bold)
$btnOpenKiosk.BackColor = [System.Drawing.Color]::FromArgb(33, 38, 52)
$btnOpenKiosk.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$btnOpenKiosk.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenKiosk.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(65, 75, 100)
$btnOpenKiosk.Location = New-Object System.Drawing.Point(22, 348)
$btnOpenKiosk.Size = New-Object System.Drawing.Size(272, 42)
$btnOpenKiosk.Cursor = [System.Windows.Forms.Cursors]::Hand
$form.Controls.Add($btnOpenKiosk)

# Button 5: Local Admin Dashboard
$btnOpenAdmin = New-Object System.Windows.Forms.Button
$btnOpenAdmin.Text = "Local Admin Dashboard (Port 8000)"
$btnOpenAdmin.Font = New-Object System.Drawing.Font("Segoe UI", 9.5, [System.Drawing.FontStyle]::Bold)
$btnOpenAdmin.BackColor = [System.Drawing.Color]::FromArgb(33, 38, 52)
$btnOpenAdmin.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$btnOpenAdmin.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenAdmin.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(65, 75, 100)
$btnOpenAdmin.Location = New-Object System.Drawing.Point(310, 348)
$btnOpenAdmin.Size = New-Object System.Drawing.Size(272, 42)
$btnOpenAdmin.Cursor = [System.Windows.Forms.Cursors]::Hand
$form.Controls.Add($btnOpenAdmin)

# -------------------------------------------------------------
# 5. Live Activity Telemetry Terminal
# -------------------------------------------------------------
$lblLog = New-Object System.Windows.Forms.Label
$lblLog.Text = "SYSTEM ACTIVITY TELEMETRY:"
$lblLog.Font = New-Object System.Drawing.Font("Segoe UI", 8, [System.Drawing.FontStyle]::Bold)
$lblLog.ForeColor = [System.Drawing.Color]::FromArgb(155, 166, 190)
$lblLog.Location = New-Object System.Drawing.Point(22, 404)
$lblLog.AutoSize = $true
$form.Controls.Add($lblLog)

$txtLog = New-Object System.Windows.Forms.TextBox
$txtLog.Multiline = $true
$txtLog.ScrollBars = "Vertical"
$txtLog.Font = New-Object System.Drawing.Font("Consolas", 8.5)
$txtLog.BackColor = [System.Drawing.Color]::FromArgb(14, 17, 23)
$txtLog.ForeColor = [System.Drawing.Color]::FromArgb(178, 190, 195)
$txtLog.Location = New-Object System.Drawing.Point(22, 424)
$txtLog.Size = New-Object System.Drawing.Size(560, 160)
$txtLog.BorderStyle = [System.Windows.Forms.BorderStyle]::FixedSingle
$txtLog.ReadOnly = $true
$form.Controls.Add($txtLog)

# Stop All Services Button
$btnStopExit = New-Object System.Windows.Forms.Button
$btnStopExit.Text = "Stop All Services and Exit"
$btnStopExit.Font = New-Object System.Drawing.Font("Segoe UI", 9.5, [System.Drawing.FontStyle]::Bold)
$btnStopExit.BackColor = [System.Drawing.Color]::FromArgb(35, 30, 35)
$btnStopExit.ForeColor = [System.Drawing.Color]::FromArgb(255, 118, 117)
$btnStopExit.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnStopExit.FlatAppearance.BorderColor = [System.Drawing.Color]::FromArgb(90, 45, 45)
$btnStopExit.Location = New-Object System.Drawing.Point(22, 600)
$btnStopExit.Size = New-Object System.Drawing.Size(560, 40)
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
    Add-Log "MySQL service active on port 3306."
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
    $lblPhp.Text = "[OK] Local Kiosk Web Server: Running (http://127.0.0.1:8000)"
    $lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
    Add-Log "PHP Web Server initialized on port 8000."
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
    Add-Log "Cloudflare tunnel process started. Negotiating domain..."
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
            $lblCloudflare.Text = "[OK] Cloudflare Public Tunnel: Connected & Active"
            $lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
            Add-Log "Assigned Cloudflare Domain: $script:rawBaseUrl"
            [System.Windows.Forms.Clipboard]::SetText("$script:rawBaseUrl/public/index.php")
            Add-Log "Public Kiosk URL copied to clipboard automatically!"
            $timer.Stop()
        }
    }
    if ($script:pollCount -ge 20) {
        $timer.Stop()
        if ($txtUrl.Text -eq "Negotiating Cloudflare edge endpoint...") {
            $txtUrl.Text = "Connection taking longer than usual. Retrying..."
        }
    }
})
$timer.Start()

# Button Actions (5 Buttons Total)
$btnCopyUrl.Add_Click({
    if ($txtUrl.Text.StartsWith("https")) {
        [System.Windows.Forms.Clipboard]::SetText($txtUrl.Text)
        $btnCopyUrl.Text = "Copied to Clipboard!"
    }
})

$btnOpenPublicKiosk.Add_Click({
    if ($script:rawBaseUrl.StartsWith("https")) {
        [System.Diagnostics.Process]::Start("$script:rawBaseUrl/public/index.php")
    } elseif ($txtUrl.Text.StartsWith("https")) {
        [System.Diagnostics.Process]::Start($txtUrl.Text)
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
    Add-Log "Shutting down background services..."
    if ($phpProcess -and !$phpProcess.HasExited) { $phpProcess.Kill() }
    if ($cfProcess -and !$cfProcess.HasExited) { $cfProcess.Kill() }
    Stop-Process -Name "cloudflared" -Force -ErrorAction SilentlyContinue
    if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }
})

# Show the GUI Form
[void]$form.ShowDialog()
