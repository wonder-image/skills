<?php

    $SEO->title       = __t('pages.servizi.seo.title');
    $SEO->description = __t('pages.servizi.seo.description');
    $SEO->url         = __r('servizi');
    $SEO->breadcrumb  = [];

    \Wonder\View\View::layout('frontend.main');

?>

<section class="intro">
    <div class="content">
        <h1 class="title-big"><?=__t('pages.servizi.content.hero.title')?></h1>
        <p class="text mt-5"><?=__t('pages.servizi.content.hero.subtitle')?></p>
    </div>
</section>

<?=\Wonder\View\View::component('frontend.sections.services')?>

<?=\Wonder\View\View::component('frontend.sections.contact-form')?>

<?php \Wonder\View\View::end(); ?>
