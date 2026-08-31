<div class="page-header"><div><p class="eyebrow">플랫폼 본사 관리자</p><h1>운영 대시보드</h1></div><a class="button primary" href="/control/churches/create">교회·단체 생성</a></div>
<div class="cards">
    <article class="card"><span>전체 교회·단체</span><strong><?= (int) ($churchCounts['total'] ?? 0) ?></strong></article>
    <article class="card"><span>30일 체험</span><strong><?= (int) ($churchCounts['trials'] ?? 0) ?></strong></article>
    <article class="card"><span>유료·활성</span><strong><?= (int) ($churchCounts['active'] ?? 0) ?></strong></article>
    <article class="card"><span>전체 관리자</span><strong><?= (int) $userCount ?></strong></article>
</div>
<section class="panel"><h2>1단계 운영 기능</h2><p>현재는 플랫폼 로그인, 교회·단체 생성, 대표 관리자 생성, 초대장 전용 30일 체험과 교회별 데이터 격리가 구현되어 있습니다.</p><a href="/control/churches">교회·단체 관리로 이동</a></section>
