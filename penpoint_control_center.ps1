# PenPoint Vending Machine - System Control Center GUI
# Built with PowerShell WinForms (Zero black terminal popups)

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

[System.Windows.Forms.Application]::EnableVisualStyles()

$form = New-Object System.Windows.Forms.Form
$form.Text = "PenPoint Control Center — UMak SkiDB"
$form.Size = New-Object System.Drawing.Size(560, 640)
$form.StartPosition = "CenterScreen"
$form.FormBorderStyle = "FixedDialog"
$form.MaximizeBox = $false
$form.BackColor = [System.Drawing.Color]::FromArgb(21, 24, 33)
$form.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)

# Title Header
$headerPanel = New-Object System.Windows.Forms.Panel
$headerPanel.Location = New-Object System.Drawing.Point(0, 0)
$headerPanel.Size = New-Object System.Drawing.Size(560, 85)
$headerPanel.BackColor = [System.Drawing.Color]::FromArgb(33, 38, 52)
$form.Controls.Add($headerPanel)

$titleLabel = New-Object System.Windows.Forms.Label
$titleLabel.Text = "PENPOINT CONTROL CENTER"
$titleLabel.Font = New-Object System.Drawing.Font("Segoe UI", 16, [System.Drawing.FontStyle]::Bold)
$titleLabel.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$titleLabel.Location = New-Object System.Drawing.Point(20, 14)
$titleLabel.AutoSize = $true
$headerPanel.Controls.Add($titleLabel)

$subtitleLabel = New-Object System.Windows.Forms.Label
$subtitleLabel.Text = "Japanese Retro Stationery Vending Kiosk • Group SkiDB (IV-CINS)"
$subtitleLabel.Font = New-Object System.Drawing.Font("Segoe UI", 8.5)
$subtitleLabel.ForeColor = [System.Drawing.Color]::FromArgb(155, 166, 190)
$subtitleLabel.Location = New-Object System.Drawing.Point(22, 46)
$subtitleLabel.AutoSize = $true
$headerPanel.Controls.Add($subtitleLabel)

# Status Group Box
$groupServices = New-Object System.Windows.Forms.GroupBox
$groupServices.Text = " System Services "
$groupServices.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$groupServices.ForeColor = [System.Drawing.Color]::FromArgb(243, 156, 18)
$groupServices.Location = New-Object System.Drawing.Point(20, 95)
$groupServices.Size = New-Object System.Drawing.Size(505, 125)
$form.Controls.Add($groupServices)

# MySQL Status
$lblMysql = New-Object System.Windows.Forms.Label
$lblMysql.Text = "● MySQL Database: Checking..."
$lblMysql.Font = New-Object System.Drawing.Font("Segoe UI", 9.5)
$lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$lblMysql.Location = New-Object System.Drawing.Point(15, 25)
$lblMysql.Size = New-Object System.Drawing.Size(470, 24)
$groupServices.Controls.Add($lblMysql)

# PHP Status
$lblPhp = New-Object System.Windows.Forms.Label
$lblPhp.Text = "● Local Web Server (Port 8000): Checking..."
$lblPhp.Font = New-Object System.Drawing.Font("Segoe UI", 9.5)
$lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(251, 247, 238)
$lblPhp.Location = New-Object System.Drawing.Point(15, 55)
$lblPhp.Size = New-Object System.Drawing.Size(470, 24)
$groupServices.Controls.Add($lblPhp)

# Cloudflare Tunnel Status
$lblCloudflare = New-Object System.Windows.Forms.Label
$lblCloudflare.Text = "● Cloudflare Public Tunnel: Connecting..."
$lblCloudflare.Font = New-Object System.Drawing.Font("Segoe UI", 9.5)
$lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(243, 156, 18)
$lblCloudflare.Location = New-Object System.Drawing.Point(15, 85)
$lblCloudflare.Size = New-Object System.Drawing.Size(470, 24)
$groupServices.Controls.Add($lblCloudflare)

# Public URL Box Group
$groupPublic = New-Object System.Windows.Forms.GroupBox
$groupPublic.Text = " Public Cloudflare URL (For Phone & ESP32) "
$groupPublic.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$groupPublic.ForeColor = [System.Drawing.Color]::FromArgb(116, 185, 255)
$groupPublic.Location = New-Object System.Drawing.Point(20, 230)
$groupPublic.Size = New-Object System.Drawing.Size(505, 115)
$form.Controls.Add($groupPublic)

