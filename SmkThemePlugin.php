<?php

/**
 * @file plugins/themes/smkTheme/SmkThemePlugin.php
 *
 * Hardened OJS 3.5 Child Theme extending DefaultThemePlugin for Kisi Berkelanjutan.
 */

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
    /** Cache TTL in seconds for aggregated journal metrics (30 minutes). */
    private const STATS_CACHE_TTL = 1800;

    /**
     * Initialize child theme options, styles, scripts, and template hooks.
     */
    public function init(): void
    {
        $this->setParent('defaultthemeplugin');

        $this->registerThemeOptions();

        $this->addStyle('smk-custom', 'styles/custom.css');
        $this->addScript('smk-theme', 'js/kisi-theme.js');

        Hook::add('TemplateManager::display', [$this, 'loadTemplateData']);
    }

    public function getDisplayName(): string
    {
        return __('plugins.themes.smkTheme.name');
    }

    public function getDescription(): string
    {
        return __('plugins.themes.smkTheme.description');
    }

    /**
     * Register customizable theme options shown under Website > Appearance > Theme.
     */
    private function registerThemeOptions(): void
    {
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

    /**
     * Inject sanitized context URLs and real server-side OJS metrics into frontend templates.
     */
    public function loadTemplateData(string $hookName, array $args): int
    {
        $templateMgr = $args[0] ?? null;
        $template = (string) ($args[1] ?? '');

        if (!$templateMgr || !$this->isFrontendTemplate($template)) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }

        $contextId = (int) $context->getId();
        $dispatcher = Application::get()->getDispatcher();
        $contextPath = $context->getPath();

        $urls = [
            'journalHome' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'index'),
            'archive' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'issue', 'archive'),
            'sdgCategory' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'catalog', 'category', ['sdgs']),
            'editorialTeam' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'about', 'editorialTeam'),
            'login' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'login'),
            'register' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'user', 'register'),
            'statistics' => $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $contextPath, 'statistics'),
            'sdgIconBase' => rtrim($request->getBaseUrl(), '/') . '/' . trim($this->getPluginPath(), '/') . '/assets/img/sdg/',
        ];

        $payload = [
            'journalName' => (string) $context->getLocalizedName(),
            'urls' => $urls,
            'heroShowStatsBar' => (bool) $this->getOption('heroShowStatsBar'),
            'launchLabel' => trim((string) $this->getOption('statsLaunchLabel')) ?: 'January 2024',
        ];

        if ($template === 'frontend/pages/indexJournal.tpl') {
            $templateMgr->assign('kisiHeroOptions', $this->buildHeroOptions());
            $payload['pageType'] = 'home';
            $payload['homeStats'] = $this->getCachedData(
                "kisi_smk_home_stats_v1_{$contextId}",
                fn () => $this->computeHomeStats($request, $contextPath, $contextId)
            );
        } elseif ($this->isStatisticsRequest($request, $templateMgr)) {
            $payload['pageType'] = 'statistics';
            $payload['fullStats'] = $this->getCachedData(
                "kisi_smk_full_stats_v1_{$contextId}",
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
            $payload['categoryArticleSdgs'] = $this->computeCatalogCategorySdgs($templateMgr);
        }

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
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

    /**
     * Cache wrapper with safe fallback if the cache driver fails.
     */
    private function getCachedData(string $key, callable $callback): array
    {
        try {
            return Cache::remember($key, self::STATS_CACHE_TTL, $callback);
        } catch (\Throwable $e) {
            return $callback();
        }
    }

    /**
     * Compute real OJS homepage metrics (researchers, total usage, SDG count, and top 5 articles).
     */
    private function computeHomeStats($request, string $contextPath, int $contextId): array
    {
        $dispatcher = Application::get()->getDispatcher();
        $submissions = $this->getPublishedSubmissionsIndexed($contextId);
        $issueTitlesById = $this->getPublishedIssueTitles($contextId);

        $researchersCount = $this->countUniquePublishedAuthors($contextId);
        $sdgArticlesCount = $this->countSdgPublishedArticles($contextId, $submissions);

        $allTimeTotals = $this->fetchSubmissionMetricsMap($contextId, null, null);
        $grandViews = 0;
        $grandDownloads = 0;
        foreach ($submissions as $subId => $meta) {
            $m = $allTimeTotals[$subId] ?? ['views' => 0, 'downloads' => 0];
            $grandViews += $m['views'];
            $grandDownloads += $m['downloads'];
        }

        // Top 5 for current calendar month; fall back transparently to last 30 days, then all-time.
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

    /**
     * Compute real OJS 3.5 statistics for the /statistics dashboard page.
     */
    private function computeFullStatistics($request, string $contextPath, int $contextId): array
    {
        $dispatcher = Application::get()->getDispatcher();
        $submissions = $this->getPublishedSubmissionsIndexed($contextId);
        $issues = $this->getPublishedIssuesData($request, $contextPath, $contextId);

        $allTimeTotals = $this->fetchSubmissionMetricsMap($contextId, null, null);

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

    /**
     * Collect real article metrics, dependent XML image URLs, and SDG IDs for article view pages.
     */
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
            $metrics = $this->fetchSingleArticleTotalsByType($submissionId, $contextId, $templateMgr);
            $views = $metrics['views'];
            $downloads = $metrics['downloads'];

            if (!$publication && method_exists($article, 'getCurrentPublication')) {
                $publication = $article->getCurrentPublication();
            }

            if ($publication) {
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

                    if ($subFileId > 0 && preg_match('/^(xml|jats|full[\s-]*text\s*xml)$/i', trim($label))) {
                        $xmlDependentImages = $this->resolveDependentImagesMap(
                            $request,
                            $dispatcher,
                            $contextPath,
                            $article->getBestId(),
                            $galleyBestId,
                            $subFileId
                        );
                    }
                }

                $sdgNumbers = $this->extractPublicationSdgs($publication);
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
        ];
    }

    /**
     * Resolve exact dependent image URLs attached to an XML galley without browser probing.
     */
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

    /**
     * Extract SDG numbers per article on the catalog category page to prevent browser N+1 fetches.
     */
    private function computeCatalogCategorySdgs($templateMgr): array
    {
        $result = [];
        $submissions = $templateMgr->getTemplateVars('publishedSubmissions')
            ?? $templateMgr->getTemplateVars('submissions')
            ?? $templateMgr->getTemplateVars('articles')
            ?? [];

        foreach ($submissions as $submission) {
            if (!is_object($submission) || !method_exists($submission, 'getId')) {
                continue;
            }
            $publication = method_exists($submission, 'getCurrentPublication')
                ? $submission->getCurrentPublication()
                : null;
            if (!$publication) {
                continue;
            }
            $sdgs = $this->extractPublicationSdgs($publication);
            if (!empty($sdgs)) {
                $result[(string) $submission->getId()] = $sdgs;
                if (method_exists($submission, 'getBestId')) {
                    $result[(string) $submission->getBestId()] = $sdgs;
                }
            }
        }

        return $result;
    }

    /**
     * Build a map of [submissionId => ['views' => int, 'downloads' => int]] using OJS 3.5 stats service.
     */
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

    /**
     * Fetch lifetime views and downloads for a single submission, mirroring article list totals.
     */
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

        try {
            $statsService = app()->get('publicationStats');
            if ($statsService && method_exists($statsService, 'getTotalsByType')) {
                $byType = $statsService->getTotalsByType($submissionId, $contextId, null, null);
                $parsed = $extractTotalsFromMap($byType);
                $views = max($views, $parsed['views']);
                $downloads = max($downloads, $parsed['downloads']);
            }

            if ($statsService && method_exists($statsService, 'getTotals')) {
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
            // Fall back safely to direct database totals below
        }

        if ($views === 0 || $downloads === 0) {
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
                // Ignore if metrics_submission is unavailable
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

    /**
     * Fetch genuine daily metrics for the last 30 days from OJS publication & context stats services.
     */
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
            // Keep zero defaults
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
            // Keep zero defaults
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

    /**
     * Fetch genuine Top 10 visitor countries from OJS geo metrics tables when available.
     */
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

    /**
     * Wrap stats service calls in case an OJS minor release expects DateTimeInterface for dateStart/dateEnd.
     */
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

    private function getPublishedSubmissionsIndexed(int $contextId): array
    {
        $indexed = [];
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
                $indexed[$subId] = [
                    'id' => $subId,
                    'bestId' => (string) $submission->getBestId(),
                    'title' => trim(strip_tags((string) $publication->getLocalizedFullTitle())) ?: ('Article #' . $subId),
                    'issueId' => (int) ($publication->getData('issueId') ?? 0),
                    'publication' => $publication,
                ];
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $indexed;
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
                ->select('a.author_id', 'ag.setting_value as given_name', 'af.setting_value as family_name')
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
            // Fall back to subject-based SDG matching below
        }

        $count = 0;
        foreach ($submissions as $meta) {
            if (!empty($this->extractPublicationSdgs($meta['publication']))) {
                $count++;
            }
        }
        return $count;
    }

    private function extractPublicationSdgs($publication): array
    {
        $texts = [];
        foreach (['subjects', 'keywords'] as $field) {
            $val = $publication->getLocalizedData($field);
            if (is_array($val)) {
                $texts[] = implode(' ; ', $val);
            } elseif (is_string($val) && $val !== '') {
                $texts[] = $val;
            }
        }

        $combined = trim(implode(' ; ', $texts));
        if ($combined === '') {
            return [];
        }

        $detected = [];
        if (preg_match_all('/(?:sdg|sdgs|goal|tujuan)\s*[-:#]?\s*(1[0-7]|0?[1-9])\b/i', $combined, $matches)) {
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
}
