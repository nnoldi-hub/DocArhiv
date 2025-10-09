<?php
// modules/superadmin/logs_content.php
// Actions: clear/download logs
$success=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && hasRole(ROLE_SUPERADMIN)){
        try{ verify_csrf(); }catch(Exception $e){ $error='CSRF invalid'; }
        if(!$error){
                $act=$_POST['action']??'';
                if($act==='clear_php_errors'){
                        $path=LOGS_PATH.'/php_errors.log';
                        if(file_exists($path)) file_put_contents($path,'');
                        $success='Fișierul php_errors.log a fost golit.';
                }elseif($act==='clear_app_errors'){
                        $path=LOGS_PATH.'/error_'.date('Y-m-d').'.log';
                        if(file_exists($path)) file_put_contents($path,'');
                        $success='Logul de erori aplicație pentru azi a fost golit.';
                }elseif($act==='clear_security'){
                        $path=LOGS_PATH.'/security_'.date('Y-m-d').'.log';
                        if(file_exists($path)) file_put_contents($path,'');
                        $success='Logul de securitate pentru azi a fost golit.';
                }
        }
}

$db=getDBConnection();

// Filters
$q=trim($_GET['q']??'');
$action_type=$_GET['type']??'';
$company_id=(int)($_GET['company_id']??0);
$date_from=$_GET['from']??'';
$date_to=$_GET['to']??'';
$page=max(1,(int)($_GET['page']??1));
$per=20; $offset=($page-1)*$per;

// Build where for activity_logs
$where=['1=1']; $params=[];
if($q!==''){ $where[]='(description LIKE :q OR entity_type LIKE :q)'; $params[':q']='%'.$q.'%'; }
if($action_type!==''){ $where[]='action_type=:t'; $params[':t']=$action_type; }
if($company_id>0){ $where[]='company_id=:cid'; $params[':cid']=$company_id; }
if($date_from!==''){ $where[]='created_at>=:from'; $params[':from']=$date_from.' 00:00:00'; }
if($date_to!==''){ $where[]='created_at<=:to'; $params[':to']=$date_to.' 23:59:59'; }
$whereSql=implode(' AND ',$where);

// Count + data
try{
        $stmt=$db->prepare("SELECT COUNT(*) c FROM activity_logs WHERE $whereSql");
        foreach($params as $k=>$v){ $stmt->bindValue($k,$v); }
        $stmt->execute(); $total=(int)$stmt->fetch()['c'];

        $stmt=$db->prepare("SELECT * FROM activity_logs WHERE $whereSql ORDER BY created_at DESC LIMIT :off,:per");
        foreach($params as $k=>$v){ $stmt->bindValue($k,$v); }
        $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
        $stmt->bindValue(':per',$per,PDO::PARAM_INT);
        $stmt->execute(); $rows=$stmt->fetchAll();
}catch(Exception $e){ $total=0; $rows=[]; }

// Companies for filter
try{ $companies=$db->query("SELECT id, name as company_name FROM companies ORDER BY name")->fetchAll(); }catch(Exception $e){ $companies=[]; }

function render_pagination($total,$page,$per){
        $pages=max(1,ceil($total/$per)); if($pages<=1) return '';
        $base=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
        $qs=$_GET; $html='<nav><ul class="pagination justify-content-end">';
        for($i=1;$i<=$pages;$i++){ $qs['page']=$i; $link=$base.'?'.http_build_query($qs);
                $active=$i===$page?' active':''; $html.='<li class="page-item'.$active.'"><a class="page-link" href="'.$link.'">'.$i.'</a></li>'; }
        $html.='</ul></nav>'; return $html;
}
?>

