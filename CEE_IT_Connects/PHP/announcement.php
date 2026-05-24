<?php $page = 'announcements';
require 'auth.php'; ?>

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

        .section-label:first-of-type { margin-top: 0; }

        /* PREP BANNER */
        .prep-banner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .prep-col {
            background: #1e2647;
            padding: 22px 20px;
            border-right: 1px solid rgba(255,255,255,0.08);
        }

        .prep-col:last-child { border-right: none; }

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
            color: rgba(255,255,255,0.45);
            margin: 0;
            line-height: 1.6;
        }

        /* UPDATE CARDS */
        .ann-card {
            background: #fff;
            border: 1px solid #e8eaf0;
            border-radius: 10px;
            overflow: hidden;
        }

        .update-card-new {
            padding: 15px 18px;
            border-bottom: 1px solid #f3f4f7;
            border-left: 4px solid transparent;
            transition: background 0.15s;
        }

        .update-card-new:last-child { border-bottom: none; }
        .update-card-new:nth-child(1) { border-left-color: #E4572E; }
        .update-card-new:nth-child(2) { border-left-color: #FFB62F; }
        .update-card-new:hover { background: #fafbfc; }

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
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .update-card-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            border: 1px solid #e0e4ed;
            border-radius: 6px;
            padding: 4px 12px;
            text-decoration: none;
            transition: border-color 0.15s, color 0.15s;
        }

        .update-card-link:hover {
            border-color: #E4572E;
            color: #E4572E;
        }

        /* FAQ */
        .faq-item {
            border-bottom: 1px solid #f3f4f7;
            border-left: 3px solid transparent;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .faq-item:last-child { border-bottom: none; }

        .faq-question {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 18px;
            transition: background 0.15s;
        }

        .faq-item:hover .faq-question { background: #fafbfc; }

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

        .faq-item.open .faq-q-text { color: #2563eb; }

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

        .faq-item.open { border-left-color: #2563eb; }

        .faq-answer {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.65;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.25s ease;
            padding: 0 18px;
            background: #f5f8ff;
            margin-bottom: 0 !important;
        }

        .faq-item.open .faq-answer {
            max-height: max-content;
            padding: 10px 18px 14px 18px;
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
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }

            .prep-letter {
                margin: 0;
                flex-shrink: 0;
            }

            .prep-content{
                flex: 1;
            }

            .prep-col:last-child { border-bottom: none; }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <section class="announcement-wrapper">
        <div class="container-fluid">

            <!-- INTERNSHIP TIPS — PREP Banner -->
            <p class="section-label">Internship Tips</p>
            <div class="prep-banner">
                <div class="prep-col">
                    <p class="prep-letter">P</p>
                    <div class="prep-content">
                        <p class="prep-tip-title">Prepare your documents early</p>
                        <p class="prep-tip-desc">Don't wait for deadlines. Have your resume, endorsement letter, and forms ready ahead of time.</p>
                    </div>
                </div>
                <div class="prep-col">
                    <p class="prep-letter">R</p>
                    <div class="prep-content">
                        <p class="prep-tip-title">Research about the company</p>
                        <p class="prep-tip-desc">Know what the company does before your interview. It shows initiative and professionalism.</p>
                    </div>
                </div>
                <div class="prep-col">
                    <p class="prep-letter">E</p>
                    <div class="prep-content">
                        <p class="prep-tip-title">Email professionally</p>
                        <p class="prep-tip-desc">Use proper text and a formal tone when communicating with companies.</p>
                    </div>
                </div>
                <div class="prep-col">
                    <p class="prep-letter">P</p>
                    <div class="prep-content">
                        <p class="prep-tip-title">Punctuality is everything</p>
                        <p class="prep-tip-desc">Being on time reflects your work ethic and attitude.</p>
                    </div>
                </div>
            </div>

            <!-- UPDATES -->
            <p class="section-label">Updates</p>
            <div class="ann-card">
                <div class="update-card-new">
                    <p class="update-card-source">Bukas &nbsp;·&nbsp; <span>May 3, 2026 · 6:00 PM</span></p>
                    <p class="update-card-title">How to Prepare for your Internship</p>
                    <p class="update-card-desc">From applications to the actual work, internships give you a glimpse of what it's like to have a full-time job. They help you learn more about the industry you want to work in and train you with different skills.</p>
                    <a href="https://bukas.ph/blog/how-to-prepare-for-your-internship/" class="update-card-link" target="_blank"><i class="fas fa-external-link-alt"></i> View Original Source</a>
                </div>
                <div class="update-card-new">
                    <p class="update-card-source">prosple &nbsp;·&nbsp; <span>March 26, 2026 · 9:07 AM</span></p>
                    <p class="update-card-title">Tips to Ace your Internship</p>
                    <p class="update-card-desc">Ready to make the most out of your internship? Check out these tips to ace your internship journey and set yourself up for success!</p>
                    <a href="https://ph.prosple.com/on-the-job/tips-to-ace-your-internship" class="update-card-link" target="_blank"><i class="fas fa-external-link-alt"></i> View Original Source</a>
                </div>
            </div>

            <!-- FAQ -->
            <p class="section-label">FAQs</p>
            <div class="ann-card">
                <div class="faq-item">
                    <div class="faq-question">
                        <p class="faq-q-text">Who can apply for internships?</p>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <p class="faq-answer">All enrolled CEE IT students who have met the required academic and departmental requirements may apply for internship opportunities.</p>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <p class="faq-q-text">What documents are required?</p>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <p class="faq-answer">Students are required to submit a resume, application form, endorsement letter, and other documents specified by the partner institution.</p>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <p class="faq-q-text">How long is the internship period?</p>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <p class="faq-answer">Internship duration depends on the program requirements and typically ranges from 300 to 600 hours.</p>
                </div>
                <div class="faq-item">
                    <div class="faq-question">
                        <p class="faq-q-text">Can I apply to multiple companies?</p>
                        <i class="fas fa-chevron-down faq-chevron"></i>
                    </div>
                    <p class="faq-answer">Yes. Students may apply to multiple internship listings, but acceptance is subject to approval and availability.</p>
                </div>
            </div>

        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/index-script.js"></script>
    <script>
        document.querySelectorAll('.faq-item').forEach(item => {
            item.querySelector('.faq-question').addEventListener('click', () => {
                const isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!isOpen) item.classList.add('open');
            });
        });
    </script>
</body>

</html>