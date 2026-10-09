<?php

declare(strict_types=1);

/*
 * Template Pest browser tests (pestphp/pest-plugin-browser, Playwright).
 * Selectors use data-test attributes: <div data-test="card-{ulid}">.
 * Verify method names against the current Pest browser-testing docs.
 */

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withWorkspace()->create();
    $this->workspace = $this->user->currentWorkspace;
    $this->board = Board::factory()->for($this->workspace)->create(['name' => 'Roadmap']);
    [$this->todo, $this->done] = BoardList::factory()->count(2)->for($this->board)->create();
    $this->card = Card::factory()->for($this->todo, 'list')->create(['title' => 'Write tests']);

    $this->actingAs($this->user);
});

it('renders the board without JavaScript errors', function () {
    visit(route('boards.show', [$this->workspace, $this->board]))
        ->assertSee('Roadmap')
        ->assertSee('Write tests')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});

it('moves a card to another list with drag and drop', function () {
    visit(route('boards.show', [$this->workspace, $this->board]))
        ->drag("@card-{$this->card->ulid}", "@list-{$this->done->ulid}")
        ->assertSeeIn("@list-{$this->done->ulid}", 'Write tests');

    expect($this->card->fresh()->board_list_id)->toBe($this->done->id);
});

it('opens the card dialog and validates the title', function () {
    visit(route('boards.show', [$this->workspace, $this->board]))
        ->click("@card-{$this->card->ulid}")
        ->assertSee('Description')
        ->clear('@card-title')
        ->press('Save')
        ->assertSee('Title is required');
});

it('works on a mobile viewport', function () {
    visit(route('boards.show', [$this->workspace, $this->board]))
        ->on()->mobile()
        ->assertSee('Roadmap')
        ->assertNoJavaScriptErrors();
});

it('blocks members of other workspaces', function () {
    $this->actingAs(User::factory()->withWorkspace()->create());

    $this->get(route('boards.show', [$this->workspace, $this->board]))->assertForbidden();
});