$txtUrl = New-Object System.Windows.Forms.TextBox
$txtUrl.Text = "Generating secure Cloudflare URL..."
$txtUrl.Font = New-Object System.Drawing.Font("Consolas", 10, [System.Drawing.FontStyle]::Bold)
$txtUrl.BackColor = [System.Drawing.Color]::FromArgb(17, 20, 28)
$txtUrl.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$txtUrl.Location = New-Object System.Drawing.Point(15, 28)
$txtUrl.Size = New-Object System.Drawing.Size(475, 28)
$txtUrl.ReadOnly = $true
$groupPublic.Controls.Add($txtUrl)

$btnCopyUrl = New-Object System.Windows.Forms.Button
$btnCopyUrl.Text = "📋 Copy Public Phone URL"
$btnCopyUrl.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnCopyUrl.BackColor = [System.Drawing.Color]::FromArgb(46, 125, 50)
$btnCopyUrl.ForeColor = [System.Drawing.Color]::White
$btnCopyUrl.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnCopyUrl.Location = New-Object System.Drawing.Point(15, 68)
$btnCopyUrl.Size = New-Object System.Drawing.Size(230, 32)
$groupPublic.Controls.Add($btnCopyUrl)

$btnOpenPhoneUrl = New-Object System.Windows.Forms.Button
$btnOpenPhoneUrl.Text = "🌐 Open Phone Link in Browser"
$btnOpenPhoneUrl.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnOpenPhoneUrl.BackColor = [System.Drawing.Color]::FromArgb(33, 40, 56)
$btnOpenPhoneUrl.ForeColor = [System.Drawing.Color]::FromArgb(116, 185, 255)
$btnOpenPhoneUrl.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenPhoneUrl.Location = New-Object System.Drawing.Point(260, 68)
$btnOpenPhoneUrl.Size = New-Object System.Drawing.Size(230, 32)
$groupPublic.Controls.Add($btnOpenPhoneUrl)

# Action Buttons
$btnOpenKiosk = New-Object System.Windows.Forms.Button
$btnOpenKiosk.Text = "🏪 Open Student Kiosk (Localhost)"
$btnOpenKiosk.Font = New-Object System.Drawing.Font("Segoe UI", 10.5, [System.Drawing.FontStyle]::Bold)
$btnOpenKiosk.BackColor = [System.Drawing.Color]::FromArgb(214, 48, 49)
$btnOpenKiosk.ForeColor = [System.Drawing.Color]::White
$btnOpenKiosk.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenKiosk.Location = New-Object System.Drawing.Point(20, 355)
$btnOpenKiosk.Size = New-Object System.Drawing.Size(245, 45)
$form.Controls.Add($btnOpenKiosk)

$btnOpenAdmin = New-Object System.Windows.Forms.Button
$btnOpenAdmin.Text = "⚙️ Open Admin Dashboard"
$btnOpenAdmin.Font = New-Object System.Drawing.Font("Segoe UI", 10.5, [System.Drawing.FontStyle]::Bold)
$btnOpenAdmin.BackColor = [System.Drawing.Color]::FromArgb(37, 44, 60)
$btnOpenAdmin.ForeColor = [System.Drawing.Color]::FromArgb(255, 234, 167)
$btnOpenAdmin.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnOpenAdmin.Location = New-Object System.Drawing.Point(280, 355)
$btnOpenAdmin.Size = New-Object System.Drawing.Size(245, 45)
$form.Controls.Add($btnOpenAdmin)

# Realtime Logs Box
$lblLog = New-Object System.Windows.Forms.Label
$lblLog.Text = "Live System Activity:"
$lblLog.Font = New-Object System.Drawing.Font("Segoe UI", 8.5, [System.Drawing.FontStyle]::Bold)
$lblLog.ForeColor = [System.Drawing.Color]::FromArgb(155, 166, 190)
$lblLog.Location = New-Object System.Drawing.Point(20, 412)
$lblLog.AutoSize = $true
$form.Controls.Add($lblLog)

$txtLog = New-Object System.Windows.Forms.TextBox
$txtLog.Multiline = $true
$txtLog.ScrollBars = "Vertical"
$txtLog.Font = New-Object System.Drawing.Font("Consolas", 8.5)
$txtLog.BackColor = [System.Drawing.Color]::FromArgb(14, 17, 23)
$txtLog.ForeColor = [System.Drawing.Color]::FromArgb(178, 190, 195)
$txtLog.Location = New-Object System.Drawing.Point(20, 432)
$txtLog.Size = New-Object System.Drawing.Size(505, 110)
$txtLog.ReadOnly = $true
$form.Controls.Add($txtLog)

