<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleSsoTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_requires_oauth_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertServiceUnavailable();
    }

    public function test_google_redirect_sends_user_to_google_when_configured(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://spmi.example.test/auth/google/callback',
        ]);
        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_verified_polinema_google_account_logs_in_to_existing_spmi_user(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@polinema.ac.id',
            'email_verified_at' => null,
        ]);
        $this->fakeGoogleUser();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-user-123',
        ]);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[DataProvider('invalidGoogleIdentities')]
    public function test_untrusted_google_identity_is_rejected(
        string $email,
        bool $emailVerified,
    ): void {
        User::factory()->create(['email' => 'staff@polinema.ac.id']);
        $this->fakeGoogleUser([
            'email' => $email,
            'email_verified' => $emailVerified,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public static function invalidGoogleIdentities(): array
    {
        return [
            'unverified address' => ['staff@polinema.ac.id', false],
            'personal email address' => ['staff@gmail.com', true],
        ];
    }

    public function test_valid_polinema_account_must_already_exist_in_spmi(): void
    {
        $this->fakeGoogleUser();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_account_cannot_be_linked_to_a_different_existing_google_identity(): void
    {
        $user = User::factory()->create(['email' => 'staff@polinema.ac.id']);
        $user->forceFill(['google_id' => 'another-google-user'])->save();
        $this->fakeGoogleUser();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame('another-google-user', $user->fresh()->google_id);
    }

    private function fakeGoogleUser(array $attributes = []): void
    {
        Socialite::fake('google', SocialiteUser::fake(array_merge([
            'id' => 'google-user-123',
            'name' => 'Polinema Staff',
            'email' => 'staff@polinema.ac.id',
            'email_verified' => true,
            'hd' => 'polinema.ac.id',
        ], $attributes)));
    }
}
