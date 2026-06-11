<?php
require_once '../includes/auth.php';
requireMember();

$pageTitle = 'Search Books';
$activePage = 'search';

$search    = clean($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$langFilter = clean($_GET['lang'] ?? '');
$books = null;
$searched = false;

if ($search || $catFilter || $langFilter) {
    $searched = true;
    $where = "WHERE b.status='active'";
    if ($search) $where .= " AND (b.title LIKE '%$search%' OR b.isbn LIKE '%$search%' OR a.name LIKE '%$search%' OR b.publisher LIKE '%$search%')";
    if ($catFilter) $where .= " AND b.category_id=$catFilter";
    if ($langFilter) $where .= " AND b.language='$langFilter'";

    $books = $conn->query("SELECT b.*, a.name AS author_name, c.name AS category_name
        FROM books b
        LEFT JOIN authors a ON a.id=b.author_id
        LEFT JOIN categories c ON c.id=b.category_id
        $where ORDER BY b.title LIMIT 60");
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
include 'layout.php';
?>

<div class="card">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="flex:1;min-width:200px;margin:0;">
        <label>Search</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Title, ISBN, author, publisher...">
      </div>
      <div class="form-group" style="min-width:150px;margin:0;">
        <label>Category</label>
        <select name="cat" class="form-control">
          <option value="">All Categories</option>
          <?php while ($c=$categories->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group" style="min-width:120px;margin:0;">
        <label>Language</label>
        <select name="lang" class="form-control">
          <option value="">All</option>
          <option value="English" <?= $langFilter==='English'?'selected':'' ?>>English</option>
          <option value="Sinhala" <?= $langFilter==='Sinhala'?'selected':'' ?>>Sinhala</option>
          <option value="Tamil"   <?= $langFilter==='Tamil'  ?'selected':'' ?>>Tamil</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit" style="height:38px;">🔍 Search</button>
      <a href="search.php" class="btn btn-secondary" style="height:38px;">Clear</a>
    </form>
  </div>
</div>

<?php if ($searched): ?>
  <?php if (!$books || $books->num_rows === 0): ?>
    <div class="empty-state"><div class="icon">📚</div><p>No books found matching your search.</p></div>
  <?php else: ?>
    <p style="color:#888;font-size:13px;margin-bottom:12px;">Found <?= $books->num_rows ?> book(s)</p>
    <div class="book-grid">
      <?php while ($b=$books->fetch_assoc()): ?>
        <div class="book-card" onclick="openModal('book_<?= $b['id'] ?>')">
          <img src="<?= $b['cover_image']!=='no-cover.png' ? UPLOAD_URL.htmlspecialchars($b['cover_image']) : '' ?>"
               style="width:100%;height:180px;object-fit:cover;background:#eee;"
               onerror="this.style.background='#e9ecef';this.style.display='block';"
               alt="<?= htmlspecialchars($b['title']) ?>">
          <div class="book-card-body">
            <h4><?= htmlspecialchars($b['title']) ?></h4>
            <div class="author"><?= htmlspecialchars($b['author_name'] ?? 'Unknown') ?></div>
            <span class="avail <?= $b['available_copies']>0?'yes':'no' ?>">
              <?= $b['available_copies']>0 ? "✅ Available ({$b['available_copies']})" : "❌ Not Available" ?>
            </span>
          </div>
        </div>

        <!-- Book Detail Modal -->
        <div class="modal-overlay" id="book_<?= $b['id'] ?>">
          <div class="modal">
            <div class="modal-header">
              <h3><?= htmlspecialchars($b['title']) ?></h3>
              <span class="modal-close" onclick="closeModal('book_<?= $b['id'] ?>')">✕</span>
            </div>
            <table style="width:100%;font-size:13px;">
              <tr><td style="padding:6px;color:#888;width:35%;">ISBN</td><td><?= htmlspecialchars($b['isbn']??'—') ?></td></tr>
              <tr style="background:#f9f9f9;"><td style="padding:6px;color:#888;">Author</td><td><?= htmlspecialchars($b['author_name']??'—') ?></td></tr>
              <tr><td style="padding:6px;color:#888;">Publisher</td><td><?= htmlspecialchars($b['publisher']??'—') ?></td></tr>
              <tr style="background:#f9f9f9;"><td style="padding:6px;color:#888;">Edition</td><td><?= htmlspecialchars($b['edition']??'—') ?></td></tr>
              <tr><td style="padding:6px;color:#888;">Category</td><td><?= htmlspecialchars($b['category_name']??'—') ?></td></tr>
              <tr style="background:#f9f9f9;"><td style="padding:6px;color:#888;">Language</td><td><?= htmlspecialchars($b['language']??'—') ?></td></tr>
              <tr><td style="padding:6px;color:#888;">Shelf</td><td><strong><?= htmlspecialchars($b['shelf_location']??'—') ?></strong></td></tr>
              <tr style="background:#f9f9f9;"><td style="padding:6px;color:#888;">Total Copies</td><td><?= $b['total_copies'] ?></td></tr>
              <tr><td style="padding:6px;color:#888;">Available</td><td>
                <span class="badge badge-<?= $b['available_copies']>0?'success':'danger' ?>">
                  <?= $b['available_copies'] ?> copy(ies)
                </span>
              </td></tr>
            </table>
            <?php if ($b['description']): ?>
              <p style="margin-top:12px;font-size:13px;color:#555;border-top:1px solid #eee;padding-top:10px;">
                <?= htmlspecialchars($b['description']) ?>
              </p>
            <?php endif; ?>
            <div style="margin-top:16px;">
              <button class="btn btn-secondary" onclick="closeModal('book_<?= $b['id'] ?>')">Close</button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="empty-state" style="margin-top:40px;">
    <div class="icon">🔍</div>
    <p>Use the search box above to find books by title, ISBN, author, or publisher.</p>
  </div>
<?php endif; ?>

<?php include 'layout_end.php'; ?>
