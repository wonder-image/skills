<?php

namespace App\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

/**
 * Tabella `project` — voci del portfolio lavori gestite dal backend.
 *
 * Ogni record è un progetto mostrato nel portfolio: nome, descrizione,
 * copertina immagine e flag di visibilità (visibile/nascosto). La colonna
 * `position` è dichiarata a parte perché non è un dato editato dall'utente
 * ma l'ordinamento manuale gestito dalla lista backend.
 */
final class Project extends Model
{
    public static string $table = 'project';
    public static string $folder = 'projects';
    public static string $icon = 'bi bi-collection';

    public static function tableSchema(): array
    {
        return [
            ...static::sqlColumnsFromDataSchema([
                'slug',
                'name',
                'description',
                'cover',
                'visible',
            ]),
            Column::key('position')->int()->null(true),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('slug')->text()->lower()->slug('name'),
            Field::key('name')->text()->sanitizeFirst(),
            Field::key('description')->text(),
            Field::key('cover')->upload()->image()->dir('/projects/cover/'),
            Field::key('visible')->text()->default('true'),
        ];
    }
}
