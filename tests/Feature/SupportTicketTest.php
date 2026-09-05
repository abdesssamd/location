<?php

use App\Models\Store;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\StoreContext;
use App\Services\SupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class]);
    $this->store = Store::firstOrFail();
    $this->user = User::where('store_id', $this->store->id)->firstOrFail();
    $this->superAdmin = User::where('is_super_admin', true)->firstOrFail();
    StoreContext::set($this->store->id);
});

it('un magasin ouvre un ticket de support', function () {
    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Support\SupportCenter::class)
        ->call('startNew')
        ->set('subject', 'Impossible d imprimer un contrat')
        ->set('category', 'bug')
        ->set('priority', SupportTicket::PRIORITY_HIGH)
        ->set('body', 'Le PDF reste bloque sur une page blanche.')
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = SupportTicket::where('store_id', $this->store->id)->firstOrFail();

    expect($ticket->subject)->toBe('Impossible d imprimer un contrat');
    expect($ticket->reference)->toStartWith('SUP-');
    expect($ticket->status)->toBe(SupportTicket::STATUS_OPEN);
    expect($ticket->messages)->toHaveCount(1);
    // Le support doit voir qu'un message l'attend.
    expect($ticket->unread_for_admin)->toBe(1);
    expect($ticket->unread_for_store)->toBe(0);
});

it('joint une photo au ticket et la stocke sur le disque prive', function () {
    Storage::fake('local');

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Support\SupportCenter::class)
        ->set('subject', 'Bug avec capture')
        ->set('body', 'Voici ce que je vois.')
        ->set('attachments', [UploadedFile::fake()->image('capture.png')])
        ->call('submit')
        ->assertHasNoErrors();

    $message = SupportMessage::firstOrFail();

    expect($message->attachment_paths)->toHaveCount(1);
    expect($message->attachment_paths[0])->toStartWith('support/'.$this->store->id);
    Storage::disk('local')->assertExists($message->attachment_paths[0]);
});

it('le support repond et le ticket passe en cours', function () {
    $ticket = SupportService::open($this->store->id, $this->user, 'Sujet', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\SupportInbox::class)
        ->call('openTicket', $ticket->id)
        ->set('reply', 'Bonjour, pouvez-vous preciser votre navigateur ?')
        ->call('sendReply')
        ->assertHasNoErrors();

    $ticket->refresh();

    expect($ticket->messages)->toHaveCount(2);
    expect($ticket->status)->toBe(SupportTicket::STATUS_PENDING);
    // La reponse est non lue cote magasin, et lue cote support.
    expect($ticket->unread_for_store)->toBe(1);
    expect($ticket->unread_for_admin)->toBe(0);
    expect($ticket->messages->last()->author_type)->toBe(SupportMessage::AUTHOR_SUPPORT);
});

it('ouvrir un ticket remet son compteur de non-lus a zero', function () {
    $ticket = SupportService::open($this->store->id, $this->user, 'Sujet', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);
    SupportService::reply($ticket, $this->superAdmin, 'Reponse du support', fromSupport: true);

    expect($ticket->fresh()->unread_for_store)->toBe(1);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\Support\SupportCenter::class)
        ->call('openTicket', $ticket->id);

    expect($ticket->fresh()->unread_for_store)->toBe(0);
});

it('le support change le statut d un ticket', function () {
    $ticket = SupportService::open($this->store->id, $this->user, 'Sujet', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Admin\SupportInbox::class)
        ->call('openTicket', $ticket->id)
        ->call('changeStatus', SupportTicket::STATUS_RESOLVED);

    $ticket->refresh();

    expect($ticket->status)->toBe(SupportTicket::STATUS_RESOLVED);
    expect($ticket->resolved_at)->not->toBeNull();
});

it('repondre a un ticket resolu le rouvre', function () {
    // Le probleme revient : le magasin ne doit pas avoir a recreer un ticket.
    $ticket = SupportService::open($this->store->id, $this->user, 'Sujet', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);
    SupportService::changeStatus($ticket, SupportTicket::STATUS_RESOLVED);

    SupportService::reply($ticket->fresh(), $this->user, 'Le probleme est revenu.', fromSupport: false);

    $ticket->refresh();

    expect($ticket->status)->toBe(SupportTicket::STATUS_OPEN);
    expect($ticket->resolved_at)->toBeNull();
});

