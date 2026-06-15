<?php

namespace App\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\FormInput;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\ResourceSchema\TableLayoutSchema;

/**
 * Backend CRUD per i progetti del portfolio.
 *
 * Espone la lista, il form di creazione/modifica, le route API e la voce
 * di menu nella sezione "Contenuti". La copertina usa l'uploader
 * drag&drop del design system; la visibilità è un select true/false.
 */
final class ProjectResource extends Resource
{
    public static string $model = \App\Models\Project::class;

    public static string $orderColumn = 'position';
    public static string $orderDirection = 'ASC';

    public static function path(): string
    {
        return static::$model::$folder;
    }

    public static function icon(): string
    {
        return static::$model::$icon;
    }

    public static function textSchema(): array
    {
        return [
            'label' => 'progetto',
            'plural_label' => 'progetti',
            'article' => 'il',
            'this' => 'questo',
        ];
    }

    public static function labelSchema(): array
    {
        return [
            'name' => 'Nome',
            'description' => 'Descrizione',
            'cover' => 'Copertina',
            'visible' => 'Visibilità',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormInput::key('name')->text()->required(),
            FormInput::key('description')->textarea(),
            FormInput::key('cover')->fileDragDrop('image', 'classic'),
            FormInput::key('visible')->select([
                'true'  => 'Visibile',
                'false' => 'Nascosto',
            ])->value('true')->required(),
        ];
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('cover')->image()->size('little'),
            TableColumn::key('name')->text()->link('edit'),
            TableColumn::key('visible')->badge()->size('little'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Lista '.static::pluralLabel())
            ->results()
            ->buttonAdd('Aggiungi '.static::label())
            ->filters()
            ->searchFields(['name', 'description']);
    }

    public static function apiSchema(): ApiSchema
    {
        return ApiSchema::for(static::class)
            ->fields('index', ['id', 'slug', 'name', 'description', 'cover', 'visible']);
    }

    public static function permissionSchema(): PermissionSchema
    {
        return PermissionSchema::for(static::class)
            ->backendCrud(['admin', 'administrator'])
            ->apiCrud(['admin', 'administrator']);
    }

    public static function navigationSchema(): NavigationSchema
    {
        // Dichiara la sezione top-level "Contenuti". Se un'altra Resource
        // l'ha già dichiarata, sostituire con `->inSection('content')`.
        return NavigationSchema::for(static::class)
            ->section('content', 'Contenuti', 'bi-collection', 500, ['admin', 'administrator'])
            ->title('Progetti')
            ->order(30);
    }
}
