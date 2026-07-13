<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Foodie AI kết nối menu QR tại bàn, màn hình bếp, thu ngân và báo cáo vận hành trong một hệ thống dành cho F&B.">
    <meta name="theme-color" content="#102b24">
    <title>Foodie AI — Vận hành F&amp;B từ bàn đến báo cáo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&amp;family=Manrope:wght@500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/landing.css?v=<?= filemtime(__DIR__ . '/assets/css/landing.css') ?>">
</head>
<body>
<header class="landing-header" id="top">
    <div class="nav-shell">
        <a class="brand" href="#top" aria-label="Foodie AI - Trang chủ">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 42 42" role="img"><path d="M9 13.5h24v8.75C33 29.3 28.25 34 21 34S9 29.3 9 22.25V13.5Z"/><path d="M14 9h14M16.5 5.5h9"/><path d="M33 17h1.8a4.2 4.2 0 0 1 0 8.4H32"/></svg>
            </span>
            <span>Foodie <b>AI</b></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Mở menu">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="main-nav" aria-label="Điều hướng chính">
            <a href="#giai-phap">Giải pháp</a>
            <a href="#quy-trinh">Cách hoạt động</a>
            <a href="#vai-tro">Phân quyền</a>
            <a href="#faq">Câu hỏi thường gặp</a>
        </nav>

        <div class="nav-actions">
            <a class="login-link" href="/admin/login.php">Đăng nhập</a>
            <a class="button button-small" href="#demo">Đặt lịch xem demo</a>
        </div>
    </div>
</header>

