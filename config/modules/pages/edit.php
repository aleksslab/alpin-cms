<?php
// --- config/modules/pages/edit.php ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$action = $_GET['action'] ?? 'create';
$pageId = $_GET['id'] ?? '';

$isEdit = ($action === 'edit' && !empty($pageId));
$editPage = null;

if ($isEdit) {
    $editPage = loadPageById($pageId);
    if (!$editPage) {
        header('Location: ?tab=pages');
        exit;
    }
}

$token = $_SESSION['csrf_token'] ?? '';

$pageTitle = $isEdit ? ($editPage['title'] ?? '') : ($_POST['title'] ?? '');
$pageSlug = $isEdit ? ($editPage['slug'] ?? '') : ($_POST['slug'] ?? '');
$pageStatus = $isEdit ? ($editPage['status'] ?? 'draft') : ($_POST['status'] ?? 'draft');
$pageMetaDesc = $isEdit ? ($editPage['meta']['description'] ?? '') : ($_POST['meta_description'] ?? '');
$pageMetaKeywords = $isEdit ? ($editPage['meta']['keywords'] ?? '') : ($_POST['meta_keywords'] ?? '');

$rowsJson = $_POST['rows_json'] ?? '';
$rows = [];
if (!empty($rowsJson)) {
    $rows = json_decode($rowsJson, true);
    if (!is_array($rows)) {
        $rows = [];
    }
} elseif ($isEdit && !empty($editPage['rows'])) {
    $rows = $editPage['rows'];
}

$formTitle = $isEdit ? 'Редактирование страницы' : 'Создание страницы';
$formAction = $isEdit ? 'edit' : 'create';
$submitButtonText = $isEdit ? 'Сохранить изменения' : 'Создать страницу';
$formActionUrl = '?tab=pages&action=' . $formAction . ($isEdit ? '&id=' . $pageId : '');
?>

<div class="flex items-center gap-4 mb-6">
    <a href="?tab=pages" class="text-slate-500 hover:text-slate-800 transition-colors">
        <span class="icon-arrow-left text-lg"></span>
    </a>
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">
            <span><?php echo e($formTitle); ?></span>
            <?php if ($isEdit && !empty($pageTitle)): ?>
                <span class="text-base font-medium text-slate-400 bg-slate-100 px-4 py-1.5 rounded-full">
                    <?php echo e($pageTitle); ?>
                </span>
            <?php endif; ?>
        </h1>
        <p class="text-slate-500 text-sm"><?php echo $isEdit ? 'Редактируйте содержимое страницы.' : 'Заполните поля для создания новой страницы.'; ?></p>
    </div>
</div>

<?php echo renderFlash(); ?>

<!-- ===== ФОРМА СТРАНИЦЫ ===== -->
<form method="POST" action="<?php echo $formActionUrl; ?>" class="space-y-6" id="page-edit-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <input type="hidden" name="save_page" value="1">
    <input type="hidden" name="page_action" value="<?php echo $formAction; ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="page_id" value="<?php echo e($pageId); ?>">
    <?php endif; ?>
    <input type="hidden" name="rows_json" id="rows-json-input" value='<?php echo htmlspecialchars(json_encode($rows, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'>
    
    <!-- ===== АККОРДЕОН ===== -->
    <div id="page-sections" class="space-y-3">
        
        <!-- СЕКЦИЯ 1: Основные настройки (ОТКРЫТА) -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
            <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
                <div class="flex items-center gap-3">
                    <span class="section-arrow icon-chevron-down text-xs text-slate-400"></span>
                    <span class="text-sm font-bold text-slate-700">Основные настройки</span>
                    <span class="text-[10px] text-slate-400">(заголовок, ЧПУ, статус)</span>
                </div>
            </div>
            <div class="section-content p-6 bg-slate-50">
                <div class="space-y-5">
                    <div class="editor-row" style="align-items: flex-start !important;">
                        <div class="editor-field">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Заголовок страницы <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" value="<?php echo e($pageTitle); ?>" required placeholder="Например: О компании" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]">
                        </div>
                        <div class="editor-field">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">ЧПУ (Slug) <span class="text-rose-500">*</span></label>
                            <input type="text" name="slug" id="slug-input" value="<?php echo e($pageSlug); ?>" placeholder="about" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]">
                            <div class="text-[10px] text-slate-400 mt-1">Только латиница, цифры, дефис и подчёркивание. Будет использован в URL: <span id="slug-preview" class="text-[var(--primary-color)] font-semibold">/<?php echo e($pageSlug); ?></span></div>
                        </div>
                    </div>
                    
                    <div class="editor-row">
                        <div class="editor-field">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Статус</label>
                            <label class="flex items-center gap-3 cursor-pointer mt-2">
                                <input type="checkbox" name="status" value="published" <?php echo ($pageStatus === 'published') ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                                <span class="text-sm font-medium text-slate-700">Опубликована</span>
                            </label>
                            <input type="hidden" name="status_hidden" value="draft">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- СЕКЦИЯ 2: SEO-настройки (ЗАКРЫТА) -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
            <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
                <div class="flex items-center gap-3">
                    <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
                    <span class="text-sm font-bold text-slate-700">SEO-настройки</span>
                    <span class="text-[10px] text-slate-400">(meta-описание, ключевые слова)</span>
                </div>
            </div>
            <div class="section-content p-6 bg-slate-50 hidden">
                <div class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Meta-описание</label>
                        <textarea name="meta_description" rows="3" placeholder="Краткое описание страницы для поисковых систем" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)] resize-y"><?php echo e($pageMetaDesc); ?></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">Рекомендуемая длина: 150-160 символов.</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Ключевые слова</label>
                        <input type="text" name="meta_keywords" value="<?php echo e($pageMetaKeywords); ?>" placeholder="ключевое слово, фраза, ещё фраза" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]">
                        <p class="text-[10px] text-slate-400 mt-1">Ключевые слова через запятую.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- СЕКЦИЯ 3: Конструктор страницы (ЗАКРЫТА) -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
            <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
                <div class="flex items-center gap-3">
                    <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
                    <span class="text-sm font-bold text-slate-700">Конструктор страницы</span>
                    <span class="text-[10px] text-slate-400">(ряды, колонки, модули)</span>
                </div>
            </div>
            <div class="section-content hidden">
                <?php 
                $constructorPage = $editPage;
                $renderConstructorOnly = true;
                include __DIR__ . '/constructor.php'; 
                ?>
            </div>
        </div>
        
    </div>
    <!-- ===== КОНЕЦ АККОРДЕОНА ===== -->
    
    <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-4 justify-between items-center">
        <a href="?tab=pages" class="w-full sm:w-auto px-6 py-4 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all text-center">Отмена</a>
        <button type="submit" class="w-full sm:w-auto bg-[var(--primary-color)] text-white font-bold px-8 py-4 rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 text-base">
            <?php echo e($submitButtonText); ?>
        </button>
    </div>
</form>
<!-- ===== КОНЕЦ ФОРМЫ ===== -->

<!-- ===== МОДАЛКИ (ВНЕ ФОРМЫ) ===== -->
<?php 
include __DIR__ . '/modals.php'; 
?>
<!-- ===== ПОДКЛЮЧЕНИЕ СКРИПТОВ ===== -->
<script src="js/main.js?v=<?php echo filemtime('js/main.js'); ?>"></script>
<script src="js/pages.js?v=<?php echo filemtime('js/pages.js'); ?>"></script>