<?php

namespace APP\plugins\themes\smkTheme;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Submission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\core\PKPApplication;
use PKP\plugins\Hook;
use PKP\plugins\ThemePlugin;
use PKP\statistics\PKPStatisticsHelper;
use PKP\submissionFile\SubmissionFile;

class SmkThemePlugin extends ThemePlugin
{

    private const STATS_CACHE_TTL = 21600;

    private const ARTICLE_METRICS_CACHE_TTL = 600;

    private const ARTICLE_META_CACHE_TTL = 86400;

    private const REF_LINKS_CACHE_TTL = 2592000;

    private const ACTIVATION_SALT = '8f07125009f8052c7f4111e3a2a32fe0';
    private const ACTIVATION_HASH = 'bfc414dfa539c2c885648c27b1714461c18f907a72b4a421f7baf0da7859e8c2';

    private ?array $requestUrlsCache = null;

    public function init(): void
    {
        $this->setParent('defaultthemeplugin');

        $this->registerThemeOptions();

        if (!$this->isThemeActivated()) {
            Hook::add('TemplateManager::display', [$this, 'renderLockedNoticeData']);
            return;
        }

        if (method_exists($this, 'removeStyle')) {
            $this->removeStyle('font');
        }

        $this->addStyle('smk-custom', 'styles/custom.css?v=' . $this->assetVersion('styles/custom.css'));
        $this->addScript('smk-logout-guard', 'js/kisi-logout-guard.js?v=' . $this->assetVersion('js/kisi-logout-guard.js'), ['contexts' => ['frontend', 'backend']]);
        $this->addScript('smk-theme', 'js/kisi-theme.js?v=' . $this->assetVersion('js/kisi-theme.js'));

        if (!headers_sent()) {
            header('Cache-Control: private, no-cache, no-store, must-revalidate, max-age=0', true);
            header('Pragma: no-cache', true);
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT', true);
        }

        Hook::add('TemplateManager::display', [$this, 'loadTemplateData']);
    }

