<?php

namespace Canvas\Tests\Http\Controllers;

use Canvas\Models\Post;
use Canvas\Models\View;
use Canvas\Models\Visit;
use Canvas\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class StatsControllerTest.
 *
 * @covers \Canvas\Http\Controllers\StatsController
 */
class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUserStatsAreFetchedByDefault(): void
    {
        factory(Post::class, 3)->create([
            'user_id' => $this->admin->id,
        ])->each(function ($post) {
            $post->visits()->createMany(factory(Visit::class, 2)->make()->toArray());
            $post->views()->createMany(factory(View::class, 3)->make()->toArray());
        });

        factory(Post::class, 2)->create([
            'user_id' => $this->contributor->id,
        ])->each(function ($post) {
            $post->visits()->createMany(factory(Visit::class, 1)->make()->toArray());
            $post->views()->createMany(factory(View::class, 2)->make()->toArray());
        });

        $this->actingAs($this->admin, 'canvas')
             ->getJson('canvas/api/stats')
             ->assertSuccessful()
             ->decodeResponseJson()
             ->assertStructure([
                 'totalViews',
                 'totalVisits',
                 'traffic' => [
                     'views',
                     'visits',
                 ],
             ])
             ->assertFragment([
                 'totalVisits' => 6,
                 'totalViews' => 9,
             ]);
    }

    public function testAllPostsCanBeFetchedWithAGivenQueryScope(): void
    {
        factory(Post::class, 3)->create([
            'user_id' => $this->admin->id,
        ])->each(function ($post) {
            $post->visits()->createMany(factory(Visit::class, 2)->make()->toArray());
            $post->views()->createMany(factory(View::class, 3)->make()->toArray());
        });

        factory(Post::class, 2)->create([
            'user_id' => $this->contributor->id,
        ])->each(function ($post) {
            $post->visits()->createMany(factory(Visit::class, 1)->make()->toArray());
            $post->views()->createMany(factory(View::class, 2)->make()->toArray());
        });

        $this->actingAs($this->admin, 'canvas')
             ->getJson('canvas/api/stats?scope=all')
             ->assertSuccessful()
             ->decodeResponseJson()
             ->assertStructure([
                 'totalViews',
                 'totalVisits',
                 'traffic' => [
                     'views',
                     'visits',
                 ],
             ])
             ->assertFragment([
                 'totalVisits' => 8,
                 'totalViews' => 13,
             ]);
    }

    public function testAnAdminCanFetchStatsForAnyPost(): void
    {
        $post = factory(Post::class)->create([
            'user_id' => $this->contributor->id,
        ]);

        $this->actingAs($this->admin, 'canvas')
             ->getJson("canvas/api/stats/{$post->id}")
             ->assertSuccessful()
             ->decodeResponseJson()
             ->assertStructure([
                 'post',
                 'readTime',
                 'popularReadingTimes',
                 'topReferers',
                 'monthlyViews',
                 'totalViews',
                 'monthlyVisits',
                 'monthOverMonthViews' => [
                     'direction',
                     'percentage',
                 ],
                 'monthOverMonthVisits' => [
                     'direction',
                     'percentage',
                 ],
                 'traffic' => [
                     'views',
                     'visits',
                 ],
             ]);
    }

    public function testAnEditorCanFetchAnyPostStats(): void
    {
        $post = factory(Post::class)->create([
            'user_id' => $this->contributor->id,
        ]);

        $this->actingAs($this->editor, 'canvas')
             ->getJson("canvas/api/stats/{$post->id}")
             ->assertSuccessful()
             ->decodeResponseJson()
             ->assertStructure([
                 'post',
                 'readTime',
                 'popularReadingTimes',
                 'topReferers',
                 'monthlyViews',
                 'totalViews',
                 'monthlyVisits',
                 'monthOverMonthViews' => [
                     'direction',
                     'percentage',
                 ],
                 'monthOverMonthVisits' => [
                     'direction',
                     'percentage',
                 ],
                 'traffic' => [
                     'views',
                     'visits',
                 ],
             ]);
    }

    public function testAContributorCanFetchTheirOwnPostStats(): void
    {
        $post = factory(Post::class)->create([
            'user_id' => $this->contributor->id,
        ]);

        $this->actingAs($this->contributor, 'canvas')
             ->getJson("canvas/api/stats/{$post->id}")
             ->assertSuccessful()
             ->decodeResponseJson()
             ->assertStructure([
                 'post',
                 'readTime',
                 'popularReadingTimes',
                 'topReferers',
                 'monthlyViews',
                 'totalViews',
                 'monthlyVisits',
                 'monthOverMonthViews' => [
                     'direction',
                     'percentage',
                 ],
                 'monthOverMonthVisits' => [
                     'direction',
                     'percentage',
                 ],
                 'traffic' => [
                     'views',
                     'visits',
                 ],
             ]);
    }

    public function testAContributorIsUnableToAccessStatsForAnotherUser(): void
    {
        $post = factory(Post::class)->create([
            'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->contributor, 'canvas')
             ->getJson("canvas/api/stats/{$post->id}")
             ->assertNotFound();
    }

    public function testDraftPostsDoNotDisplayStats(): void
    {
        $post = factory(Post::class)->create([
            'published_at' => null,
        ]);

        $this->actingAs($this->admin, 'canvas')
             ->getJson("canvas/api/stats/{$post->id}")
             ->assertNotFound();
    }

    public function testScheduledPostsDoNotDisplayStats(): void
    {
        $post = factory(Post::class)->create([
            'published_at' => now()->addWeek(),
        ]);

        $this->actingAs($this->admin, 'canvas')
             ->getJson("canvas/api/stats/{$post->id}")
             ->assertNotFound();
    }

    public function testPostNotFound(): void
    {
        $this->actingAs($this->admin, 'canvas')
             ->getJson('canvas/api/stats/not-a-post')
             ->assertNotFound();
    }
}
