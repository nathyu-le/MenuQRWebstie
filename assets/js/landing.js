document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('.landing-header');
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.main-nav');

    function updateHeader() {
        if (header) header.classList.toggle('scrolled', window.scrollY > 12);
    }
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('open');
            navToggle.classList.toggle('active', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('menu-open', isOpen);
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('open');
                navToggle.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('menu-open');
            });
        });
    }

    const revealElements = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver(function (entries, revealObserver) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -35px' });
        revealElements.forEach(function (element) { observer.observe(element); });
    } else {
        revealElements.forEach(function (element) { element.classList.add('visible'); });
    }

    const roles = {
        kitchen: {
            kicker: 'Màn hình bếp', title: 'Tập trung hoàn toàn vào chế biến',
            copy: 'Bếp nhận đơn mới, cập nhật đang làm và báo hoàn tất mà không nhìn thấy những dữ liệu tài chính không cần thiết.',
            list: ['Nhận cảnh báo khi có order mới', 'Xem ghi chú theo từng món', 'Cập nhật đúng tiến độ chế biến'], screen: 'Màn hình bếp',
            body: '<div class="kitchen-board-mini"><div><strong>Đơn mới <b>3</b></strong><article><span>Bàn 08 <small>2 phút</small></span><b>2× Bò nướng</b><b>1× Cơm chiên</b><em>Nhận làm</em></article></div><div><strong>Đang làm <b>5</b></strong><article><span>Bàn 12 <small>8 phút</small></span><b>1× Lẩu hải sản</b><b>3× Bia</b><em>Hoàn tất</em></article></div><div><strong>Đã xong <b>2</b></strong><article><span>Bàn 03 <small>11 phút</small></span><b>2× Cá nướng</b><b>1× Salad</b><em>Sẵn sàng</em></article></div></div>'
        },
        cashier: {
            kicker: 'Màn hình thu ngân', title: 'Thanh toán nhanh và rõ theo từng bàn',
            copy: 'Thu ngân kiểm tra món đã gọi, xác nhận phương thức thanh toán và in hóa đơn mà không cần cộng lại thủ công.',
            list: ['Theo dõi trạng thái bàn', 'Tổng hợp hóa đơn chính xác', 'Xác nhận thanh toán có kiểm soát'], screen: 'Thu ngân · Bàn 08',
            body: '<div class="cashier-demo"><div class="cashier-items"><strong>Chi tiết hóa đơn</strong><span>2× Bò nướng sốt tiêu <b>378.000đ</b></span><span>1× Cơm chiên hải sản <b>125.000đ</b></span><span>4× Nước giải khát <b>100.000đ</b></span><span>1× Salad đặc biệt <b>89.000đ</b></span></div><div class="cashier-total"><small>Tạm tính</small><b>692.000đ</b><small>Phương thức</small><div><i>Tiền mặt</i><i>Chuyển khoản</i></div><button type="button">Xác nhận thanh toán</button></div></div>'
        },
        manager: {
            kicker: 'Màn hình quản lý', title: 'Điều phối cả ca trên một màn hình',
            copy: 'Quản lý nhìn thấy bàn, đơn, menu và tiến độ bếp để xử lý vấn đề ngay trong ca thay vì chờ tổng kết cuối ngày.',
            list: ['Theo dõi toàn bộ bàn đang phục vụ', 'Quản lý menu và trạng thái món', 'Kiểm soát đơn hủy và thay đổi'], screen: 'Quản lý ca',
            body: '<div class="manager-demo"><div class="manager-stats"><article><small>Bàn đang phục vụ</small><b>24 / 30</b></article><article><small>Đơn đang xử lý</small><b>12</b></article><article><small>Thời gian trung bình</small><b>14 phút</b></article></div><div class="table-map"><i class="busy">01</i><i>02</i><i class="busy">03</i><i class="busy">04</i><i>05</i><i class="busy">06</i><i>07</i><i class="busy">08</i><i class="busy">09</i><i>10</i><i class="busy">11</i><i>12</i></div></div>'
        },
        owner: {
            kicker: 'Dashboard chủ quán', title: 'Biết điều gì đang diễn ra dù vắng mặt',
            copy: 'Chủ quán theo dõi doanh thu, số đơn, món bán chạy và hiệu suất vận hành với quyền truy cập cao nhất.',
            list: ['Xem báo cáo doanh thu tập trung', 'Theo dõi món bán chạy và giá trị đơn', 'Quản lý tài khoản và quyền nhân sự'], screen: 'Tổng quan chủ quán',
            body: '<div class="owner-demo"><div class="owner-metrics"><article><small>Doanh thu hôm nay</small><b>18.450.000đ</b><em>+12,8%</em></article><article><small>Giá trị đơn trung bình</small><b>146.400đ</b><em>+4,2%</em></article></div><div class="owner-chart"><div><strong>Doanh thu 7 ngày</strong><small>126 đơn hôm nay</small></div><svg viewBox="0 0 500 150" preserveAspectRatio="none"><path d="M0 130 C55 112 76 122 120 88 S193 107 245 65 S322 91 374 47 S444 57 500 15"/></svg></div></div>'
        }
    };

    const tabs = document.querySelectorAll('.role-tab');
    const description = document.querySelector('.role-description');
    const roleScreen = document.getElementById('role-screen');
    const screenName = document.getElementById('screen-name');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const role = roles[tab.dataset.role];
            if (!role || !description || !roleScreen || !screenName) return;
            tabs.forEach(function (item) {
                const active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            description.classList.add('switching');
            roleScreen.style.opacity = '0';
            window.setTimeout(function () {
                document.getElementById('role-kicker').textContent = role.kicker;
                document.getElementById('role-title').textContent = role.title;
                document.getElementById('role-copy').textContent = role.copy;
                document.getElementById('role-list').innerHTML = role.list.map(function (item) { return '<li>' + item + '</li>'; }).join('');
                screenName.textContent = role.screen;
                const oldContent = roleScreen.querySelector('.kitchen-board-mini, .cashier-demo, .manager-demo, .owner-demo');
                if (oldContent) oldContent.remove();
                roleScreen.insertAdjacentHTML('beforeend', role.body);
                description.classList.remove('switching');
                roleScreen.style.opacity = '1';
            }, 180);
        });
    });

    const details = document.querySelectorAll('.faq-list details');
    details.forEach(function (detail) {
        detail.addEventListener('toggle', function () {
            if (!detail.open) return;
            details.forEach(function (other) { if (other !== detail) other.open = false; });
        });
    });
});