<main>
    <section class="hero section-shell">
        <div class="hero-copy reveal">
            <p class="eyebrow"><span></span>Nền tảng vận hành dành cho F&amp;B</p>
            <h1>Từ QR tại bàn đến <em>báo cáo doanh thu</em></h1>
            <p class="hero-lead">Khách tự gọi món, bếp nhận đơn tức thời, thu ngân thanh toán chính xác và chủ quán theo dõi toàn bộ hoạt động trên một hệ thống.</p>
            <div class="hero-actions">
                <a class="button" href="#demo">Đặt lịch xem demo vận hành</a>
                <a class="text-button" href="#quy-trinh">
                    Xem quy trình 90 giây
                    <span aria-hidden="true">↗</span>
                </a>
            </div>
            <div class="hero-assurance">
                <span class="assurance-avatars" aria-hidden="true"><i>B</i><i>T</i><i>Q</i></span>
                <p><strong>Thiết lập theo mô hình quán</strong><br>Không bắt nhân viên học cả hệ thống.</p>
            </div>
        </div>

        <div class="hero-visual reveal" aria-label="Mô phỏng hệ thống quản lý đơn hàng Foodie AI">
            <div class="visual-glow"></div>
            <div class="dashboard-window">
                <div class="window-bar">
                    <div class="window-brand"><span class="mini-logo">F</span> Foodie AI</div>
                    <div class="window-status"><i></i> Đang vận hành</div>
                    <div class="window-user">CQ</div>
                </div>
                <div class="dashboard-body">
                    <aside class="mock-sidebar" aria-hidden="true">
                        <span class="active"></span><span></span><span></span><span></span><span></span>
                    </aside>
                    <div class="mock-content">
                        <div class="mock-heading">
                            <div><small>Tổng quan hôm nay</small><strong>Vận hành nhà hàng</strong></div>
                            <span>13/07/2026</span>
                        </div>
                        <div class="metric-row">
                            <article><small>Doanh thu</small><strong>18.450.000đ</strong><em>+12,8%</em></article>
                            <article><small>Đơn hàng</small><strong>126</strong><em>+8,2%</em></article>
                            <article><small>Bàn phục vụ</small><strong>24/30</strong><em class="neutral">80%</em></article>
                        </div>
                        <div class="mock-grid">
                            <article class="order-panel">
                                <div class="panel-title"><strong>Luồng đơn hàng</strong><span>Trực tiếp</span></div>
                                <div class="order-flow">
                                    <div><i class="dot new"></i><span><b>Bàn 08</b><small>3 món · vừa xong</small></span><em>Mới</em></div>
                                    <div><i class="dot cooking"></i><span><b>Bàn 12</b><small>5 món · 06 phút</small></span><em>Đang làm</em></div>
                                    <div><i class="dot ready"></i><span><b>Bàn 03</b><small>4 món · 11 phút</small></span><em>Đã xong</em></div>
                                </div>
                            </article>
                            <article class="chart-panel">
                                <div class="panel-title"><strong>Doanh thu</strong><span>7 ngày</span></div>
                                <div class="chart-bars" aria-hidden="true">
                                    <i style="--h:36%"></i><i style="--h:51%"></i><i style="--h:43%"></i><i style="--h:67%"></i><i style="--h:58%"></i><i style="--h:82%"></i><i class="today" style="--h:94%"></i>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
            <div class="floating-order">
                <span class="floating-icon">✓</span>
                <div><small>Order mới từ bàn 08</small><strong>Đã chuyển đến bếp</strong></div>
            </div>
            <div class="floating-revenue">
                <small>Hôm nay</small><strong>+2,1 triệu</strong>
            </div>
        </div>
    </section>

    <section class="trust-strip section-shell reveal" aria-label="Điểm nổi bật">
        <p>Được thiết kế cho nhịp vận hành thực tế</p>
        <div class="trust-items">
            <span><i>01</i> Dữ liệu tập trung</span>
            <span><i>02</i> Phân quyền 4 vai trò</span>
            <span><i>03</i> Theo dõi theo thời gian thực</span>
        </div>
    </section>

    <section class="pain-section dark-section" id="giai-phap">
        <div class="section-shell">
            <div class="section-intro light reveal">
                <p class="eyebrow"><span></span>Đúng vấn đề của quán</p>
                <h2>Giờ cao điểm không nên<br>phụ thuộc vào trí nhớ</h2>
                <p>Khi order còn được truyền miệng hoặc ghi chép rời rạc, một sai sót nhỏ có thể kéo chậm cả bàn, bếp và quầy thanh toán.</p>
            </div>
            <div class="pain-grid">
                <article class="pain-card reveal"><span>01</span><div class="line-icon">↯</div><h3>Sai và thiếu món</h3><p>Order đi thẳng từ khách đến bếp, giữ nguyên số lượng và ghi chú của từng món.</p></article>
                <article class="pain-card reveal"><span>02</span><div class="line-icon">◎</div><h3>Bếp không rõ ưu tiên</h3><p>Đơn mới, đang làm và hoàn tất được tách rõ để bếp xử lý theo đúng nhịp.</p></article>
                <article class="pain-card reveal"><span>03</span><div class="line-icon">⌁</div><h3>Thanh toán chậm</h3><p>Thu ngân có sẵn toàn bộ món theo bàn, giảm thời gian kiểm tra và cộng lại hóa đơn.</p></article>
                <article class="pain-card reveal"><span>04</span><div class="line-icon">⌗</div><h3>Chủ quán thiếu dữ liệu</h3><p>Doanh thu, số đơn và món bán chạy được tổng hợp để theo dõi ngay cả khi vắng mặt.</p></article>
            </div>
        </div>
    </section>

    <section class="workflow-section section-shell" id="quy-trinh">
        <div class="section-intro centered reveal">
            <p class="eyebrow"><span></span>Một luồng vận hành xuyên suốt</p>
            <h2>Một order. Bốn điểm được kết nối.</h2>
            <p>Không nhập lại dữ liệu, không truyền miệng, không phải đoán đơn đang ở đâu.</p>
        </div>
        <div class="workflow-line reveal">
            <article><div class="step-icon">⌁<b>1</b></div><h3>Khách gọi món</h3><p>Quét QR, xem menu và gửi món ngay tại bàn.</p></article>
            <span class="flow-arrow">→</span>
            <article><div class="step-icon">▤<b>2</b></div><h3>Bếp xử lý</h3><p>Nhận đơn mới cùng số lượng và ghi chú rõ ràng.</p></article>
            <span class="flow-arrow">→</span>
            <article><div class="step-icon">▱<b>3</b></div><h3>Thu ngân thanh toán</h3><p>Kiểm tra hóa đơn theo bàn và xác nhận thanh toán.</p></article>
            <span class="flow-arrow">→</span>
            <article><div class="step-icon">↗<b>4</b></div><h3>Chủ quán theo dõi</h3><p>Xem doanh thu và hiệu suất vận hành tập trung.</p></article>
        </div>
    </section>

    <section class="products-section">
        <div class="section-shell">
            <div class="section-intro split reveal">
                <div><p class="eyebrow"><span></span>Hệ thống trọn vẹn</p><h2>Mọi điểm chạm trong quán,<br>chung một nhịp vận hành</h2></div>
                <p>Từng màn hình được thiết kế cho một công việc cụ thể, nhưng dữ liệu luôn liền mạch từ đầu đến cuối.</p>
            </div>
            <div class="product-grid">
                <article class="product-card featured reveal">
                    <div class="product-copy"><span class="product-number">01</span><h3>Menu QR gọi món tại bàn</h3><p>Khách chủ động xem món và gửi order, giúp nhân viên dành nhiều thời gian hơn cho phục vụ.</p><a href="/menu.php">Trải nghiệm menu mẫu <span>↗</span></a></div>
                    <div class="phone-mock" aria-hidden="true"><div class="phone-top"></div><div class="phone-title"><small>Xin chào, Bàn 08</small><strong>Hôm nay dùng gì?</strong></div><div class="food-tabs"><b>Phổ biến</b><span>Món chính</span><span>Đồ uống</span></div><div class="food-card"><span>Đặc biệt</span><div><small>Món được gọi nhiều</small><strong>Bò nướng sốt tiêu</strong><b>189.000đ</b></div></div><div class="mini-foods"><i></i><i></i><i></i></div></div>
                </article>
                <article class="product-card reveal"><span class="product-number">02</span><div class="product-icon">▤</div><h3>Màn hình báo đơn bếp</h3><p>Giúp bếp nhìn rõ đơn mới, đơn đang làm và món đã hoàn tất để hạn chế bỏ sót.</p><div class="mini-kanban"><span><i></i>Mới <b>4</b></span><span><i></i>Đang làm <b>7</b></span><span><i></i>Đã xong <b>3</b></span></div></article>
                <article class="product-card reveal"><span class="product-number">03</span><div class="product-icon">▱</div><h3>POS và thanh toán</h3><p>Tập hợp món theo từng bàn, tính tiền chính xác và sẵn sàng in hóa đơn khi khách yêu cầu.</p><div class="receipt-mini"><span>Tạm tính <b>780.000đ</b></span><span>Giảm giá <b>0đ</b></span><strong>Tổng thanh toán <b>780.000đ</b></strong></div></article>
                <article class="product-card reveal"><span class="product-number">04</span><div class="product-icon">↗</div><h3>Báo cáo vận hành</h3><p>Giúp chủ quán nắm doanh thu, số đơn và món bán chạy ngay cả khi không có mặt.</p><div class="spark-chart"><svg viewBox="0 0 300 90" preserveAspectRatio="none"><path class="area" d="M0 78 C38 66 50 72 79 53 S131 71 160 41 S211 52 238 27 S273 32 300 8 L300 90 L0 90Z"/><path class="curve" d="M0 78 C38 66 50 72 79 53 S131 71 160 41 S211 52 238 27 S273 32 300 8"/></svg></div></article>
            </div>
        </div>
    </section>

    <section class="roles-section dark-section" id="vai-tro">
        <div class="section-shell roles-shell">
            <div class="section-intro light reveal">
                <p class="eyebrow"><span></span>Đúng người, đúng màn hình</p>
                <h2>Mỗi vai trò chỉ thấy<br>đúng phần việc của mình</h2>
                <p>Không còn một trang admin chứa mọi thứ. Quyền truy cập được tách theo trách nhiệm thực tế trong quán.</p>
            </div>
            <div class="role-tabs reveal" role="tablist" aria-label="Vai trò trong hệ thống">
                <button class="role-tab active" data-role="kitchen" role="tab" aria-selected="true"><span>01</span>Bếp</button>
                <button class="role-tab" data-role="cashier" role="tab" aria-selected="false"><span>02</span>Thu ngân</button>
                <button class="role-tab" data-role="manager" role="tab" aria-selected="false"><span>03</span>Quản lý</button>
                <button class="role-tab" data-role="owner" role="tab" aria-selected="false"><span>04</span>Chủ quán</button>
            </div>
            <div class="role-display reveal">
                <div class="role-description">
                    <span class="role-kicker" id="role-kicker">Màn hình bếp</span>
                    <h3 id="role-title">Tập trung hoàn toàn vào chế biến</h3>
                    <p id="role-copy">Bếp nhận đơn mới, cập nhật đang làm và báo hoàn tất mà không nhìn thấy những dữ liệu tài chính không cần thiết.</p>
                    <ul id="role-list"><li>Nhận cảnh báo khi có order mới</li><li>Xem ghi chú theo từng món</li><li>Cập nhật đúng tiến độ chế biến</li></ul>
                </div>
                <div class="role-screen" id="role-screen" data-screen="kitchen">
                    <div class="screen-header"><div><small>Foodie AI</small><strong id="screen-name">Màn hình bếp</strong></div><span><i></i> Trực tuyến</span></div>
                    <div class="kitchen-board-mini">
                        <div><strong>Đơn mới <b>3</b></strong><article><span>Bàn 08 <small>2 phút</small></span><b>2× Bò nướng</b><b>1× Cơm chiên</b><em>Nhận làm</em></article></div>
                        <div><strong>Đang làm <b>5</b></strong><article><span>Bàn 12 <small>8 phút</small></span><b>1× Lẩu hải sản</b><b>3× Bia</b><em>Hoàn tất</em></article></div>
                        <div><strong>Đã xong <b>2</b></strong><article><span>Bàn 03 <small>11 phút</small></span><b>2× Cá nướng</b><b>1× Salad</b><em>Sẵn sàng</em></article></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="implementation-section section-shell">
        <div class="section-intro centered reveal"><p class="eyebrow"><span></span>Triển khai có lộ trình</p><h2>Thiết lập theo cách quán đang vận hành</h2><p>Bắt đầu từ quy trình hiện tại, chạy thử rõ ràng rồi mới đưa vào ca bán hàng thực tế.</p></div>
        <div class="implementation-steps reveal">
            <article><b>01</b><h3>Tiếp nhận mô hình</h3><p>Hiểu loại hình, quy trình và điểm đang gây chậm cho quán.</p></article>
            <article><b>02</b><h3>Thiết lập dữ liệu</h3><p>Nhập menu, sơ đồ bàn, tài khoản và quyền truy cập.</p></article>
            <article><b>03</b><h3>Hướng dẫn theo vai trò</h3><p>Mỗi vị trí chỉ học đúng những thao tác cần dùng.</p></article>
            <article><b>04</b><h3>Chạy thử và vận hành</h3><p>Kiểm tra luồng order trước khi áp dụng chính thức.</p></article>
        </div>
    </section>

    <section class="testimonial-section">
        <div class="section-shell testimonial-shell reveal">
            <div class="quote-mark">“</div>
            <blockquote>Điều quan trọng không phải thêm một phần mềm vào quán, mà là để mọi bộ phận phối hợp rõ ràng hơn trong giờ đông khách.</blockquote>
            <p>Nguyên tắc thiết kế của Foodie AI</p>
            <div class="testimonial-note">Khi có khách hàng thực tế, khu vực này sẽ được thay bằng case study và số liệu đã xác minh.</div>
        </div>
    </section>

    <section class="faq-section section-shell" id="faq">
        <div class="faq-layout">
            <div class="section-intro reveal"><p class="eyebrow"><span></span>Câu hỏi thường gặp</p><h2>Rõ ràng trước khi bắt đầu</h2><p>Những điều chủ quán thường cân nhắc trước khi thay đổi quy trình vận hành.</p><a class="text-button" href="#demo">Trao đổi về mô hình của bạn <span>↗</span></a></div>
            <div class="faq-list reveal">
                <details open><summary>Nhân viên không rành công nghệ có dùng được không?<span>+</span></summary><p>Mỗi vai trò có một giao diện riêng và chỉ chứa những thao tác cần thiết. Bếp, thu ngân và quản lý không phải học toàn bộ hệ thống.</p></details>
                <details><summary>Quán có phải thay toàn bộ thiết bị không?<span>+</span></summary><p>Hệ thống vận hành trên trình duyệt. Thiết bị cụ thể sẽ được tư vấn dựa trên số bàn, khu vực bếp và quầy thu ngân hiện tại.</p></details>
                <details><summary>Khách lớn tuổi không muốn tự gọi món thì sao?<span>+</span></summary><p>QR là thêm một lựa chọn gọi món, không loại bỏ việc nhân viên hỗ trợ khách hoặc tạo order theo quy trình của quán.</p></details>
                <details><summary>Dữ liệu doanh thu được phân quyền thế nào?<span>+</span></summary><p>Bếp không thấy doanh thu; thu ngân, quản lý và chủ quán được cấp quyền theo đúng trách nhiệm thay vì dùng chung một tài khoản admin.</p></details>
                <details><summary>Mất bao lâu để bắt đầu vận hành?<span>+</span></summary><p>Thời gian phụ thuộc số lượng món, bàn và độ phức tạp của quy trình. Sau khi khảo sát, quán sẽ nhận một lộ trình thiết lập và chạy thử cụ thể.</p></details>
            </div>
        </div>
    </section>

    <section class="final-cta section-shell" id="demo">
        <div class="cta-panel reveal">
            <div class="cta-copy"><p class="eyebrow"><span></span>Sẵn sàng xem luồng thực tế?</p><h2>Chuẩn hóa vận hành trước giờ cao điểm tiếp theo</h2><p>Trải nghiệm cách một order đi từ bàn đến bếp, qua thu ngân và xuất hiện trong báo cáo.</p></div>
            <div class="cta-actions"><a class="button button-gold" href="/menu.php">Trải nghiệm menu QR</a><a class="button button-outline" href="/admin/login.php">Đăng nhập hệ thống</a><small>Không cần cài ứng dụng để xem bản trải nghiệm.</small></div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="section-shell footer-grid">
        <div><a class="brand footer-brand" href="#top"><span class="brand-mark"><svg viewBox="0 0 42 42"><path d="M9 13.5h24v8.75C33 29.3 28.25 34 21 34S9 29.3 9 22.25V13.5Z"/><path d="M14 9h14M16.5 5.5h9"/><path d="M33 17h1.8a4.2 4.2 0 0 1 0 8.4H32"/></svg></span><span>Foodie <b>AI</b></span></a><p>Nền tảng kết nối gọi món, bếp, thanh toán và báo cáo dành cho mô hình F&amp;B hiện đại.</p></div>
        <div><h3>Sản phẩm</h3><a href="#giai-phap">Menu QR</a><a href="#giai-phap">Màn hình bếp</a><a href="#giai-phap">POS &amp; thanh toán</a><a href="#vai-tro">Phân quyền</a></div>
        <div><h3>Khám phá</h3><a href="#quy-trinh">Cách hoạt động</a><a href="#faq">Câu hỏi thường gặp</a><a href="/menu.php">Menu mẫu</a><a href="/admin/login.php">Đăng nhập</a></div>
        <div><h3>Phù hợp cho</h3><span>Quán ăn</span><span>Quán nhậu</span><span>Quán nước &amp; cà phê</span><span>Nhà hàng</span></div>
    </div>
    <div class="section-shell footer-bottom"><span>© <?= date('Y') ?> Foodie AI. All rights reserved.</span><span>Vận hành rõ ràng. Phục vụ chỉn chu.</span></div>
</footer>

<script src="/assets/js/landing.js?v=<?= filemtime(__DIR__ . '/assets/js/landing.js') ?>"></script>
</body>
</html>
