{**
 * plugins/themes/smkTheme/templates/frontend/pages/indexJournal.tpl
 *
 * Hardened eLife-Style Homepage for Kisi Berkelanjutan (OJS 3.5 Child Theme).
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div class="page_index_journal">

	{* --- HERO SECTION (Configurable from Website > Appearance > Theme) --- *}
	<div class="elife-hero-section">
		<div class="elife-hero-content">
			<h1 class="elife-journal-title">
				{$currentJournal->getLocalizedName()|escape}
			</h1>
			<p class="elife-journal-scope">
				{if $kisiHeroOptions.scopeText}
					{$kisiHeroOptions.scopeText|escape}
				{else}
					Every Result Matters to Enhance Our Perspective, Policy, and Practice in Health, Medicine, and Biomedicine.
				{/if}
			</p>

			<div class="elife-hero-actions">
				<a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="about" op="submissions"}" class="btn-elife-primary">
					{$kisiHeroOptions.primaryCtaLabel|default:"Submit your research"|escape}
				</a>
				<a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive"}" class="btn-elife-text">
					{$kisiHeroOptions.secondaryCtaLabel|default:"Explore all articles"|escape} &rarr;
				</a>
			</div>

			{if $kisiHeroOptions.acceptanceRate || $kisiHeroOptions.firstDecisionDays || $kisiHeroOptions.acceptDays}
				<div class="elife-metrics">
					{assign var="hasPrevHeroMetric" value=false}
					{if $kisiHeroOptions.acceptanceRate}
						<span><strong>{$kisiHeroOptions.acceptanceRate|escape}</strong> Acceptance Rate</span>
						{assign var="hasPrevHeroMetric" value=true}
					{/if}
					{if $kisiHeroOptions.firstDecisionDays}
						{if $hasPrevHeroMetric}<span class="divider">|</span>{/if}
						<span><strong>{$kisiHeroOptions.firstDecisionDays|escape}</strong> Days to 1st Decision</span>
						{assign var="hasPrevHeroMetric" value=true}
					{/if}
					{if $kisiHeroOptions.acceptDays}
						{if $hasPrevHeroMetric}<span class="divider">|</span>{/if}
						<span><strong>{$kisiHeroOptions.acceptDays|escape}</strong> Days to Accept</span>
					{/if}
				</div>
			{/if}
		</div>
	</div>
	{* --- END HERO SECTION --- *}

	{call_hook name="Templates::Index::journal"}

	<div class="elife-content-container">
		{if $highlights && $highlights->count()}
			{include file="frontend/components/highlights.tpl" highlights=$highlights}
		{/if}

		{if $activeTheme && !$activeTheme->getOption('useHomepageImageAsHeader') && $homepageImage}
			<div class="homepage_image">
				<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}"{if $homepageImage.altText} alt="{$homepageImage.altText|escape}"{/if}>
			</div>
		{/if}

		{if $categories && $categories->count() > 0}
			{include file="frontend/components/categoryHeader.tpl" categories=$categories}
		{/if}

		{* Journal Description (when enabled in Theme Options) *}
		{if $activeTheme && $activeTheme->getOption('showDescriptionInJournalIndex')}
			<section class="homepage_about">
				<a id="homepageAbout"></a>
				<h2>{translate key="about.aboutContext"}</h2>
				{$currentContext->getLocalizedData('description')|strip_unsafe_html}
			</section>
		{/if}

		{* Latest Published Publications (OJS 3.5 feature) *}
		{if $publishedPublications && $publishedPublications->count()}
			{include file="frontend/objects/latest_article.tpl" articles=$publishedPublications heading="h2"}
		{/if}

		{* --- 1. LATEST RESEARCH (CURRENT ISSUE) --- *}
		{if $issue}
			<section class="elife-latest-articles" style="margin-top: 50px;">
				<a id="homepageIssue"></a>

				<div class="section-header-elife">
					<span class="section-eyebrow">Discover Science</span>
					<h2 class="elife-section-title">
						<span class="title-bold">Latest</span> <span class="title-italic">Research</span>
					</h2>
				</div>

				<div class="elife-current-issue-title" style="font-size: 0.95rem; color: #555; margin-bottom: 30px;">
					{$issue->getIssueIdentification()|escape}
				</div>

				<div class="elife-native-toc">
					{include file="frontend/objects/issue_toc.tpl" heading="h3"}
				</div>

				<div style="margin-top: 30px;">
					<a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive"}" style="color: #0076c6; text-decoration: none; font-weight: 600; font-size: 1rem;">
						Explore all articles &rarr;
					</a>
				</div>
			</section>
		{else}
			<p>{translate key="current.noCurrentIssueDesc"}</p>
		{/if}

		{* --- 2. LATEST ANNOUNCEMENTS --- *}
		{if $numAnnouncementsHomepage && $announcements}
			<section class="elife-latest-announcements" style="margin-top: 70px; margin-bottom: 50px; padding-top: 40px; border-top: 2px solid #212121;">
				<div class="section-header-elife">
					<span class="section-eyebrow">Stay Updated</span>
					<h2 class="elife-section-title">
						<span class="title-bold">Latest</span> <span class="title-italic">News &amp; Announcements</span>
					</h2>
				</div>

				<div class="elife-native-announcements">
					{include file="frontend/objects/announcements_list.tpl" numAnnouncements=$numAnnouncementsHomepage}
				</div>

				<div style="margin-top: 25px;">
					<a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="announcement"}" style="color: #0076c6; text-decoration: none; font-weight: 600; font-size: 1rem;">
						View all announcements &rarr;
					</a>
				</div>
			</section>
		{/if}

		{* Additional Homepage Content *}
		{if $additionalHomeContent}
			<div class="additional_content">
				{$additionalHomeContent|strip_unsafe_html}
			</div>
		{/if}
	</div>
</div>

{include file="frontend/components/footer.tpl"}
