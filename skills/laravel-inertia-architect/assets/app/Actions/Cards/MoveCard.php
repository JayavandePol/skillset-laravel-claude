<?php

declare(strict_types=1);

namespace App\Actions\Cards;

use App\Events\CardMoved;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Template Action: one business operation, one public method.
 * Reused by controllers, Filament actions, jobs, commands and tests.
 */
final class MoveCard
{
    public function handle(User $actor, Card $card, BoardList $target, ?Card $after = null): Card
    {
        return DB::transaction(function () use ($actor, $card, $target, $after): Card {
            $card->board_list_id = $target->id;
            $card->position = $this->positionAfter($target, $after);
            $card->save();

            CardMoved::dispatch($card, $actor);

            return $card;
        });
    }

    /**
     * Fractional indexing: place the card between $after and the next card,
     * so a move updates one row instead of renumbering the whole list.
     */
    private function positionAfter(BoardList $list, ?Card $after): string
    {
        $cards = $list->cards()->active()->orderBy('position');

        if ($after === null) {
            $first = $cards->value('position');

            return $first === null ? '1024' : bcdiv((string) $first, '2', 10);
        }

        $next = (clone $cards)->where('position', '>', $after->position)->value('position');

        return $next === null
            ? bcadd((string) $after->position, '1024', 10)
            : bcdiv(bcadd((string) $after->position, (string) $next, 10), '2', 10);
    }
}