$btnStopExit = New-Object System.Windows.Forms.Button
$btnStopExit.Text = "🛑 Stop All Services & Exit"
$btnStopExit.Font = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$btnStopExit.BackColor = [System.Drawing.Color]::FromArgb(45, 52, 54)
$btnStopExit.ForeColor = [System.Drawing.Color]::FromArgb(255, 118, 117)
$btnStopExit.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
$btnStopExit.Location = New-Object System.Drawing.Point(20, 552)
$btnStopExit.Size = New-Object System.Drawing.Size(505, 34)
$form.Controls.Add($btnStopExit)

function Add-Log($msg) {
    $time = (Get-Date).ToString("HH:mm:ss")
    $txtLog.AppendText("[$time] $msg`r`n")
}

# --- Background Services Starter ---
$phpProcess = $null
$cfProcess = $null
$logFile = "$env:TEMP\cf_gui_penpoint.log"
if (Test-Path $logFile) { Remove-Item $logFile -Force -ErrorAction SilentlyContinue }

# 1. Start MySQL
Add-Log "Checking MySQL..."
if (Get-Process "mysqld" -ErrorAction SilentlyContinue) {
    $lblMysql.Text = "● MySQL Database: Running (Port 3306)"
    $lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
    Add-Log "MySQL is already active."
} else {
    if (Test-Path "C:\xampp\mysql_start.bat") {
        Start-Process -FilePath "C:\xampp\mysql_start.bat" -WindowStyle Hidden
        $lblMysql.Text = "● MySQL Database: Started (Port 3306)"
        $lblMysql.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
        Add-Log "Started MySQL via XAMPP."
    }
}

# 2. Start PHP Built-in Server in Hidden Window
Add-Log "Starting PHP server on 127.0.0.1:8000..."
$phpPsi = New-Object System.Diagnostics.ProcessStartInfo
$phpPsi.FileName = "C:\xampp\php\php.exe"
$phpPsi.Arguments = "-S 127.0.0.1:8000 -t c:\Coding\EcoPoints\penpoint"
$phpPsi.WindowStyle = [System.Diagnostics.ProcessWindowStyle]::Hidden
$phpPsi.CreateNoWindow = $true
$phpPsi.UseShellExecute = $false
try {
    $phpProcess = [System.Diagnostics.Process]::Start($phpPsi)
    $lblPhp.Text = "● Local Web Server: Running on http://127.0.0.1:8000"
    $lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
    Add-Log "PHP Web Server initialized."
} catch {
    $lblPhp.Text = "● Local Web Server: Error starting PHP"
    $lblPhp.ForeColor = [System.Drawing.Color]::FromArgb(231, 76, 60)
    Add-Log "Failed to launch PHP: $_"
}

# 3. Start Cloudflare in Hidden Window
Add-Log "Establishing Cloudflare edge tunnel..."
$cfPsi = New-Object System.Diagnostics.ProcessStartInfo
$cfPsi.FileName = "C:\xampp\cloudflared.exe"
$cfPsi.Arguments = "tunnel --logfile `"$logFile`" --url http://127.0.0.1:8000"
$cfPsi.WindowStyle = [System.Diagnostics.ProcessWindowStyle]::Hidden
$cfPsi.CreateNoWindow = $true
$cfPsi.UseShellExecute = $false
try {
    $cfProcess = [System.Diagnostics.Process]::Start($cfPsi)
    Add-Log "Cloudflare process started. Negotiating public URL..."
} catch {
    $lblCloudflare.Text = "● Cloudflare: Error starting executable"
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
            $url = $match.Matches.Value
            $txtUrl.Text = "$url/public/index.php"
            $lblCloudflare.Text = "● Cloudflare Public Tunnel: Active & Connected"
            $lblCloudflare.ForeColor = [System.Drawing.Color]::FromArgb(46, 204, 113)
            Add-Log "Public URL Assigned: $url"
            [System.Windows.Forms.Clipboard]::SetText("$url/public/index.php")
            Add-Log "URL copied to clipboard automatically!"
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
    if ($txtUrl.Text -like "https*") {
        [System.Windows.Forms.Clipboard]::SetText($txtUrl.Text)
        $btnCopyUrl.Text = "✅ Copied to Clipboard!"
        Start-Sleep -Milliseconds 500
        $btnCopyUrl.Text = "📋 Copy Public Phone URL"
    }
})

$btnOpenPhoneUrl.Add_Click({
    if ($txtUrl.Text -like "https*") {
        [System.Diagnostics.Process]::Start($txtUrl.Text)
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