it('un magasin ne voit pas les tickets d un autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre Sup', 'slug' => 'autre-sup', 'token' => 'tok-sup', 'status' => 'active']);
    \App\Services\SubscriptionService::createSubscription($otherStore, \App\Models\Plan::where('slug', 'pro')->firstOrFail(), \App\Models\Subscription::STATUS_ACTIVE);
    \App\Models\StoreToken::issue($otherStore->id);

    $otherUser = User::create(['store_id' => $otherStore->id, 'name' => 'Autre', 'email' => 'autre-sup@test.com', 'password' => 'password', 'is_active' => true]);
    $otherUser->assignRole('admin');

    StoreContext::set($otherStore->id);
    $foreign = SupportService::open($otherStore->id, $otherUser, 'Ticket confidentiel', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);
    StoreContext::set($this->store->id);

    SupportService::open($this->store->id, $this->user, 'Mon ticket', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    $this->actingAs($this->user)
        ->get(route('support.index'))
        ->assertOk()
        ->assertSee('Mon ticket')
        ->assertDontSee('Ticket confidentiel');

    // Et l'acces direct au fil est impossible : le scope tenant rend le ticket
    // introuvable, sans meme reveler qu'il existe.
    expect(fn () => Livewire::actingAs($this->user)
        ->test(\App\Livewire\Support\SupportCenter::class)
        ->call('openTicket', $foreign->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('une piece jointe n est pas accessible depuis un autre magasin', function () {
    $otherStore = Store::create(['name' => 'Autre Sup B', 'slug' => 'autre-sup-b', 'token' => 'tok-sup-b', 'status' => 'active']);
    $otherUser = User::create(['store_id' => $otherStore->id, 'name' => 'Autre B', 'email' => 'autre-sup-b@test.com', 'password' => 'password', 'is_active' => true]);
    $otherUser->assignRole('admin');

    StoreContext::set($otherStore->id);
    $ticket = SupportService::open($otherStore->id, $otherUser, 'Avec piece jointe', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL, ['support/'.$otherStore->id.'/secret.webp']);
    StoreContext::set($this->store->id);

    $message = $ticket->messages()->first();

    $this->actingAs($this->user)
        ->get(route('files.support', [$message, 0]))
        ->assertForbidden();
});

it('le super admin voit les tickets de tous les magasins', function () {
    $otherStore = Store::create(['name' => 'Magasin B', 'slug' => 'magasin-b-sup', 'token' => 'tok-sup-c', 'status' => 'active']);
    $otherUser = User::create(['store_id' => $otherStore->id, 'name' => 'User B', 'email' => 'user-b-sup@test.com', 'password' => 'password', 'is_active' => true]);
    $otherUser->assignRole('admin');

    StoreContext::set($otherStore->id);
    SupportService::open($otherStore->id, $otherUser, 'Ticket magasin B', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);
    StoreContext::set($this->store->id);
    SupportService::open($this->store->id, $this->user, 'Ticket magasin A', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    $component = Livewire::actingAs($this->superAdmin)->test(\App\Livewire\Admin\SupportInbox::class);

    expect($component->viewData('tickets')->pluck('subject'))
        ->toContain('Ticket magasin A')
        ->toContain('Ticket magasin B');
});

it('un utilisateur de magasin ne peut pas ouvrir la boite du support', function () {
    $this->actingAs($this->user)->get(route('admin.support.index'))->assertForbidden();
});

it('tous les roles de magasin peuvent ouvrir un ticket', function () {
    foreach (['manager', 'cashier', 'storekeeper', 'employee'] as $roleName) {
        $user = User::create([
            'store_id' => $this->store->id, 'name' => ucfirst($roleName),
            'email' => $roleName.'-support@test.com', 'password' => 'password', 'is_active' => true,
        ]);
        $user->assignRole($roleName);

        expect($user->can('support.create'))->toBeTrue();
        $this->actingAs($user)->get(route('support.index'))->assertOk();
    }
});

it('genere des references uniques entre magasins', function () {
    // La reference est unique sur toute la base : son calcul doit ignorer le
    // scope tenant, sinon deux magasins tombent sur le meme numero.
    $otherStore = Store::create(['name' => 'Magasin Ref', 'slug' => 'magasin-ref', 'token' => 'tok-ref', 'status' => 'active']);
    $otherUser = User::create(['store_id' => $otherStore->id, 'name' => 'User Ref', 'email' => 'user-ref@test.com', 'password' => 'password', 'is_active' => true]);
    $otherUser->assignRole('admin');

    StoreContext::set($otherStore->id);
    $first = SupportService::open($otherStore->id, $otherUser, 'Premier', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    StoreContext::set($this->store->id);
    $second = SupportService::open($this->store->id, $this->user, 'Second', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    expect($second->reference)->not->toBe($first->reference);
});

it('compte les tickets non lus pour la pastille du menu', function () {
    $ticket = SupportService::open($this->store->id, $this->user, 'Sujet', 'Corps', 'bug', SupportTicket::PRIORITY_NORMAL);

    expect(SupportService::unreadCountForAdmin())->toBe(1);
    expect(SupportService::unreadCountForStore($this->store->id))->toBe(0);

    SupportService::reply($ticket, $this->superAdmin, 'Reponse', fromSupport: true);

    expect(SupportService::unreadCountForAdmin())->toBe(0);
    expect(SupportService::unreadCountForStore($this->store->id))->toBe(1);
});
