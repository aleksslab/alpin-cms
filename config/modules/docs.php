<?php
/**
 * Вкладка «Документация»: README плагинов и файлы из docs/.
 */

if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

require_once __DIR__ . '/../core/docs.php';

$docsCatalog = docs_catalog();
$docsKey = isset($_GET['doc']) ? trim((string)$_GET['doc']) : '';
$docsDoc = $docsKey !== '' ? docs_resolve($docsKey) : null;
$docsRender = $docsDoc !== null ? markdown_render($docsDoc['markdown']) : ['html' => '', 'toc' => []];
$docsToc = $docsRender['toc'];
$docsBody = $docsDoc !== null
    ? docs_rewrite_links($docsRender['html'], $docsDoc['source'], $docsCatalog)
    : '';
$docsPluginsWithReadme = count(array_filter($docsCatalog['plugins'], function ($docsItem) {
    return !empty($docsItem['path']);
}));
$docsTotal = $docsPluginsWithReadme + count($docsCatalog['user']) + count($docsCatalog['dev']);
$docsSources = [
    'plugin' => 'Плагин',
    'user'   => 'Руководство пользователя',
    'dev'    => 'Для разработчиков',
];
?>

<style>
    .docs-layout { display: grid; gap: 1.25rem; align-items: start; }
    .docs-nav { background: var(--bg-section); border: 1px solid var(--border-color); border-radius: 16px; padding: .75rem; }
    @media (min-width: 1024px) {
        .docs-layout { grid-template-columns: 270px minmax(0, 1fr); }
        .docs-nav { position: sticky; top: 6rem; max-height: calc(100vh - 8rem); overflow: auto; }
    }
    .docs-nav-group + .docs-nav-group { margin-top: .65rem; border-top: 1px solid var(--border-color); padding-top: .65rem; }
    .docs-nav-title { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: var(--text-muted); padding: .25rem .55rem; }
    .docs-nav-more { margin-top: .35rem; }
    .docs-nav-more summary { cursor: pointer; font-size: 11px; color: var(--text-muted); padding: .3rem .55rem; border-radius: 8px; }
    .docs-nav-more summary:hover { color: var(--text-main); }
    .docs-nav-more .docs-nav-link { font-size: 12px; padding: .3rem .55rem; }
    .docs-nav-link { display: block; padding: .45rem .6rem; border-radius: 10px; text-decoration: none; color: var(--text-muted); font-size: 13px; line-height: 1.35; border: 1px solid transparent; }
    .docs-nav-link:hover { background: var(--bg-card); color: var(--text-main); }
    .docs-nav-link.is-active { background: var(--bg-card); border-color: var(--border-color); color: var(--primary-color); font-weight: 600; }
    .docs-nav-link.is-disabled { opacity: .55; cursor: default; }
    .docs-nav-name { display: block; }
    .docs-nav-meta { display: block; font-size: 10px; color: var(--text-muted); margin-top: .15rem; }
    .docs-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.4rem 1.6rem; }
    .docs-head { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: 1rem; padding-bottom: .85rem; border-bottom: 1px solid var(--border-color); }
    .docs-source { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--primary-color); background: var(--bg-section); border: 1px solid var(--border-color); border-radius: 999px; padding: .2rem .6rem; }
    .docs-doc-meta { font-size: 12px; color: var(--text-muted); }
    .docs-note { font-size: 13px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: .65rem .85rem; margin-bottom: 1rem; }
    [data-theme="dark"] .docs-note { color: #fde68a; background: rgba(146, 64, 14, .18); border-color: rgba(253, 230, 138, .35); }
    .docs-empty { text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); }
    .docs-empty-icon { width: 52px; height: 52px; margin: 0 auto .75rem; border-radius: 16px; background: var(--bg-section); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); }
    .docs-toc { background: var(--bg-section); border: 1px solid var(--border-color); border-radius: 12px; padding: .55rem .9rem; margin-bottom: 1rem; font-size: 13px; }
    .docs-toc summary { cursor: pointer; font-weight: 600; color: var(--text-main); }
    .docs-toc ol { margin: .5rem 0 .25rem; padding-left: 1.1rem; list-style: decimal; }
    .docs-toc li { margin: .15rem 0; }
    .docs-toc li.docs-toc-l3 { margin-left: .9rem; list-style: circle; }
    .docs-toc a { color: var(--text-muted); text-decoration: none; }
    .docs-toc a:hover { color: var(--primary-color); text-decoration: underline; }
    .docs-md { font-size: 14px; line-height: 1.7; color: var(--text-main); overflow-wrap: anywhere; }
    .docs-md .docs-h { scroll-margin-top: 6rem; }
    .docs-md .docs-h1 { font-size: 1.5rem; line-height: 1.3; font-weight: 700; color: var(--text-main); margin: 0 0 1rem; }
    .docs-md .docs-h2 { font-size: 1.2rem; line-height: 1.35; font-weight: 700; color: var(--text-main); margin: 1.75rem 0 .65rem; }
    .docs-md .docs-h3 { font-size: 1.03rem; font-weight: 700; color: var(--text-main); margin: 1.4rem 0 .5rem; }
    .docs-md .docs-h4, .docs-md .docs-h5, .docs-md .docs-h6 { font-size: .95rem; font-weight: 700; color: var(--text-main); margin: 1.1rem 0 .4rem; }
    .docs-md .docs-p { margin: .7rem 0; }
    .docs-md .docs-list { margin: .5rem 0; padding-left: 1.3rem; list-style: disc; }
    .docs-md ol.docs-list { list-style: decimal; }
    .docs-md .docs-list li { margin: .25rem 0; }
    .docs-md .docs-list .docs-list { margin: .3rem 0; }
    .docs-md .docs-link { color: var(--primary-color); text-decoration: underline; text-underline-offset: 2px; }
    .docs-md .docs-inline-code { background: var(--bg-section); border: 1px solid var(--border-color); border-radius: 6px; padding: .05rem .35rem; font-size: .9em; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .docs-md .docs-pre { background: #0f172a; color: #e2e8f0; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; overflow: auto; margin: 1rem 0; font-size: 12.5px; line-height: 1.55; }
    .docs-md .docs-pre code { background: none; border: 0; color: inherit; padding: 0; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .docs-md .docs-table-wrap { overflow-x: auto; margin: 1rem 0; }
    .docs-md .docs-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .docs-md .docs-table th, .docs-md .docs-table td { border: 1px solid var(--border-color); padding: .5rem .7rem; text-align: left; vertical-align: top; }
    .docs-md .docs-table th { background: var(--bg-section); font-weight: 600; color: var(--text-main); }
    .docs-md .docs-table tr:nth-child(even) td { background: var(--bg-section); }
    .docs-md .docs-cell-center { text-align: center; }
    .docs-md .docs-cell-right { text-align: right; }
    .docs-md .docs-quote { margin: 1rem 0; padding: .35rem 0 .35rem 1rem; border-left: 3px solid var(--border-color); color: var(--text-muted); }
    .docs-md .docs-hr { border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0; }
    .docs-md .docs-task { display: inline-flex; align-items: center; gap: .4rem; }
</style>

<h1 class="text-3xl font-bold text-slate-800 mb-2">Документация</h1>
<p class="text-slate-500 text-sm mb-6">README установленных плагинов и руководства из <code class="font-mono bg-white px-1 rounded border border-slate-200">docs/</code>. README обновляются вместе с плагином, документы открываются только для чтения.</p>

<div class="docs-layout">
    <aside class="docs-nav">
        <div class="docs-nav-group">
            <?php
            $docsWithReadme = [];
            $docsWithoutReadme = [];

            foreach ($docsCatalog['plugins'] as $docsItem) {
                if (!empty($docsItem['path'])) {
                    $docsWithReadme[] = $docsItem;
                } else {
                    $docsWithoutReadme[] = $docsItem;
                }
            }
            ?>
            <div class="docs-nav-title">Плагины (<?php echo count($docsCatalog['plugins']); ?>)</div>
            <?php if (!$docsCatalog['plugins']): ?>
                <div class="docs-nav-link is-disabled">Плагинов нет</div>
            <?php endif; ?>
            <?php foreach ($docsWithReadme as $docsItem): ?>
                <?php
                $docsMeta = [];

                if ($docsItem['version'] !== '') {
                    $docsMeta[] = 'v' . $docsItem['version'];
                }

                $docsMeta[] = $docsItem['active'] ? 'включён' : 'выключен';
                ?>
                <a class="docs-nav-link<?php echo $docsKey === $docsItem['key'] ? ' is-active' : ''; ?>"
                   href="index.php?tab=docs&amp;doc=<?php echo urlencode($docsItem['key']); ?>">
                    <span class="docs-nav-name"><?php echo e($docsItem['title']); ?></span>
                    <span class="docs-nav-meta"><?php echo e(implode(' · ', $docsMeta)); ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($docsWithoutReadme): ?>
                <details class="docs-nav-more">
                    <summary>Без README (<?php echo count($docsWithoutReadme); ?>)</summary>
                    <?php foreach ($docsWithoutReadme as $docsItem): ?>
                        <div class="docs-nav-link is-disabled" title="Плагин не содержит README.md">
                            <span class="docs-nav-name"><?php echo e($docsItem['title']); ?></span>
                            <span class="docs-nav-meta">v<?php echo e($docsItem['version'] !== '' ? $docsItem['version'] : '—'); ?> · <?php echo $docsItem['active'] ? 'включён' : 'выключен'; ?></span>
                        </div>
                    <?php endforeach; ?>
                </details>
            <?php endif; ?>
        </div>

        <div class="docs-nav-group">
            <div class="docs-nav-title">Руководства (<?php echo count($docsCatalog['user']); ?>)</div>
            <?php foreach ($docsCatalog['user'] as $docsItem): ?>
                <a class="docs-nav-link<?php echo $docsKey === $docsItem['key'] ? ' is-active' : ''; ?>"
                   href="index.php?tab=docs&amp;doc=<?php echo urlencode($docsItem['key']); ?>">
                    <span class="docs-nav-name"><?php echo e($docsItem['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="docs-nav-group">
            <div class="docs-nav-title">Для разработчиков (<?php echo count($docsCatalog['dev']); ?>)</div>
            <?php foreach ($docsCatalog['dev'] as $docsItem): ?>
                <a class="docs-nav-link<?php echo $docsKey === $docsItem['key'] ? ' is-active' : ''; ?>"
                   href="index.php?tab=docs&amp;doc=<?php echo urlencode($docsItem['key']); ?>">
                    <span class="docs-nav-name"><?php echo e($docsItem['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="docs-card">
        <?php if ($docsKey !== '' && $docsDoc === null): ?>
            <div class="docs-note">Документ не найден или недоступен.</div>
        <?php endif; ?>

        <?php if ($docsDoc === null): ?>
            <div class="docs-empty">
                <div class="docs-empty-icon"><span class="icon-book-open text-xl"></span></div>
                <p class="text-sm font-semibold text-slate-700 mb-1">Выберите документ слева</p>
                <p class="text-xs text-slate-500">
                    <?php echo (int)$docsTotal; ?> документов: README плагинов, руководства пользователя и материалы для разработчиков.
                </p>
            </div>
        <?php else: ?>
            <div class="docs-head">
                <span class="docs-source"><?php echo e($docsSources[$docsDoc['source']] ?? 'Документ'); ?></span>
                <?php if ($docsDoc['meta'] !== ''): ?>
                    <span class="docs-doc-meta"><?php echo e($docsDoc['meta']); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($docsDoc['truncated'])): ?>
                <div class="docs-note">
                    Файл больше <?php echo (int)(DOCS_MAX_BYTES / 1024); ?> КБ — показано начало.
                </div>
            <?php endif; ?>

            <?php if (count($docsToc) > 1): ?>
                <details class="docs-toc"<?php echo count($docsToc) <= 12 ? ' open' : ''; ?>>
                    <summary>Содержание</summary>
                    <ol>
                        <?php foreach ($docsToc as $docsTocItem): ?>
                            <li class="docs-toc-l<?php echo (int)$docsTocItem['level']; ?>">
                                <a href="#<?php echo e($docsTocItem['id']); ?>"><?php echo e($docsTocItem['title']); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </details>
            <?php endif; ?>

            <article class="docs-md">
                <?php if (!preg_match('/^#\s+/m', $docsDoc['markdown'])): ?>
                    <h1 class="docs-h docs-h1"><?php echo e($docsDoc['title']); ?></h1>
                <?php endif; ?>
                <?php echo $docsBody; ?>
            </article>
        <?php endif; ?>
    </section>
</div>
