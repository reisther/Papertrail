<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_attachments_render_inline_and_keep_access_checks(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $path = 'announcements/photo.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9S8AAAAASUVORK5CYII='
        ));
        $author = User::factory()->create(['role' => 'Teacher']);
        $other = User::factory()->create();
        $post = Announcement::create([
            'user_id' => $author->id,
            'message' => 'Photo announcement',
            'audience_type' => 'adviser_students',
            'attachment_path' => $path,
            'attachment_name' => 'photo.png',
        ]);
        $url = route('announcements.attachment', [$post->id, 'preview' => 1]);

        $this->actingAs($author)->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'inline; filename=photo.png');

        $html = view('partials.announcement-attachment', ['announcement' => $post])->render();
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('preview=1', $html);

        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_other_attachments_stay_download_links(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('announcements/notes.txt', 'Meeting notes');
        $author = User::factory()->create(['role' => 'Admin']);
        $post = Announcement::create([
            'user_id' => $author->id,
            'message' => 'Meeting',
            'audience_type' => 'global',
            'attachment_path' => 'announcements/notes.txt',
            'attachment_name' => 'notes.txt',
        ]);

        $html = view('partials.announcement-attachment', ['announcement' => $post])->render();
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('notes.txt', $html);
        $this->actingAs($author)
            ->get(route('announcements.attachment', [$post->id, 'preview' => 1]))
            ->assertDownload('notes.txt');
    }

    public function test_archive_is_private_and_only_author_can_restore(): void
    {
        $author = User::factory()->create(['role' => 'Teacher']);
        $other = User::factory()->create(['role' => 'Teacher']);
        $post = Announcement::create(['user_id' => $author->id, 'message' => 'Private archive content', 'audience_type' => 'global']);
        $this->actingAs($other)->post(route('announcements.archive', $post))->assertForbidden();
        $this->actingAs($author)->post(route('announcements.archive', $post))->assertRedirect();
        $this->assertNull(Announcement::find($post->id));
        $this->actingAs($other)->get(route('announcements.archived'))->assertDontSee('Private archive content');
        $this->post(route('announcements.restore', $post->id))->assertForbidden();
        $this->actingAs($author)->get(route('announcements.archived'))->assertSee('Private archive content');
        $this->post(route('announcements.restore', $post->id))->assertRedirect();
        $this->assertNotNull(Announcement::find($post->id));
    }

    public function test_deletion_has_a_thirty_day_recovery_period(): void
    {
        $author = User::factory()->create(['role' => 'Teacher']);
        $post = Announcement::create(['user_id' => $author->id, 'message' => 'Delete me', 'audience_type' => 'global']);
        $this->actingAs($author)->delete(route('announcements.destroy', $post))->assertRedirect();
        $this->assertNull(Announcement::find($post->id));
        $this->travel(29)->days();
        $this->post(route('announcements.restore', $post->id))->assertRedirect();
        $this->assertNotNull(Announcement::find($post->id));
        $this->delete(route('announcements.destroy', $post))->assertRedirect();
        $this->travel(30)->days();
        $this->post(route('announcements.restore', $post->id))->assertNotFound();
        $this->artisan('announcements:purge-deleted')->assertSuccessful();
        $this->assertDatabaseMissing('announcements', ['id' => $post->id]);
    }
}
