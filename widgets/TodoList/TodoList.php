<?php

declare(strict_types=1);

namespace App\Widgets\TodoList;

use App\Exception\ValidationException;
use App\Widget\ActionWidgetInterface;
use App\Widget\WidgetContext;

final class TodoList implements ActionWidgetInterface
{
    private const int TEXT_MAX = 500;

    public static function type(): string
    {
        return 'todolist';
    }

    public static function label(): string
    {
        return 'Todo List';
    }

    public static function configSchema(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'default' => 'To-do',
            ],
        ];
    }

    public function render(array $config): string
    {
        $title = htmlspecialchars($config['title'] ?? 'To-do', ENT_QUOTES);

        return <<<HTML
        <div class="todo">
          <div class="todo__head">
            <span class="todo__title">{$title}</span>
          </div>

          <form class="todo__add" data-role="add-form">
            <input class="todo__input" data-role="input" type="text"
                   placeholder="Add a task…" maxlength="500" autocomplete="off" />
            <button class="todo__add-btn" type="submit" aria-label="Add task"></button>
          </form>

          <div class="todo__scroll">
            <ul class="todo__list" data-role="active"></ul>
            <p class="todo__empty" data-role="empty" hidden>Nothing to do — add a task above.</p>

            <section class="todo__done" data-role="done-wrap" hidden>
              <div class="todo__done-head">
                <span class="todo__done-label" data-role="done-label">Completed</span>
                <button class="todo__clear" data-role="clear" type="button">Clear</button>
              </div>
              <ul class="todo__list todo__list--done" data-role="completed"></ul>
            </section>
          </div>
        </div>
        HTML;
    }

    public function handleAction(WidgetContext $ctx): array
    {
        $store = new TodoStore($ctx->dataDir . '/todolist.sqlite');
        $instanceId = (int)$ctx->instance->id;

        return match ($ctx->action) {
            'list' => $store->snapshot($instanceId),
            'add' => $store->add($instanceId, self::text($ctx)),
            'toggle' => $store->toggle($instanceId, self::itemId($ctx)),
            'edit' => $store->edit($instanceId, self::itemId($ctx), self::text($ctx)),
            'delete' => $store->remove($instanceId, self::itemId($ctx)),
            'clear' => $store->clearCompleted($instanceId),
            default => throw new ValidationException('unknown action'),
        };
    }

    private static function text(WidgetContext $ctx): string
    {
        $text = trim((string)($ctx->body['text'] ?? ''));
        if ($text === '') {
            throw new ValidationException('text required');
        }
        return mb_substr($text, 0, self::TEXT_MAX);
    }

    private static function itemId(WidgetContext $ctx): int
    {
        $id = (int)($ctx->body['id'] ?? 0);
        if ($id <= 0) {
            throw new ValidationException('id required');
        }
        return $id;
    }
}
