<div class="page-header"><div><p class="eyebrow">플랫폼 본사 관리자</p><h1>운영 대시보드</h1></div><a class="button primary" href="/control/churches/create">교회·단체 생성</a></div>
<div class="cards">
    <article class="card"><span>전체 교회·단체</span><strong><?= (int) ($churchCounts['total'] ?? 0) ?></strong></article>
    <article class="card"><span>30일 체험</span><strong><?= (int) ($churchCounts['trials'] ?? 0) ?></strong></article>
    <article class="card"><span>유료·활성</span><strong><?= (int) ($churchCounts['active'] ?? 0) ?></strong></article>
    <article class="card"><span>전체 관리자</span><strong><?= (int) $userCount ?></strong></article>
</div>
<section class="panel"><h2>1단계 운영 기능</h2><p>현재는 플랫폼 로그인, 교회·단체 생성, 대표 관리자 생성, 초대장 전용 30일 체험과 교회별 데이터 격리가 구현되어 있습니다.</p><a href="/control/churches">교회·단체 관리로 이동</a></section>
<?php
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$pct=static fn($used,$limit)=>$limit>0?min(100,round($used/$limit*100,1)):0;
?>
<section class="panel"><h2>교회 운영 현황</h2><p class="muted">조회 전용 화면이며 교회 데이터나 구독을 변경하지 않습니다. 최대 200건을 표시합니다.</p>
<form method="get" action="/control" class="form-grid"><label>검색<input name="q" value="<?=$e($filters['query'])?>" maxlength="100" placeholder="교회명 또는 주소 ID"></label><label>교회 상태<select name="church_status"><option value="">전체</option><?php foreach(['trial'=>'체험','active'=>'활성','suspended'=>'중지','archived'=>'보관'] as $v=>$l):?><option value="<?=$v?>" <?=$filters['churchStatus']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></label><label>상품 계열<select name="product_family"><option value="">전체</option><?php foreach(['invitation'=>'초대장','website'=>'홈페이지','custom'=>'맞춤'] as $v=>$l):?><option value="<?=$v?>" <?=$filters['family']===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></label><label>구독 상태<select name="subscription_status"><option value="">전체</option><?php foreach(['trialing','active','past_due','suspended','cancelled','expired'] as $v):?><option value="<?=$v?>" <?=$filters['subscriptionStatus']===$v?'selected':''?>><?=$e($v)?></option><?php endforeach;?></select></label><button type="submit">조회</button></form>
<div class="table-wrap"><table><thead><tr><th>교회·단체</th><th>상태</th><th>상품·구독</th><th>월 트래픽</th><th>저장공간</th><th>가입일</th></tr></thead><tbody><?php foreach($operations as $row):?><tr><td><strong><?=$e($row['name'])?></strong><br><small><?=$e($row['slug'])?></small></td><td><?=$e($row['status'])?></td><td><?=$e($row['product_family'])?> · <?=$e($row['plan_name']??'-')?> / <?=$e($row['subscription_status']??'-')?></td><td><?=number_format((int)$row['traffic_bytes']/1048576,1)?>MB / <?=$row['traffic_limit']>0?number_format((int)$row['traffic_limit']/1073741824,1).'GB':'-'?><br><progress max="100" value="<?=$pct((int)$row['traffic_bytes'],(int)$row['traffic_limit'])?>"></progress></td><td><?=number_format((int)$row['storage_bytes']/1048576,1)?>MB / <?=$row['storage_limit']>0?number_format((int)$row['storage_limit']/1048576).'MB':'-'?><br><progress max="100" value="<?=$pct((int)$row['storage_bytes'],(int)$row['storage_limit'])?>"></progress></td><td><?=$e($row['created_at'])?></td></tr><?php endforeach;?><?php if($operations===[]):?><tr><td colspan="6">조건에 맞는 교회가 없습니다.</td></tr><?php endif;?></tbody></table></div></section>

<section class="panel">
    <h2>트래픽 한도 요청</h2>
    <?php if (!empty($success)): ?><p class="notice success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="notice danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (($quotaRequests ?? []) === []): ?>
        <p>처리 대기 중인 요청이 없습니다.</p>
    <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>교회·단체</th><th>요청</th><th>요청자</th><th>사유</th><th>접수일</th><th>처리</th></tr></thead><tbody>
        <?php foreach ($quotaRequests as $request): ?>
            <tr>
                <td><?= htmlspecialchars((string)$request['church_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $request['request_type']==='traffic_reset' ? '이번 달 트래픽 초기화' : '트래픽 '.number_format((int)$request['requested_bytes']/1073741824).'GB 증액' ?></td>
                <td><?= htmlspecialchars((string)$request['requester_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= nl2br(htmlspecialchars((string)$request['reason'], ENT_QUOTES, 'UTF-8')) ?></td>
                <td><?= htmlspecialchars((string)$request['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <form method="post" action="/control/quota-requests/<?= (int)$request['id'] ?>/approve" class="inline-form">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="review_note" maxlength="500" placeholder="처리 메모">
                        <button class="button primary" type="submit">승인</button>
                    </form>
                    <form method="post" action="/control/quota-requests/<?= (int)$request['id'] ?>/reject" class="inline-form">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="review_note" maxlength="500" placeholder="반려 사유">
                        <button class="button danger" type="submit">반려</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>유료 전환 검토 요청</h2>
    <p class="muted">먼저 검토를 완료하고, 실제 입금·결제 확인 뒤 참조값을 기록해야 구독이 전환됩니다.</p>
    <?php if (($subscriptionRequests ?? []) === []): ?>
        <p>검토 대기 중인 유료 전환 요청이 없습니다.</p>
    <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>교회·단체</th><th>요청 상품</th><th>월 금액</th><th>요청자</th><th>메모</th><th>접수일</th><th>검토</th></tr></thead><tbody>
        <?php foreach ($subscriptionRequests as $request): ?>
            <tr><td><?= htmlspecialchars((string)$request['church_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$request['plan_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((int)$request['price_krw']) ?>원</td><td><?= htmlspecialchars((string)$request['requester_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= nl2br(htmlspecialchars((string)($request['reason'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td><td><?= htmlspecialchars((string)$request['created_at'], ENT_QUOTES, 'UTF-8') ?></td><td>
                <?php if($request['status']==='pending'): ?><form method="post" action="/control/subscription-requests/<?= (int)$request['id'] ?>/payment-review" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="text" name="review_note" maxlength="500" placeholder="결제 안내·검토 메모"><button class="button primary" type="submit">결제 확인 대기</button></form>
                <form method="post" action="/control/subscription-requests/<?= (int)$request['id'] ?>/reject" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="text" name="review_note" maxlength="500" placeholder="반려 사유"><button class="button danger" type="submit">반려</button></form><?php else: ?>
                <form method="post" action="/control/subscription-requests/<?= (int)$request['id'] ?>/complete" class="inline-form"><input type="hidden" name="_token" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="text" name="payment_reference" maxlength="100" required placeholder="결제 참조값"><input type="text" name="review_note" maxlength="500" placeholder="확인 메모"><button class="button primary" type="submit">결제 확인·전환 완료</button></form><?php endif; ?>
            </td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
