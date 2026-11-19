<?php
require_once dirname(__DIR__, 2) . '/includes/app.php';
$file = __DIR__ . '/output/results.json';
$data = is_file($file) ? json_decode(file_get_contents($file), true) : null;
?><!doctype html>
<html lang="en"><head><meta charset="UTF-8"><title>Scraping preview</title>
<link rel="stylesheet" href="../../assets/gallery.css">
<style>body{max-width:1100px;margin:2rem auto;padding:1rem;font-family:Arial,sans-serif}.paintings{display:flex;flex-wrap:wrap;gap:1rem}figure{margin:0;width:190px}figure img{width:190px;height:190px;object-fit:contain}section{margin:2rem 0}li{margin:.4rem 0}</style></head><body>
<h1>Scraping preview</h1><p>This page reads generated JSON only. Source links identify saved material.</p>
<?php if (!$data): ?><p>Run scripts/scraping/scraper.php to generate results.</p>
<?php else: ?><p>Generated: <?= art_h($data['generated_at']) ?>. SQL: <?= $data['ready_for_import'] ? 'generated; review missing profile fields before import' : 'blocked: incomplete paintings' ?>.</p>
<?php if (!empty($data['issues'])): ?><ul><?php foreach ($data['issues'] as $issue): ?><li><?= art_h($issue) ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php foreach ($data['artists'] as $a): ?><section>
<h2><?= art_h($a['requested_name']) ?></h2><p>Saved-page name: <?= art_h($a['name'] ?? 'Missing') ?></p>
<p>Born: <?= art_h($a['birth_date'] ?? 'Not found') ?> · Died: <?= art_h($a['death_date'] ?? 'Not found') ?> · Nationality: <?= art_h($a['nationality'] ?? 'Not found') ?></p>
<p><?= nl2br(art_h($a['biography'] ?? 'Biography not found in saved HTML.')) ?></p>
<p><?= count($a['paintings']) ?>/5 paintings. <a href="<?= art_h($a['source']) ?>">Artist source</a></p>
<?php if ($a['issues']): ?><ul><?php foreach ($a['issues'] as $issue): ?><li><?= art_h($issue) ?></li><?php endforeach; ?></ul><?php endif; ?>
<div class="paintings"><?php foreach ($a['paintings'] as $p): ?><figure>
<img src="../../<?= art_h(art_image($p['image_stem'])) ?>" alt="<?= art_h($p['title']) ?>">
<figcaption><a href="<?= art_h($p['source']) ?>"><?= art_h($p['title']) ?></a> (<?= art_h($p['year'] ?? 'Year unavailable') ?>)</figcaption>
<?php foreach (['genres'=>'Genre','medium'=>'Medium','dimensions'=>'Dimensions','style'=>'Style','original_title'=>'Original title','description'=>'Description'] as $key=>$label): ?>
<?php if (!empty($p[$key])): ?><p><strong><?= art_h($label) ?>:</strong> <?= art_h(is_array($p[$key]) ? implode(', ', $p[$key]) : $p[$key]) ?></p><?php endif; ?>
<?php endforeach; ?>
</figure><?php endforeach; ?></div></section><?php endforeach; endif; ?>
</body></html>
