<?php $page = 'announcements';
require 'db.php';
require 'auth.php';

$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");

$announcements = [
    'news' => [],
    'updates' => [],
    'FAQs' => []
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($announcements[$row['category']])) {
        $announcements[$row['category']] = [];
    }
    $announcements[$row['category']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../CSS/index-style.css" rel="stylesheet">

    <style>
        body {
            background: #f0f2f5;
        }

        .announcement-wrapper {
            min-height: calc(100vh - 70px);
            padding: 28px 25px;
        }

        .section-label {
            font-size: 11px;
            font-weight: 700;
            color: #56585c;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            margin: 24px 0 8px 0;
        }

        .section-label:first-of-type {
            margin-top: 0;
        }

        /* PREP BANNER */
        .prep-banner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 8px;
        }

        .prep-col {
            background: #1e2647;
            opacity: 0.90;
            padding: 22px 20px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .prep-col:last-child {
            border-right: none;
        }

        .prep-letter {
            font-size: 48px;
            font-weight: 900;
            color: #FFB62F;
            line-height: 1;
            margin: 0 0 10px 0;
        }

        .prep-tip-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin: 0 0 5px 0;
        }

        .prep-tip-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.45);
            margin: 0;
            line-height: 1.6;
        }

        /* SHARED CARD WRAPPER */
        .ann-card {
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 10px;
            overflow: hidden;
        }

        /* NEWS CARDS */
        .news-card-new {
            padding: 15px 18px;
            border-bottom: 1px solid #f3f4f7;
            border-left: 4px solid #E4572E;
            transition: background 0.15s;
        }

        .news-card-new:last-child {
            border-bottom: none;
        }

        .news-card-new:hover {
            background: #fafbfc;
        }

        .news-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #E4572E;
            margin: 0 0 4px 0;
        }

        .news-card-desc {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 6px 0;
            line-height: 1.55;
        }

        .news-card-desc.collapsed {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-toggle-btn {
            background: none;
            border: 1px solid #e0e4ed;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: border-color 0.15s, color 0.15s;
        }

        .news-toggle-btn:hover {
            border-color: #E4572E;
            color: #E4572E;
        }

        /* UPDATE CARDS */
        .update-card-new {
            padding: 15px 18px;
            border-bottom: 1px solid #f3f4f7;
            border-left: 4px solid transparent;
            transition: background 0.15s;
        }

        .update-card-new:last-child {
            border-bottom: none;
        }

        .update-card-new:nth-child(odd) {
            border-left-color: #E4572E;
        }

        .update-card-new:nth-child(even) {
            border-left-color: #FFB62F;
        }

        .update-card-new:hover {
            background: #fafbfc;
        }

        .update-card-source {
            font-size: 11px;
            font-weight: 700;
            color: #E4572E;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin: 0 0 4px 0;
        }

        .update-card-source span {
            font-weight: 400;
            color: #b0b8cc;
            text-transform: none;
            letter-spacing: 0;
        }

        .update-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #1a1f36;
            margin: 0 0 5px 0;
            line-height: 1.3;
        }

        .update-card-desc {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 10px 0;
            line-height: 1.55;
        }

        /* FAQ */
        .faq-item {
            border-bottom: 1px solid #f3f4f7;
            border-left: 3px solid transparent;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 18px;
            transition: background 0.15s;
        }

        .faq-item:hover .faq-question {
            background: #fafbfc;
        }

        .faq-item.open .faq-question {
            background: #e8f1ff;
        }

        .faq-q-text {
            font-size: 14px;
            font-weight: 600;
            color: #1a1f36;
            margin: 0;
            transition: color 0.2s;
        }

        .faq-item.open .faq-q-text {
            color: #2563eb;
        }

        .faq-chevron {
            font-size: 12px;
            color: #b0b8cc;
            transition: transform 0.25s ease, color 0.2s;
            flex-shrink: 0;
        }

        .faq-item.open .faq-chevron {
            transform: rotate(180deg);
            color: #2563eb;
        }

        .faq-item.open {
            border-left-color: #2563eb;
        }

        .faq-answer {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.65;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.25s ease;
            padding: 0 18px;
            background: #f5f8ff;
        }

        .faq-item.open .faq-answer {
            max-height: 200px;
            padding: 10px 18px 14px 18px;
        }

        /* Empty state */
        .empty-state {
            padding: 20px 18px;
            font-size: 13px;
            color: #b0b8cc;
        }

        @media (max-width: 768px) {
            .announcement-wrapper {
                padding: 17px 10px;
            }

            .prep-banner {
                grid-template-columns: 1fr;
            }

            .prep-col {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .prep-letter {
                margin: 0;
                flex-shrink: 0;
            }

            .prep-content {
                flex: 1;
            }

            .prep-col:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <section class="announcement-wrapper">
        <div class="container-fluid">

            <!-- INTERNSHIP TIPS -->
            <p class="section-label">Internship Tips</p>
            <div class="prep-banner">
                <div class="prep-col">
                    <p class="prep-letter">P</p>
                    <p class="prep-tip-title">Prepare your documents early</p>
                    <p class="prep-tip-desc">Don't wait for deadlines. Have your resume, endorsement letter, and forms
                        ready ahead of time.</p>
                </div>
                <div class="prep-col">
                    <p class="prep-letter">R</p>
                    <p class="prep-tip-title">Research about the company</p>
                    <p class="prep-tip-desc">Know what the company does before your interview. It shows initiative and
                        professionalism.</p>
                </div>
                <div class="prep-col">
                    <p class="prep-letter">E</p>
                    <p class="prep-tip-title">Email professionally</p>
                    <p class="prep-tip-desc">Use proper text and a formal tone when communicating with companies.</p>
                </div>
                <div class="prep-col">
                    <p class="prep-letter">P</p>
                    <p class="prep-tip-title">Punctuality is everything</p>
                    <p class="prep-tip-desc">Being on time reflects your work ethic and attitude.</p>
                </div>
            </div>

            <!-- NEWS — from DB -->
            <p class="section-label">News</p>
            <div class="ann-card">
                <?php if (empty($announcements['news'])): ?>
                    <p class="empty-state">No news posted yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements['news'] as $news):
                        $message = htmlspecialchars($news['message']);
                        $isLong = strlen($message) > 120;
                        ?>
                        <div class="news-card-new">
                            <p class="news-card-title"><?= htmlspecialchars($news['title']) ?></p>
                            <p class="news-card-desc collapsed" id="newsDesc<?= $news['id'] ?>">
                                <?= $message ?>
                            </p>
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted">
                                    Posted <?= date('F j, Y', strtotime($news['created_at'])) ?>
                                </small>
                                <?php if ($isLong): ?>
                                    <button class="news-toggle-btn" onclick="toggleNews(<?= $news['id'] ?>, this)">
                                        Read more
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- UPDATES — from DB -->
            <p class="section-label">Updates</p>
            <div class="ann-card">
                <?php if (empty($announcements['updates'])): ?>
                    <p class="empty-state">No updates posted yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements['updates'] as $update): ?>
                        <div class="update-card-new">
                            <p class="update-card-source">
                                Update &nbsp;·&nbsp;
                                <span><?= date('F j, Y', strtotime($update['created_at'])) ?></span>
                            </p>
                            <p class="update-card-title"><?= htmlspecialchars($update['title']) ?></p>
                            <p class="update-card-desc"><?= htmlspecialchars($update['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- FAQs — from DB -->
            <p class="section-label">FAQs</p>
            <div class="ann-card">
                <?php if (empty($announcements['FAQs'])): ?>
                    <p class="empty-state">No FAQs posted yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements['FAQs'] as $faq): ?>
                        <div class="faq-item">
                            <div class="faq-question">
                                <p class="faq-q-text"><?= htmlspecialchars($faq['title']) ?></p>
                                <i class="fas fa-chevron-down faq-chevron"></i>
                            </div>
                            <p class="faq-answer"><?= htmlspecialchars($faq['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/index-script.js"></script>
    <script>
        // FAQ toggle
        document.querySelectorAll('.faq-item').forEach(item => {
            item.querySelector('.faq-question').addEventListener('click', () => {
                const isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!isOpen) item.classList.add('open');
            });
        });

        // News read more toggle
        function toggleNews(id, btn) {
            const desc = document.getElementById(`newsDesc${id}`);
            const collapsed = desc.classList.contains('collapsed');
            desc.classList.toggle('collapsed');
            btn.textContent = collapsed ? 'Read less' : 'Read more';
        }
    </script>
</body>

</html>