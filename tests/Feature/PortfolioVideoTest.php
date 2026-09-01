<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function makePortfolio(?string $videoUrl = null): Portfolio
    {
        return Portfolio::create([
            'title' => 'Motion Reel',
            'slug' => 'motion-reel',
            'is_active' => true,
            'video_url' => $videoUrl,
        ]);
    }

    public function test_video_urls_resolve_to_embed_urls(): void
    {
        $portfolio = $this->makePortfolio();

        $cases = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'https://vimeo.com/123456789' => 'https://player.vimeo.com/video/123456789',
        ];

        foreach ($cases as $input => $expected) {
            $portfolio->video_url = $input;
            $this->assertSame($expected, $portfolio->videoEmbedUrl(), $input);
        }

        $portfolio->video_url = 'https://example.com/not-a-video';
        $this->assertNull($portfolio->videoEmbedUrl());

        $portfolio->video_url = null;
        $this->assertNull($portfolio->videoEmbedUrl());
    }

    public function test_case_study_page_embeds_the_video_player(): void
    {
        $portfolio = $this->makePortfolio('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->get(route('portfolio.show', $portfolio))
            ->assertOk()
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', escape: false);
    }
}
