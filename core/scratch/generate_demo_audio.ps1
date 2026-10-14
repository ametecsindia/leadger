Add-Type -AssemblyName System.Speech

$tempWav = "C:\Users\MPS\.gemini\antigravity-ide\brain\6af63730-1fb2-4d30-9a53-52e46a9d9554\scratch\temp_demo_call.wav"
$outMp3 = "c:\Lt_Leadger_source - Copy 1\core\public\assets\uploads\recordings\leadger_demo_call.mp3"
$outMp3Client = "C:\Clients\lt_files\call_recordings\leadger_demo_call.mp3"
$outMp3Root = "c:\Lt_Leadger_source - Copy 1\core\leadger_demo_call.mp3"

# Ensure directories exist
$null = New-Item -ItemType Directory -Path (Split-Path $outMp3 -Parent) -Force
$null = New-Item -ItemType Directory -Path (Split-Path $outMp3Client -Parent) -Force
$null = New-Item -ItemType Directory -Path (Split-Path $tempWav -Parent) -Force

$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synth.SetOutputToWaveFile($tempWav)

# Set up voices
$femaleVoice = "Microsoft Zira Desktop"
$maleVoice = "Microsoft David Desktop"

# Dialogue sequence
$dialogue = @(
    @{ Voice = $femaleVoice; Text = "Hello, this is Akshitha calling from LeadGER. Am I speaking with Mr. Rahul?"; Pause = 800 },
    @{ Voice = $maleVoice;   Text = "Yes, this is Rahul. How can I help you?"; Pause = 700 },
    @{ Voice = $femaleVoice; Text = "I'm calling regarding your home loan inquiry. We have an option with a competitive interest rate and flexible repayment period."; Pause = 800 },
    @{ Voice = $maleVoice;   Text = "Okay, I'm interested. Please tell me the eligibility and documents required."; Pause = 700 },
    @{ Voice = $femaleVoice; Text = "Sure. We need identity proof, address proof, income documents, and recent bank statements. I can arrange a follow-up call tomorrow afternoon to explain the details."; Pause = 800 },
    @{ Voice = $maleVoice;   Text = "That works for me. Please call me tomorrow at three PM."; Pause = 700 },
    @{ Voice = $femaleVoice; Text = "Perfect. I have scheduled the follow-up for tomorrow at three PM. Thank you for your time."; Pause = 700 },
    @{ Voice = $maleVoice;   Text = "Thank you. I'll be available."; Pause = 500 }
)

foreach ($line in $dialogue) {
    $synth.SelectVoice($line.Voice)
    $synth.Rate = 0
    $synth.Speak($line.Text)
    # Pause between speakers
    Start-Sleep -Milliseconds ($line.Pause)
}

$synth.Dispose()

Write-Host "Wav file created at $tempWav"

# Convert to MP3 using ffmpeg
ffmpeg -y -i $tempWav -codec:a libmp3lame -qscale:a 2 $outMp3
Copy-Item $outMp3 $outMp3Client -Force
Copy-Item $outMp3 $outMp3Root -Force

Write-Host "MP3 generated successfully at $outMp3"
