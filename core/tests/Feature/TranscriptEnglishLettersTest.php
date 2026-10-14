<?php

namespace Tests\Feature;

use App\Services\SarvamAiService;
use Tests\TestCase;

/** Transcripts in English letters (Sarvam translit mode): script detection and the keyword analysis on romanized speech. */
class TranscriptEnglishLettersTest extends TestCase
{
    public function test_indic_script_is_detected_and_english_letters_are_not()
    {
        $this->assertTrue(SarvamAiService::hasIndicScript('హలో హలో హలో'));
        $this->assertTrue(SarvamAiService::hasIndicScript('Hello ଆପଣ'));
        $this->assertFalse(SarvamAiService::hasIndicScript('Meeru ela unnaru? Hello sir'));
        $this->assertFalse(SarvamAiService::hasIndicScript(null));
    }

    public function test_translit_mode_is_configured_for_saaras_v3()
    {
        $this->assertSame('translit', config('services.sarvam.stt_mode'));
    }

    public function test_romanized_telugu_words_do_not_trigger_english_keywords()
    {
        $svc = new SarvamAiService();
        // "nannu", "ninnu", "nenu" contain "no"/"ne" letters but are not the English word "no"
        $this->assertNotSame('NEGATIVE', $svc->analyzeTranscript('Hello nannu ninnu nenu ippudu call chesthunnaanu')['sentiment']);
        $this->assertSame('NEGATIVE', $svc->analyzeTranscript('No, not interested, wrong number')['sentiment']);
        $res = $svc->analyzeTranscript('Meeru ela unnaru');
        $this->assertStringContainsString('Meeru ela unnaru', $res['summary'], 'summary quotes the English-letter transcript');
        $this->assertStringContainsString('Meeru ela unnaru', $res['key_points']);
    }
}