    private function assetVersion(string $relativePath): string
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return is_file($filePath) ? (string) filemtime($filePath) : '1';
    }

    private function isThemeActivated(): bool
    {
        $rawToken = trim((string) $this->getOption('activationToken'));
        if ($rawToken === '' && class_exists(\PKP\config\Config::class)) {
            $rawToken = trim((string) \PKP\config\Config::getVar('kisi', 'activation_token', ''));
        }
        if ($rawToken === '') {
            return false;
        }

        $candidateHash = hash('sha256', self::ACTIVATION_SALT . ':' . strtoupper($rawToken));
        return hash_equals(self::ACTIVATION_HASH, $candidateHash);
    }

    public function renderLockedNoticeData(string $hookName, array $args): int
    {
        $templateMgr = $args[0] ?? null;
        if ($templateMgr) {
            $templateMgr->assign('kisiThemeActivated', false);
        }
        return Hook::CONTINUE;
    }

    public function getDisplayName(): string
    {
        return __('plugins.themes.smkTheme.name');
    }

    public function getDescription(): string
    {
        return __('plugins.themes.smkTheme.description');
    }

    private function registerThemeOptions(): void
    {
        $this->addOption('activationToken', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.activationToken.label'),
            'description' => __('plugins.themes.smkTheme.option.activationToken.description'),
            'default' => '',
        ]);

        $this->addOption('heroScopeText', 'FieldTextarea', [
            'label' => __('plugins.themes.smkTheme.option.heroScopeText.label'),
            'description' => __('plugins.themes.smkTheme.option.heroScopeText.description'),
            'default' => 'Every Result Matters to Enhance Our Perspective, Policy, and Practice in Health, Medicine, and Biomedicine.',
        ]);

        $this->addOption('heroPrimaryCtaLabel', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.heroPrimaryCtaLabel.label'),
            'default' => 'Submit your research',
        ]);

        $this->addOption('heroSecondaryCtaLabel', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.heroSecondaryCtaLabel.label'),
            'default' => 'Explore all articles',
        ]);

        $this->addOption('heroAcceptanceRate', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.heroAcceptanceRate.label'),
            'description' => __('plugins.themes.smkTheme.option.heroAcceptanceRate.description'),
            'default' => '75%',
        ]);

        $this->addOption('heroFirstDecisionDays', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.heroFirstDecisionDays.label'),
            'description' => __('plugins.themes.smkTheme.option.heroFirstDecisionDays.description'),
            'default' => '18',
        ]);

        $this->addOption('heroAcceptDays', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.heroAcceptDays.label'),
            'description' => __('plugins.themes.smkTheme.option.heroAcceptDays.description'),
            'default' => '41',
        ]);

        $this->addOption('heroShowStatsBar', 'FieldOptions', [
            'label' => __('plugins.themes.smkTheme.option.heroShowStatsBar.label'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.smkTheme.option.heroShowStatsBar.option'),
                ],
            ],
            'default' => true,
        ]);

        $this->addOption('statsLaunchLabel', 'FieldText', [
            'label' => __('plugins.themes.smkTheme.option.statsLaunchLabel.label'),
            'description' => __('plugins.themes.smkTheme.option.statsLaunchLabel.description'),
            'default' => 'January 2024',
        ]);
    }

    public function loadTemplateData(string $hookName, array $args): int
    {
        $templateMgr = $args[0] ?? null;
        $template = (string) ($args[1] ?? '');

        if (!$templateMgr) {
            return Hook::CONTINUE;
        }

        if (str_starts_with($template, 'layouts/backend') && method_exists($templateMgr, 'addJavaScript')) {
            $req = Application::get()->getRequest();
            $guardUrl = rtrim($req->getBaseUrl(), '/') . '/' . trim($this->getPluginPath(), '/') . '/js/kisi-logout-guard.js?v=' . $this->assetVersion('js/kisi-logout-guard.js');
            $templateMgr->addJavaScript('smk-logout-guard-backend', $guardUrl, ['contexts' => ['backend']]);
            return Hook::CONTINUE;
        }

        if (!$this->isFrontendTemplate($template)) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }

        $contextId = (int) $context->getId();
        $contextPath = $context->getPath();

        if ($this->requestUrlsCache === null) {
            $dispatcher = Application::get()->getDispatcher();
            $this->requestUrlsCache = [
                'journalHome' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'index'),
                'archive' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'issue', 'archive'),
                'sdgCategory' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'catalog', 'category', ['sdgs']),
                'editorialTeam' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'about', 'editorialTeam'),
                'login' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'login'),
                'signOut' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'login', 'signOut'),
                'register' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'user', 'register'),
                'statistics' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'statistics'),
                'sdgIconBase' => rtrim($request->getBaseUrl(), '/') . '/' . trim($this->getPluginPath(), '/') . '/assets/img/sdg/',
            ];
        }
        $urls = $this->requestUrlsCache;

        $payload = [
            'journalName' => (string) $context->getLocalizedName(),
            'urls' => $urls,
            'heroShowStatsBar' => (bool) $this->getOption('heroShowStatsBar'),
            'launchLabel' => trim((string) $this->getOption('statsLaunchLabel')) ?: 'January 2024',
        ];

        $protocol = strtolower((string) $request->getProtocol()) === 'https' ? 'https' : 'http';

        if ($template === 'frontend/pages/indexJournal.tpl') {
            $templateMgr->assign('kisiHeroOptions', $this->buildHeroOptions());
            $payload['pageType'] = 'home';
            $payload['homeStats'] = $this->getCachedData(
                "kisi_smk_home_stats_v3_{$contextId}_{$protocol}",
                fn () => $this->computeHomeStats($request, $contextPath, $contextId)
            );
        } elseif ($this->isStatisticsRequest($request, $templateMgr)) {
            $payload['pageType'] = 'statistics';
            $payload['fullStats'] = $this->getCachedData(
                "kisi_smk_full_stats_v3_{$contextId}_{$protocol}",
                fn () => $this->computeFullStatistics($request, $contextPath, $contextId)
            );
        } elseif (
            $template === 'frontend/pages/article.tpl'
            || str_ends_with($template, '/frontend/pages/article.tpl')
            || (
                $templateMgr->getTemplateVars('article')
                && str_ends_with($template, '/article.tpl')
            )
        ) {
            $payload['pageType'] = 'article';
            $payload['articleData'] = $this->computeArticlePageData($request, $contextPath, $contextId, $templateMgr);
        } elseif ($template === 'frontend/pages/catalogCategory.tpl') {
            $payload['pageType'] = 'catalogCategory';
            $payload['categoryArticleSdgs'] = $this->computeCatalogCategorySdgs($contextId, $templateMgr);
        }

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $templateMgr->assign('kisiThemeActivated', true);
        $templateMgr->assign('kisiThemeDataJson', json_encode($payload, $jsonFlags) ?: '{}');

        return Hook::CONTINUE;
    }

    private function isFrontendTemplate(string $template): bool
    {
        return str_starts_with($template, 'frontend/')
            || str_contains($template, 'staticPages')
            || str_ends_with($template, 'content.tpl');
    }

    private function isStatisticsRequest($request, $templateMgr): bool
    {
        $requestedPage = strtolower((string) $request->getRequestedPage());
        if (in_array($requestedPage, ['statistics', 'statistik', 'metrics'], true)) {
            return true;
        }

        $requestPath = strtolower((string) $request->getRequestPath());
        if (preg_match('#/(statistics|statistik|metrics)(/|$)#', $requestPath)) {
            return true;
        }

        $content = (string) ($templateMgr->getTemplateVars('content') ?? '');
        return str_contains($content, 'kisi-journal-stats-page');
    }

    private function buildHeroOptions(): array
    {
        $scope = trim((string) $this->getOption('heroScopeText'));
        if ($scope === '') {
            $scope = 'Every Result Matters to Enhance Our Perspective, Policy, and Practice in Health, Medicine, and Biomedicine.';
        }

        $primaryCta = trim((string) $this->getOption('heroPrimaryCtaLabel')) ?: 'Submit your research';
        $secondaryCta = trim((string) $this->getOption('heroSecondaryCtaLabel')) ?: 'Explore all articles';

        return [
            'scopeText' => $scope,
            'primaryCtaLabel' => $primaryCta,
            'secondaryCtaLabel' => $secondaryCta,
            'acceptanceRate' => trim((string) $this->getOption('heroAcceptanceRate')),
            'firstDecisionDays' => trim((string) $this->getOption('heroFirstDecisionDays')),
            'acceptDays' => trim((string) $this->getOption('heroAcceptDays')),
        ];
    }

    private function getCachedData(string $key, callable $callback, int $ttl = self::STATS_CACHE_TTL): array
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Throwable $e) {
            return $callback();
        }
    }

    private function computeHomeStats($request, string $contextPath, int $contextId): array
    {
        $dispatcher = Application::get()->getDispatcher();
        $submissions = $this->getCachedPublishedSubmissions($contextId);
        $issueTitlesById = $this->getPublishedIssueTitles($contextId);

        $researchersCount = $this->countUniquePublishedAuthors($contextId);
        $sdgArticlesCount = $this->countSdgPublishedArticles($contextId, $submissions);

        $allTimeTotals = $this->getCachedAllTimeMetrics($contextId);
        $grandViews = 0;
        $grandDownloads = 0;
        foreach ($submissions as $subId => $meta) {
            $m = $allTimeTotals[$subId] ?? ['views' => 0, 'downloads' => 0];
            $grandViews += $m['views'];
            $grandDownloads += $m['downloads'];
        }

        $monthStart = date('Y-m-01');
        $today = date('Y-m-d');
        $periodLabel = date('F Y');
        $periodMetrics = $this->fetchSubmissionMetricsMap($contextId, $monthStart, $today);

        if ($this->sumMetricsMap($periodMetrics) === 0) {
            $last30Start = date('Y-m-d', strtotime('-29 days'));
            $periodMetrics = $this->fetchSubmissionMetricsMap($contextId, $last30Start, $today);
            $periodLabel = 'Last 30 Days';
        }
        if ($this->sumMetricsMap($periodMetrics) === 0) {
            $periodMetrics = $allTimeTotals;
            $periodLabel = 'All-Time Top Read';
        }

        $ranked = [];
        foreach ($submissions as $subId => $meta) {
            $pm = $periodMetrics[$subId] ?? ['views' => 0, 'downloads' => 0];
            $am = $allTimeTotals[$subId] ?? ['views' => 0, 'downloads' => 0];
            $periodTotal = $pm['views'] + $pm['downloads'];
            $allTimeTotal = $am['views'] + $am['downloads'];
            if ($periodTotal === 0 && $allTimeTotal === 0) {
                continue;
            }
            $ranked[] = [
                'id' => $subId,
                'title' => $meta['title'],
                'url' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'article', 'view', [$meta['bestId']]),
                'issueTitle' => $issueTitlesById[$meta['issueId']] ?? 'Published Issue',
                'periodViews' => $pm['views'],
                'periodDownloads' => $pm['downloads'],
                'periodTotal' => $periodTotal,
                'allTimeTotal' => $allTimeTotal,
            ];
        }

        usort($ranked, static function (array $a, array $b): int {
            return ($b['periodTotal'] <=> $a['periodTotal']) ?: ($b['allTimeTotal'] <=> $a['allTimeTotal']);
        });

        return [
            'researchersCount' => $researchersCount,
            'combinedImpact' => $grandViews + $grandDownloads,
            'sdgArticlesCount' => $sdgArticlesCount,
            'periodLabel' => $periodLabel,
            'monthlyTop5' => array_slice($ranked, 0, 5),
        ];
    }

    private function computeFullStatistics($request, string $contextPath, int $contextId): array
    {
        $dispatcher = Application::get()->getDispatcher();
        $submissions = $this->getCachedPublishedSubmissions($contextId);
        $issues = $this->getPublishedIssuesData($request, $contextPath, $contextId);

        $allTimeTotals = $this->getCachedAllTimeMetrics($contextId);

        $grandViews = 0;
        $grandDownloads = 0;
        $articlesList = [];
        $issueStats = [];
        foreach ($issues as $issId => $issMeta) {
            $issueStats[$issId] = [
                'id' => $issId,
                'title' => $issMeta['title'],
                'url' => $issMeta['url'],
                'articleCount' => 0,
                'views' => 0,
                'downloads' => 0,
            ];
        }

        foreach ($submissions as $subId => $meta) {
            $m = $allTimeTotals[$subId] ?? ['views' => 0, 'downloads' => 0];
            $grandViews += $m['views'];
            $grandDownloads += $m['downloads'];

            $issId = $meta['issueId'];
            if (isset($issueStats[$issId])) {
                $issueStats[$issId]['articleCount']++;
                $issueStats[$issId]['views'] += $m['views'];
                $issueStats[$issId]['downloads'] += $m['downloads'];
            }

            $articlesList[] = [
                'id' => $subId,
                'title' => $meta['title'],
                'url' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'article', 'view', [$meta['bestId']]),
                'issueTitle' => $issueStats[$issId]['title'] ?? 'Published Issue',
                'views' => $m['views'],
                'downloads' => $m['downloads'],
                'total' => $m['views'] + $m['downloads'],
            ];
        }

        usort($articlesList, static function (array $a, array $b): int {
            return ($b['views'] <=> $a['views']) ?: ($b['downloads'] <=> $a['downloads']);
        });

        $validIssues = array_values(array_filter($issueStats, static fn (array $iss): bool => $iss['articleCount'] > 0));
        $articlesCount = count($submissions);
        $combinedImpact = $grandViews + $grandDownloads;
        $contextViews = $this->fetchContextTotalViews($contextId);
        $totalTraffic = $contextViews + $combinedImpact;
        $avgViews = $articlesCount > 0 ? (int) round($grandViews / $articlesCount) : 0;
        $conversionRate = $grandViews > 0 ? number_format(($grandDownloads / $grandViews) * 100, 1) : '0.0';

        return [
            'totalSiteTraffic' => $totalTraffic,
            'contextHomepageViews' => $contextViews,
            'totalAbstractViews' => $grandViews,
            'totalDownloads' => $grandDownloads,
            'combinedImpact' => $combinedImpact,
            'issuesCount' => count($validIssues),
            'articlesCount' => $articlesCount,
            'avgViews' => $avgViews,
            'conversionRate' => $conversionRate,
            'dailySeries30' => $this->fetchRealDailySeries30($contextId),
            'topCountries' => $this->fetchRealTopCountries($contextId),
            'issuesBreakdown' => $validIssues,
            'topArticles10' => array_slice($articlesList, 0, 10),
        ];
    }

    private function computeArticlePageData($request, string $contextPath, int $contextId, $templateMgr): array
    {
        $article = $templateMgr->getTemplateVars('article');
        $publication = $templateMgr->getTemplateVars('publication') ?: $templateMgr->getTemplateVars('currentPublication');
        $dispatcher = Application::get()->getDispatcher();

        $views = 0;
        $downloads = 0;
        $xmlDependentImages = [];
        $galleyMeta = [];
        $sdgNumbers = [];
        $resolvedRefs = null;
        $resolveRefsUrl = '';

        if (!$article && $publication && method_exists($publication, 'getData')) {
            $fallbackSubId = (int) $publication->getData('submissionId');
            if ($fallbackSubId > 0) {
                try {
                    $article = \APP\facades\Repo::submission()->get($fallbackSubId);
                } catch (\Throwable $e) {
                    $article = null;
                }
            }
        }

        if ($article) {
            $submissionId = (int) $article->getId();
            if (!$publication && method_exists($article, 'getCurrentPublication')) {
                $publication = $article->getCurrentPublication();
            }
            $pubId = ($publication && method_exists($publication, 'getId')) ? (int) $publication->getId() : 0;

            $metrics = $this->getCachedData(
                "kisi_smk_art_metrics_v2_{$contextId}_{$submissionId}",
                fn () => $this->fetchSingleArticleTotalsByType($submissionId, $contextId, $templateMgr),
                self::ARTICLE_METRICS_CACHE_TTL
            );
            $views = $metrics['views'];
            $downloads = $metrics['downloads'];

            if ($publication) {
                $bestId = $article->getBestId();
                $protocol = strtolower((string) $request->getProtocol()) === 'https' ? 'https' : 'http';
                $metaCached = $this->getCachedData(
                    "kisi_smk_art_meta_v4_{$contextId}_{$submissionId}_{$pubId}_{$protocol}",
                    function () use ($request, $dispatcher, $contextPath, $bestId, $publication): array {
                        $galleyMeta = [];
                        $xmlDependentImages = [];
                        $galleys = $publication->getData('galleys') ?? [];
                        foreach ($galleys as $galley) {
                            $subFileId = (int) ($galley->getData('submissionFileId') ?: $galley->getData('fileId'));
                            $label = (string) $galley->getGalleyLabel();
                            $galleyBestId = (string) $galley->getBestGalleyId();

                            $galleyMeta[] = [
                                'galleyId' => $galleyBestId,
                                'numericGalleyId' => (string) $galley->getId(),
                                'fileId' => (string) $subFileId,
                                'label' => $label,
                            ];

                            if ($subFileId > 0 && preg_match('/\b(xml|jats)\b/i', trim($label))) {
                                $xmlDependentImages = $this->resolveDependentImagesMap(
                                    $request,
                                    $dispatcher,
                                    $contextPath,
                                    $bestId,
                                    $galleyBestId,
                                    $subFileId
                                );
                            }
                        }

                        return [
                            'galleyMeta' => $galleyMeta,
                            'xmlDependentImages' => $xmlDependentImages,
                            'sdgNumbers' => $this->extractPublicationSdgs($publication),
                        ];
                    },
                    self::ARTICLE_META_CACHE_TTL
                );

                $galleyMeta = $metaCached['galleyMeta'] ?? [];
                $xmlDependentImages = $metaCached['xmlDependentImages'] ?? [];
                $sdgNumbers = $metaCached['sdgNumbers'] ?? [];

                $rawCitations = $this->extractPublicationRawCitations($publication, $templateMgr);
                if (!empty($rawCitations)) {
                    $refCacheKey = "kisi_smk_refs_v2_{$contextId}_{$submissionId}_" . md5(implode("\n", $rawCitations));
                    if ((string) $request->getUserVar('kisiResolveRefs') === '1') {
                        $resolved = $this->getCachedData(
                            $refCacheKey,
                            fn () => $this->resolveCitationsBatch($rawCitations),
                            self::REF_LINKS_CACHE_TTL
                        );
                        while (ob_get_level() > 0) {
                            @ob_end_clean();
                        }
                        if (!headers_sent()) {
                            header('Content-Type: application/json; charset=utf-8', true);
                            header('Cache-Control: public, max-age=3600', true);
                        }
                        echo json_encode(['references' => $resolved], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{"references":[]}';
                        exit;
                    }

                    try {
                        $cachedRefs = Cache::get($refCacheKey);
                        if (is_array($cachedRefs)) {
                            $resolvedRefs = $cachedRefs;
                        }
                    } catch (\Throwable $e) {
                        $resolvedRefs = null;
                    }

                    if ($resolvedRefs === null) {
                        $resolveRefsUrl = $dispatcher->url(
                            $request,
                            PKPApplication::ROUTE_PAGE,
                            $contextPath,
                            'article',
                            'view',
                            [$bestId],
                            ['kisiResolveRefs' => '1']
                        );
                    }
                }
            }
        }

        return [
            'metrics' => [
                'views' => $views,
                'downloads' => $downloads,
            ],
            'galleyMeta' => $galleyMeta,
            'xmlDependentImages' => $xmlDependentImages,
            'sdgNumbers' => $sdgNumbers,
            'resolvedRefs' => $resolvedRefs,
            'resolveRefsUrl' => $resolveRefsUrl,
        ];
    }

    private function resolveDependentImagesMap($request, $dispatcher, string $contextPath, $articleBestId, string $galleyBestId, int $xmlSubmissionFileId): array
    {
        $map = [];
        try {
            $depFiles = Repo::submissionFile()
                ->getCollector()
                ->filterByAssoc(Application::ASSOC_TYPE_SUBMISSION_FILE, [$xmlSubmissionFileId])
                ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
                ->includeDependentFiles()
                ->getMany();

            foreach ($depFiles as $depFile) {
                $name = trim((string) $depFile->getLocalizedData('name'));
                $pathName = trim(basename((string) $depFile->getData('path')));
                $downloadUrl = $dispatcher->url(
                    $request,
                    PKPApplication::ROUTE_PAGE,
                    $contextPath,
                    'article',
                    'download',
                    [$articleBestId, $galleyBestId, (int) $depFile->getId()]
                );

                if ($name !== '') {
                    $map[strtolower($name)] = $downloadUrl;
                    $map[strtolower(basename($name))] = $downloadUrl;
                }
                if ($pathName !== '') {
                    $map[strtolower($pathName)] = $downloadUrl;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $map;
    }

    private function computeCatalogCategorySdgs(int $contextId, $templateMgr): array
    {
        $submissions = $templateMgr->getTemplateVars('publishedSubmissions')
            ?? $templateMgr->getTemplateVars('submissions')
            ?? $templateMgr->getTemplateVars('articles')
            ?? [];

        $entries = [];
        if (is_iterable($submissions)) {
            foreach ($submissions as $submission) {
                if (!is_object($submission) || !method_exists($submission, 'getId')) {
                    continue;
                }
                $subId = (int) $submission->getId();
                $bestId = method_exists($submission, 'getBestId') ? (string) $submission->getBestId() : (string) $subId;
                $publication = method_exists($submission, 'getCurrentPublication')
                    ? $submission->getCurrentPublication()
                    : null;
                $pubId = ($publication && method_exists($publication, 'getId')) ? (int) $publication->getId() : 0;

                if ($subId > 0) {
                    $entries[] = [
                        'subId' => $subId,
                        'bestId' => $bestId,
                        'pubId' => $pubId,
                        'publication' => $publication,
                    ];
                }
            }
        }

        if (empty($entries)) {
            try {
                $category = $templateMgr->getTemplateVars('category');
                $categoryId = (is_object($category) && method_exists($category, 'getId')) ? (int) $category->getId() : 0;
                $query = DB::table('publication_categories as pc')
                    ->join('categories as c', 'c.category_id', '=', 'pc.category_id')
                    ->join('publications as p', 'p.publication_id', '=', 'pc.publication_id')
                    ->join('submissions as s', 's.submission_id', '=', 'p.submission_id')
                    ->where('c.context_id', $contextId)
                    ->where('s.context_id', $contextId)
                    ->where('s.status', Submission::STATUS_PUBLISHED)
                    ->whereColumn('s.current_publication_id', 'p.publication_id');

                if ($categoryId > 0) {
                    $query->where('c.category_id', $categoryId);
                } else {
                    $query->where('c.path', 'sdgs');
                }

                $hasUrlPath = Schema::hasColumn('publications', 'url_path');
                $cols = ['s.submission_id', 'p.publication_id'];
                if ($hasUrlPath) {
                    $cols[] = 'p.url_path';
                }
                foreach ($query->select($cols)->get() as $row) {
                    $subId = (int) $row->submission_id;
                    $bestId = ($hasUrlPath && !empty($row->url_path)) ? (string) $row->url_path : (string) $subId;
                    $entries[] = [
                        'subId' => $subId,
                        'bestId' => $bestId,
                        'pubId' => (int) $row->publication_id,
                        'publication' => null,
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        if (empty($entries)) {
            return [];
        }

        $ids = array_map(static fn (array $e): int => $e['subId'], $entries);
        $cacheKey = 'kisi_smk_cat_sdgs_v3_' . $contextId . '_' . md5(implode(',', $ids));

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
        }

        $pubIds = array_values(array_unique(array_filter(array_map(static fn (array $e): int => $e['pubId'], $entries))));
        $vocabTextsByPub = $this->fetchControlledVocabTextsByPubIds($pubIds);

        $result = [];
        foreach ($entries as $entry) {
            $pubId = $entry['pubId'];
            $texts = $vocabTextsByPub[$pubId] ?? [];
            if ($entry['publication']) {
                foreach (['subjects', 'keywords'] as $field) {
                    $this->flattenMixedTexts($entry['publication']->getLocalizedData($field), $texts);
                }
            }
            $sdgs = $this->extractSdgsFromTexts($texts);
            if (!empty($sdgs)) {
                $subKey = (string) $entry['subId'];
                $bestKey = (string) $entry['bestId'];
                $result[$subKey] = $sdgs;
                $result[$bestKey] = $sdgs;
                $result[strtolower($bestKey)] = $sdgs;
            }
        }

        if (!empty($result)) {
            try {
                Cache::put($cacheKey, $result, self::STATS_CACHE_TTL);
            } catch (\Throwable $e) {
            }
        }

        return $result;
    }

    private function fetchSubmissionMetricsMap(int $contextId, ?string $dateStart, ?string $dateEnd): array
    {
        $map = [];
        try {
            $statsService = app()->get('publicationStats');
            if (!$statsService) {
                return [];
            }

            $baseArgs = ['contextIds' => [$contextId]];
            if ($dateStart !== null && $dateEnd !== null) {
                $baseArgs['dateStart'] = $dateStart;
                $baseArgs['dateEnd'] = $dateEnd;
            }

            $viewRows = $this->safeStatsCall(fn ($args) => $statsService->getTotals($args), array_merge($baseArgs, [
                'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION],
            ]));

            foreach ($viewRows as $row) {
                $subId = (int) ($row->submission_id ?? 0);
                if ($subId > 0) {
                    $map[$subId] = [
                        'views' => (int) ($row->metric ?? 0),
                        'downloads' => 0,
                    ];
                }
            }

            $dlRows = $this->safeStatsCall(fn ($args) => $statsService->getTotals($args), array_merge($baseArgs, [
                'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION_FILE],
            ]));

            foreach ($dlRows as $row) {
                $subId = (int) ($row->submission_id ?? 0);
                if ($subId > 0) {
                    if (!isset($map[$subId])) {
                        $map[$subId] = ['views' => 0, 'downloads' => 0];
                    }
                    $map[$subId]['downloads'] += (int) ($row->metric ?? 0);
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $map;
    }

    private function fetchSingleArticleTotalsByType(int $submissionId, int $contextId, $templateMgr = null): array
    {
        $views = 0;
        $downloads = 0;

        $extractTotalsFromMap = function ($byType): array {
            $arr = (array) $byType;
            return [
                'views' => (int) ($arr['abstractViews'] ?? $arr['abstract'] ?? 0),
                'downloads' => (int) ($arr['pdfViews'] ?? $arr['pdf'] ?? 0)
                    + (int) ($arr['htmlViews'] ?? $arr['html'] ?? 0)
                    + (int) ($arr['otherViews'] ?? $arr['other'] ?? 0),
            ];
        };

        if ($templateMgr && method_exists($templateMgr, 'getTemplateVars')) {
            $tplByType = $templateMgr->getTemplateVars('metricsByType');
            if ($tplByType) {
                $parsed = $extractTotalsFromMap($tplByType);
                $views = max($views, $parsed['views']);
                $downloads = max($downloads, $parsed['downloads']);
            }
        }

        $hasValidByType = false;
        try {
            $statsService = app()->get('publicationStats');
            if ($statsService && method_exists($statsService, 'getTotalsByType')) {
                $byType = $statsService->getTotalsByType($submissionId, $contextId, null, null);
                $byTypeArr = (array) $byType;
                if (array_key_exists('abstract', $byTypeArr) || array_key_exists('abstractViews', $byTypeArr)) {
                    $parsed = $extractTotalsFromMap($byTypeArr);
                    $views = max($views, $parsed['views']);
                    $downloads = max($downloads, $parsed['downloads']);
                    $hasValidByType = ($views > 0 || $downloads > 0);
                }
            }

            if (!$hasValidByType && $statsService && method_exists($statsService, 'getTotals')) {
                if ($views === 0) {
                    $subTotals = $statsService->getTotals([
                        'contextIds' => [$contextId],
                        'submissionIds' => [$submissionId],
                        'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION],
                    ]);
                    $views = is_numeric($subTotals) ? (int) $subTotals : 0;
                }
                if ($downloads === 0) {
                    $fileTotals = $statsService->getTotals([
                        'contextIds' => [$contextId],
                        'submissionIds' => [$submissionId],
                        'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION_FILE],
                    ]);
                    $downloads = is_numeric($fileTotals) ? (int) $fileTotals : 0;
                }
            }
        } catch (\Throwable $e) {
        }

        if (!$hasValidByType && ($views === 0 || $downloads === 0)) {
            try {
                if ($views === 0) {
                    $dbViews = (int) \Illuminate\Support\Facades\DB::table('metrics_submission')
                        ->where('context_id', $contextId)
                        ->where('submission_id', $submissionId)
                        ->where('assoc_type', Application::ASSOC_TYPE_SUBMISSION)
                        ->sum('metric');
                    if ($dbViews > 0) {
                        $views = $dbViews;
                    }
                }
                if ($downloads === 0) {
                    $dbDownloads = (int) \Illuminate\Support\Facades\DB::table('metrics_submission')
                        ->where('context_id', $contextId)
                        ->where('submission_id', $submissionId)
                        ->where('assoc_type', Application::ASSOC_TYPE_SUBMISSION_FILE)
                        ->sum('metric');
                    if ($dbDownloads > 0) {
                        $downloads = $dbDownloads;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        return ['views' => $views, 'downloads' => $downloads];
    }

    private function fetchContextTotalViews(int $contextId): int
    {
        try {
            $contextStats = app()->get('contextStats');
            if ($contextStats && method_exists($contextStats, 'getTotal')) {
                return (int) $contextStats->getTotal(['contextIds' => [$contextId]]);
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return 0;
    }

    private function fetchRealDailySeries30(int $contextId): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-29 days'));
        $dimDay = defined('PKP\statistics\PKPStatisticsHelper::STATISTICS_DIMENSION_DAY')
            ? PKPStatisticsHelper::STATISTICS_DIMENSION_DAY
            : 'day';

        $viewsByDate = [];
        $downloadsByDate = [];
        $contextByDate = [];

        try {
            $pubStats = app()->get('publicationStats');
            if ($pubStats && method_exists($pubStats, 'getTimeline')) {
                $viewTimeline = $this->safeStatsCall(
                    fn ($args) => $pubStats->getTimeline($dimDay, $args),
                    [
                        'contextIds' => [$contextId],
                        'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION],
                        'dateStart' => $startDate,
                        'dateEnd' => $endDate,
                    ]
                );
                foreach ($viewTimeline as $item) {
                    $dKey = substr((string) ($item['date'] ?? ''), 0, 10);
                    if ($dKey !== '') {
                        $viewsByDate[$dKey] = (int) ($item['value'] ?? 0);
                    }
                }

                $dlTimeline = $this->safeStatsCall(
                    fn ($args) => $pubStats->getTimeline($dimDay, $args),
                    [
                        'contextIds' => [$contextId],
                        'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION_FILE],
                        'dateStart' => $startDate,
                        'dateEnd' => $endDate,
                    ]
                );
                foreach ($dlTimeline as $item) {
                    $dKey = substr((string) ($item['date'] ?? ''), 0, 10);
                    if ($dKey !== '') {
                        $downloadsByDate[$dKey] = (int) ($item['value'] ?? 0);
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $ctxStats = app()->get('contextStats');
            if ($ctxStats && method_exists($ctxStats, 'getTimeline')) {
                $ctxTimeline = $this->safeStatsCall(
                    fn ($args) => $ctxStats->getTimeline($dimDay, $args),
                    [
                        'contextIds' => [$contextId],
                        'dateStart' => $startDate,
                        'dateEnd' => $endDate,
                    ]
                );
                foreach ($ctxTimeline as $item) {
                    $dKey = substr((string) ($item['date'] ?? ''), 0, 10);
                    if ($dKey !== '') {
                        $contextByDate[$dKey] = (int) ($item['value'] ?? 0);
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        $series = [];
        $todayKey = date('Y-m-d');
        for ($offset = 29; $offset >= 0; $offset--) {
            $ts = strtotime("-{$offset} days");
            $dateKey = date('Y-m-d', $ts);
            $views = $viewsByDate[$dateKey] ?? 0;
            $downloads = $downloadsByDate[$dateKey] ?? 0;
            $ctxViews = $contextByDate[$dateKey] ?? 0;
            $reads = $views + $downloads;
            $totalVisits = $reads + $ctxViews;

            $series[] = [
                'date' => $dateKey,
                'isToday' => ($dateKey === $todayKey),
                'shortLabel' => ($dateKey === $todayKey) ? 'Today' : date('d M', $ts),
                'fullLabel' => date('D, d M Y', $ts),
                'visitors' => $totalVisits,
                'reads' => $reads,
                'views' => $views,
                'downloads' => $downloads,
                'homepageViews' => $ctxViews,
            ];
        }

        return $series;
    }

    private function fetchRealTopCountries(int $contextId): array
    {
        try {
            $rows = [];
            if (Schema::hasTable('metrics_submission_geo_monthly')) {
                $rows = DB::table('metrics_submission_geo_monthly')
                    ->select('country', DB::raw('SUM(metric) as total_metric'))
                    ->where('context_id', $contextId)
                    ->whereNotNull('country')
                    ->where('country', '!=', '')
                    ->groupBy('country')
                    ->orderByDesc('total_metric')
                    ->limit(10)
                    ->get();
            }

            if (empty($rows) || count($rows) === 0) {
                if (Schema::hasTable('metrics_submission_geo_daily')) {
                    $rows = DB::table('metrics_submission_geo_daily')
                        ->select('country', DB::raw('SUM(metric) as total_metric'))
                        ->where('context_id', $contextId)
                        ->whereNotNull('country')
                        ->where('country', '!=', '')
                        ->groupBy('country')
                        ->orderByDesc('total_metric')
                        ->limit(10)
                        ->get();
                }
            }

            if (empty($rows) || count($rows) === 0) {
                return [];
            }

            $totalAll = 0;
            if (Schema::hasTable('metrics_submission_geo_monthly')) {
                $totalAll = (int) DB::table('metrics_submission_geo_monthly')
                    ->where('context_id', $contextId)
                    ->whereNotNull('country')
                    ->where('country', '!=', '')
                    ->sum('metric');
            }
            if ($totalAll <= 0 && Schema::hasTable('metrics_submission_geo_daily')) {
                $totalAll = (int) DB::table('metrics_submission_geo_daily')
                    ->where('context_id', $contextId)
                    ->whereNotNull('country')
                    ->where('country', '!=', '')
                    ->sum('metric');
            }
            if ($totalAll <= 0) {
                return [];
            }

            $result = [];
            foreach ($rows as $row) {
                $isoCode = strtoupper(trim((string) ($row->country ?? '')));
                $count = (int) ($row->total_metric ?? 0);
                if ($isoCode === '' || $count <= 0) {
                    continue;
                }
                $result[] = [
                    'code' => strtolower($isoCode),
                    'name' => $this->resolveCountryName($isoCode),
                    'count' => $count,
                    'percent' => round(($count / $totalAll) * 100, 1),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function safeStatsCall(callable $fn, array $args): iterable
    {
        try {
            $res = $fn($args);
            return is_iterable($res) ? $res : [];
        } catch (\TypeError $e) {
            if (isset($args['dateStart']) && is_string($args['dateStart'])) {
                $args['dateStart'] = new \DateTimeImmutable($args['dateStart']);
            }
            if (isset($args['dateEnd']) && is_string($args['dateEnd'])) {
                $args['dateEnd'] = new \DateTimeImmutable($args['dateEnd']);
            }
            try {
                $res = $fn($args);
                return is_iterable($res) ? $res : [];
            } catch (\Throwable $inner) {
                return [];
            }
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getCachedAllTimeMetrics(int $contextId): array
    {
        return $this->getCachedData(
            "kisi_smk_alltime_totals_v2_{$contextId}",
            fn () => $this->fetchSubmissionMetricsMap($contextId, null, null)
        );
    }

    private function getCachedPublishedSubmissions(int $contextId): array
    {
        return $this->getCachedData(
            "kisi_smk_pub_submissions_v3_{$contextId}",
            function () use ($contextId): array {
                $indexed = [];
                $rawEntries = [];
                $pubIds = [];
                try {
                    $submissions = Repo::submission()
                        ->getCollector()
                        ->filterByContextIds([$contextId])
                        ->filterByStatus([Submission::STATUS_PUBLISHED])
                        ->getMany();

                    foreach ($submissions as $submission) {
                        $publication = $submission->getCurrentPublication();
                        if (!$publication) {
                            continue;
                        }
                        $subId = (int) $submission->getId();
                        $pubId = method_exists($publication, 'getId') ? (int) $publication->getId() : 0;
                        if ($pubId > 0) {
                            $pubIds[] = $pubId;
                        }
                        $rawEntries[$subId] = [
                            'id' => $subId,
                            'pubId' => $pubId,
                            'bestId' => (string) $submission->getBestId(),
                            'title' => trim(strip_tags((string) $publication->getLocalizedFullTitle())) ?: ('Article #' . $subId),
                            'issueId' => (int) ($publication->getData('issueId') ?? 0),
                            'publication' => $publication,
                        ];
                    }

                    $vocabByPub = $this->fetchControlledVocabTextsByPubIds($pubIds);
                    foreach ($rawEntries as $subId => $item) {
                        $texts = $vocabByPub[$item['pubId']] ?? [];
                        foreach (['subjects', 'keywords'] as $field) {
                            $this->flattenMixedTexts($item['publication']->getLocalizedData($field), $texts);
                        }
                        $indexed[$subId] = [
                            'id' => $item['id'],
                            'bestId' => $item['bestId'],
                            'title' => $item['title'],
                            'issueId' => $item['issueId'],
                            'sdgs' => $this->extractSdgsFromTexts($texts),
                        ];
                    }
                } catch (\Throwable $e) {
                    return [];
                }

                return $indexed;
            }
        );
    }

    private function getPublishedIssuesData($request, string $contextPath, int $contextId): array
    {
        $dispatcher = Application::get()->getDispatcher();
        $result = [];
        try {
            $issues = Repo::issue()
                ->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByPublished(true)
                ->getMany();

            foreach ($issues as $issue) {
                $issId = (int) $issue->getId();
                $result[$issId] = [
                    'id' => $issId,
                    'title' => (string) $issue->getIssueIdentification(),
                    'url' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'issue', 'view', [$issue->getBestIssueId()]),
                ];
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $result;
    }

    private function getPublishedIssueTitles(int $contextId): array
    {
        $titles = [];
        try {
            $issues = Repo::issue()
                ->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByPublished(true)
                ->getMany();

            foreach ($issues as $issue) {
                $titles[(int) $issue->getId()] = (string) $issue->getIssueIdentification();
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $titles;
    }

    private function countUniquePublishedAuthors(int $contextId): int
    {
        try {
            $rows = DB::table('authors as a')
                ->join('publications as p', 'p.publication_id', '=', 'a.publication_id')
                ->join('submissions as s', 's.submission_id', '=', 'p.submission_id')
                ->leftJoin('author_settings as ag', static function ($join): void {
                    $join->on('ag.author_id', '=', 'a.author_id')
                        ->where('ag.setting_name', '=', 'givenName');
                })
                ->leftJoin('author_settings as af', static function ($join): void {
                    $join->on('af.author_id', '=', 'a.author_id')
                        ->where('af.setting_name', '=', 'familyName');
                })
                ->where('s.context_id', $contextId)
                ->where('s.status', Submission::STATUS_PUBLISHED)
                ->whereColumn('s.current_publication_id', 'p.publication_id')
                ->select('ag.setting_value as given_name', 'af.setting_value as family_name')
                ->distinct()
                ->get();

            $uniqueNames = [];
            foreach ($rows as $row) {
                $full = strtolower(trim(preg_replace('/\s+/', ' ', ($row->given_name ?? '') . ' ' . ($row->family_name ?? ''))));
                if ($full !== '') {
                    $uniqueNames[$full] = true;
                }
            }
            return count($uniqueNames);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countSdgPublishedArticles(int $contextId, array $submissions): int
    {
        try {
            if (Schema::hasTable('categories') && Schema::hasTable('publication_categories')) {
                $sdgCount = (int) DB::table('publication_categories as pc')
                    ->join('categories as c', 'c.category_id', '=', 'pc.category_id')
                    ->join('publications as p', 'p.publication_id', '=', 'pc.publication_id')
                    ->join('submissions as s', 's.submission_id', '=', 'p.submission_id')
                    ->where('c.context_id', $contextId)
                    ->where('c.path', 'sdgs')
                    ->where('s.context_id', $contextId)
                    ->where('s.status', Submission::STATUS_PUBLISHED)
                    ->whereColumn('s.current_publication_id', 'p.publication_id')
                    ->distinct()
                    ->count('s.submission_id');

                if ($sdgCount > 0) {
                    return $sdgCount;
                }
            }
        } catch (\Throwable $e) {
        }

        $count = 0;
        foreach ($submissions as $meta) {
            if (!empty($meta['sdgs'])) {
                $count++;
            }
        }
        return $count;
    }

    private function extractPublicationSdgs($publication): array
    {
        if (!$publication) {
            return [];
        }

        $texts = [];
        $pubId = method_exists($publication, 'getId') ? (int) $publication->getId() : 0;
        if ($pubId > 0) {
            $vocabMap = $this->fetchControlledVocabTextsByPubIds([$pubId]);
            $texts = $vocabMap[$pubId] ?? [];
        }

        foreach (['subjects', 'keywords'] as $field) {
            if (method_exists($publication, 'getLocalizedData')) {
                $this->flattenMixedTexts($publication->getLocalizedData($field), $texts);
            }
        }

        return $this->extractSdgsFromTexts($texts);
    }

    private function fetchControlledVocabTextsByPubIds(array $publicationIds): array
    {
        $pubIds = array_values(array_unique(array_filter(array_map('intval', $publicationIds), static fn (int $id): bool => $id > 0)));
        if (empty($pubIds)) {
            return [];
        }

        $map = [];
        try {
            if (Schema::hasTable('controlled_vocabs') && Schema::hasTable('controlled_vocab_entries') && Schema::hasTable('controlled_vocab_entry_settings')) {
                $rows = DB::table('controlled_vocabs as cv')
                    ->join('controlled_vocab_entries as cve', 'cve.controlled_vocab_id', '=', 'cv.controlled_vocab_id')
                    ->join('controlled_vocab_entry_settings as cves', 'cves.controlled_vocab_entry_id', '=', 'cve.controlled_vocab_entry_id')
                    ->whereIn('cv.assoc_id', $pubIds)
                    ->whereIn('cv.symbolic', ['submissionKeyword', 'submissionSubject', 'submissionDiscipline'])
                    ->whereNotNull('cves.setting_value')
                    ->where('cves.setting_value', '!=', '')
                    ->select('cv.assoc_id as publication_id', 'cves.setting_value')
                    ->get();

                foreach ($rows as $row) {
                    $pid = (int) $row->publication_id;
                    $val = trim((string) $row->setting_value);
                    if ($val !== '') {
                        $map[$pid][] = $val;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            if (Schema::hasTable('publication_settings')) {
                $psRows = DB::table('publication_settings')
                    ->whereIn('publication_id', $pubIds)
                    ->whereIn('setting_name', ['subjects', 'keywords'])
                    ->whereNotNull('setting_value')
                    ->where('setting_value', '!=', '')
                    ->select('publication_id', 'setting_value')
                    ->get();

                foreach ($psRows as $row) {
                    $pid = (int) $row->publication_id;
                    $raw = trim((string) $row->setting_value);
                    if ($raw === '') {
                        continue;
                    }
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $this->flattenMixedTexts($decoded, $map[$pid]);
                    } else {
                        $map[$pid][] = $raw;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return $map;
    }

    private function flattenMixedTexts($value, ?array &$out): void
    {
        if ($out === null) {
            $out = [];
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                $out[] = $trimmed;
            }
            return;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->flattenMixedTexts($item, $out);
            }
            return;
        }
        if (is_object($value)) {
            if (method_exists($value, 'getLocalizedData')) {
                $this->flattenMixedTexts($value->getLocalizedData('name'), $out);
            } elseif (method_exists($value, 'toArray')) {
                $this->flattenMixedTexts($value->toArray(), $out);
            } elseif (method_exists($value, '__toString')) {
                $this->flattenMixedTexts((string) $value, $out);
            }
        }
    }

    private function extractSdgsFromTexts(array $texts): array
    {
        $combined = trim(implode(' ; ', array_unique($texts)));
        if ($combined === '') {
            return [];
        }

        $detected = [];
        if (preg_match_all('/(?:sdgs?|goals?|tujuan)\s*(?:goals?|#|:|-)?\s*(1[0-7]|0?[1-9])\b/i', $combined, $matches)) {
            foreach ($matches[1] as $numStr) {
                $num = (int) $numStr;
                if ($num >= 1 && $num <= 17 && !in_array($num, $detected, true)) {
                    $detected[] = $num;
                }
            }
        }

        if (empty($detected)) {
            $names = [
                1 => 'no poverty',
                2 => 'zero hunger',
                3 => 'good health and well-being',
                4 => 'quality education',
                5 => 'gender equality',
                6 => 'clean water and sanitation',
                7 => 'affordable and clean energy',
                8 => 'decent work and economic growth',
                9 => 'industry, innovation and infrastructure',
                10 => 'reduced inequalities',
                11 => 'sustainable cities and communities',
                12 => 'responsible consumption and production',
                13 => 'climate action',
                14 => 'life below water',
                15 => 'life on land',
                16 => 'peace, justice and strong institutions',
                17 => 'partnerships for the goals',
            ];
            $lower = strtolower($combined);
            foreach ($names as $num => $label) {
                if (str_contains($lower, $label)) {
                    $detected[] = $num;
                }
            }
        }

        return $detected;
    }

    private function sumMetricsMap(array $metricsMap): int
    {
        $total = 0;
        foreach ($metricsMap as $item) {
            $total += ($item['views'] ?? 0) + ($item['downloads'] ?? 0);
        }
        return $total;
    }

    private function resolveCountryName(string $isoCode): string
    {
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-' . $isoCode, 'en');
            if ($name && strtoupper($name) !== $isoCode) {
                return $name;
            }
        }

        $common = [
            'ID' => 'Indonesia',
            'MY' => 'Malaysia',
            'US' => 'United States',
            'SG' => 'Singapore',
            'AU' => 'Australia',
            'JP' => 'Japan',
            'TL' => 'Timor-Leste',
            'GB' => 'United Kingdom',
            'IN' => 'India',
            'NL' => 'Netherlands',
            'DE' => 'Germany',
            'CN' => 'China',
            'KR' => 'South Korea',
            'PH' => 'Philippines',
            'TH' => 'Thailand',
            'VN' => 'Vietnam',
            'CA' => 'Canada',
            'FR' => 'France',
            'BR' => 'Brazil',
        ];

        return $common[$isoCode] ?? $isoCode;
    }

    private function extractPublicationRawCitations($publication, $templateMgr = null): array
    {
        $items = [];

        $sources = [];
        if ($templateMgr && method_exists($templateMgr, 'getTemplateVars')) {
            $tplCits = $templateMgr->getTemplateVars('parsedCitations');
            if ($tplCits) {
                $sources[] = $tplCits;
            }
        }
        if ($publication && method_exists($publication, 'getData')) {
            $pubCits = $publication->getData('citations');
            if ($pubCits) {
                $sources[] = $pubCits;
            }
        }

        foreach ($sources as $src) {
            if (!is_iterable($src)) {
                continue;
            }
            foreach ($src as $citObj) {
                $raw = '';
                if (is_object($citObj) && method_exists($citObj, 'getRawCitation')) {
                    $raw = trim((string) $citObj->getRawCitation());
                } elseif (is_string($citObj)) {
                    $raw = trim($citObj);
                }
                if ($raw !== '') {
                    $items[] = $raw;
                }
            }
            if (!empty($items)) {
                return array_slice($items, 0, 80);
            }
        }

        $pubId = ($publication && method_exists($publication, 'getId')) ? (int) $publication->getId() : 0;
        if ($pubId > 0) {
            try {
                if (Schema::hasTable('citations')) {
                    $rows = DB::table('citations')
                        ->where('publication_id', $pubId)
                        ->orderBy('seq')
                        ->limit(80)
                        ->pluck('raw_citation');
                    foreach ($rows as $r) {
                        $trimmed = trim((string) $r);
                        if ($trimmed !== '') {
                            $items[] = $trimmed;
                        }
                    }
                    if (!empty($items)) {
                        return $items;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        if ($publication && method_exists($publication, 'getData')) {
            $rawBlock = (string) ($publication->getData('citationsRaw') ?? '');
            if ($rawBlock !== '') {
                foreach (preg_split('/\r\n|\r|\n/', $rawBlock) ?: [] as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $items[] = $trimmed;
                    }
                }
            }
        }

        return array_slice($items, 0, 80);
    }

    private function resolveCitationsBatch(array $rawCitations): array
    {
        $parsedList = [];
        foreach ($rawCitations as $idx => $raw) {
            $parsedList[$idx] = $this->parseSingleCitation($raw, $idx);
        }

        $explicitDoisByIdx = [];
        $crossrefBibRequests = [];
        foreach ($parsedList as $idx => $info) {
            if ($info['doi'] !== '') {
                $explicitDoisByIdx[$idx] = strtolower($info['doi']);
            } elseif ($info['titleWordCount'] >= 4 && mb_strlen($info['title'], 'UTF-8') >= 22) {
                $queryStr = mb_substr($info['cleanBody'], 0, 300, 'UTF-8');
                $crossrefBibRequests['bib_' . $idx] = 'https://api.crossref.org/works?rows=2&query.bibliographic=' . rawurlencode($queryStr);
            }
        }

        if (!empty($explicitDoisByIdx)) {
            $crMetaByDoi = $this->batchVerifyCrossrefDois(array_values(array_unique($explicitDoisByIdx)));
            foreach ($explicitDoisByIdx as $idx => $lowerDoi) {
                if (isset($crMetaByDoi[$lowerDoi])) {
                    $meta = $crMetaByDoi[$lowerDoi];
                    $parsedList[$idx]['verifiedCrossrefDoi'] = $meta['doi'];
                    if ($meta['title'] !== '' && mb_strlen($meta['title'], 'UTF-8') >= 10) {
                        $parsedList[$idx]['title'] = $meta['title'];
                        $parsedList[$idx]['titleWordCount'] = count($this->normalizeTitleTokens($meta['title']));
                    }
                    if ($parsedList[$idx]['firstAuthor'] === '' && $meta['firstAuthor'] !== '') {
                        $parsedList[$idx]['firstAuthor'] = $meta['firstAuthor'];
                    }
                    if ($parsedList[$idx]['year'] === '' && $meta['year'] !== '') {
                        $parsedList[$idx]['year'] = $meta['year'];
                    }
                }
            }
        }

        if (!empty($crossrefBibRequests)) {
            $crResponses = $this->runConcurrentGetRequests($crossrefBibRequests, 5.5);
            foreach ($parsedList as $idx => &$info) {
                if (!isset($crResponses['bib_' . $idx])) {
                    continue;
                }
                $res = $crResponses['bib_' . $idx];
                if ($res['status'] !== 200) {
                    continue;
                }
                $json = json_decode($res['body'], true);
                $candidates = $json['message']['items'] ?? [];
                if (!is_array($candidates)) {
                    continue;
                }
                foreach ($candidates as $cand) {
                    $candDoi = trim((string) ($cand['DOI'] ?? ''));
                    $candTitle = trim(strip_tags((string) ($cand['title'][0] ?? '')));
                    if ($candDoi === '' || $candTitle === '') {
                        continue;
                    }
                    $candYear = (string) ($cand['issued']['date-parts'][0][0] ?? $cand['published']['date-parts'][0][0] ?? '');
                    if ($info['year'] !== '' && preg_match('/^\d{4}$/', $candYear)) {
                        if (abs((int) $info['year'] - (int) $candYear) > 1) {
                            continue;
                        }
                    }
                    $candFamily = trim((string) ($cand['author'][0]['family'] ?? ''));
                    if (!$info['isInstitutional'] && $info['firstAuthor'] !== '' && $candFamily !== '') {
                        if (!$this->authorSurnameMatches($info['firstAuthor'], $candFamily, $info['normFull'])) {
                            continue;
                        }
                    }
                    if ($this->isStrictTitleMatch($info['title'], $candTitle, $info['normFull'])) {
                        $info['doi'] = $candDoi;
                        $info['verifiedCrossrefDoi'] = $candDoi;
                        $info['title'] = $candTitle;
                        $info['titleWordCount'] = count($this->normalizeTitleTokens($candTitle));
                        if ($info['firstAuthor'] === '' && $candFamily !== '') {
                            $info['firstAuthor'] = $candFamily;
                        }
                        if ($info['year'] === '' && preg_match('/^\d{4}$/', $candYear)) {
                            $info['year'] = $candYear;
                        }
                        break;
                    }
                }
            }
            unset($info);
        }

        $doisForPubmed = [];
        foreach ($parsedList as $idx => $info) {
            if ($info['pmid'] === '' && $info['doi'] !== '') {
                $doisForPubmed[$idx] = strtolower($info['doi']);
            }
        }

        if (!empty($doisForPubmed)) {
            $doiMap = $this->batchLookupPubmedByDois(array_values(array_unique($doisForPubmed)));
            foreach ($doisForPubmed as $idx => $lowerDoi) {
                if (isset($doiMap[$lowerDoi])) {
                    $parsedList[$idx]['pmid'] = $doiMap[$lowerDoi]['pmid'];
                    if ($parsedList[$idx]['verifiedCrossrefDoi'] === '' && $doiMap[$lowerDoi]['title'] !== '') {
                        $parsedList[$idx]['title'] = $doiMap[$lowerDoi]['title'];
                        $parsedList[$idx]['titleWordCount'] = count($this->normalizeTitleTokens($doiMap[$lowerDoi]['title']));
                    }
                }
            }
        }

        $titlePubmedCandidates = [];
        foreach ($parsedList as $idx => $info) {
            if (
                $info['pmid'] === ''
                && $info['doi'] === ''
                && !$info['isInstitutional']
                && !$info['looksIndonesian']
                && $info['titleWordCount'] >= 6
                && count($titlePubmedCandidates) < 5
            ) {
                $titlePubmedCandidates[$idx] = $info['title'];
            }
        }
        if (!empty($titlePubmedCandidates)) {
            $this->lookupPubmedByExactTitles($parsedList, $titlePubmedCandidates);
        }

        $garudaRequests = [];
        foreach ($parsedList as $idx => $info) {
            if ($info['garudaId'] !== '') {
                continue;
            }
            if ($info['titleWordCount'] < 4 || mb_strlen($info['title'], 'UTF-8') < 22) {
                continue;
            }
            if ($info['doi'] !== '' && $this->isMajorInternationalPublisherDoi($info['doi'])) {
                continue;
            }
            if ($info['pmid'] !== '' && !$info['looksIndonesian'] && !$this->isIndonesianDoiPrefix($info['doi'])) {
                continue;
            }
            $garudaRequests[$idx] = 'https://garuda.kemdiktisaintek.go.id/documents?select=title&q=' . rawurlencode($info['title']);
        }

        if (!empty($garudaRequests)) {
            $garudaResponses = $this->runConcurrentGetRequests($garudaRequests, 4.2);
            foreach ($garudaResponses as $idx => $res) {
                if ($res['status'] !== 200 || $res['body'] === '') {
                    continue;
                }
                $matchedId = $this->matchGarudaDocumentId($res['body'], $parsedList[$idx]);
                if ($matchedId !== '') {
                    $parsedList[$idx]['garudaId'] = $matchedId;
                }
            }
        }

        $output = [];
        foreach ($parsedList as $idx => $info) {
            $links = [];
            if ($info['pmid'] !== '') {
                $links['pubmed'] = 'https://pubmed.ncbi.nlm.nih.gov/' . rawurlencode($info['pmid']) . '/';
            }
            if ($info['verifiedCrossrefDoi'] !== '') {
                $links['crossref'] = 'https://doi.org/' . str_replace(' ', '', $info['verifiedCrossrefDoi']);
            }
            if ($info['garudaId'] !== '') {
                $links['garuda'] = 'https://garuda.kemdiktisaintek.go.id/documents/detail/' . rawurlencode($info['garudaId']);
            }

            $canBuildScholar = ($info['verifiedCrossrefDoi'] !== '')
                || ($info['pmid'] !== '')
                || ($info['garudaId'] !== '')
                || ($info['titleWordCount'] >= 4 && $info['year'] !== '' && ($info['firstAuthor'] !== '' || $info['isInstitutional']));

            if ($canBuildScholar && $info['title'] !== '') {
                $queryParts = [trim(preg_replace('/\s+/u', ' ', str_replace('"', '', $info['title'])) ?? '')];
                if ($info['firstAuthor'] !== '') {
                    $queryParts[] = $info['firstAuthor'];
                }
                if ($info['year'] !== '') {
                    $queryParts[] = $info['year'];
                }
                $scholarQuery = trim(implode(' ', array_filter($queryParts)));
                if ($scholarQuery !== '') {
                    $links['scholar'] = 'https://scholar.google.com/scholar?q=' . rawurlencode($scholarQuery);
                }
            }

            $output[] = [
                'index' => $idx,
                'number' => $info['number'],
                'sig' => $info['sig'],
                'links' => $links,
            ];
        }

        return $output;
    }

    private function parseSingleCitation(string $raw, int $idx): array
    {
        $decoded = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = trim(preg_replace('/\s+/u', ' ', $decoded) ?? '');

        $number = (string) ($idx + 1);
        $cleanBody = $decoded;
        if (preg_match('/^(?:\[\s*(\d{1,3})\s*\]|\(\s*(\d{1,3})\s*\)|(\d{1,3})\s*[.)])\s*(.+)$/u', $decoded, $nm)) {
            $number = $nm[1] !== '' ? $nm[1] : ($nm[2] !== '' ? $nm[2] : $nm[3]);
            $cleanBody = trim($nm[4]);
        }

        $sig = substr(preg_replace('/[^a-z0-9]+/', '', strtolower($cleanBody)) ?? '', 0, 40);

        $doi = '';
        if (preg_match('/(?:doi\s*[:\s]\s*|https?:\/\/(?:dx\.)?doi\.org\/|https?:\/\/doi:\s*)?(10\.\d{4,9}\/[^\s"\'<>;]+)/i', $cleanBody, $dm)) {
            $candDoi = rtrim($dm[1], ".,;: \t\r\n]}>");
            while (str_ends_with($candDoi, ')') && substr_count($candDoi, ')') > substr_count($candDoi, '(')) {
                $candDoi = substr($candDoi, 0, -1);
            }
            $doi = $candDoi;
        }

        $pmid = '';
        if (preg_match('/(?:pmid\s*[:\s]\s*|pubmed\.ncbi\.nlm\.nih\.gov\/|ncbi\.nlm\.nih\.gov\/pubmed\/)(\d{5,9})\b/i', $cleanBody, $pm)) {
            $pmid = $pm[1];
        }

        $garudaId = '';
        if (preg_match('/garuda\.(?:kemdiktisaintek|kemdikbud|ristekbrin)\.go\.id\/documents\/detail\/(\d+)/i', $cleanBody, $gm)) {
            $garudaId = $gm[1];
        }

        $stripped = preg_replace('/\b(?:doi\s*[:\s]\s*10\.\S+|https?:\/\/\S+|pubmed\s+pmid\s*:\s*\d+|pmid\s*:\s*\d+|pubmed\s+central\s+pmcid\s*:\s*pmc\d+|pmcid\s*:\s*pmc\d+|available\s+from\s*:.*|tersedia\s+pada\s*:.*)/iu', ' ', $cleanBody) ?? $cleanBody;
        $stripped = preg_replace('/\[\s*(?:internet|serial\s+online|cited[^\]]*|diakses[^\]]*)\s*\]/iu', ' ', $stripped) ?? $stripped;
        $stripped = trim(preg_replace('/\s+/u', ' ', $stripped) ?? '');

        $year = '';
        if (preg_match('/\(\s*(19[5-9]\d|20[0-3]\d)[a-z]?\s*\)/u', $stripped, $ym)) {
            $year = $ym[1];
        } elseif (preg_match_all('/\b(19[5-9]\d|20[0-3]\d)\b/u', $stripped, $ymAll) && !empty($ymAll[1])) {
            $year = (string) end($ymAll[1]);
        }

        $normForSplit = preg_replace('/\b([A-Z])\.\s+(?=[A-Z]\.)/u', '$1.', $stripped) ?? $stripped;
        $normForSplit = preg_replace('/\bet\s+al\.\s+/iu', 'et al||SPLIT||', $normForSplit) ?? $normForSplit;

        $authorsRaw = '';
        $title = '';
        if (preg_match('/^([^.?]{3,140}?)\(\s*(?:19[5-9]\d|20[0-3]\d)[a-z]?\s*\)\.?\s*(.+)$/u', $normForSplit, $apaMatch)) {
            $authorsRaw = trim(str_replace('||SPLIT||', ' ', $apaMatch[1]), " .,;:");
            $rest = trim(str_replace('||SPLIT||', '. ', $apaMatch[2]));
            $parts = preg_split('/(?<=[.?])\s+/u', $rest, 2) ?: [];
            $title = trim((string) ($parts[0] ?? $rest), " .");
        } else {
            $vancNorm = str_replace('||SPLIT||', '. ', $normForSplit);
            $segments = preg_split('/(?<!\b[A-Z])\.\s+|\?\s+|(?<=\b[A-Z]{2})\.\s+|(?<=,\s[A-Z])\.\s+|(?<=\s[A-Z])\.\s+(?=[A-Z][a-z]{2,})/u', $vancNorm) ?: [];
            if (count($segments) < 2) {
                $segments = preg_split('/\.\s+|\?\s+/u', $vancNorm) ?: [];
            }
            if (count($segments) >= 2) {
                $authorsRaw = trim($segments[0], " .,;:");
                $title = trim($segments[1], " .");
                if (mb_strlen($title, 'UTF-8') < 14 && isset($segments[2])) {
                    $title = trim($title . '. ' . $segments[2], " .");
                }
            } else {
                $title = trim($vancNorm, " .");
            }
        }

        $isInstitutional = (bool) preg_match('/\b(kementerian|kemenkes|depkes|world\s+health|who|unicef|badan|dinas|pusat|bps|lembaga|perkumpulan|ikatan|united\s+nations|ministry|organization|tim\s+riskesdas)\b/iu', $authorsRaw);

        $firstAuthor = '';
        if (!$isInstitutional && $authorsRaw !== '') {
            $firstChunk = trim((string) (preg_split('/[,;&]|(\bet\s+al\b)/iu', $authorsRaw)[0] ?? ''));
            $firstChunk = preg_replace('/\s+[A-Z]{1,4}\.?$/u', '', $firstChunk) ?? $firstChunk;
            $tokens = preg_split('/\s+/u', trim($firstChunk)) ?: [];
            $candidateSurname = (string) end($tokens);
            if (count($tokens) >= 2 && in_array(strtolower($tokens[count($tokens) - 2]), ['de', 'van', 'von', 'bin', 'binti', 'al', 'el', 'la'], true)) {
                $candidateSurname = $tokens[count($tokens) - 2] . ' ' . $candidateSurname;
            }
            if (mb_strlen(preg_replace('/[^\p{L}]/u', '', $candidateSurname) ?? '', 'UTF-8') >= 2) {
                $firstAuthor = $candidateSurname;
            }
        }

        $looksIndonesian = (bool) preg_match('/\b(dan|pada|dengan|terhadap|hubungan|pengaruh|faktor|ibu\s+hamil|kesehatan|rumah\s+sakit|puskesmas|jurnal|kabupaten|kota|provinsi|universitas|poltekkes|stikes|di\s+wilayah|tahun)\b/iu', $cleanBody);
        $titleTokens = $this->normalizeTitleTokens($title);

        return [
            'number' => $number,
            'cleanBody' => $cleanBody,
            'normFull' => implode(' ', $this->normalizeTitleTokens($cleanBody)),
            'sig' => $sig,
            'doi' => $doi,
            'verifiedCrossrefDoi' => '',
            'pmid' => $pmid,
            'garudaId' => $garudaId,
            'year' => $year,
            'firstAuthor' => $firstAuthor,
            'isInstitutional' => $isInstitutional,
            'looksIndonesian' => $looksIndonesian,
            'title' => $title,
            'titleWordCount' => count($titleTokens),
        ];
    }

    private function batchVerifyCrossrefDois(array $lowerDois): array
    {
        $verified = [];
        if (empty($lowerDois)) {
            return $verified;
        }

        foreach (array_chunk($lowerDois, 25) as $chunk) {
            $filters = array_map(static fn (string $d): string => 'doi:' . $d, $chunk);
            $url = 'https://api.crossref.org/works?rows=50&select=DOI,title,author,issued&filter=' . rawurlencode(implode(',', $filters));
            $resp = $this->runConcurrentGetRequests(['batch_cr' => $url], 6.5);
            if (($resp['batch_cr']['status'] ?? 0) === 200) {
                $json = json_decode($resp['batch_cr']['body'], true);
                $items = $json['message']['items'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $d = trim((string) ($it['DOI'] ?? ''));
                        if ($d === '') {
                            continue;
                        }
                        $yr = (string) ($it['issued']['date-parts'][0][0] ?? '');
                        $verified[strtolower($d)] = [
                            'doi' => $d,
                            'title' => trim(strip_tags((string) ($it['title'][0] ?? ''))),
                            'firstAuthor' => trim((string) ($it['author'][0]['family'] ?? '')),
                            'year' => preg_match('/^\d{4}$/', $yr) ? $yr : '',
                        ];
                    }
                }
            }
        }

        return $verified;
    }

    private function batchLookupPubmedByDois(array $lowerDois): array
    {
        $doiToPmid = [];
        if (empty($lowerDois)) {
            return $doiToPmid;
        }

        foreach (array_chunk($lowerDois, 20) as $chunk) {
            $clauses = array_map(static fn (string $d): string => '"' . str_replace('"', '', $d) . '"[AID]', $chunk);
            $term = implode(' OR ', $clauses);
            $searchUrl = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&retmode=json&retmax=50&term=' . rawurlencode($term);
            $searchResp = $this->runConcurrentGetRequests(['esearch' => $searchUrl], 5.5);
            if (($searchResp['esearch']['status'] ?? 0) !== 200) {
                continue;
            }
            $sJson = json_decode($searchResp['esearch']['body'], true);
            $idList = $sJson['esearchresult']['idlist'] ?? [];
            if (!is_array($idList) || empty($idList)) {
                continue;
            }

            $cleanIds = array_values(array_filter(array_map(static fn ($v): string => preg_replace('/\D/', '', (string) $v) ?? '', $idList)));
            if (empty($cleanIds)) {
                continue;
            }

            $sumUrl = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&id=' . implode(',', $cleanIds);
            $sumResp = $this->runConcurrentGetRequests(['esummary' => $sumUrl], 5.5);
            if (($sumResp['esummary']['status'] ?? 0) !== 200) {
                continue;
            }
            $sumJson = json_decode($sumResp['esummary']['body'], true);
            $resultBlock = $sumJson['result'] ?? [];
            if (!is_array($resultBlock)) {
                continue;
            }
            foreach ($cleanIds as $pmid) {
                $doc = $resultBlock[$pmid] ?? null;
                if (!is_array($doc) || empty($doc['articleids']) || !is_array($doc['articleids'])) {
                    continue;
                }
                $pmTitle = rtrim(trim(strip_tags((string) ($doc['title'] ?? ''))), '.');
                foreach ($doc['articleids'] as $aid) {
                    if (strtolower((string) ($aid['idtype'] ?? '')) === 'doi' && !empty($aid['value'])) {
                        $doiToPmid[strtolower(trim((string) $aid['value']))] = [
                            'pmid' => $pmid,
                            'title' => $pmTitle,
                        ];
                    }
                }
            }
        }

        return $doiToPmid;
    }

    private function lookupPubmedByExactTitles(array &$parsedList, array $candidatesByIdx): void
    {
        $reqs = [];
        foreach ($candidatesByIdx as $idx => $title) {
            $reqs[$idx] = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&retmode=json&retmax=2&field=title&term=' . rawurlencode('"' . $title . '"');
        }
        $resps = $this->runConcurrentGetRequests($reqs, 4.0);
        $foundPmidsByIdx = [];
        $allPmids = [];
        foreach ($resps as $idx => $r) {
            if ($r['status'] !== 200) {
                continue;
            }
            $j = json_decode($r['body'], true);
            $ids = $j['esearchresult']['idlist'] ?? [];
            if (is_array($ids) && !empty($ids[0])) {
                $pid = preg_replace('/\D/', '', (string) $ids[0]) ?? '';
                if ($pid !== '') {
                    $foundPmidsByIdx[$idx] = $pid;
                    $allPmids[] = $pid;
                }
            }
        }

        if (empty($allPmids)) {
            return;
        }

        $sumUrl = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&id=' . implode(',', array_unique($allPmids));
        $sumResp = $this->runConcurrentGetRequests(['sum' => $sumUrl], 4.5);
        if (($sumResp['sum']['status'] ?? 0) !== 200) {
            return;
        }
        $sumJson = json_decode($sumResp['sum']['body'], true);
        $resMap = $sumJson['result'] ?? [];

        foreach ($foundPmidsByIdx as $idx => $pid) {
            $doc = $resMap[$pid] ?? null;
            if (!is_array($doc)) {
                continue;
            }
            $pmTitle = trim(strip_tags((string) ($doc['title'] ?? '')));
            $pmYear = '';
            if (preg_match('/\b(19\d{2}|20\d{2})\b/', (string) ($doc['pubdate'] ?? ''), $ym)) {
                $pmYear = $ym[1];
            }
            if ($parsedList[$idx]['year'] !== '' && $pmYear !== '' && abs((int) $parsedList[$idx]['year'] - (int) $pmYear) > 1) {
                continue;
            }
            if ($this->isStrictTitleMatch($parsedList[$idx]['title'], $pmTitle, $parsedList[$idx]['normFull'])) {
                $parsedList[$idx]['pmid'] = $pid;
            }
        }
    }

    private function matchGarudaDocumentId(string $html, array $info): string
    {
        if (!preg_match_all('/<div\s+class="article-item"[^>]*>(.*?)<p\s+class="action-article"/isu', $html, $blocks, PREG_SET_ORDER)) {
            return '';
        }

        foreach (array_slice($blocks, 0, 5) as $blk) {
            $snippet = $blk[1];
            if (!preg_match('/<a[^>]*href="\/documents\/detail\/(\d+)"[^>]*>(.*?)<\/a>/isu', $snippet, $tm)) {
                continue;
            }
            $docId = $tm[1];
            $rawTitle = html_entity_decode(strip_tags($tm[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanTitle = trim(preg_replace('/\s+/u', ' ', $rawTitle) ?? '');
            if ($cleanTitle === '') {
                continue;
            }

            if ($info['year'] !== '' && preg_match_all('/\b(19[6-9]\d|20[0-3]\d)\b/', strip_tags($snippet), $yMatches) && !empty($yMatches[1])) {
                $yearOk = false;
                foreach ($yMatches[1] as $candY) {
                    if (abs((int) $info['year'] - (int) $candY) <= 1) {
                        $yearOk = true;
                        break;
                    }
                }
                if (!$yearOk) {
                    continue;
                }
            }

            if (!$info['isInstitutional'] && $info['firstAuthor'] !== '') {
                $authorPlain = strtolower(html_entity_decode(strip_tags($snippet), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $surnameNeedle = strtolower(trim($info['firstAuthor']));
                if (mb_strlen($surnameNeedle, 'UTF-8') >= 3 && !str_contains($authorPlain, $surnameNeedle)) {
                    continue;
                }
            }

            if ($this->isStrictTitleMatch($info['title'], $cleanTitle, $info['normFull'])) {
                return $docId;
            }
        }

        return '';
    }

    private function normalizeTitleTokens(string $text): array
    {
        $clean = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $clean) ?? '';
        $stop = ['in', 'on', 'of', 'to', 'at', 'by', 'an', 'or', 'as', 'the', 'and', 'for', 'di', 'ke', 'dan', 'yang'];
        $tokens = [];
        foreach (preg_split('/\s+/u', trim($clean)) ?: [] as $w) {
            if ($w === '' || mb_strlen($w, 'UTF-8') < 2) {
                continue;
            }
            if (in_array($w, $stop, true)) {
                continue;
            }
            $tokens[] = $w;
        }
        return $tokens;
    }

    private function isStrictTitleMatch(string $expectedTitle, string $candidateTitle, string $normFullCitation = ''): bool
    {
        $expTokens = $this->normalizeTitleTokens($expectedTitle);
        $candTokens = $this->normalizeTitleTokens($candidateTitle);
        if (count($expTokens) < 3 || count($candTokens) < 3) {
            return false;
        }

        $expStr = implode(' ', $expTokens);
        $candStr = implode(' ', $candTokens);
        if ($expStr === $candStr) {
            return true;
        }

        if (count($expTokens) >= 5 && (str_starts_with($candStr, $expStr . ' ') || str_ends_with($candStr, ' ' . $expStr))) {
            return true;
        }

        $expUnique = array_values(array_unique($expTokens));
        $candUnique = array_values(array_unique($candTokens));
        $common = array_intersect($expUnique, $candUnique);
        $dice = (2.0 * count($common)) / (count($expUnique) + count($candUnique));
        if ($dice >= 0.86) {
            return true;
        }

        if (count($candTokens) >= 5 && mb_strlen($candStr, 'UTF-8') >= 28 && $normFullCitation !== '' && str_contains($normFullCitation, $candStr)) {
            return true;
        }

        return false;
    }

    private function authorSurnameMatches(string $expectedAuthor, string $candFamily, string $normFullCitation): bool
    {
        $a = strtolower(preg_replace('/[^\p{L}]/u', '', $expectedAuthor) ?? '');
        $b = strtolower(preg_replace('/[^\p{L}]/u', '', $candFamily) ?? '');
        if ($a === '' || $b === '') {
            return true;
        }
        if ($a === $b || str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }
        return mb_strlen($b, 'UTF-8') >= 3 && str_contains($normFullCitation, strtolower($candFamily));
    }

    private function isMajorInternationalPublisherDoi(string $doi): bool
    {
        return (bool) preg_match('/^10\.(1001|1002|1007|1016|1021|1038|1056|1080|1093|1097|1101|1111|1126|1136|1177|1186|1200|1371|1542|2147|2196|3389|3390|4103|7717|7759)\//i', $doi);
    }

    private function isIndonesianDoiPrefix(string $doi): bool
    {
        return (bool) preg_match('/^10\.(12928|14710|15294|18510|20473|20884|21109|21776|22146|22435|23887|24198|24843|25077|26555|26714|29313|30597|30604|30787|31983|32807|33024|33086|33490|33860|35451|35842|36565|36590|37341|47560|5220|54259|55606)\//i', $doi);
    }

    private function runConcurrentGetRequests(array $urlMap, float $timeoutSec = 4.2): array
    {
        if (empty($urlMap)) {
            return [];
        }
        $results = [];
        try {
            $httpClient = Application::get()->getHttpClient();
            $reqOpts = [
                'timeout' => $timeoutSec,
                'connect_timeout' => 2.5,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => 'KisiBerkelanjutanOJS/3.5 (https://kisiberkelanjutan.com; mailto:info@kisiberkelanjutan.com)',
                    'Accept' => 'application/json, text/html;q=0.9, */*;q=0.8',
                ],
            ];
            if (method_exists($httpClient, 'getAsync') && class_exists(\GuzzleHttp\Promise\Utils::class)) {
                foreach (array_chunk($urlMap, 6, true) as $batch) {
                    $promises = [];
                    foreach ($batch as $k => $url) {
                        $promises[$k] = $httpClient->getAsync($url, $reqOpts);
                    }
                    $settled = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
                    foreach ($settled as $k => $outcome) {
                        if (($outcome['state'] ?? '') === 'fulfilled' && isset($outcome['value'])) {
                            $resp = $outcome['value'];
                            $results[$k] = [
                                'status' => (int) $resp->getStatusCode(),
                                'body' => (string) $resp->getBody(),
                            ];
                        }
                    }
                }
                return $results;
            }
            foreach ($urlMap as $k => $url) {
                try {
                    $resp = $httpClient->request('GET', $url, $reqOpts);
                    $results[$k] = [
                        'status' => (int) $resp->getStatusCode(),
                        'body' => (string) $resp->getBody(),
                    ];
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
        }

        return $results;
    }
}