<?php if($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" id="logsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-activity" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab"><i class="bi bi-person-lines-fill me-1"></i>Activitate</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-errors" data-bs-toggle="tab" data-bs-target="#errors" type="button" role="tab"><i class="bi bi-bug me-1"></i>Erori</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-security" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab"><i class="bi bi-shield-exclamation me-1"></i>Securitate</button>
    </li>
</ul>

<div class="tab-content" id="logsTabsContent">
    <div class="tab-pane fade show active" id="activity" role="tabpanel">
            <div class="card mb-3">
                <div class="card-body">
                    <form class="row gy-2 gx-3 align-items-end" method="GET">
                    <div class="col-md-3">
                        <label class="form-label">Căutare</label>
                        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="cuvinte cheie">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tip acțiune</label>
                        <select class="form-select" name="type">
                            <option value="">Toate</option>
                            <?php foreach (['login','logout','upload','download','view','edit','delete','share','search','create','update'] as $t): ?>
                                <option value="<?= $t ?>" <?= $action_type===$t?'selected':'' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Companie</label>
                        <select class="form-select" name="company_id">
                            <option value="0">Toate</option>
                            <?php foreach($companies as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $company_id===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">De la</label>
                        <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Până la</label>
                        <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                        <div class="col-md-12 d-flex gap-2 mt-2">
                            <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrează</button>
                            <a href="<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)) ?>" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                    <div class="d-flex justify-content-end mt-2">
                        <form method="POST" action="<?= APP_URL ?>/superadmin-export.php" target="_blank" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="dataset" value="activity_last_30">
                            <input type="hidden" name="format" value="csv">
                            <button class="btn btn-success"><i class="bi bi-download me-1"></i>Exportă 30 zile</button>
                        </form>
                    </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr>
                            <th>Data</th><th>Companie</th><th>User</th><th>Acțiune</th><th>Entitate</th><th>Descriere</th><th>IP</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($rows as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['created_at']) ?></td>
                                <td>#<?= (int)$r['company_id'] ?></td>
                                <td><?= (int)$r['user_id'] ?></td>
                                <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($r['action_type']) ?></span></td>
                                <td><?= htmlspecialchars(($r['entity_type']??'').($r['entity_id']?(' #'.$r['entity_id']):'')) ?></td>
                                <td><?= htmlspecialchars($r['description']??'') ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($r['ip_address']??'') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Total: <?= (int)$total ?> înregistrări</small>
                        <?= render_pagination($total,$page,$per) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="errors" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="bi bi-file-earmark-text me-1"></i>php_errors.log</h6>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= APP_URL ?>/superadmin-log-download.php?kind=php_errors">
                                            <i class="bi bi-download me-1"></i>Descarcă
                                        </a>
                                        <form method="POST" class="m-0">
                                            <?= csrf_field() ?><input type="hidden" name="action" value="clear_php_errors">
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Golește</button>
                                        </form>
                                    </div>
                                </div>
                    <div class="card-body" style="max-height:360px; overflow:auto; white-space:pre-wrap; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                        <?php $phpLog=LOGS_PATH.'/php_errors.log'; if(file_exists($phpLog)){ echo htmlspecialchars(implode("", array_slice(file($phpLog), -500))); } else { echo '<span class="text-muted">Nu există.</span>'; } ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Erori aplicație (azi)</h6>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= APP_URL ?>/superadmin-log-download.php?kind=app_error&date=<?= urlencode(date('Y-m-d')) ?>">
                                            <i class="bi bi-download me-1"></i>Descarcă
                                        </a>
                                        <form method="POST" class="m-0">
                                            <?= csrf_field() ?><input type="hidden" name="action" value="clear_app_errors">
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Golește</button>
                                        </form>
                                    </div>
                                </div>
                    <div class="card-body" style="max-height:360px; overflow:auto; white-space:pre-wrap; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                        <?php $appLog=LOGS_PATH.'/error_'.date('Y-m-d').'.log'; if(file_exists($appLog)){ echo htmlspecialchars(implode("", array_slice(file($appLog), -500))); } else { echo '<span class="text-muted">Nu există.</span>'; } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-shield-lock me-1"></i> Securitate (azi)</h6>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= APP_URL ?>/superadmin-log-download.php?kind=security&date=<?= urlencode(date('Y-m-d')) ?>">
                                <i class="bi bi-download me-1"></i>Descarcă
                            </a>
                            <form method="POST" class="m-0">
                                <?= csrf_field() ?><input type="hidden" name="action" value="clear_security">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Golește</button>
                            </form>
                        </div>
                    </div>
            <div class="card-body" style="max-height:420px; overflow:auto; white-space:pre-wrap; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                <?php $secLog=LOGS_PATH.'/security_'.date('Y-m-d').'.log'; if(file_exists($secLog)){ echo htmlspecialchars(implode("", array_slice(file($secLog), -500))); } else { echo '<span class="text-muted">Nu există.</span>'; } ?>
            </div>
        </div>
    </div>
</div>
