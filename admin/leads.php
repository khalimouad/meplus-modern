<?php
$page_title = "Demandes de Devis & Inscriptions";
$page_subtitle = "Messages reçus depuis le simulateur, les fiches formations et la page contact";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();

// Mise à jour de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (verify_csrf_token()) {
        $lead_id = (int)($_POST['id'] ?? 0);
        $new_status = trim($_POST['status'] ?? 'Nouveau');
        $db->prepare("UPDATE leads SET status = :status WHERE id = :id")->execute([
            ':status' => $new_status,
            ':id' => $lead_id
        ]);
        header("Location: leads.php?updated=1");
        exit;
    }
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verify_csrf_token()) {
        $del_id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM leads WHERE id = :id")->execute([':id' => $del_id]);
        header("Location: leads.php?deleted=1");
        exit;
    }
}

$leads = $db->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll();
?>

<div class="space-y-6">

  <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden shadow-xl">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
      <span><strong><?= count($leads) ?></strong> demande(s) enregistrée(s)</span>
      <span>Base de prospection directe</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-900/90 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4 font-semibold">Date & Source</th>
            <th class="py-3 px-4 font-semibold">Contact</th>
            <th class="py-3 px-4 font-semibold">Entreprise</th>
            <th class="py-3 px-4 font-semibold">Objet / Formation</th>
            <th class="py-3 px-4 font-semibold">Statut</th>
            <th class="py-3 px-4 font-semibold text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <?php if (empty($leads)): ?>
            <tr>
              <td colspan="6" class="py-12 text-center text-slate-500">
                <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-600 mb-2"></i>
                <span>Aucune demande pour le moment. Les formulaires de contact et de devis seront enregistrés ici.</span>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($leads as $lead): ?>
              <tr class="hover:bg-slate-800/30 transition">
                <td class="py-3.5 px-4 text-slate-400">
                  <div class="font-medium text-slate-200"><?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?></div>
                  <div class="text-[10px] text-cyan-400"><?= e($lead['source'] ?? 'Contact Web') ?></div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-white"><?= e($lead['name']) ?></div>
                  <div class="text-[11px] text-slate-400">
                    <a href="mailto:<?= e($lead['email']) ?>" class="hover:underline hover:text-cyan-300"><?= e($lead['email']) ?></a>
                  </div>
                  <?php if (!empty($lead['phone'])): ?>
                    <div class="text-[11px] text-slate-400">
                      <a href="tel:<?= e($lead['phone']) ?>" class="hover:underline"><?= e($lead['phone']) ?></a>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4 font-medium text-slate-300">
                  <?= e($lead['company'] ?: '—') ?>
                </td>
                <td class="py-3.5 px-4 max-w-xs">
                  <div class="font-medium text-white truncate"><?= e($lead['formation_title'] ?: 'Demande générale') ?></div>
                  <?php if (!empty($lead['message'])): ?>
                    <div class="text-[11px] text-slate-400 truncate mt-0.5"><?= e($lead['message']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="py-3.5 px-4">
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                    <select 
                      name="status" 
                      onchange="this.form.submit()" 
                      class="text-[11px] font-semibold py-1 px-2.5 rounded-full border bg-slate-800 focus:outline-none cursor-pointer <?= 
                        $lead['status'] === 'Traité' ? 'text-emerald-400 border-emerald-500/30' : 
                        ($lead['status'] === 'En cours' ? 'text-blue-400 border-blue-500/30' : 'text-amber-400 border-amber-500/30')
                      ?>"
                    >
                      <option value="Nouveau" <?= $lead['status'] === 'Nouveau' ? 'selected' : '' ?>>Nouveau</option>
                      <option value="En cours" <?= $lead['status'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                      <option value="Traité" <?= $lead['status'] === 'Traité' ? 'selected' : '' ?>>Traité</option>
                    </select>
                  </form>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <form method="POST" onsubmit="return confirm('Supprimer cette demande ?');" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                    <button type="submit" class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition" title="Supprimer">
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
