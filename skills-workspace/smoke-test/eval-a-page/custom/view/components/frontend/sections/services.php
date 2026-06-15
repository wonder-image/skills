<?php
    /**
     *
     * Sezione lista servizi.
     *
     * Argomenti opzionali:
     * - title    : titolo della sezione (default: pages.servizi.content.services.title)
     * - subtitle : sottotitolo della sezione (default: pages.servizi.content.services.subtitle)
     * - items    : array di chiavi servizio in lang/it/pages.json sotto
     *              pages.servizi.content.services.items.* (default: i tre servizi standard)
     *
     */

    $title    = $title    ?? __t('pages.servizi.content.services.title');
    $subtitle = $subtitle ?? __t('pages.servizi.content.services.subtitle');
    $items    = $items    ?? ['one', 'two', 'three'];

?>
<section id="services">
    <div class="content">

        <div class="a-c mb-4">
            <div class="subtitle"><?= $title ?></div>
            <div class="text mt-3"><?= $subtitle ?></div>
        </div>

        <div class="d-grid col-3 col-p-1 gap-5 w-100">

            <?php foreach ($items as $item) : ?>
                <div class="col-1 col-p-1 b-r-5 p-5">
                    <div class="subtitle"><?= __t('pages.servizi.content.services.items.'.$item.'.title') ?></div>
                    <div class="text mt-3"><?= __t('pages.servizi.content.services.items.'.$item.'.description') ?></div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>
</section>
